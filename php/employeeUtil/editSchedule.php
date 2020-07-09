<?php

    include_once '../bookFunctions.php';
    include_once '../connect.php';

    if(!isset($_SESSION)) 
        session_start(); 

    if(!isset($_SESSION["username"]))
        return header("location: ../../login.php"); 

    include '../bookDbManager.php';
    include '../connect.php';

    if(!isset($_GET['slot']) || !isset($_GET['parrucchiere']))
        return header("location: ../../book.php?date=".$_SESSION["date"]."&msg=-1");
    
    $start = $_GET['slot'];
    $hairdresser = $_GET['parrucchiere'];  
    $remove = false;

    $hairdresserId = getHairdresserId($mysqli, $hairdresser);
    $scheduleId = getScheduleId($_SESSION["selectedDay"], $mysqli);
    
    list($newSchedule[0], $newSchedule[1], $newSchedule[2], $newSchedule[3]) = getEmployeeDaySchedule($hairdresserId, $_SESSION["date"], $mysqli);  
    
    $employeePrenotations = getHairdresserPrenotationsOfTheDay($mysqli, $hairdresserId, $_SESSION["date"]);

    if(is_null($newSchedule[0]) && is_null($newSchedule[2]))
        list($newSchedule[0], $newSchedule[1], $newSchedule[2], $newSchedule[3]) = getGeneralEmployeeSchedule($hairdresserId, $scheduleId, $mysqli);
    else 
        $remove = true;
    
    if(isBetweenTwoTimes($newSchedule[0] , $newSchedule[1], $start)){
        $newSchedule[1] = $start;
        $AM = true;
    }else {
        $newSchedule[3] = $start; 
        $AM = false; 
    }
    
    if(!isHairdresserEditable($employeePrenotations, $newSchedule, $AM))
        return header("location: ../../book.php?date=".$_SESSION["date"]."&msg=5");

    if($remove)
        removeHairdresserDaySchedule($mysqli, $hairdresserId, $_SESSION["date"]);

    if($newSchedule[0] == $newSchedule[1] && $newSchedule[2] == $newSchedule[3])
        $newSchedule = array(null, null, null, null);
    insertNewEmployeeSchedule($hairdresserId, $scheduleId, $_SESSION["date"], $newSchedule, 1, $mysqli);

    header("location: ../../book.php?date=".$_SESSION["date"]);

?>