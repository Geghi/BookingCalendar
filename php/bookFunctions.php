<?php

/* --------------- START SUBMIT FUNCTIONS --------------- */
function addPrenotationSubmit($mysqli, $dailyHairdressers, $prenotationsOfTheDay, $daySchedule, &$msg, $date){
    $hairdresser = $_POST['parrucchiere'];
    $name = preg_replace("/[^A-Za-z0-9 ]/", '', $_POST['nome']);
    $cut = $_POST['taglio'];
    $startTime1 = $_POST['orario'];
    $phases = getPhaseIntValues();
    list($endTime1, $startTime3, $endTime3) = getMultipleConsequentEndPhaseTime($startTime1, $phases);
    $employeeSchedule = $dailyHairdressers[$hairdresser];

    if( !strlen($name) > 0){
        $msg = "<div class='alert alert-danger'>Prenotazione non effettuata: Inserisci un nome valido.</div>";
        return;
    }

    //check if cut time does not fit in the day schedule.
    if(!checkOpeningTime($startTime1, $endTime3, $daySchedule)){
        $msg = "<div class='alert alert-danger'>Prenotazione non effettuata: La durata del taglio supera gli orari di apertura.</div>";
        return;
    }

    //check if phase1 time does not fit in the EMPLOYEE schedule.
    if(!checkOpeningTime($startTime1, $endTime1, $employeeSchedule)){
        $msg = "<div class='alert alert-danger'>Prenotazione non effettuata: La durata del taglio supera gli orari di lavoro del parrucchiere .</div>";
        return;
    }

    //check if prenotations conflicts in phase 1
    if(!checkOtherPrenotations($startTime1, $endTime1, $hairdresser, $date, $prenotationsOfTheDay, $mysqli)){
        $msg = "<div class='alert alert-danger'>Prenotazione non effettuata: Conflitto tra orari rilevato.</div>";
        return;
    }

    //check if phase2 time does not fit employee schedule or prenotations conflicts, if not, add the prenotation.
    if(checkOpeningTime($startTime3, $endTime3, $employeeSchedule) && checkOtherPrenotations($startTime3, $endTime3, $hairdresser, $date, $prenotationsOfTheDay, $mysqli)){
        addCompletePrenotationInfo($startTime1, $endTime1, $startTime3, $endTime3, $hairdresser, $hairdresser, $name, $cut, $date, $mysqli);
        return;
    }
  
    //if prenotations in phase2 conflicts then check if another employee is free.
    $freeHairdresser = getFreestAvailableEmployee($prenotationsOfTheDay, $startTime3, $endTime3, $dailyHairdressers);
    if(!$freeHairdresser){
        $msg = "<div class='alert alert-danger'>Prenotazione non effettuata: Conflitto tra orari rilevato.</div>";
        return;
    }

    addCompletePrenotationInfo($startTime1, $endTime1, $startTime3, $endTime3, $hairdresser, $freeHairdresser, $name, $cut, $date, $mysqli);            
}

/* --------------- END SUBMIT FUNCTIONS --------------- */

// Return TRUE if the given time respect the opening time. FALSE Otherwise.
function checkOpeningTime($startTime, $endTime, $schedule){
    if($startTime < $schedule[0] || ($startTime >= $schedule[1] && $startTime < $schedule[2]))
        return false;
    if(($startTime < $schedule[1] && $endTime <= $schedule[1]) || ($startTime >= $schedule[2] && $endTime <= $schedule[3]))
        return true;
    return false;
}

// Return TRUE if no Prenotation collide. FALSE Otherwhise.
function checkOtherPrenotations($newStartTime, $newEndTime, $hairdresser, $date, $prenotationsOfTheDay, $mysqli){
    if($newStartTime == $newEndTime)
        return true;
    
    foreach($prenotationsOfTheDay as $prenotation){
        if($prenotation['hairdresser'] != $hairdresser)
            continue;
        $startTime = $prenotation['start'];
        $endTime = $prenotation['end'];
        if(checkOverlappingPeriods($startTime, $endTime, $newStartTime, $newEndTime)) 
            return false;
    }
    return true;
}

// return TRUE if the periods overlaps. FALSE Otherwhise.
function checkOverlappingPeriods($startOne , $endOne, $startTwo, $endTwo){
    if($startOne == $endOne OR $startTwo == $endTwo)
        return false;
    if(($startOne > $startTwo && $startOne < $endTwo) || ($startTwo >= $startOne && $startTwo < $endOne))
        return true;
    else 
        return false;
}

// return TRUE if the checkTime is between start and end. FALSE Otherwhise.
function isBetweenTwoTimes($start , $end, $checkTime){
    if(is_null($start) || is_null($end))
        return false;
    if(($checkTime >= $start && $checkTime < $end))
        return true;
    return false;
}

// Given start time and interval --> return time after that interval.
function getEndPhaseTime($start, $interval){
    $phaseDuration = new DateInterval('PT'.$interval.'M');
    $endPhase = new DateTime ($start);
    $endPhase->add($phaseDuration);
    return $endPhase->format("H:i");
}

function getMultipleConsequentEndPhaseTime($start, $phases){
    $result = array();
    for($i = 0; $i < count($phases); $i++){
        $start = getEndPhaseTime($start, $phases[$i]);
        $result[] = $start;
    }
    return $result;
}

// Given start time and interval --> return the number of slots.
function getNumberOfSlots($start, $end){
    $interval = new DateInterval('PT15M');
    $count = 0;
    $next = new DateTime ($start);
    while($next->format("H:i") != $end){
        $next->add($interval);
        $count++;
    }
    return $count;
}

// return the prenotation of a booked cut of an employee. (-1 if empty).
function getSinglePrenotation($prenotations, $timeslot, $employee){
    foreach($prenotations as $booking)
        if($booking['start'] == $timeslot && $booking['hairdresser'] == $employee)
            return $booking;    
    return -1;
}

function getTimeslotsOfTheDay($daySchedule){
    $duration = 15;
    $cleanup = 0;
    $timeslots = array();

    if($daySchedule[0] != "00:00")
        $timeslots = timeslots($duration, $cleanup, $daySchedule[0], $daySchedule[1]);
        
    if($daySchedule[2] != "00:00"){
        $timeslots["End-Morning"] = "End-Morning";
        $timeslots = array_merge($timeslots, timeslots($duration, $cleanup, $daySchedule[2], $daySchedule[3]));
    }
    return $timeslots;
}

// return an array of timeslots.
function timeslots($duration, $cleanup, $start, $end){
    $start = new DateTime($start);
    $end = new DateTime($end);
    $interval = new DateInterval("PT".$duration."M");
    $cleanupInterval = new DateInterval("PT".$cleanup."M");
    $slots = array();

    for($intStart = $start; $intStart < $end; $intStart->add($interval)->add($cleanupInterval)){
        $endPeriod = clone $intStart;
        $endPeriod->add($interval);
        if($endPeriod>$end){
            break;
        }
        $slots[$intStart->format("H:i")] = array();        
    }
    return $slots;
}

function setTableHeaders($hairdressers, $str){
    $bookingTable = "<tr><th class='header'>Orario</th>";
    foreach($hairdressers as $hairdresser) 
        $bookingTable .= "<th class='header hairdresser-header' id='$str'>$hairdresser</th>";
    $bookingTable .= "<th class='header'>Orario</th></tr>";
    return $bookingTable;
}

function addBlankRow(){
    global $bookingTable, $hairdressers;
    $span = count($hairdressers) + 2;
    $bookingTable .= '<tr class="blank_row">';
    $bookingTable .= '<td colspan='.$span.'></td>';
    $bookingTable .= '</tr>';
    $bookingTable .= setTableHeaders($hairdressers, "PM");
}

function generateOptions($start, $end, $selected){
    $options = "";
    for( ; $start <= $end; $start += 15){
        if($start != $selected)
            $options .= "<option value='$start'>$start</option>";
        else 
            $options .= "<option value='$start' selected>$start</option>";
    }
    echo $options;
}

function setHairdresserNamesEditOptions($hairdressers){
    $options = "<option value='' selected disabled hidden>Seleziona parrucchiere</option>";
    foreach($hairdressers as $hairdresser){
        $options .= "<option value='$hairdresser'>$hairdresser</option>";
    }
    echo $options;
}

function getCurrentDayName($date){
    $timestampCurrentDay = strtotime($date);
    return date('l', $timestampCurrentDay);
}

//Return TRUE if the hairdresser bookings are still valid with the new schedule. False otherwhise.
function checkPrenotationsWithNewSchedule(&$msg, $newSchedule, $prenotations, $hairdresser){
    $delete = isArrayNull($newSchedule);
    foreach($prenotations as $booking){
        if($booking['hairdresser'] != $hairdresser)
            continue;
        $startTime = $booking['start'];
        $endTime = $booking['end'];
        if($delete || !checkOpeningTime($startTime, $endTime, $newSchedule)){
            $msg = "<div class='alert alert-danger'>Modifica non effettuata: Conflitti rilevati tra le prenotazioni ed il nuovo orario.</div>";
            return false;     
        }
    }
    return true;
}

function isHairdresserEditable($employeePrenotations, $schedule, $AM){
    for( $i = 0; $i < count($employeePrenotations); $i++){
        $start = $employeePrenotations[$i][0];
        $end = $employeePrenotations[$i][1];
        if($AM){
            if($start <= $schedule[2]){ //AM prenotation
                echo $schedule[1]."\n";
                if($schedule[1] < $start)
                    return false;
            }
        } else {
            if($start >= $schedule[2]){ //PM prenotation
                echo $schedule[1]."\n";
                if($schedule[3] < $start)
                    return false;
            }
        }
    }
    return true;
}

function isArrayNull($array) {
    for ($i = 0; $i < count ($array); $i ++)
      if ($array[$i] != null)
        return false;
    return true;
}

function getFreestAvailableEmployee($prenotationsOfTheDay, $startTime3, $endTime3, $dailyHairdressers){
    $countHairdresserTimeslots = array();
    foreach($dailyHairdressers as $employee=>$employeeSchedule)
        if(checkOpeningTime($startTime3, $endTime3, $employeeSchedule))
            $countHairdresserTimeslots[$employee] = 0;

    foreach($prenotationsOfTheDay as $prenotation){
        $employee = $prenotation['hairdresser'];
        if(!array_key_exists($employee, $countHairdresserTimeslots))
            continue;
        $start = $prenotation['start'];
        $end = $prenotation['end'];
        
        if(checkOverlappingPeriods($start, $end, $startTime3, $endTime3))
            unset($countHairdresserTimeslots[$employee]);  
        else    
            $countHairdresserTimeslots[$employee] += getNumberOfSlots($start, $end);
    }

    if(array_key_exists("Extra", $countHairdresserTimeslots)){
        $extra = true;
        unset($countHairdresserTimeslots["Extra"]);  
    } else 
        $extra = false;

    if(count($countHairdresserTimeslots) > 0)
        return current(array_keys($countHairdresserTimeslots, min($countHairdresserTimeslots)));

    if($extra)
        return "Extra";

    return false;
}

function getPostInputIfExists($inputName){
    if(isset($_POST[$inputName]))
        return $_POST[$inputName];
    else 
        return false;
}

function getPhaseIntValues(){
    $phase1 = intval($_POST['phase1']);
    $phase2 = intval($_POST['phase2']);
    $phase3 = intval($_POST['phase3']);
    return array($phase1, $phase2, $phase3);
}

function setEmployeeSchedule($row, &$dailyHairdressers, $hairdresser){
    $Am = "Mattina";
    $Pm = "Pomeriggio";
    setEmployeeScheduleAMPM($row, $dailyHairdressers, $hairdresser, $Am);
    setEmployeeScheduleAMPM($row, $dailyHairdressers, $hairdresser, $Pm);
}

function setEmployeeScheduleAMPM($row, &$dailyHairdressers, $hairdresser, $AmPm){
    $start = ($AmPm == "Mattina")? 0 : 2;
    $end = ($AmPm == "Mattina")? 1 : 3;

    if(!is_null($row['turno'.$AmPm])){
        $dailyHairdressers[$hairdresser][$start] = $row['turno'.$AmPm];
        $dailyHairdressers[$hairdresser][$end] = $row['fineTurno'.$AmPm];
    } else {
        $dailyHairdressers[$hairdresser][$start] = "00:00";
        $dailyHairdressers[$hairdresser][$end] = "00:00";
    }
}

function getBookingInformation($prenotationSlot){
    $client = $prenotationSlot['clientName'];
    $cut = $prenotationSlot['cut'];
    $end = $prenotationSlot['end'];
    $start = $prenotationSlot['start'];
    return array($client, $cut, $end, $start);
}

function setScheduleValuesOption($start, $end, $selected){
    $options = "";
    $options .= "<option value='Nessuno'>Nessuno</option>";

    $duration = 15;
    $cleanup = 0;
    $timeslots = timeslots($duration, $cleanup, $start, $end);
    $timeslots = array_keys($timeslots);
    if(end($timeslots) != "00:00")
        $timeslots[] = getEndPhaseTime(end($timeslots), $duration);
    
    foreach($timeslots as $ts){
        if($ts == $selected)
        $options .= "<option value='$ts' selected>$ts</option>";
        else
            $options .= "<option value='$ts'>$ts</option>";
    }

    echo $options;
}

function checkFormTimeValues(&$newSchedule, &$msg){
    $AM = checkTimeValues($newSchedule, "AM");
    $PM = checkTimeValues($newSchedule, "PM");
    if(($newSchedule[0] != "Nessuno" && $newSchedule[2] != "Nessuno" &&
         ($newSchedule[0] >= $newSchedule[2] || $newSchedule[1] > $newSchedule[2] )) || !$AM || !$PM ){
        $msg = "<div class='alert alert-danger'>Modifica non effettuata: Orari non validi.</div>";
        return false;
    }
    return true;
}

//return TRUE if time values are valid. FALSE otherwhise.
function checkTimeValues(&$newSchedule, $AMPM){
    $index = $AMPM == "AM"? 0 : 2;
    if($newSchedule[$index] == NULL)
        return true;
    if($newSchedule[$index] == "Nessuno"){
        $newSchedule[$index] = NULL;
        $newSchedule[($index + 1)] = NULL;
    }else if ( $newSchedule[$index] >= $newSchedule[($index + 1)] )
        return false;
    return true;
}

function setInputFormTimeValues($start, $end, $show){
    if($show)
        return array($start, $end);
    else 
        return array(NULL, NULL);
}

?>