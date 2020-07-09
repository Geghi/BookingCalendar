<?php

if(!isset($_SESSION)) 
    session_start(); 

if(!isset($_SESSION["username"]))
    return header("location: ../../login.php"); 
    
include_once '../bookFunctions.php';
include_once '../bookDbManager.php';
include_once '../connect.php';

if(!isset($_POST['parrucchiere']))
    return header("location: ../../index.php?msg=1");

$hairdresser = $_POST['parrucchiere'];


$hairdresserId = getHairdresserId($mysqli, $hairdresser);

if(removeEmployee($hairdresserId, $mysqli))
    header("location: ../../index.php?msg=success");
else
    header("location: ../../index.php?msg=2");

?>