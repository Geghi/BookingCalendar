<?php

if(!isset($_SESSION)) 
    session_start(); 

if(!isset($_SESSION["username"]))
    return header("location: ../../login.php"); 
    
include_once '../bookFunctions.php';
include_once '../bookDbManager.php';
include_once '../connect.php';

if(!isset($_GET['parrucchiere']))
    return header("location: ../../book.php?date=".$_SESSION["date"]."&msg=-1");

$hairdresser = $_GET['parrucchiere'];
$hairdresserId = getHairdresserId($mysqli, $hairdresser);

if(getHairdresserPrenotationsOfTheDay($mysqli, $hairdresserId, $_SESSION["date"]) != null)
    return header("location: ../../book.php?date=".$_SESSION["date"]."&msg=4");

if(isEmployeeDaySchedulePresent($hairdresserId, $_SESSION["date"], $mysqli))
    updateShowHideEmployee($hairdresserId, $_SESSION["date"], $mysqli);
else {
    $showHide = 0;
    $scheduleId = getScheduleId($_SESSION["selectedDay"], $mysqli);
    if($scheduleId == 1 || $scheduleId == 7)
        $showHide = 1;
    list($schedule[0], $schedule[1], $schedule[2], $schedule[3]) = getGeneralEmployeeSchedule($hairdresserId, $scheduleId, $mysqli);
    insertNewEmployeeSchedule($hairdresserId, $scheduleId, $_SESSION["date"], $schedule, $showHide, $mysqli);
}
header("location: ../../book.php?date=".$_SESSION["date"]);

?>