<?php


use Controller\AttendanceController;

function createAttendance(){

    $AttendenceController= new AttendanceController();
    $AttendenceController->createAttendance();
}

function getAttendanceById(){

    $AttendenceController= new AttendanceController();
    $AttendenceController->getAttendanceById();
}

function getAttendanceByIdAndDateRange(){

    $AttendenceController= new AttendanceController();
    $AttendenceController->getAttendanceByIdAndDateRange();
}

function getAttendanceByIdAndMonth(){

    $AttendenceController= new AttendanceController();
    $AttendenceController->getAttendanceByIdAndMonth();
}

function getTotalAttendance(){

    $AttendenceController= new AttendanceController();
    $AttendenceController->getTotalAttendance();
}

function getAttendenceByYearAcademicyearId(){

    $AttendenceController= new AttendanceController();
    $AttendenceController->getAttendenceByYearAcademicyearId();
}

function editAttendance(){

    $AttendenceController= new AttendanceController();
    $AttendenceController->editAttendance();
}

function getAttendanceForReportsByMonth(){

    $AttendenceController= new AttendanceController();
    $AttendenceController->getAttendanceForReportsByMonth();
}

function getAttendanceForReportsByMbbsYear(){

    $AttendenceController= new AttendanceController();
    $AttendenceController->getAttendanceForReportsByMbbsYear();
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
    
    case 'createAttendance':
        createAttendance();
        break;
    case 'getAttendanceById':
        getAttendanceById();
        break;
    case 'getAttendanceByIdAndDateRange':
        getAttendanceByIdAndDateRange();
        break;
    case 'getAttendanceByIdAndMonth':
        getAttendanceByIdAndMonth();
        break;
    case 'getTotalAttendance':
        getTotalAttendance();
        break;
    case 'getAttendenceByYearAcademicyearId':
        getAttendenceByYearAcademicyearId();
        break;
    case 'editAttendance':
        editAttendance();
        break;
    case 'getAttendanceForReportsByMonth':
        getAttendanceForReportsByMonth();
        break;
    case 'getAttendanceForReportsByMbbsYear':
        getAttendanceForReportsByMbbsYear();
        break;
    
}
