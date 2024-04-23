<?php
namespace Controller;



use Core\Database;
use PDO;
use Exception;

use PDOException;

class TimetableController{

    private PDO $pdo;

    function createTimetable() {
        try {
            $rawdata=file_get_contents('php://input');
            $formData = json_decode(file_get_contents('php://input'), true);
            // Connect to your database
            $db = new Database();
            $this->pdo = $db->getConnection();
            $year = trim($formData['year']) ?? null;
$academicyear = trim($formData['academicyear'])?? null;
$days = $formData['Days']?? null;
    
            // Begin transaction
           if($year!==null && $academicyear !==null && $days !==null){
            $this->pdo->beginTransaction();
    
            // Create the timetable record
            $stmtTimetable = $this->pdo->prepare("INSERT INTO Timetable (year, academicyear) VALUES (?, ?)");
            $stmtTimetable->execute([$year, $academicyear]);
            $timetableId = $this->pdo->lastInsertId();
    
            // Loop through days
            foreach ($days as $day) {
                // Create the day record
                $stmtDay = $this->pdo->prepare("INSERT INTO Days (timetableId, day) VALUES (?, ?)");
                $stmtDay->execute([$timetableId, $day['day']]);
                $dayId = $this->pdo->lastInsertId();
    
                // Loop through periods
                foreach ($day['Periods'] as $period) {
                    // Create the period record
                    $stmtPeriod = $this->pdo->prepare("INSERT INTO Periods (daysId, time, subject) VALUES (?, ?, ?)");
                    $stmtPeriod->execute([$dayId, $period['time'], trim($period['subject'])]);
                }
            }
    
            // Commit transaction
            $this->pdo->commit();
           }
    
            // Close the database connection
            // $this->pdo = null;
    
            // Return success message
            // http_response_code(200);
            header('Content-Type: application/json');
            
            // echo json_encode(array("message" => "Timetable created successfully"));
            echo json_encode(array("year"=>$formData['year'],"academicyear"=>$formData['academicyear'],"Days"=>$formData['Days']));
        } catch (PDOException $e) {
            // Rollback transaction
            $this->pdo->rollBack();
    
           
            echo json_encode(array("error" => "Database Error: " . $e->getMessage()));
        } catch (Exception $e) {
            // Handle other errors
            // http_response_code(400);
            echo json_encode(array("error" => "Error: " . $e->getMessage()));
        }
    }


    function getTimetableYearAndAcademicyear() {
        try {
            // Connect to your database
            $db = new Database();
            $this->pdo = $db->getConnection();
            
            // Fetch distinct years
            $stmtYears = $this->pdo->prepare("SELECT DISTINCT year FROM Timetable");
            $stmtYears->execute();
            $years = $stmtYears->fetchAll(PDO::FETCH_COLUMN);

            // Fetch distinct academicyears
            $stmtAcademicyears = $this->pdo->prepare("SELECT DISTINCT academicyear FROM Timetable");
            $stmtAcademicyears->execute();
            $academicyears = $stmtAcademicyears->fetchAll(PDO::FETCH_COLUMN);

            // Close the database connection
            // $this->pdo = null;

            // Return the fetched years and academicyears
            echo json_encode(array("years" => $years, "academicyears" => $academicyears));
        } catch (PDOException $e) {
            // Handle database errors
            echo json_encode(array("error" => "Database Error: " . $e->getMessage()));
        } catch (Exception $e) {
            // Handle other errors
            echo json_encode(array("error" => "Error: " . $e->getMessage()));
        }
    }


    function getTimetableBYyearAndAcademicyear() {
        try {

            
             $year = $_GET['year'];
            $academicyear = $_GET['academicyear'];
            // Connect to your database
            $db = new Database();
            $this->pdo = $db->getConnection();

            // Fetch timetable data including days and periods based on year and academicyear
            $stmt = $this->pdo->prepare("
                SELECT t.*, d.day, p.time, p.subject 
                FROM Timetable t 
                INNER JOIN Days d ON t.id = d.timetableId 
                INNER JOIN Periods p ON d.id = p.daysId 
                WHERE t.year = :year AND t.academicyear = :academicyear
            ");
            $stmt->bindParam(':year', $year, PDO::PARAM_STR);
            $stmt->bindParam(':academicyear', $academicyear, PDO::PARAM_STR);
            $stmt->execute();
            $timetableData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $formattedTimetable = array(
                'id' => $timetableData[0]['id'],
                'year' => $timetableData[0]['year'],
                'academicyear' => $timetableData[0]['academicyear'],
                'Days' => array()
            );
            
            foreach ($timetableData as $row) {
                // Check if the day exists in the formatted timetable array
                $dayExists = false;
                foreach ($formattedTimetable['Days'] as &$formattedDay) {
                    if ($formattedDay['day'] == $row['day']) {
                        // If the day exists, add the period to the existing day
                        $formattedDay['Periods'][] = array(
                            'time' => $row['time'],
                            'subject' => $row['subject']
                        );
                        $dayExists = true;
                        break;
                    }
                }
                unset($formattedDay); // Unset the reference to avoid potential bugs
            
                if (!$dayExists) {
                    // If the day doesn't exist, create a new day entry
                    $formattedTimetable['Days'][] = array(
                        'day' => $row['day'],
                        'Periods' => array(
                            array(
                                'time' => $row['time'],
                                'subject' => $row['subject']
                            )
                        )
                    );
                }
            }
            
            // Now, $formattedTimetable contains the data in the desired format
            

            // Return timetable data
            header('content-type:application/json');
            echo json_encode($formattedTimetable);
        } catch (PDOException $e) {
            // Handle database errors
            echo json_encode(array("error" => "Database Error: " . $e->getMessage()));
        } catch (Exception $e) {
            // Handle other errors
            echo json_encode(array("error" => "Error: " . $e->getMessage()));
        }
    }

    // function updateTimetable() {
    //     try {
    //         $formData = json_decode(file_get_contents('php://input'), true);
    //             // Connect to your database
                
    //             $id=$formData['id'];
    //             $year = $formData['year'];
    //     $academicyear = $formData['academicyear'];
    //     $Days = $formData['Days'];
            
    //     if($id!==null && $year!==null && $academicyear!==null && $Days!==null){

        
    //         // Start a transaction
    //         $db = new Database();
    //         $this->pdo = $db->getConnection();
    //         $this->pdo->beginTransaction();

    //         // Delete related Periods
    //         $stmtDeletePeriods = $this->pdo->prepare("DELETE FROM Periods WHERE daysId IN (SELECT id FROM Days WHERE timetableId = :id)");
    //         $stmtDeletePeriods->bindParam(':id', $id, PDO::PARAM_INT);
    //         $stmtDeletePeriods->execute();

    //         // Delete related Days
    //         $stmtDeleteDays = $this->pdo->prepare("DELETE FROM Days WHERE timetableId = :id");
    //         $stmtDeleteDays->bindParam(':id', $id, PDO::PARAM_INT);
    //         $stmtDeleteDays->execute();

    //         // Delete the timetable itself
    //         $stmtDeleteTimetable = $this->pdo->prepare("DELETE FROM Timetable WHERE id = :id");
    //         $stmtDeleteTimetable->bindParam(':id', $id, PDO::PARAM_INT);
    //         $stmtDeleteTimetable->execute();

    //         // Create the updated timetable
    //         $stmtCreateTimetable = $this->pdo->prepare("INSERT INTO Timetable (id, year, academicyear) VALUES (:id, :year, :academicyear)");
    //         $stmtCreateTimetable->bindParam(':id', $id, PDO::PARAM_INT);
    //         $stmtCreateTimetable->bindParam(':year', $year, PDO::PARAM_STR);
    //         $stmtCreateTimetable->bindParam(':academicyear', $academicyear, PDO::PARAM_STR);
    //         $stmtCreateTimetable->execute();

    //         // Retrieve the ID of the inserted timetable
    //         $timetableId = $this->pdo->lastInsertId();

    //         // Insert Days and Periods
    //         foreach ($Days as $day) {
    //             $stmtInsertDay = $this->pdo->prepare("INSERT INTO Days (day, timetableId) VALUES (:day, :timetableId)");
    //             $stmtInsertDay->bindParam(':day', $day['day'], PDO::PARAM_STR);
    //             $stmtInsertDay->bindParam(':timetableId', $timetableId, PDO::PARAM_INT);
    //             $stmtInsertDay->execute();
    //             $dayId = $this->pdo->lastInsertId();

    //             foreach ($day['Periods'] as $period) {
    //                 $stmtInsertPeriod = $this->pdo->prepare("INSERT INTO Periods (daysId, time, subject) VALUES (:daysId, :time, :subject)");
    //                 $stmtInsertPeriod->bindParam(':daysId', $dayId, PDO::PARAM_INT);
    //                 $stmtInsertPeriod->bindParam(':time', $period['time'], PDO::PARAM_STR);
    //                 $stmtInsertPeriod->bindParam(':subject', $period['subject'], PDO::PARAM_STR);
    //                 $stmtInsertPeriod->execute();
    //             }
    //         }

    //         // Commit the transaction
    //         $this->pdo->commit();

    //         // Return success message
    //         echo "Table created";
    //     }
    //     } catch (PDOException $e) {
    //         // Rollback the transaction and handle database errors
    //         $this->pdo->rollBack();
    //         echo "Database Error: " . $e->getMessage();
    //     } catch (Exception $e) {
    //         // Handle other errors
    //         echo "Error: " . $e->getMessage();
    //     }
    // }


   public function updateTimetable() {
        try {
            $formData = json_decode(file_get_contents('php://input'), true);
            
            // Validate and retrieve data from the request
            $id = $formData['id'] ?? null;
            $year = $formData['year'] ?? null;
            $academicyear = $formData['academicyear'] ?? null;
            $Days = $formData['Days'] ?? null;
    
            // Ensure all required data is provided
            if ($id !== null && $year !== null && $academicyear !== null && $Days !== null) {
                // Start a transaction
                $db = new Database();
                $pdo = $db->getConnection();
                $pdo->beginTransaction();
    
                // Delete existing periods
                $stmtDeletePeriods = $pdo->prepare("DELETE FROM Periods WHERE daysId IN (SELECT id FROM Days WHERE timetableId = :id)");
                $stmtDeletePeriods->bindParam(':id', $id, PDO::PARAM_INT);
                $stmtDeletePeriods->execute();
    
                // Delete existing days
                $stmtDeleteDays = $pdo->prepare("DELETE FROM Days WHERE timetableId = :id");
                $stmtDeleteDays->bindParam(':id', $id, PDO::PARAM_INT);
                $stmtDeleteDays->execute();
    
                // Delete the existing timetable
                $stmtDeleteTimetable = $pdo->prepare("DELETE FROM Timetable WHERE id = :id");
                $stmtDeleteTimetable->bindParam(':id', $id, PDO::PARAM_INT);
                $stmtDeleteTimetable->execute();
    
                // Create the updated timetable
                $stmtCreateTimetable = $pdo->prepare("INSERT INTO Timetable (id, year, academicyear) VALUES (:id, :year, :academicyear)");
                $stmtCreateTimetable->bindParam(':id', $id, PDO::PARAM_INT);
                $stmtCreateTimetable->bindParam(':year', $year, PDO::PARAM_STR);
                $stmtCreateTimetable->bindParam(':academicyear', $academicyear, PDO::PARAM_STR);
                $stmtCreateTimetable->execute();
    
                // Retrieve the ID of the inserted timetable
                $timetableId = $pdo->lastInsertId();
    
                // Insert Days and Periods
                foreach ($Days as $day) {
                    $stmtInsertDay = $pdo->prepare("INSERT INTO Days (day, timetableId) VALUES (:day, :timetableId)");
                    $stmtInsertDay->bindParam(':day', $day['day'], PDO::PARAM_STR);
                    $stmtInsertDay->bindParam(':timetableId', $timetableId, PDO::PARAM_INT);
                    $stmtInsertDay->execute();
                    $dayId = $pdo->lastInsertId();
    
                    foreach ($day['Periods'] as $period) {
                        $stmtInsertPeriod = $pdo->prepare("INSERT INTO Periods (daysId, time, subject) VALUES (:daysId, :time, :subject)");
                        $stmtInsertPeriod->bindParam(':daysId', $dayId, PDO::PARAM_INT);
                        $stmtInsertPeriod->bindParam(':time', $period['time'], PDO::PARAM_STR);
                        $stmtInsertPeriod->bindParam(':subject', trim($period['subject']), PDO::PARAM_STR);
                        $stmtInsertPeriod->execute();
                    }
                }
    
                // Commit the transaction
                $pdo->commit();
    
                http_response_code(200);
                // Return success message
                echo "Table updated";
            } else {
                // Handle missing or invalid data
                echo "Invalid data provided";
            }
        } catch (PDOException $e) {
            // Rollback the transaction and handle database errors
            $pdo->rollBack();
            echo "Database Error: " . $e->getMessage();
        } catch (Exception $e) {
            // Handle other errors
            echo "Error: " . $e->getMessage();
        }
    }

    public function deleteTimetable() {
        try {
            $formData = json_decode(file_get_contents('php://input'), true);
            
            // Validate and retrieve data from the request
            $id = $formData['id'] ?? null;
           
            
    
            // Ensure all required data is provided
            if ($id !== null  ) {
                // Start a transaction
                $db = new Database();
                $pdo = $db->getConnection();
                $pdo->beginTransaction();
    
                // Delete existing periods
                $stmtDeletePeriods = $pdo->prepare("DELETE FROM Periods WHERE daysId IN (SELECT id FROM Days WHERE timetableId = :id)");
                $stmtDeletePeriods->bindParam(':id', $id, PDO::PARAM_INT);
                $stmtDeletePeriods->execute();
    
                // Delete existing days
                $stmtDeleteDays = $pdo->prepare("DELETE FROM Days WHERE timetableId = :id");
                $stmtDeleteDays->bindParam(':id', $id, PDO::PARAM_INT);
                $stmtDeleteDays->execute();
    
                // Delete the existing timetable
                $stmtDeleteTimetable = $pdo->prepare("DELETE FROM Timetable WHERE id = :id");
                $stmtDeleteTimetable->bindParam(':id', $id, PDO::PARAM_INT);
                $stmtDeleteTimetable->execute();
    
               
               
                // Commit the transaction
                $pdo->commit();
    
                http_response_code(200);
                // Return success message
                echo "Table Deleted";
            } else {
                // Handle missing or invalid data
                echo "Invalid data provided";
            }
        } catch (PDOException $e) {
            // Rollback the transaction and handle database errors
            $pdo->rollBack();
            echo "Database Error: " . $e->getMessage();
        } catch (Exception $e) {
            // Handle other errors
            echo "Error: " . $e->getMessage();
        }
    }
    

    


}


