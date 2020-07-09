<?php

include_once './php/bookFunctions.php';
include_once './php/connect.php';
include_once './php/bookDbManager.php';

if(!isset($_SESSION))  
    session_start(); 

if(!isset($_SESSION["username"]))
    return header("location: ./login.php"); 


if(!isset($_GET['parrucchiere']) || !isset($_GET['midday']))
    return header("location: ./book.php?date=".$_SESSION["date"]."&msg=error");


$hairdresser = $_GET['parrucchiere']; 
$midday = $_GET['midday'];

$hairdresserId = getHairdresserId($mysqli, $hairdresser);
$schedule = getEmployeeSchedule($_SESSION["selectedDay"], $_SESSION["date"], $hairdresserId, $mysqli);
$currentDaySchedule = getScheduleForCurrentDay($_SESSION["selectedDay"], $mysqli);

if($currentDaySchedule[2] == "00:00"){
    $schedule[2] = null;
    $schedule[3] = null;
}

?>

<!doctype html>
<html lang="it">
    <head>
        <title>Modifica Orario Addetto</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
        <link rel="stylesheet" href="../css/index.css">
    </head>

    <body>
        <div class="container">
            <h1 class="text-center">Modifica Orario Parrucchiere</h1><hr>
            <div class="row">
                <div class="col-md-12">
                    <form action="./php/employeeUtil/editMidday.php" method="post">
                   
                    <?php echo "<input type='hidden' name='parrucchiere' value='$hairdresserId'>" ?>

                    <center><label for="" class="col-sm-2 col-form-label">Inizio Mattina</label></center>
                    <div class="form-group row">
                        <select class="form-control" name="startAM" id="startMorningHairdresser">
                            <?php setScheduleValuesOption($currentDaySchedule[0], $currentDaySchedule[1], $schedule[0]); ?>
                        </select>
                    </div>
                    <center><label for="" class="col-sm-2 col-form-label">Fine Mattina</label></center>
                    <div class="form-group row">
                        <select class="form-control" name="endAM" id="startMorningHairdresser">
                            <?php setScheduleValuesOption($currentDaySchedule[0], $currentDaySchedule[1], $schedule[1]); ?>
                        </select>
                    </div>
                    <br><br>
                    <?php 
                        if($currentDaySchedule[2] != "00:00"){
                    ?>
                    <center><label for="" class="col-sm-2 col-form-label">Inizio Pomeriggio</label></center>
                    <div class="form-group row">
                        <select class="form-control" name="startPM" id="startMorningHairdresser">
                            <?php setScheduleValuesOption($currentDaySchedule[2], $currentDaySchedule[3], $schedule[2]); ?>
                        </select>
                    </div>
                    <center><label for="" class="col-sm-2 col-form-label">Fine Pomeriggio</label></center>
                    <div class="form-group row">
                        <select class="form-control" name="endPM" id="startMorningHairdresser">
                            <?php setScheduleValuesOption($currentDaySchedule[2], $currentDaySchedule[3], $schedule[3]); ?>
                        </select>
                    </div>

                    <?php 
                        }
                    ?>
                
                    <center><button type="submit" class="btn btn-primary">Modifica</button></center>
                    </form>
                </div>
            </div>
        </div>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
        <script type="text/javascript" src="../js/employee.js"></script>
    </body>
</html>