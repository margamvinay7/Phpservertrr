<?php

namespace Controller;


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
       
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE)[0];
            $data = array_combine($headerRow, $rowData);

            $assessment = $data['Assessment'];
            $rollNo = $data['RollNo'];
            $subjectCount = $data['SubjectCount'];
            $year = $data['Year'];
            $academicyear = $data['Academicyear'];
            $studentName = $data['StudentName'];
            $finalstatus = $data['FinalStatus'];
           
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
                $status=$data["Status$i"];
                $subjects[] = [
                    'subject' => $subject,
                    'theoryMarks' => $theoryMarks,
                    'practicalMarks' => $practicalMarks,
                    'status'=>$status,
                ];
           
            }
            $this->pdo->beginTransaction();
            // Insert data into database
           $stmt = $this->pdo->prepare("INSERT INTO Assessment (assessment, studentId, year, academicyear, studentName, finalstatus,name) VALUES (?, ?, ?, ?, ?, ?,?)");

        $stmt->execute([$assessment, $rollNo, $year, $academicyear, $studentName, $finalstatus,$name]);

           $assessmentId = $this->pdo->lastInsertId();

           foreach ($subjects as $subjectData) {
               $stmt = $this->pdo->prepare("INSERT INTO AssessmentSubject (assessmentId, subject, theoryMarks, practicalMarks,status) VALUES (?, ?, ?, ?,?)");
               $stmt->execute([$assessmentId, $subjectData['subject'], $subjectData['theoryMarks'], $subjectData['practicalMarks'],$subjectData['status']]);
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
         
    
            // Return the results as JSON
            header('content-type:application/json');
            echo json_encode($results);
        } catch (PDOException $e) {
            // Handle any PDOException that occurred during connection
            echo "Error: " . $e->getMessage();
          
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
                $stmt = $this->pdo->prepare("UPDATE AssessmentSubject SET subject = :subject, theoryMarks = :theoryMarks, practicalMarks = :practicalMarks , status = :status WHERE id = :subjectId");
                $stmt->bindParam(':subject', $subject['subject']);
                $stmt->bindParam(':status', $subject['status']);
                $stmt->bindParam(':theoryMarks', $subject['theoryMarks'], PDO::PARAM_INT);
                $stmt->bindParam(':practicalMarks', $subject['practicalMarks'], PDO::PARAM_INT);
                $stmt->bindParam(':subjectId', $subject['id']);
                $stmt->execute();
            }

            $stmt = $this->pdo->prepare("UPDATE Assessment SET finalstatus = CASE WHEN EXISTS (SELECT * FROM AssessmentSubject WHERE assessmentId = :assessmentId AND status = 'Fail') THEN 'Fail' ELSE 'Pass' END WHERE id = :assessmentId");
        $stmt->bindParam(':assessmentId', $id);
        $stmt->execute();
    
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


    public function getAttendanceReports(){
        $academicyear = $_GET['academicyear'];
            $year = $_GET['year'];
            $assessment = $_GET['assessment'];
        // $academicyear = '2024-2025';
        //     $year = 'MBBS-I';
        //     $assessment = "prefinal Assessment" ;

        

        try {

            $db = new Database();
            $this->pdo = $db->getConnection();

           // SQL query
    $sql = "
    SELECT 
        A.id AS assessment_id,
        A.studentId,
        A.year AS assessment_year,
        A.studentName,
        A.name AS assessment_name,
        A.finalstatus,
        A.academicyear,
        ASU.id AS assessment_subject_id,
        ASU.subject,
        ASU.theoryMarks,
        ASU.practicalMarks,
        ASU.status,
        S.id AS id,
        S.fullName AS student_fullName,
        S.email AS student_email,
        S.mobile AS student_mobile,
        S.gender AS student_gender,
        S.parentName AS parent_name,
        S.parentMobile AS parent_mobile
    FROM 
        Assessment AS A
    JOIN 
        AssessmentSubject AS ASU ON A.id = ASU.assessmentId
    JOIN 
        Student AS S ON A.studentId = S.id
    WHERE 
        A.academicyear = :academicyear
        AND A.year = :year
        AND A.assessment = :assessment";
    
    // Prepare the SQL statement
    $stmt = $this->pdo->prepare($sql);
    
    // Bind parameters
   
    $stmt->bindParam(':academicyear', $academicyear);
    $stmt->bindParam(':year', $year);
    $stmt->bindParam(':assessment', $assessment);
    
    // Execute the SQL statement
    $stmt->execute();
    
    // Fetch all the rows as an associative array
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize data by student
    $data = array();
    foreach ($results as $row) {
        $studentId = $row['studentId'];
        if (!isset($data[$studentId])) {
            // Initialize student data if not already present
            $data[$studentId] = array(
                
                    'id'=>$row['id'],
                    'fullName' => $row['student_fullName'],
                    
                    'mobile' => $row['student_mobile'],
                    
                    'parentName' => $row['parent_name'],
                    'parentMobile' => $row['parent_mobile'],
                    'email' => $row['student_email'],
                    'assessmentname' => $row['assessment_name'],
                    'finalstatus' => $row['finalstatus'],
                    'assessments' => array()
            );
        }
        
        // Add assessment details
        $data[$studentId]['assessments'][] = array(
            'assessmentid' => $row['assessment_id'],
            'assessmentyear' => $row['assessment_year'],
            'assessmentname' => $row['assessment_name'], 
            'academicyear' => $row['academicyear'],
            'assessmentsubjectid' => $row['assessment_subject_id'],
            'subject' => $row['subject'],
            'status' => $row['status'],
            'theoryMarks' => $row['theoryMarks'],
            'practicalMarks' => $row['practicalMarks']
        );
    }
            
            // Output the result
            header('content-type:application/json');
            echo json_encode(array_values($data));
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }

    }
    
    
    
}

