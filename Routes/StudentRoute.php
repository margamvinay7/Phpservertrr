

<?php


use Controller\StudentController;








function createStudent(){
$req = $_REQUEST; // Assuming this is how you get request data
$res = null; // Placeholder for response, assuming not used in this context

$StudentController = new StudentController($req, $res);

$StudentController->createStudents($req,$res);

}


function getStudents(){
    $req = $_REQUEST; // Assuming this is how you get request data
$res = null; // Placeholder for response, assuming not used in this context

$StudentController = new StudentController($req, $res);

$StudentController->getStudents();
}

function getYearAndAcademicYear(){

    $req = $_REQUEST; // Assuming this is how you get request data
    $res = null; // Placeholder for response, assuming not used in this context
    
    $StudentController = new StudentController($req, $res);
    
    $StudentController->getYearAndAcademicYear();

}

function getStudentByYearAndAcademicYear(){

    $req = $_REQUEST; // Assuming this is how you get request data
    $res = null; // Placeholder for response, assuming not used in this context
    
    $StudentController = new StudentController($req, $res);
    $StudentController->getStudentByYearAndAcademicYear();
}
function getStudentById(){

    $req = $_REQUEST; // Assuming this is how you get request data
    $res = null; // Placeholder for response, assuming not used in this context
    
    $StudentController = new StudentController($req, $res);
    $StudentController->getStudentById();
}
function updateStudent(){

    $req = $_REQUEST; // Assuming this is how you get request data
    $res = null; // Placeholder for response, assuming not used in this context
    
    $StudentController = new StudentController($req, $res);
    $StudentController->updateStudent();
}
function fetchImageData(){

    $req = $_REQUEST; // Assuming this is how you get request data
    $res = null; // Placeholder for response, assuming not used in this context
    
    $StudentController = new StudentController($req, $res);
    $StudentController->fetchImageData();
}

function promotestudents(){

    $req = $_REQUEST; // Assuming this is how you get request data
    $res = null; // Placeholder for response, assuming not used in this context
    
    $StudentController = new StudentController($req, $res);
    $StudentController->promotestudents();
}

function deleteStudents(){

    $req = $_REQUEST; // Assuming this is how you get request data
    $res = null; // Placeholder for response, assuming not used in this context
    
    $StudentController = new StudentController($req, $res);
    $StudentController->deleteStudents();
}
function getAllYearsAndAcademicYears(){

    $req = $_REQUEST; // Assuming this is how you get request data
    $res = null; // Placeholder for response, assuming not used in this context
    
    $StudentController = new StudentController($req, $res);
    $StudentController->getAllYearsAndAcademicYears();
}

$url=explode('/', trim($_SERVER['REQUEST_URI'], '/')) ;
// echo implode("/",$url);
// echo "<br>";
// echo json_encode($url[2])."<br>";

// getStudentsByYearAndAcademicYear?year=MBBS-I&academicyear=2024-2025

$url1=$_SERVER['REQUEST_URI'];
// $url1="getStudentsByYearAndAcademicYear";

$parts = explode('?', $url1);
// echo json_encode($parts);
// echo $url[2];

$current='';

if(count($parts)==2){
    $current=$parts[0];
    $current= explode('/', trim($current, '/'));
    $current=$current[2];
}else{
    $current=$url[2];
}

// echo $current;


// http://localhost:8080/api/student/getStudentById?id=9747674821
// http://localhost:8080/api/student/getStudentByYearAndAcademicYear?year=MBBS-I&academicyear=2024-2025
switch($current){
    case 'createStudent':
        createStudent();
        break;
    case 'getStudents':
        getStudents();
        break;
    case 'getYearAndAcademicYear':
        getYearAndAcademicYear();
        break;
    case 'getStudentByYearAndAcademicYear':
        getStudentByYearAndAcademicYear(); 
        break;
    case 'getStudentById':
        getStudentById(); 
        break;
    case 'updateStudent':
        updateStudent(); 
        break;
    case 'fetchImageData':
        fetchImageData(); 
        break;
    case 'promotestudents':
        promotestudents(); 
        break;
    case 'deleteStudents':
        deleteStudents(); 
        break;
    case 'getAllYearsAndAcademicYears':
        getAllYearsAndAcademicYears(); 
        break;


}
