<?php

function build_calendar($month, $year) { 
    $daysOfWeek = array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
    $giorniDellaSettimana = array('Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato','Domenica');
    $mesiAnno = array('Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno',
                    'Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre');

    // get timestamp --> FIRST DAY of the CURRENT MONTH.
    $dateString = strval($year). '-' .strval($month). '-01';
    $firstDayOfMonthTimestamp = strtotime($dateString);

    // Number of days in CURRENT MONTH.
    $daysInCurrentMonth = date('t',$firstDayOfMonthTimestamp);

    // Italian name of the CURRENT MONTH.
    $monthName = $mesiAnno[strval($month) - 1];

    // Index value of the FIRST DAY of the CURRENT MONTH.
    $dayOfWeekIndex = getdate($firstDayOfMonthTimestamp)['wday'];
    $dayOfWeekIndex = updateDayOfWeek($dayOfWeekIndex);
    
    $datetoday = date('Y-m-d');
    
    $previousMonthString = strval($year) .'-'. strval($month - 1) .'-01'; 
    $previousMonthTimestamp = strtotime($previousMonthString);
    if(strval($month) == 12)
        $nextMonthString = strval($year + 1) .'-01-01';
    else 
        $nextMonthString = strval($year) .'-'. strval($month + 1) .'-01';
    $nextMonthTimestamp = strtotime($nextMonthString);

    $calendar = "<table class='table table-bordered'>";
    $calendar .= "<center><h2>$monthName $year</h2>";
    $calendar .= "<a class='btn btn-xs btn-primary' href='?month=".date('m', $previousMonthTimestamp)."&year=".date('Y', $previousMonthTimestamp)."'>Mese Precedente</a> ";
    
    $calendar .= " <a class='btn btn-xs btn-primary' href='?month=".date('m')."&year=".date('Y')."'>Mese Attuale</a>";
    
    $calendar .= " <a class='btn btn-xs btn-primary' href='?month=".date('m', $nextMonthTimestamp)."&year=".date('Y', $nextMonthTimestamp)."'>Mese Successivo</a><br><br>";
    $calendar .= "<a onclick='showCurrentDay();' class='btn btn-xs btn-primary'>Visualizza Giorno Attuale</a></center><br>";
    $calendar .= "<tr>";

    // Create the calendar headers
    for($i = 0; $i < count($daysOfWeek); $i++) 
        $calendar .= "<th  class='header'>$giorniDellaSettimana[$i]</th>";
    
    // Initiate the day counter, starting with the 1st.
    $currentDay = 1;
    $calendar .= "</tr><tr>";

    //add blank cells in the table before the first day of that month.
    if ($dayOfWeekIndex > 0) 
        for($k=0; $k < $dayOfWeekIndex; $k++)
            $calendar .= "<td  class='empty'></td>"; 

    // add zero before the number of the month (eg. month 3 becomes 03)
    $month = str_pad($month, 2, "0", STR_PAD_LEFT);

    while ($currentDay <= $daysInCurrentMonth) {
        if ($dayOfWeekIndex == 7) {
            $dayOfWeekIndex = 0;
            $calendar .= "</tr><tr>";
        }
        
        $currentDayRel = str_pad($currentDay, 2, "0", STR_PAD_LEFT);
        $date = "$year-$month-$currentDayRel";
        
        $dayName = strtolower(date('l', strtotime($date)));
        $today = $date==date('Y-m-d')? "today" : "";

        if($date<date('Y-m-d'))
            $calendar.="<td><h4>$currentDay</h4> <button class='btn btn-danger btn-xs'>Non Prenotabile</button>";
        else if($dayOfWeekIndex == 0 || $dayOfWeekIndex == 6)
            $calendar.="<td class='$today'><h4>$currentDay</h4> <a id='$today' href='book.php?date=".$date."' class='btn btn-primary btn-xs'>Festivo</a>";
         else 
            $calendar.="<td class='$today'><h4>$currentDay</h4> <a id='$today' href='book.php?date=".$date."' class='btn btn-success btn-xs'>Visualizza</a>";
        
        $calendar .="</td>";

        // Increment counters
        $currentDay++;
        $dayOfWeekIndex++;
    }
     
    // Complete the row of the last week in month, if necessary
    if ($dayOfWeekIndex != 7) { 
        $remainingDays = 7 - $dayOfWeekIndex;
        for($l=0; $l < $remainingDays; $l++)
            $calendar .= "<td class='empty'></td>"; 
    }
    
    $calendar .= "</tr>";
    $calendar .= "</table>";

    echo $calendar;
}

function updateDayOfWeek($dayOfWeekIndex){
    if($dayOfWeekIndex == 0)
        $dayOfWeekIndex = 6;
    else 
        $dayOfWeekIndex = $dayOfWeekIndex - 1;
    return $dayOfWeekIndex;
}
    
?>