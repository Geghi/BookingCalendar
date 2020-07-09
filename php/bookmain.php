<?php

if(!isset($_SESSION)) 
    session_start(); 

if(!isset($_SESSION["username"]))
    return header("location: ../login.php"); 

include_once './php/bookFunctions.php';
include_once './php/bookDbManager.php';
include_once './php/connect.php';

if(isset($_GET['date'])){
    $_SESSION["date"] = $_GET['date'];
    $date = $_GET['date'];
    $prenotationsOfTheDay = getPrenotationsOfTheDay($mysqli, $date);
}

$msg = "";
$selectedDay = getCurrentDayName($date);
$_SESSION["selectedDay"] = $selectedDay;
$daySchedule = getScheduleForCurrentDay($selectedDay, $mysqli);
$allHairdressers = getHairdressers($mysqli);
list($dailyHairdressers, $hairdressers) = getEmployeesScheduleForCurrentDay($selectedDay, $date, $mysqli);

foreach($hairdressers as $hairdresser)
    $dailyBookings[$hairdresser] = getTimeslotsOfTheDay($dailyHairdressers[$hairdresser]);

$timeslots = getTimeslotsOfTheDay($daySchedule);


if(isset($_POST['removeSubmit'])){
    $name = $_POST['nome'];
    $time = $_POST['orario'];
    $hairdresser = $_POST['parrucchiere'];

    $clientId = getClientId($date ,$time, $hairdresser, $mysqli);
    removePrenotationAndRelatedClient($date, $clientId, $mysqli);
}

if(isset($_POST['editSubmit'])){
    $oldHairdresser = $_POST['attualeParrucchiere'];
    $newHairdresser = getPostInputIfExists('nuovoParrucchiere');
    $startTime = $_POST['orario'];
    $endTime = $_POST['orarioFine'];

    if(in_array($newHairdresser, $hairdressers) && $oldHairdresser != $newHairdresser){
        $employeeSchedule = $dailyHairdressers[$newHairdresser];
        if(checkOpeningTime($startTime, $endTime, $employeeSchedule))
            if(checkOtherPrenotations($startTime, $endTime, $newHairdresser, $date, $prenotationsOfTheDay, $mysqli))
                movePrenotation($newHairdresser, $oldHairdresser, $startTime, $date, $mysqli); 
            else 
                $msg = "<div class='alert alert-danger'>Modifica non effettuata: Conflitto tra orari rilevato.</div>";
        else
            $msg = "<div class='alert alert-danger'>Modifica non effettuata: Il parrucchiere selezionato non è in negozio durante il taglio considerato.</div>";
    }else
        $msg = "<div class='alert alert-danger'>Modifica non effettuata: Parrucchiere selezionato non valido.</div>";
}

if(isset($_POST['splitSubmit'])){
    $startTime = $_POST['inizioTaglio'];
    $middleTime = $_POST['divisioneTaglio'];
    $endTime = $_POST['fineTaglio'];
    $hairdresser = $_POST['parrucchiere'];

    list($clientId, $cut) = getClientAndCut($startTime, $date, $hairdresser, $mysqli);
    updatePrenotationEndTime($middleTime, $startTime, $date, $hairdresser, $mysqli);
    addSinglePrenotation($middleTime, $endTime, $clientId, $hairdresser, $cut, $date, $mysqli);
    header('Refresh: 0');
}

if(isset($_POST['addSubmit'])){    
    addPrenotationSubmit($mysqli, $dailyHairdressers, $prenotationsOfTheDay, $daySchedule, $msg, $date);
}

// fill the array containing All the bookings for each hairdresser.
$ts = array_keys($timeslots);
for($i = 0; $i < count($hairdressers); $i ++){
    for($j = 0; $j < count($timeslots); $j++){
        if($ts[$j] == "End-Morning") continue;
        
        $prenotation = getSinglePrenotation($prenotationsOfTheDay, $ts[$j], $hairdressers[$i]);
        if($prenotation == -1) continue;

        $clientId = $prenotation['client'];
        $clientName = $prenotation['name'];
        $cut = $prenotation['cut'];
        $endTime = $prenotation['end'];
        $start = $prenotation['start'];
        $dailyBookings[$hairdressers[$i]][$ts[$j]]['first'] = true;
        
        while($ts[$j] != $endTime){
            if($ts[$j] == "End-Morning"){
                $j++;
                break;
            }
            $dailyBookings[$hairdressers[$i]][$ts[$j]]['clientId'] = $clientId;
            $dailyBookings[$hairdressers[$i]][$ts[$j]]['clientName'] = $clientName;
            $dailyBookings[$hairdressers[$i]][$ts[$j]]['cut'] = $cut;
            $dailyBookings[$hairdressers[$i]][$ts[$j]]['start'] = $start;
            $dailyBookings[$hairdressers[$i]][$ts[$j]]['end'] = $endTime;
            $j++;
            if($j >= count($timeslots))
                break;
        }
        $j--;
    }  
}


$bookingTable = "<div class='table-responsive'><table class='table table-bordered'><tr><td><h4>Mostra/Nascondi</h4></td>";
foreach($allHairdressers as $employee)
    $bookingTable .= "<td id=$employee class='show-hide'><h5>$employee</h5></td>";
$bookingTable .= "</tr></table></div>";

$bookingTable .= "<div class='table-responsive'><table class='table table-bordered'>";

// Fill the bookingTable for each timeslot and hairdresser.
if(!empty($hairdressers)){
    $bookingTable .= setTableHeaders($hairdressers, "AM");
    foreach($timeslots as $ts => $value){
        if($ts == "End-Morning"){
            addBlankRow();
            continue;
        }else
            $bookingTable .= "<tr><td><h4>$ts</h4></td>";

        foreach($hairdressers as $hairdresser){
            if(!array_key_exists($ts, $dailyBookings[$hairdresser]))
                $bookingTable .= "<td class='empty'></td>";          
            else if(count($dailyBookings[$hairdresser][$ts]) > 0){
                list($client, $cut, $end, $start) = getBookingInformation($dailyBookings[$hairdresser][$ts]);

                if(array_key_exists('first', $dailyBookings[$hairdresser][$ts])){
                    $bookingTable .= "<td><button id=$hairdresser-$end class='btn btn-danger btn-xs operations' data-toggle='tooltip' data-placement='top' title='$client: $cut'>Operazioni</button> ";
                    $bookingTable .= "<p class='text-left' id='$ts-$hairdresser'>$client: $cut</p></td>";
                }else 
                    $bookingTable .= "<td><button id=$hairdresser-$start-$end class='btn btn-danger btn-xs splitButton' data-toggle='tooltip' data-placement='top' title='$client: $cut'>$client $cut</button></td>";
            }else
                $bookingTable .= "<td><button id=$hairdresser class='btn btn-success book btn-xs'>Prenota</button><button type='submit' id=$hairdresser class='close removeSlots'>&times;</button></td>";   
        }  
        $bookingTable .= "<td><h4>$ts</h4></td></tr>";
    } 
}else 
    $msg = "<div class='alert alert-danger'>Non ci sono parrucchieri attualmente attivi.</div>";

$bookingTable .= "</table></div>";





?>

