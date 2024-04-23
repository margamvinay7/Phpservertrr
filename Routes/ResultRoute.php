<?php


use Controller\ResultController;


function createResults(){
    $ResultController= new ResultController();
    $ResultController->createResults();
}

function getAssessmentyearAndAcademicyear(){
    $ResultController= new ResultController();
    $ResultController->getAssessmentyearAndAcademicyear();
}

//getAssessmentByYearAndIdAndAssessment?id=9747674811&year=MBBS-I&assessment=Ist Internal Assessment
function getAssessmentByYearAndIdAndAssessment(){
    $ResultController= new ResultController();
    $ResultController->getAssessmentByYearAndIdAndAssessment();
}

//getAssessmentById?id=20 //not use or same as getasessmentbyyearandidandassessment
function getAssessmentById(){
    $ResultController= new ResultController();
    $ResultController->getAssessmentById();
}

//getAssessmentsByYearAndId?id=9747674811&year=MBBS-I
function getAssessmentsByYearAndId(){
    $ResultController= new ResultController();
    $ResultController->getAssessmentsByYearAndId();
}

//getResultByYearAndAcademicYearAndStudentId?studentId=9747674811&year=MBBS-I&academicyear=2024-2025&assessment=Ist Internal Assessment
function getResultByYearAndAcademicYearAndStudentId(){
    $ResultController= new ResultController();
    $ResultController->getResultByYearAndAcademicYearAndStudentId();
}

//getResults
function getResults(){
    $ResultController= new ResultController();
    $ResultController->getResults();
}

//getAssessments?year=MBBS-I&academicyear=2024-2025
function getAssessments(){
    $ResultController= new ResultController();
    $ResultController->getAssessments();
}
    //getAssessmentList?year=MBBS-I&academicyear=2024-2025&assessment=Ist Internal Assessment
function getAssessmentList(){
    $ResultController= new ResultController();
    $ResultController->getAssessmentList();
}

//for array of year values for profile page in frontend
//getResultByYearsAndAcademicYearAndStudentId?studentId=9747674811&assessment=Ist Internal Assessment
function getResultByYearsAndAcademicYearAndStudentId(){
    $ResultController= new ResultController();
    $ResultController->getResultByYearsAndAcademicYearAndStudentId();
}

//updateAssessment?id=20
function updateAssessment(){
    $ResultController= new ResultController();
    $ResultController->updateAssessment();
}
function deleteAssessment(){
    $ResultController= new ResultController();
    $ResultController->deleteAssessment();
}

function updateAssessmentName(){
    $ResultController= new ResultController();
    $ResultController->updateAssessmentName();
}

function getAttendanceReports(){
    $ResultController= new ResultController();
    $ResultController->getAttendanceReports();
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
    case 'createResults':
        createResults();
        break;
    case 'getAssessmentyearAndAcademicyear':
        getAssessmentyearAndAcademicyear();
        break;
    case 'getAssessmentByYearAndIdAndAssessment':
        getAssessmentByYearAndIdAndAssessment();
        break;
    case 'getAssessmentById':
        getAssessmentById();
        break;
    case 'getAssessmentsByYearAndId':
        getAssessmentsByYearAndId();
        break;
    case 'getResultByYearAndAcademicYearAndStudentId':
        getResultByYearAndAcademicYearAndStudentId();
        break;
    case 'getResults':
        getResults();
        break;
    case 'getAssessments':
        getAssessments();
        break;
    case 'getAssessmentList':
        getAssessmentList();
        break;
    case 'getResultByYearsAndAcademicYearAndStudentId':
        getResultByYearsAndAcademicYearAndStudentId();
        break;
    case 'updateAssessment':
        updateAssessment();
        break;
    case 'deleteAssessment':
        deleteAssessment();
        break;
    case 'updateAssessmentName':
        updateAssessmentName();
        break;
    case 'getAttendanceReports':
        getAttendanceReports();
        break;
   
    
}
