<?php

include './php/bookDbManager.php';
include './php/connect.php';

if(!isset($_SESSION)) 
    session_start(); 

if(!isset($_SESSION["username"]))
    return header("location: ../login.php"); 

if(isset($_POST['removePrenotations'])){
    removeOldPrenotationsAndClients($mysqli);
    header("location: ./index.php?msg=success");
}

?>