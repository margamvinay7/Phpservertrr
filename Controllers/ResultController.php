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

class ResultController{
private PDO $pdo;
public function createResults() {
    $db = new Database();
        $this->pdo = $db->getConnection();
        if ($_SERVER["REQUEST_METHOD"] == "POST") {  
        $name=$_POST['name'];
        error_log('name'.$name);

        }
        
    try {
        
        if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("NO file found");
        }
        
        
        $excelFile = $_FILES['excelFile']['tmp_name'];
        $workbook = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelFile);
        $sheet = $workbook->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $headerRow = $sheet->rangeToArray('A1:' . $highestColumn . '1', NULL, TRUE, FALSE)[0];
        // error_log('ex'.json_encode($sheet));
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE)[0];
            $data = array_combine($headerRow, $rowData);

            $assessment = $data['Assessment'];
            $rollNo = $data['RollNo'];
            $subjectCount = $data['SubjectCount'];
            $year = $data['Year'];
            $academicyear = $data['Academicyear'];
            $studentName = $data['StudentName'];
            $status = $data['Status'];
            error_log('d'.json_encode(array($assessment=>$assessment,$rollNo=>$rollNo)));
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM Assessment WHERE studentId = ? AND year = ? AND academicyear = ? AND assessment = ?");
            $stmt->execute([$rollNo, $year, $academicyear, $assessment]);
            $existingCount = $stmt->fetchColumn();

            if ($existingCount > 0) {
                // Assessment already exists for the student, skip current execution
                continue;
            }

            $subjects = [];
            for ($i = 1; $i <= $subjectCount; $i++) {
                $subject = $data["Department$i"];
                $theoryMarks = $data["Theory$i"];
                $practicalMarks = $data["Practical$i"];

                $subjects[] = [
                    'subject' => $subject,
                    'theoryMarks' => $theoryMarks,
                    'practicalMarks' => $practicalMarks
                ];
            error_log('sub'.json_encode($subjects));
            }
            $this->pdo->beginTransaction();
            // Insert data into database
           $stmt = $this->pdo->prepare("INSERT INTO Assessment (assessment, studentId, year, academicyear, studentName, status,name) VALUES (?, ?, ?, ?, ?, ?,?)");

        $stmt->execute([$assessment, $rollNo, $year, $academicyear, $studentName, $status,$name]);

           $assessmentId = $this->pdo->lastInsertId();

           foreach ($subjects as $subjectData) {
               $stmt = $this->pdo->prepare("INSERT INTO AssessmentSubject (assessmentId, subject, theoryMarks, practicalMarks) VALUES (?, ?, ?, ?)");
               $stmt->execute([$assessmentId, $subjectData['subject'], $subjectData['theoryMarks'], $subjectData['practicalMarks']]);
           }

           $this->pdo->commit();
           
        }


        
        // If everything is successful, return success message
        echo 'Results created successfully';
    } catch (PDOException $e) {
        // Handle any PDOException that occurred during connection
        echo "Connection failed: " . $e->getMessage();
    } catch (Exception $e) {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        echo 'Error: ' . $e->getMessage();
    }
}



    public function getAssessmentyearAndAcademicyear() {
        $db = new Database();
        $this->pdo = $db->getConnection();

        try {
            // Get distinct years
            $yearsStmt = $this->pdo->prepare("SELECT DISTINCT `year` FROM Assessment");
            $yearsStmt->execute();
            $years = $yearsStmt->fetchAll(PDO::FETCH_COLUMN);

            // Get distinct academic years
            $academicyearsStmt = $this->pdo->prepare("SELECT DISTINCT `academicyear` FROM Assessment");
            $academicyearsStmt->execute();
            $academicyears = $academicyearsStmt->fetchAll(PDO::FETCH_COLUMN);

            // Return the results as JSON
            header('content-type:application/json');
            echo json_encode(["years" => $years, "academicyears" => $academicyears]);
        } catch (PDOException $e) {
            // Handle any PDOException that occurred during connection
            echo "Error: " . $e->getMessage();
        }
    }


    public function getAssessmentByYearAndIdAndAssessment() {
        $db = new Database();
        $this->pdo = $db->getConnection();
        $studentId=$_GET['id'];
         $year=$_GET['year'];
          $assessment=$_GET['assessment'];
          try {
            // Prepare the SQL query to fetch the assessment based on studentId, year, and assessment
            $assessmentQuery = "SELECT * FROM Assessment WHERE studentId = ? AND year = ? AND assessment = ?";
            $stmt = $this->pdo->prepare($assessmentQuery);
            $stmt->execute([$studentId, $year, $assessment]);
            
            // Fetch the assessment
            $assessment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($assessment) {
                // If assessment found, retrieve assessment subjects using the assessment's ID
                $assessmentId = $assessment['id'];
                $subjectsQuery = "SELECT * FROM AssessmentSubject WHERE assessmentId = ?";
                $stmt = $this->pdo->prepare($subjectsQuery);
                $stmt->execute([$assessmentId]);
                
                // Fetch all assessment subjects
                $assessmentSubjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
                header('content-type:application/json');
            echo json_encode(['assessment' => $assessment, 'assessmentSubjects' => $assessmentSubjects]);
               
            } else {
                return null; // No assessment found
            }
        } catch (PDOException $e) {
            
            // Handle any PDOException that occurred during connection
            echo "Error: " . $e->getMessage();
        }
    }


    public function getAssessmentById() {
        $id=$_GET['id'];
        $db = new Database();
        $this->pdo = $db->getConnection();

        try {
            // Prepare the SQL query
            $sql = "SELECT * FROM AssessmentSubject WHERE assessmentId = :assessmentId";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':assessmentId', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Fetch all the AssessmentSubjects for the Assessment ID
            $assessmentSubjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
error_log('t'.json_encode($assessmentSubjects));
            // Return the assessment as JSON
            header('content-type:application/json');
            echo json_encode($assessmentSubjects);
        } catch (PDOException $e) {
            // Handle any PDOException that occurred during connection
            echo "Error: " . $e->getMessage();
        }
    }

    public function getAssessmentsByYearAndId() {
        $studentId=$_GET['id'];
         $year=$_GET['year'];
        $db = new Database();
        $this->pdo = $db->getConnection();
    
        try {
            // Prepare the SQL query
            $stmt = $this->pdo->prepare("SELECT DISTINCT assessment FROM Assessment WHERE studentId = :studentId AND year = :year");
            $stmt->bindParam(':studentId', $studentId);
            $stmt->bindParam(':year', $year);
            // Bind parameters and execute the query
            $stmt->execute();
    
            // Fetch the assessments
            $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log('Assessments: ' . json_encode($assessments));
    
            // Return the assessments as JSON
            header('content-type:application/json');
            echo json_encode($assessments);
        } catch (PDOException $e) {
            // Handle any PDOException that occurred during connection
            echo "Error: " . $e->getMessage();
        }
    }

    
    public function getResultByYearAndAcademicYearAndStudentId() {
        $db = new Database();
        $this->pdo = $db->getConnection();
        $year = $_GET['year']; // Assuming the year is sent in a POST request
$academicYear = $_GET['academicyear']; // Assuming the academic year is sent in a POST request
$studentId = $_GET['studentId']; // Assuming the student ID is sent in a POST request
$assessment = $_GET['assessment'];
error_log('r'.json_encode(array($year=>$year,$academicYear=>$academicYear)));

    
        try {
            // Prepare the SQL query
            $stmt = $this->pdo->prepare("SELECT * FROM Assessment WHERE year = :year AND academicyear = :academicYear AND studentId = :studentId AND assessment = :assessment");
            $stmt->bindParam(':year', $year);
            $stmt->bindParam(':academicYear', $academicYear);
            $stmt->bindParam(':studentId', $studentId);
            $stmt->bindParam(':assessment', $assessment);
    
            // Bind parameters and execute the query
            $stmt->execute();
    
            // Fetch the results
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log('Results: ' . json_encode($results));
    
            // Return the results as JSON
            header('content-type:application/json');
            echo json_encode($results);
        } catch (PDOException $e) {
            // Handle any PDOException that occurred during connection
            echo "Error: " . $e->getMessage();
            error_log('er'.$e->getMessage());
        }
    }
    

    public function getResults() {
        $db = new Database();
        $this->pdo = $db->getConnection();
    
        try {
            // Prepare the SQL query
            $stmt = $this->pdo->prepare("SELECT * FROM Assessment");
    
            // Execute the query
            $stmt->execute();
    
            // Fetch all results
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            // Return the results as JSON
            header('Content-Type: application/json');
            echo json_encode($results);
        } catch (PDOException $e) {
            // Handle any PDOException that occurred during connection
            echo "Error: " . $e->getMessage();
        }
    }

    public function getAssessments() {
        $year=$_GET['year'];
        $academicyear=$_GET['academicyear'];
        $db = new Database();
        $this->pdo = $db->getConnection();
    
        try {
            // Prepare the SQL query
            $stmt = $this->pdo->prepare("SELECT DISTINCT assessment,name FROM Assessment WHERE year = :year AND academicyear = :academicyear");
            $stmt->bindParam(':year', $year);
            $stmt->bindParam(':academicyear', $academicyear);
    
            // Execute the query
            $stmt->execute();
    
            // Fetch all distinct assessments
            $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            // $stmtname = $this->pdo->prepare("SELECT DISTINCT  `assessment`,`name`  FROM Assessment WHERE year = :year AND academicyear = :academicyear");
            // $stmtname->bindParam(':year', $year);
            // $stmtname->bindParam(':academicyear', $academicyear);
    
            // // Execute the query
            // $stmtname->execute();
    
            // // Fetch all distinct assessments
            // $name = $stmtname->fetchAll(PDO::FETCH_ASSOC);
    error_log(json_encode(array($assessments)));
            // Return the assessments as JSON
            header('Content-Type: application/json');
            echo json_encode($assessments);
        } catch (PDOException $e) {
            // Handle any PDOException that occurred during connection
            echo "Error: " . $e->getMessage();
        }
        
    }
    

    public function getAssessmentList() {
        $year=$_GET['year'];
        $academicyear=$_GET['academicyear'];
        $assessment=$_GET['assessment'];
        $db = new Database();
        $this->pdo = $db->getConnection();
    
        try {
            // Prepare the SQL query
            $stmt = $this->pdo->prepare("SELECT * FROM Assessment WHERE year = :year AND academicyear = :academicyear AND assessment = :assessment");
            $stmt->bindParam(':year', $year);
            $stmt->bindParam(':academicyear', $academicyear);
            $stmt->bindParam(':assessment', $assessment);
    
            // Execute the query
            $stmt->execute();
    
            // Fetch the assessment list
            $assessmentsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            // Return the assessment list as JSON
            header('Content-Type: application/json');
            echo json_encode($assessmentsList);
        } catch (PDOException $e) {
            // Handle any PDOException that occurred during connection
            echo "Error: " . $e->getMessage();
        }
    }


    
    public function getResultByYearsAndAcademicYearAndStudentId() {
        $db = new Database();
        $this->pdo = $db->getConnection();
        $year=['MBBS-I','MBBS-II','MBBS-III','MBBS-IV'];
        // $year='MBBS-I';
         $studentId=$_GET['studentId'];
         $assessment=$_GET['assessment'];
    
        try {
            // Prepare the SQL query
            $results=[];
            for($i=0;$i<4;$i++){
                $stmt = $this->pdo->prepare("SELECT * FROM Assessment WHERE year IN (:year) AND studentId = :studentId AND assessment = :assessment");
            $stmt->bindParam(':year', $year[$i]); // Convert array to comma-separated string
            // $stmt->bindParam(':year', $year); // Convert array to comma-separated string
            $stmt->bindParam(':studentId', $studentId);
            $stmt->bindParam(':assessment', $assessment);
    
            // Execute the query
            $stmt->execute();
    
            // Fetch the results
            $yearResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results = array_merge($results, $yearResults);
    
            }
            // Return the results as JSON
            header('Content-Type: application/json');
            echo json_encode($results);
        } catch (PDOException $e) {
            // Handle any PDOException that occurred during connection
            echo "Error: " . $e->getMessage();
        }
    }
      

    public function updateAssessment() {
        $db = new Database();
        $this->pdo = $db->getConnection();
    
        // Get the parameters from the request
        $id = $_GET['id'];
        $assessmentSubject = json_decode(file_get_contents('php://input'), true);
    
        try {
            // Find the existing assessment
            $stmt = $this->pdo->prepare("SELECT * FROM Assessment WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $existingAssessment = $stmt->fetch(PDO::FETCH_ASSOC);
    
            // Update the assessment subjects
            foreach ($assessmentSubject as $subject) {
                $stmt = $this->pdo->prepare("UPDATE AssessmentSubject SET subject = :subject, theoryMarks = :theoryMarks, practicalMarks = :practicalMarks WHERE id = :subjectId");
                $stmt->bindParam(':subject', $subject['subject']);
                $stmt->bindParam(':theoryMarks', $subject['theoryMarks'], PDO::PARAM_INT);
                $stmt->bindParam(':practicalMarks', $subject['practicalMarks'], PDO::PARAM_INT);
                $stmt->bindParam(':subjectId', $subject['id']);
                $stmt->execute();
            }
    
            // Return the updated assessment
            header('Content-Type: application/json');
            echo json_encode($existingAssessment);
        } catch (PDOException $e) {
            // Handle any PDOException that occurred during connection
            echo "Error: " . $e->getMessage();
        }
    }
    
    public function deleteAssessment() {
        $db = new Database();
        $this->pdo = $db->getConnection();
    
        // Get the parameters from the request body
        $requestData = json_decode(file_get_contents('php://input'), true);
        $year = $requestData['year'];
        $academicyear = $requestData['academicyear'];
        $assessment = $requestData['assessment'];
        error_log('t'.$year.$academicyear.$assessment);
        if($year !==null && $academicyear!==null && $assessment!==null){

        
    
        try {
            
            // Delete assessment subjects
            $stmt = $this->pdo->prepare("DELETE AssessmentSubject FROM AssessmentSubject INNER JOIN Assessment ON AssessmentSubject.assessmentId = Assessment.id WHERE Assessment.year = :year AND Assessment.academicyear = :academicyear AND Assessment.assessment = :assessment");
            $stmt->bindParam(':year', $year);
            $stmt->bindParam(':academicyear', $academicyear);
            $stmt->bindParam(':assessment', $assessment);
            $stmt->execute();

            // Delete assessments
            $stmt = $this->pdo->prepare("DELETE FROM Assessment WHERE year = :year AND academicyear = :academicyear AND assessment = :assessment");
            $stmt->bindParam(':year', $year);
            $stmt->bindParam(':academicyear', $academicyear);
            $stmt->bindParam(':assessment', $assessment);
            $stmt->execute();
    
            // Return success message
            http_response_code(200);
            echo "Assessment deleted successfully";
        } catch (PDOException $e) {
            // Handle any PDOException that occurred during connection
            echo "Error: " . $e->getMessage();
        }

    }
    }


    public function updateAssessmentName() {
        $db = new Database();
        $this->pdo = $db->getConnection();
    
        // Get the parameters from the request body
        $requestData = json_decode(file_get_contents('php://input'), true);
        $year = $requestData['year']?? null;
        $academicyear = $requestData['academicyear']?? null;
        $assessment = $requestData['assessment']?? null;
        $newName = $requestData['newName']?? null; 

        // $year = 'MBBS-I';
        // $academicyear ='2024-2025 ';
        // $assessment = $requestData['assessment']
        // $newName = $requestData['newName'] 
        
        
        // New name for the assessment
        error_log('t'.json_encode($requestData));
        // Check if all required parameters are present
        if($year !== null && $academicyear !== null && $assessment !== null && $newName !== null) {
            try {
                // Update the name field in assessments
                $stmt = $this->pdo->prepare("UPDATE Assessment SET name = :newName WHERE year = :year AND academicyear = :academicyear AND assessment = :assessment");
                $stmt->bindParam(':year', $year);
                $stmt->bindParam(':academicyear', $academicyear);
                $stmt->bindParam(':assessment', $assessment);
                $stmt->bindParam(':newName', $newName);
                $stmt->execute();
    error_log('testnamje'.json_encode(array($year,$assessment,$academicyear,$newName)));
                // Check if any assessment was updated
                $rowCount = $stmt->rowCount();
                if ($rowCount > 0) {
                    // Return success message
                    http_response_code(200);
                    echo "Name field updated for $rowCount assessments";
                } else {
                    // If no assessments were updated, return appropriate message
                    http_response_code(404);
                    echo "No assessments found matching the criteria";
                }
            } catch (PDOException $e) {
                // Handle any PDOException that occurred during connection
                http_response_code(500);
                echo "Error: " . $e->getMessage();
            }
        } else {
            // Return error if any parameter is missing
            // http_response_code(400);
            echo "Missing parameters";
        }
    }
    
    
    
}

