<?php

namespace Controller;


require 'vendor/autoload.php'; // Include Composer's autoloader

use PhpOffice\PhpSpreadsheet\IOFactory;
use Core\Database;
use PDO;
use Exception;
use finfo;

use PDOException;

class StudentController {
    private PDO $pdo;

   

   

    public function createStudents($req, $res) {
    
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            try {
                if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception("No files were uploaded");
                }
                
                $excelFile = $_FILES['excelFile']['tmp_name'];
                $workbook = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelFile);
                $sheet = $workbook->getActiveSheet();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
    
                $headerRow = $sheet->rangeToArray('A1:' . $highestColumn . '1', NULL, TRUE, FALSE)[0];
    
                $studentData = [];
    
                for ($row = 2; $row <= $highestRow; $row++) {
                    $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE)[0];
                    $data = array_combine($headerRow, $rowData);
    
                    // Assuming these are your column names
                    $id = $data['RollNo'];
                    $fullName = $data['FullName'];
                    $email = $data['Email'];
                    $gender = $data['Gender'];
                    $mobile = isset($data['Mobile']) ? $data['Mobile'] : null;
                    $joiningyear = isset($data['Joiningyear']) ? $data['Joiningyear'] : null;
                    $parentName = isset($data['ParentName']) ? $data['ParentName'] : null;
                    $parentMobile = isset($data['ParentMobile']) ? $data['ParentMobile'] : null;
                    $address = isset($data['Address']) ? $data['Address'] : null;
                    $year = isset($data['Year']) ? $data['Year'] : null;
                    $academicyear = isset($data['Academicyear']) ? $data['Academicyear'] : null;
                    // Add any other columns as needed
    
                    // Assuming you want to store these in an array
                    $studentData[] = [
                        'id' => $id,
                        'fullName' => $fullName,
                        'email' => $email,
                        'gender' => $gender,
                        'mobile' => $mobile,
                        'joiningyear' => $joiningyear,
                        'parentName' => $parentName,
                        'parentMobile' => $parentMobile,
                        'address' => $address,
                        'year' => $year,
                        'academicyear' => $academicyear
                    ];
                }
    
                $db = new Database();
                $this->pdo = $db->getConnection();
                
                // Upsert data into the database
                foreach ($studentData as $student) {
                    $query = "INSERT INTO Student (id, fullName, email, gender, mobile, joiningyear, parentName, parentMobile, address, year, academicyear) 
                              VALUES (:id, :fullName, :email, :gender, :mobile, :joiningyear, :parentName, :parentMobile, :address, :year, :academicyear) 
                              ON DUPLICATE KEY UPDATE fullName = VALUES(fullName), email = VALUES(email), gender = VALUES(gender), 
                              mobile = VALUES(mobile), joiningyear = VALUES(joiningyear), parentName = VALUES(parentName), 
                              parentMobile = VALUES(parentMobile), address = VALUES(address), year = VALUES(year), 
                              academicyear = VALUES(academicyear)";
                    $stmt = $this->pdo->prepare($query);
                    $stmt->execute($student);
                }
                
                http_response_code(200);
                echo "Data inserted or updated successfully.";
    
            } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
                // Handle PhpSpreadsheet reader exception
                echo json_encode(array("error" => "PhpSpreadsheet Reader Error: " . $e->getMessage()));
            } catch (PDOException $e) {
                // Handle database errors
                echo json_encode(array("error" => "Database Error: " . $e->getMessage()));
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage();
            }
        }
    }
    

  

    public function updateStudent() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            try {
                // Connect to your database
                $db = new Database();
                $pdo = $db->getConnection();
    
                // Extract form data
                $id = $_POST['id'];
                $fullName = $_POST['fullName'];
                $email = $_POST['email'];
                $mobile = $_POST['mobile'];
                $gender = $_POST['gender'];
                $year = $_POST['year'];
                $joiningyear = $_POST['joiningyear'];
                $parentName = $_POST['parentName'];
                $parentMobile = $_POST['parentMobile'];
                $address = $_POST['address'];
                $academicyear = $_POST['academicyear'];
    
                // Check if an image file was uploaded
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                
                    // Handle the uploaded image
                    $imageTmpName = $_FILES['image']['tmp_name'];
                    $imageData = file_get_contents($imageTmpName); // Read image data
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $contentType = $finfo->buffer($imageData);
                    
                    $imageDimensions = getimagesize($imageTmpName);

        // Check if the image dimensions exceed a certain limit (e.g., 450x450)
        $maxWidth = 500;
        $maxHeight = 500;
        if ($imageDimensions[0] > $maxWidth || $imageDimensions[1] > $maxHeight) {
            // Image dimensions exceed the limit
            http_response_code(204);
            echo "Error: Image dimensions must be less than 450x450 pixels.";
        } else {
            http_response_code(200);
            // Image dimensions are within the limit
            echo "Image dimensions are acceptable.";
        }
                   // Compress the image
                  
                    
                 } 
                 else {
                    // No image uploaded, keep the previous image data
                    $sql = "SELECT image FROM Student WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':id', $id, PDO::PARAM_STR);
                    $stmt->execute();
                    $previousImageData = $stmt->fetchColumn();
    
                    // Set the image data to the previous image data
                    $imageData = $previousImageData;
                }
    
                // Prepare the SQL query
                $sql = "INSERT INTO Student (id, fullName, email, mobile, gender, year, joiningyear,parentName,parentMobile, address, image, academicyear) 
                        VALUES (:id, :fullName, :email, :mobile, :gender, :year, :joiningyear,:parentName,:parentMobile, :address, :image, :academicyear)
                        ON DUPLICATE KEY UPDATE 
                        fullName = VALUES(fullName), email = VALUES(email), mobile = VALUES(mobile), gender = VALUES(gender), 
                        year = VALUES(year), joiningyear = VALUES(joiningyear),parentName=VALUES(parentName),parentMobile=VALUES(parentMobile), address = VALUES(address), image = VALUES(image), 
                        academicyear = VALUES(academicyear)";
    
                // Prepare the statement
                $stmt = $pdo->prepare($sql);
    
                // Bind parameters
                $stmt->bindParam(':id', $id, PDO::PARAM_STR);
                $stmt->bindParam(':fullName', $fullName, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':mobile', $mobile, PDO::PARAM_STR);
                $stmt->bindParam(':gender', $gender, PDO::PARAM_STR);
                $stmt->bindParam(':year', $year, PDO::PARAM_STR);
                $stmt->bindParam(':joiningyear', $joiningyear, PDO::PARAM_STR);
                $stmt->bindParam(':parentName', $parentName, PDO::PARAM_STR);
                $stmt->bindParam(':parentMobile', $parentMobile, PDO::PARAM_STR);
                $stmt->bindParam(':address', $address, PDO::PARAM_STR);
                $stmt->bindParam(':image', $imageData, PDO::PARAM_LOB); // Bind image data as a BLOB
                $stmt->bindParam(':academicyear', $academicyear, PDO::PARAM_STR);
    
                // Execute the statement
                $stmt->execute();
    
                // Close the database connection
                $pdo = null;
    
                // Return success message or updated data
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Student data updated successfully"));
            } catch (PDOException $e) {
                // Handle database errors
                echo json_encode(array("error" => "Database Error: " . $e->getMessage()));
            } catch (Exception $e) {
                // Handle other errors
                echo json_encode(array("error" => "Error: " . $e->getMessage()));
            }
        }
    }
    

    


    //getStudents
    public function getStudents(){
        try {
            // Connect to your database
            $db = new Database();
   
            $this->pdo = $db->getConnection();
        
            // Fetch all students along with their related data
            $query = "SELECT * FROM Student";

                    
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $allStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
            
           
        
            // Send the formatted data as JSON response
            header('Content-Type: application/json');
            echo json_encode($allStudents);

        } catch (PDOException $e) {
            // Handle database errors
            echo "Database Error: " . $e->getMessage();
            exit();
        } catch (Exception $e) {
            // Handle other errors
            echo "Error: " . $e->getMessage();
            exit();
        }
    }


    // Define the function to get years and academic years
public function getYearAndAcademicYear() {
    try {
        // Connect to your database
        $db = new Database();
        $pdo = $db->getConnection();

        // Get distinct years
        $stmt1 = $pdo->prepare("SELECT DISTINCT year FROM Student");
        $stmt1->execute();
        $years = $stmt1->fetchAll(PDO::FETCH_COLUMN);

        // Get distinct academic years
        $stmt2 = $pdo->prepare("SELECT DISTINCT academicyear FROM Student");
        $stmt2->execute();
        $academicYears = $stmt2->fetchAll(PDO::FETCH_COLUMN);

        // Close the database connection
        $pdo = null;

        // Return the years and academic years as JSON
        header('Content-Type: application/json');
        echo json_encode(array("years" => $years, "academicyears" => $academicYears));
    } catch (PDOException $e) {
        // Handle database errors
        return json_encode(array("error" => "Database Error: " . $e->getMessage()));
    } catch (Exception $e) {
        // Handle other errors
        echo json_encode(array("error" => "Error: " . $e->getMessage()));
    }
}


public function getStudentByYearAndAcademicYear() {
    try {
        // Connect to your database
        $db = new Database();
        $pdo = $db->getConnection();

        // Prepare the SQL query
        // $stmt = $pdo->prepare("SELECT * FROM Student WHERE year = :year AND academicyear = :academicyear");
        $stmt = $pdo->prepare("SELECT id, fullName, rollNo, email, mobile, gender,parentName,parentMobile, year, joiningyear, address, academicyear, password, role FROM Student WHERE year = :year AND academicyear = :academicyear");
        
        $year = $_GET['year'];
        $academicYear = $_GET['academicyear'];
        // Bind parameters
        $stmt->bindParam(':year', $year, PDO::PARAM_STR);
        $stmt->bindParam(':academicyear', $academicYear, PDO::PARAM_STR);

        // Execute the query
        $stmt->execute();

        // Fetch all matching students
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Close the database connection
        $pdo = null;

        // Return the students as JSON
        header('Content-Type: application/json');
        echo json_encode($students);
    } catch (PDOException $e) {
        // Handle database errors
        echo json_encode(array("error" => "Database Error: " . $e->getMessage()));
    } catch (Exception $e) {
        // Handle other errors
        echo json_encode(array("error" => "Error: " . $e->getMessage()));
    }
}

public function getStudentById() {
    try {
        // Connect to your database
        $db = new Database();
        $pdo = $db->getConnection();

        // Prepare the SQL query
        $stmt = $pdo->prepare("SELECT id, fullName, rollNo, email, mobile, gender,parentName,parentMobile, year, joiningyear, address, academicyear, password, role FROM Student WHERE id = :id");
        $id = $_GET['id'];
        // Bind the parameter
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        // Execute the query
        $stmt->execute();

        // Fetch the student
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        // Close the database connection
        $pdo = null;

        // Return the student as JSON
        header('Content-Type: application/json');
        echo json_encode($student);
    } catch (PDOException $e) {
        // Handle database errors
        echo json_encode(array("error" => "Database Error: " . $e->getMessage()));
    } catch (Exception $e) {
        // Handle other errors
        echo json_encode(array("error" => "Error: " . $e->getMessage()));
    }
}


public function fetchImageData() {
    try {
        // Establish database connection
        $db = new Database();
        $pdo = $db->getConnection();

        $studentId = $_GET['id'];
        $stmt = $pdo->prepare("SELECT image FROM Student WHERE id = ?");
        $stmt->execute([$studentId]);
        $imageData = $stmt->fetchColumn();
        
        
        // Determine the content type based on the image data
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $contentType = $finfo->buffer($imageData);
        if($contentType=="application/x-empty"){
            header('Content-Type: text/plain; charset=UTF-8');
            $text="imagenotfound";
            // $text = str_replace("\r\n", "", $text);
            
            echo $text;
            
        }else{
            // Set the appropriate content type
        header("Content-Type: $contentType");
        
        // Output the image data
        echo base64_encode($imageData);

        }
        
        
    } catch (PDOException $e) {
        return null; // Return null in case of error
    }
}


public function promotestudents(){
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body, true);
    
    if($data!==null){
        $year=$data['year'];
        $academicYear=$data['academicyear'];
        $studentsToUpdate = $data['students'];
    }

    if($year!==null && $academicYear!==null && $studentsToUpdate!==null){
    try {
        // Establish connection to MySQL database using PDO
        $db = new Database();
        $pdo = $db->getConnection();
    
        // Prepare SQL statement
        $sql = "UPDATE Student SET year = :year, academicyear = :academicyear WHERE id = :studentId";
        $stmt = $pdo->prepare($sql);
    
        // Loop through the array of students to update each student's information
        foreach ($studentsToUpdate as $studentData) {
            
            $studentId = $studentData;
            $year =$year ;
            $academicyear =$academicYear ;
    
            // Bind parameters and execute the statement
            $stmt->bindParam(':year', $year);
            $stmt->bindParam(':academicyear', $academicyear);
            $stmt->bindParam(':studentId', $studentId);
            $stmt->execute();
    
            // Output success or error message
            echo "Studentsupdated successfully.<br>";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage(); // Output any PDO exception error
    }
    }
}


public function deleteStudents(){
   
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body, true);
   
    if($data!==null){
        $year=$data['year'];
        $academicYear=$data['academicyear'];
        
    }
    if($year!==null && $academicYear!==null ){
try {
    // Create a PDO connection
    $db = new Database();
        $pdo = $db->getConnection();
    
    // Begin a transaction
    $pdo->beginTransaction();

    // Year and Academic Year values to delete students
  

    // Step 1: Fetch student IDs based on year and academic year values
    $stmt = $pdo->prepare("SELECT id FROM Student WHERE year = :year AND academicyear = :academicYear");
    $stmt->bindParam(':year', $year);
    $stmt->bindParam(':academicYear', $academicYear);
    $stmt->execute();
    $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Step 2: Delete related data from dependent tables for each student
    foreach ($studentIds as $studentId) {
        // Delete from Subject table
        $stmt = $pdo->prepare("DELETE FROM Subject WHERE attendanceId IN (SELECT id FROM Attendance WHERE studentId = :studentId)");
        $stmt->bindParam(':studentId', $studentId);
        $stmt->execute();

        // Delete from AssessmentSubject table
        $stmt = $pdo->prepare("DELETE FROM AssessmentSubject WHERE assessmentId IN (SELECT id FROM Assessment WHERE studentId = :studentId)");
        $stmt->bindParam(':studentId', $studentId);
        $stmt->execute();

        // Delete from Assessment table
        $stmt = $pdo->prepare("DELETE FROM Assessment WHERE studentId = :studentId");
        $stmt->bindParam(':studentId', $studentId);
        $stmt->execute();

        // Delete from Attendance table
        $stmt = $pdo->prepare("DELETE FROM Attendance WHERE studentId = :studentId");
        $stmt->bindParam(':studentId', $studentId);
        $stmt->execute();

        // Step 3: Delete the student from the main Student table
        $stmt = $pdo->prepare("DELETE FROM Student WHERE id = :studentId");
        $stmt->bindParam(':studentId', $studentId);
        $stmt->execute();
    }

    // Commit the transaction if all operations are successful
    $pdo->commit();
    
    http_response_code(200);
    echo "Students and related data deleted successfully.";

} catch (PDOException $e) {
    // Rollback the transaction if an error occurred
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
    }

}

function fetchDistinctYearsAndAcademicYears($pdo, $table) {
    $query = "SELECT DISTINCT year, academicyear FROM $table";
    $statement = $pdo->prepare($query);
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}


public function getAllYearsAndAcademicYears(){
    

    try {
        $db = new Database();
            $pdo = $db->getConnection();
        // Fetch distinct values from Assessment table
        $assessmentYears = $this->fetchDistinctYearsAndAcademicYears($pdo, 'Assessment');
    
        
    
        // Fetch distinct values from Attendance table
        $attendanceYears = $this->fetchDistinctYearsAndAcademicYears($pdo, 'Attendance');
    
        // Fetch distinct values from Student table
        $studentYears = $this->fetchDistinctYearsAndAcademicYears($pdo, 'Student');
    
        // Merge all distinct years and academic years
        $allYears = array_merge($assessmentYears, $attendanceYears, $studentYears);
    
        // Remove duplicates
        $distinctYears = array_unique($allYears, SORT_REGULAR);
    
       
        $years = [];
        $academicYears = [];
        
        foreach ($distinctYears as $year) {
            $years[] = $year['year'];
            $academicYears[] = $year['academicyear'];
        }

        $uniqueYears = array_values(array_unique($years));
$uniqueAcademicYears = array_values(array_unique($academicYears));
        
        header("Content-Type: application/json");
        
        // Output the years and academic years arrays in JSON format
        echo json_encode(['years' => $uniqueYears, 'academicyears' => $uniqueAcademicYears]);
        

        // foreach ($distinctYears as $year) {
        //     echo "Year: " . $year['year'] . ", Academic Year: " . $year['academicyear'] . "<br>";
        // }
        
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}


}


// Example usage
// $studentController = new \Controller\StudentController();
// $studentController->createStudents($_REQUEST, null);

