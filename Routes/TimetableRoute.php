<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: *');
use Controller\TimetableController;

function createTimetable(){

    $timetableController=new TimetableController();
    $timetableController->createTimetable();

}

//http://localhost:8080/api/timetable/getTimetableYearAndAcademicyear
function getTimetableYearAndAcademicyear(){

    $timetableController=new TimetableController();
    $timetableController->getTimetableYearAndAcademicyear();

}

//http://localhost:8080/api/timetable/getTimetableBYyearAndAcademicyear?year=MBBS-I&academicyear=2024-2025
function getTimetableBYyearAndAcademicyear(){

    $timetableController=new TimetableController();
    $timetableController->getTimetableBYyearAndAcademicyear();


}

function updateTimetable(){

    $timetableController=new TimetableController();
    $timetableController->updateTimetable();


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



switch($current){
    case 'createTimetable':
        createTimetable();
        break;
    case 'getTimetableYearAndAcademicyear':
        getTimetableYearAndAcademicyear();
        break;
    case 'getTimetableBYyearAndAcademicyear':
        getTimetableBYyearAndAcademicyear();
        break;
    case 'updateTimetable':
        updateTimetable();
        break;
    
}
