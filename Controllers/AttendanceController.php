<?php

namespace Controller;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: *');

require 'vendor/autoload.php'; // Include Composer's autoloader

use PhpOffice\PhpSpreadsheet\IOFactory;
use Core\Database;
use PDO;
use Exception;

use PDOException;

class AttendanceController {
    private PDO $pdo;

    public function createAttendance() {
        $attendanceData = json_decode(file_get_contents('php://input'), true);

        // Initialize the PDO connection
        $db = new Database();
        $this->pdo = $db->getConnection();

        try {
            // Begin transaction
            $this->pdo->beginTransaction();

            foreach ($attendanceData as $studentAttendance) {
                $studentId = $studentAttendance['studentId'];
                $date1 = $studentAttendance['date'];
                $dateTime = date_create_from_format('Y-m-d\TH:i:s.u\Z', $date1);
                $date = $dateTime->format('Y-m-d H:i:s');
                $year = $studentAttendance['year'];
                $academicyear = $studentAttendance['academicyear'];
                $subjects = $studentAttendance['subjects'];

                // Check if attendance already exists for the student on the given date, year, and academic year
                $stmtExist = $this->pdo->prepare("SELECT COUNT(*) FROM Attendance WHERE studentId = :studentId AND date = :date AND year = :year AND academicyear = :academicyear");
                $stmtExist->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                $stmtExist->bindParam(':date', $date, PDO::PARAM_STR);
                $stmtExist->bindParam(':year', $year, PDO::PARAM_STR);
                $stmtExist->bindParam(':academicyear', $academicyear, PDO::PARAM_STR);
                $stmtExist->execute();
                $isExist = $stmtExist->fetchColumn();
                error_log('duplicte'.$isExist);

                if ($isExist > 0) {
                    throw new Exception("Attendance already marked for student $studentId");
                }

                // Insert attendance data
                $stmtAttendance = $this->pdo->prepare("INSERT INTO Attendance (studentId, date, year, academicyear) VALUES (:studentId, :date, :year, :academicyear)");
                $stmtAttendance->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                $stmtAttendance->bindParam(':date', $date, PDO::PARAM_STR);
                $stmtAttendance->bindParam(':year', $year, PDO::PARAM_STR);
                $stmtAttendance->bindParam(':academicyear', $academicyear, PDO::PARAM_STR);
                $stmtAttendance->execute();

                // Retrieve the ID of the inserted attendance record
                $attendanceId = $this->pdo->lastInsertId();

                // Insert subject-wise attendance data
                foreach ($subjects as $subject) {
                    $stmtSubject = $this->pdo->prepare("INSERT INTO Subject (attendanceId, time, subject, present) VALUES (:attendanceId, :time, :subject, :present)");
                    $stmtSubject->bindParam(':attendanceId', $attendanceId, PDO::PARAM_INT);
                    $stmtSubject->bindParam(':time', $subject['time'], PDO::PARAM_STR);
                    $stmtSubject->bindParam(':subject', $subject['subject'], PDO::PARAM_STR);

                    $present = $subject['present'] ? 1 : 0;
                    // error_log("test present".$subject['present']);
    $stmtSubject->bindParam(':present', $present, PDO::PARAM_INT);
    
                    $stmtSubject->execute();
                }
            }

            // Commit transaction
            $this->pdo->commit();
            http_response_code(200);
            echo "Attendance updated";
        } catch (PDOException $e) {
            // Rollback transaction and handle database errors
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            echo "Database Error: " . $e->getMessage();
        } catch (Exception $e) {
            // Handle other errors
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            http_response_code(208);
            error_log('er'.$e->getMessage());
            echo "Error: " . $e->getMessage();
        }



    }



    public function getAttendanceById() {
        $id=$_GET['id'];
        try {
            $db = new Database();
            $this->pdo = $db->getConnection();

            $stmt = $this->pdo->prepare("SELECT * FROM Student WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                echo "Student not found";
                return;
            }

            $stmt = $this->pdo->prepare("SELECT * FROM Attendance WHERE studentId = :studentId");
            $stmt->bindParam(':studentId', $id, PDO::PARAM_INT);
            $stmt->execute();
            $attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($attendances as &$attendance) {
                $attendanceId = $attendance['id'];

                $stmt = $this->pdo->prepare("SELECT * FROM Subject WHERE attendanceId = :attendanceId");
                $stmt->bindParam(':attendanceId', $attendanceId, PDO::PARAM_INT);
                $stmt->execute();
                $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $attendance['subjects'] = $subjects;
            }

            return [
                'student' => $student,
                'attendances' => $attendances
            ];
        } catch (PDOException $e) {
            echo "Database Error: " . $e->getMessage();
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function getAttendanceByIdAndDateRange() {
        $id=$_GET['id'];
        $startDate=$_GET['startDate'];
        $endDate=$_GET['endDate'];
        try {
            $db = new Database();
            $this->pdo = $db->getConnection();

            // Convert date strings to YYYY-MM-DD format
            $startDate = date('Y-m-d', strtotime($startDate));
            $endDate = date('Y-m-d', strtotime($endDate));

            // Adjust end date to include the whole day
            $endDate .= ' 23:59:59';

            $stmt = $this->pdo->prepare("
                SELECT * 
                FROM Attendance 
                WHERE studentId = :studentId 
                AND date >= :startDate 
                AND date <= :endDate
            ");
            $stmt->bindParam(':studentId', $id, PDO::PARAM_INT);
            $stmt->bindParam(':startDate', $startDate, PDO::PARAM_STR);
            $stmt->bindParam(':endDate', $endDate, PDO::PARAM_STR);
            $stmt->execute();
            $attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($attendances as &$attendance) {
                $attendanceId = $attendance['id'];

                $stmt = $this->pdo->prepare("SELECT * FROM Subject WHERE attendanceId = :attendanceId");
                $stmt->bindParam(':attendanceId', $attendanceId, PDO::PARAM_INT);
                $stmt->execute();
                $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $attendance['subjects'] = $subjects;
            }

            header('content-type:application/json');
            echo json_encode($attendances);
        } catch (PDOException $e) {
            echo "Database Error: " . $e->getMessage();
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function getAttendanceByIdAndMonth() {
        $id=$_GET['id'];
        $month=$_GET['month'];
        $year=$_GET['year'];
        try {
            $db = new Database();
            $this->pdo = $db->getConnection();

            // Calculate start and end dates based on month and year
            $startDate = date('Y-m-d', strtotime("$year-$month-01"));
            $endDate = date('Y-m-t', strtotime("$year-$month-01"));

            $stmt = $this->pdo->prepare("
                SELECT * 
                FROM Attendance 
                WHERE studentId = :studentId 
                AND date >= :startDate 
                AND date <= :endDate
            ");
            $stmt->bindParam(':studentId', $id, PDO::PARAM_INT);
            $stmt->bindParam(':startDate', $startDate, PDO::PARAM_STR);
            $stmt->bindParam(':endDate', $endDate, PDO::PARAM_STR);
            $stmt->execute();
            $attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $subjectCounts = [];
            $presentSubjectCounts = [];

            foreach ($attendances as $attendance) {
                $attendanceId = $attendance['id'];

                $stmt = $this->pdo->prepare("SELECT * FROM Subject WHERE attendanceId = :attendanceId");
                $stmt->bindParam(':attendanceId', $attendanceId, PDO::PARAM_INT);
                $stmt->execute();
                $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($subjects as $subject) {
                    $subjectName = $subject['subject'];
                    $subjectCounts[$subjectName] = ($subjectCounts[$subjectName] ?? 0) + 1;

                    if ($subject['present']) {
                        $presentSubjectCounts[$subjectName] = ($presentSubjectCounts[$subjectName] ?? 0) + 1;
                    }
                }
            }

            $subjectPercentages = [];
            foreach ($subjectCounts as $subject => $count) {
                $presentCount = $presentSubjectCounts[$subject] ?? 0;
                $percentage = $count > 0 ? ($presentCount / $count) * 100 : 0;
                $subjectPercentages[$subject] = number_format($percentage, 2);
            }
            header('content-type:application/json');
            echo json_encode([$subjectCounts, $presentSubjectCounts, $subjectPercentages]);
        } catch (PDOException $e) {
            echo "Database Error: " . $e->getMessage();
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }


    public function getTotalAttendance() {
        $studentId=$_GET['id'];
        $years=["MBBS-I", "MBBS-II", "MBBS-III", "MBBS-IV"];
        $responseData = [];
        $db = new Database();
            $this->pdo = $db->getConnection();
            try {
                foreach ($years as $year) {
                    $attendance = $this->getAttendanceByYear($studentId, $year);
    
                    $totalSubjectsCount = 0;
                    $totalPresentSubjectsCount = 0;
                    $academicyear = '';
    
                    foreach ($attendance as $record) {
                        $totalSubjectsCount += count($record['subjects']);
                        foreach ($record['subjects'] as $subject) {
                            if ($subject['present']) {
                                $totalPresentSubjectsCount++;
                            }
                        }
                    }
    
                    $responseData[] = [
                        'year' => $year,
                        'totalSubjectsCount' => $totalSubjectsCount,
                        'totalPresentSubjectsCount' => $totalPresentSubjectsCount,
                        'academicyear' => $record['academicyear'],
                    ];
                }
                header('content-type:application/json');
                echo json_encode($responseData);
            } catch (PDOException $e) {
                echo ['error' => $e->getMessage()];
            } catch (Exception $e) {
                echo ['error' => $e->getMessage()];
            }
        }
    
        private function getAttendanceByYear($studentId, $year) {
            $stmt = $this->pdo->prepare("SELECT * FROM Attendance WHERE studentId = :studentId AND year = :year");
            $stmt->bindParam(':studentId', $studentId, PDO::PARAM_INT);
            $stmt->bindParam(':year', $year, PDO::PARAM_STR);
            $stmt->execute();
            $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            foreach ($attendance as &$record) {
                $record['subjects'] = $this->getSubjectsByAttendanceId($record['id']);
            }
    
            return $attendance;
        }
    
        private function getSubjectsByAttendanceId($attendanceId) {
            $stmt = $this->pdo->prepare("SELECT * FROM Subject WHERE attendanceId = :attendanceId");
            $stmt->bindParam(':attendanceId', $attendanceId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    
}

    

