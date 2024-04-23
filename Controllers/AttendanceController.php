<?php

namespace Controller;


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
                $studentName=$studentAttendance['name'];

                // Check if attendance already exists for the student on the given date, year, and academic year
                $stmtExist = $this->pdo->prepare("SELECT COUNT(*) FROM Attendance WHERE studentId = :studentId AND date = :date AND year = :year AND academicyear = :academicyear");
                $stmtExist->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                $stmtExist->bindParam(':date', $date, PDO::PARAM_STR);
                $stmtExist->bindParam(':year', $year, PDO::PARAM_STR);
                $stmtExist->bindParam(':academicyear', $academicyear, PDO::PARAM_STR);
                $stmtExist->execute();
                $isExist = $stmtExist->fetchColumn();
                

                if ($isExist > 0) {
                    throw new Exception("Attendance already marked for student $studentId");
                }

                // Insert attendance data
                $stmtAttendance = $this->pdo->prepare("INSERT INTO Attendance (studentId, date, year, academicyear,studentName) VALUES (:studentId, :date, :year, :academicyear,:studentName)");
                $stmtAttendance->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                $stmtAttendance->bindParam(':date', $date, PDO::PARAM_STR);
                $stmtAttendance->bindParam(':year', $year, PDO::PARAM_STR);
                $stmtAttendance->bindParam(':academicyear', $academicyear, PDO::PARAM_STR);
                
                $stmtAttendance->bindParam(':studentName', $studentName, PDO::PARAM_STR);
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


 /**
  * The PHP code consists of functions to retrieve and manipulate attendance data for students,
  * including getting total attendance, fetching attendance records by year and academic year, and
  * editing attendance details.
  */

    // public function getTotalAttendance() {
    //     $studentId=$_GET['id'];
    //     $years=["MBBS-I", "MBBS-II", "MBBS-III", "MBBS-IV"];
    //     $responseData = [];
    //     $db = new Database();
    //         $this->pdo = $db->getConnection();
    //         try {
    //             foreach ($years as $year) {
    //                 $attendance = $this->getAttendanceByYear($studentId, $year);
    
    //                 $totalSubjectsCount = 0;
    //                 $totalPresentSubjectsCount = 0;
    //                 $academicyear = '';
    
    //                 foreach ($attendance as $record) {
    //                     $totalSubjectsCount += count($record['subjects']);
    //                     foreach ($record['subjects'] as $subject) {
    //                         if ($subject['present']) {
    //                             $totalPresentSubjectsCount++;
    //                         }
    //                     }
    //                 }
    
    //                 $responseData[] = [
    //                     'year' => $year,
    //                     'totalSubjectsCount' => $totalSubjectsCount,
    //                     'totalPresentSubjectsCount' => $totalPresentSubjectsCount,
    //                     'academicyear' => $record['academicyear'],
    //                 ];
    //             }
    //             header('content-type:application/json');
    //             echo json_encode($responseData);
    //         } catch (PDOException $e) {
    //             echo ['error' => $e->getMessage()];
    //         } catch (Exception $e) {
    //             echo ['error' => $e->getMessage()];
    //         }
    //     }

    public function getTotalAttendance() {
        $studentId = $_GET['id'];
        $years = ["MBBS-I", "MBBS-II", "MBBS-III", "MBBS-IV"];
        $responseData = [];
        $db = new Database();
        $this->pdo = $db->getConnection();
        try {
            foreach ($years as $year) {
                $attendance = $this->getAttendanceByYear($studentId, $year);
                $totalSubjectsCount = 0;
                $totalTSubjectsCount = 0; // Total subjects with (T)
                $totalPSubjectsCount = 0; // Total subjects with (P)
                $totalTPresentSubjectsCount = 0; // Total subjects with (T) and present true
                $totalPPresentSubjectsCount = 0; // Total subjects with (P) and present true
                $academicyear = '';
    
                foreach ($attendance as $record) {
                    foreach ($record['subjects'] as $subject) {
                        $totalSubjectsCount++;
                        if (strpos($subject['subject'], '(T)') !== false) {
                            
                            $totalTSubjectsCount++;
                            if ($subject['present']) {
                                $totalTPresentSubjectsCount++;
                            }
                        }
                        if (strpos($subject['subject'], '(P)') !== false) {
                            $totalPSubjectsCount++;
                            
                            if ($subject['present']) {
                                $totalPPresentSubjectsCount++;
                            }
                        }
                    }
                    $academicyear = $record['academicyear']; // Assuming academic year is the same for all records within the same year
                }
    
                $responseData[] = [
                    'year' => $year,
                    'totalSubjectsCount' => $totalSubjectsCount,
                    'totalTSubjectsCount' => $totalTSubjectsCount,
                    'totalPSubjectsCount' => $totalPSubjectsCount,
                    'totalTPresentSubjectsCount' => $totalTPresentSubjectsCount,
                    'totalPPresentSubjectsCount' => $totalPPresentSubjectsCount,
                    'academicyear' => $academicyear,
                ];
            }
            header('content-type:application/json');
            echo json_encode($responseData);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
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
    



        public function getAttendenceByYearAcademicyearId(){
            $data = json_decode(file_get_contents('php://input'),true);
            try {
                // Connect to the database
                $db = new Database();
            $this->pdo = $db->getConnection();
            $year = $data['year'];
            $academicYear = $data['academicYear'];
            $date = $data['date'];
            
                // Check if year, academic year, and date are sent via POST
                if (isset($data['year']) && isset($data['academicYear']) && isset($data['date'])) {
                    // Retrieve year, academic year, and date from POST data
                   
            
                    // Fetch attendance records based on year, academic year, and date
                    $stmt = $this->pdo->prepare("SELECT * FROM Attendance WHERE year = :year AND academicyear = :academicYear AND date = :date");
                    $stmt->bindParam(':year', $year);
                    $stmt->bindParam(':academicYear', $academicYear);
                    $stmt->bindParam(':date', $date);
                    $stmt->execute();
                    $attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
                    // Prepare array to store attendance data with subjects
                    $attendanceDataWithSubjects = array();
            
                    // Iterate through attendance records
                    foreach ($attendances as $attendance) {
                        // Retrieve attendance ID
                        $attendanceId = $attendance['id'];
            
                        // Fetch attendance subjects based on attendance ID
                        $stmt = $this->pdo->prepare("SELECT * FROM Subject WHERE attendanceId = :attendanceId");
                        $stmt->bindParam(':attendanceId', $attendanceId);
                        $stmt->execute();
                        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
                        // Combine attendance and subjects into an array
                        $attendanceDataWithSubjects[] = array(
                            'attendance' => $attendance,
                            'subjects' => $subjects
                        );
                    }
            
                    // Encode the data as JSON and send it to the frontend
                    header('Content-Type: application/json');
                    echo json_encode($attendanceDataWithSubjects);
                } else {
                    echo "Year, academic year, and date are required.";
                }
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        }


        public function editAttendance(){
            $data = json_decode(file_get_contents('php://input'),true);
            $subjectArrays=$data['subjects'];
           
            if($subjectArrays!==null){

            
            try{
                $db = new Database();
                $this->pdo = $db->getConnection();
                foreach ($subjectArrays as $subjectArray) {
                    foreach ($subjectArray as $subject) {
                        $subjectId = $subject['id'];
                        $time = $subject['time'];
                        $subjectName = $subject['subject'];
                        $presentvalue = $subject['present'] ? 1 : 0;
                        $present = $presentvalue;
    
                        // Prepare SQL statement
                        $sql = "UPDATE Subject 
                                SET time = :time, 
                                    subject = :subject, 
                                    present = :present 
                                WHERE id = :id";
    
                        // Prepare and execute the statement
                        $stmt = $this->pdo->prepare($sql);
                        $stmt->bindParam(':time', $time);
                        $stmt->bindParam(':subject', $subjectName);
                        $stmt->bindParam(':present', $present);
                        $stmt->bindParam(':id', $subjectId);
    
                        $stmt->execute();
                    }
                }

            }catch(PDOException $e){
                echo "Error: " . $e->getMessage();
            }catch(Exception $e){
                echo "Error: ".$e->getMessage();
            }
            }
        }


        public function getAttendanceForReportsByMonth(){
            $year=$_GET['year'];
            $academicyear=$_GET['academicyear'];
            $mbbsyear=$_GET['mbbsyear'];
            $month=$_GET['month'];
            try{
                $db = new Database();
                $this->pdo = $db->getConnection();
                $responseData = array();

        // Prepare and execute SQL query
        $query = "SELECT Student.id,Student.fullName, Student.parentName, Student.mobile ,Student.email, Student.parentMobile, Subject.subject, Subject.present
        FROM Attendance
        JOIN Subject ON Attendance.id = Subject.attendanceId
        JOIN Student ON Attendance.studentId = Student.id
        WHERE YEAR(Attendance.date) = :year
        AND MONTH(Attendance.date) = :month
        AND Attendance.academicyear = :academicyear AND Attendance.year=:mbbsyear
        ORDER BY Attendance.date";
                    
       

        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':academicyear', $academicyear);
        $stmt->bindParam(':mbbsyear', $mbbsyear);
        $stmt->bindParam(':month', $month);
        $stmt->execute();

        

        $studentAttendanceData = array(); // Array to hold attendance data for each student
        $studentTotals = array(); // Array to hold total counts for each student
        
        // Fetch data and organize it
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Initialize totals for the current student if not already initialized
            if (!isset($studentTotals[$row['id']])) {
                $studentTotals[$row['id']] = array(
                    'totalSubjectsCount' => 0,
                    'totalTSubjectsCount' => 0,
                    'totalPSubjectsCount' => 0,
                    'totalTPresentSubjectsCount' => 0,
                    'totalPPresentSubjectsCount' => 0
                );
            }
        
            // Update totals for the current student
            $studentTotals[$row['id']]['totalSubjectsCount']++;
            if (strpos($row['subject'], '(T)') !== false) {
                $studentTotals[$row['id']]['totalTSubjectsCount']++;
                if ($row['present']) {
                    $studentTotals[$row['id']]['totalTPresentSubjectsCount']++;
                }
            }
            if (strpos($row['subject'], '(P)') !== false) {
                $studentTotals[$row['id']]['totalPSubjectsCount']++;
                if ($row['present']) {
                    $studentTotals[$row['id']]['totalPPresentSubjectsCount']++;
                }
            }
        
            // Add attendance data for the current student if not already added
            if (!isset($studentAttendanceData[$row['id']])) {
                $studentAttendanceData[$row['id']] = array(
                    'id'=>$row['id'],
                    'fullName' => $row['fullName'],
                    'mobile'=>$row['mobile'],
                    'email'=>$row['email'],
                    'parentName' => $row['parentName'],
                    'parentMobile' => $row['parentMobile'],
                    'attendance' => array()
                );
            }
        
            
        }
        
        // Merge total counts with attendance data for each student
        foreach ($studentAttendanceData as $studentName => &$studentData) {
            $studentData = array_merge($studentData, $studentTotals[$studentName]);
        }
        unset($studentData); // unset the reference to avoid unwanted variable modification
        
        // Prepare final response data
        $responseDataFinal = array(
            'year' => $year,
            'mbbsyear' => $mbbsyear,
            'month' => $month,
            'academicyear' => $academicyear,
            'studentAttendanceData' => array_values($studentAttendanceData) // re-index the array
        );
       
        header('Content-Type: application/json');
        echo json_encode($responseDataFinal);
            }
         catch (PDOException $e) {
            // Handle database error
            echo "Error: " . $e->getMessage();
        }
        }


        public function getAttendanceForReportsByMbbsYear(){
            $academicyear=$_GET['academicyear'];
            $mbbsyear=$_GET['mbbsyear'];
            try{
                $db = new Database();
                $this->pdo = $db->getConnection();
                $responseData = array();

        // Prepare and execute SQL query
        $query = "SELECT Student.id,Student.fullName, Student.parentName, Student.mobile ,Student.email, Student.parentMobile, Subject.subject, Subject.present
        FROM Attendance
        JOIN Subject ON Attendance.id = Subject.attendanceId
        JOIN Student ON Attendance.studentId = Student.id
        WHERE  Attendance.academicyear = :academicyear AND Attendance.year=:mbbsyear
        ORDER BY Attendance.date";
                    
       

        $stmt = $this->pdo->prepare($query);
        // $stmt->bindParam(':year', $year);
        $stmt->bindParam(':academicyear', $academicyear);
        $stmt->bindParam(':mbbsyear', $mbbsyear);
        // $stmt->bindParam(':month', $month);
        $stmt->execute();

       

        $studentAttendanceData = array(); // Array to hold attendance data for each student
        $studentTotals = array(); // Array to hold total counts for each student
        
        // Fetch data and organize it
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Initialize totals for the current student if not already initialized
            if (!isset($studentTotals[$row['id']])) {
                $studentTotals[$row['id']] = array(
                    'totalSubjectsCount' => 0,
                    'totalTSubjectsCount' => 0,
                    'totalPSubjectsCount' => 0,
                    'totalTPresentSubjectsCount' => 0,
                    'totalPPresentSubjectsCount' => 0
                );
            }
        
            // Update totals for the current student
            $studentTotals[$row['id']]['totalSubjectsCount']++;
            if (strpos($row['subject'], '(T)') !== false) {
                $studentTotals[$row['id']]['totalTSubjectsCount']++;
                if ($row['present']) {
                    $studentTotals[$row['id']]['totalTPresentSubjectsCount']++;
                }
            }
            if (strpos($row['subject'], '(P)') !== false) {
                $studentTotals[$row['id']]['totalPSubjectsCount']++;
                if ($row['present']) {
                    $studentTotals[$row['id']]['totalPPresentSubjectsCount']++;
                }
            }
        
            // Add attendance data for the current student if not already added
            if (!isset($studentAttendanceData[$row['id']])) {
                $studentAttendanceData[$row['id']] = array(
                    'id'=>$row['id'],
                    'fullName' => $row['fullName'],
                    'mobile'=>$row['mobile'],
                    'email'=>$row['email'],
                    'parentName' => $row['parentName'],
                    'parentMobile' => $row['parentMobile'],
                    'attendance' => array()
                );
            }
        
           
        }
        
        // Merge total counts with attendance data for each student
        foreach ($studentAttendanceData as $studentName => &$studentData) {
            $studentData = array_merge($studentData, $studentTotals[$studentName]);
        }
        unset($studentData); // unset the reference to avoid unwanted variable modification
        
        // Prepare final response data
        $responseDataFinal = array(
            'mbbsyear' => $mbbsyear,
            'academicyear' => $academicyear,
            'studentAttendanceData' => array_values($studentAttendanceData) // re-index the array
        );
       
        header('Content-Type: application/json');
        echo json_encode($responseDataFinal);
            }
         catch (PDOException $e) {
            // Handle database error
            echo "Error: " . $e->getMessage();
        }
        }

}



    

