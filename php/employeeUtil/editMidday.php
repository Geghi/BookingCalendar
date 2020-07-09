<?php

if(!isset($_SESSION)) 
    session_start(); 

if(!isset($_SESSION["username"]))
    return header("location: ../../login.php"); 
    
include_once '../bookFunctions.php';
include_once '../bookDbManager.php';
include_once '../connect.php';

if(!isset($_POST['startAM']) || !isset($_POST['endAM']) || !isset($_POST['parrucchiere']))
    return header("location: ../../book.php?date=".$_SESSION["date"]."&msg=-1");

$schedule[0] = $_POST['startAM'];
$schedule[1] = $_POST['endAM'];
$hairdresserId = $_POST['parrucchiere'];
$hairdresser = getHairdresser($mysqli, $hairdresserId);
$scheduleId = getScheduleId($_SESSION["selectedDay"], $mysqli);
$prenotations = getPrenotationsOfTheDay($mysqli, $_SESSION["date"]);

if(isset($_POST['startPM']) || isset($_POST['endPM'])){
    $schedule[2] = $_POST['startPM'];
    $schedule[3] = $_POST['endPM'];
} else {
    $schedule[2] = null;
    $schedule[3] = null;
}

for($i = 0; $i < count($schedule) ; $i++)
    if($schedule[$i] == "Nessuno")
        $schedule[$i] = null;

if(!is_null($schedule[0]) && $schedule[0] >= $schedule[1])
    return header("location: ../../book.php?date=".$_SESSION["date"]."&msg=1");

if(!is_null($schedule[2]) && $schedule[2] >= $schedule[3])
    return header("location: ../../book.php?date=".$_SESSION["date"]."&msg=1");

$msg = null;
if(!checkPrenotationsWithNewSchedule($msg, $schedule, $prenotations, $hairdresser))
    return header("location: ../../book.php?date=".$_SESSION["date"]."&msg=5");


removeHairdresserDaySchedule($mysqli, $hairdresserId, $_SESSION["date"]);
insertNewEmployeeSchedule($hairdresserId, $scheduleId, $_SESSION["date"], $schedule, 1, $mysqli);

header("location: ../../book.php?date=".$_SESSION["date"]."&msg=success");

?>