<?php

use Controller\Login;

$login=new Login();
// $login->getAll();

function loginuser (){
 $LoginController= new Login();
 $LoginController->loginuser();
}

$url=explode('/', trim($_SERVER['REQUEST_URI'], '/')) ;
// echo implode("/",$url);
// echo "<br>";
// echo json_encode($url[2])."<br>";

$url[2]();
$url1=$_SERVER['PATH_INFO'];
// echo $url1;


switch($url[2]){
    case 'loginuser':
        loginuser();
        break;
}
