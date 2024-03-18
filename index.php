  <?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: *');

require __DIR__.'/vendor/autoload.php';







$url=explode('/', trim($_SERVER['REQUEST_URI'], '/')) ;
// echo implode("/",$url);
// echo count($url)."<br>";
// echo json_encode($url);

$route=$url[1];



// echo "<br> after url route {$route}";

switch($route){
    
    case 'login':
       
        require 'Routes/LoginRoute.php';
        break;
    case 'student':
        
        require 'Routes/StudentRoute.php';
        break;

    case 'timetable':
        
        require 'Routes/TimetableRoute.php';
        break;

    case 'result':
       
        require 'Routes/ResultRoute.php';
        break;

    case 'attendance':
        
        require 'Routes/AttendanceRoute.php';
        break;
    default :
    
        $yourData = array("name" => "John", "age" => 30);
        
        $jsonData = json_encode($yourData);
        
        // Check for errors
        if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Error encoding JSON: " . json_last_error_msg();
        exit;
        }
        
        // Set Content-Type header
        header('Content-Type: application/json');
        
        echo $jsonData;
        break;
}
  



?>

 
 
 