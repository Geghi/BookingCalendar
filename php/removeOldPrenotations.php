<?php

if(!isset($_SESSION)) 
    session_start(); 

if(!isset($_SESSION["username"]))
    return header("location: ../login.php"); 

include_once './bookDbManager.php';
include_once './connect.php';

removeOldPrenotationsAndClients($mysqli);
header("location: ../index.php?msg=success");

?>