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

$hairdresser = preg_replace("/[^A-Za-z0-9 ]/", '', $_POST['parrucchiere']);
if( !strlen($hairdresser) > 0)
    return header("location: ../../index.php?msg=1");

$hairdressers = getHairdressers($mysqli);
if(in_array($hairdresser, $hairdressers))  
    return header("location: ../../index.php?msg=3");

addEmployee($hairdresser, $mysqli);
header("location: ../../index.php?msg=success");

?>