<?php 

function prepared_query($mysqli, $sql, $params, $types = ""){
    $types = $types ?: str_repeat("s", count($params));
    $stmt = $mysqli->prepare($sql);
    if(count($params) > 0)
        $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt;
}


/* --------------- START READ OPERATIONS --------------- */

function getHairdressers($mysqli){
    $shopAssistants = array();
    $stmt = prepared_query($mysqli, "SELECT DISTINCT nome FROM addetto", []);
    $result = $stmt->get_result();
    if($result->num_rows>0)
        while($row = $result->fetch_assoc())
            $shopAssistants[] = $row['nome'];                
    $stmt->close();
    return $shopAssistants;
}

function getHairdresserId($mysqli, $hairdresser){
    $stmt = prepared_query($mysqli, "SELECT id FROM addetto WHERE nome = ?;", [$hairdresser]);
    $id = NULL;
    $result = $stmt->get_result();
    if($result->num_rows>0){
        $row = $result->fetch_assoc();
        $id = $row['id'];
    }
    $stmt->close();
    return $id;
}

function getHairdresser($mysqli, $hairdresserId){
    $stmt = prepared_query($mysqli, "SELECT nome FROM addetto WHERE id = ?;", [$hairdresserId]);
    $name = NULL;
    $result = $stmt->get_result();
    if($result->num_rows>0){
        $row = $result->fetch_assoc();
        $name = $row['nome'];
    }
    $stmt->close();
    return $name;
}

function getScheduleForCurrentDay($selectedDay, $mysqli){
    for($i = 0; $i < 4; $i ++)
        $schedule[$i] = "00:00";

    $stmt = prepared_query($mysqli, "SELECT * FROM orario WHERE Giorno = ?", [$selectedDay]);
    $result = $stmt->get_result();
    if($result->num_rows>0)
        while($row = $result->fetch_assoc()){
            if($row['inizioMattina'] == NULL) 
                break;
            $schedule[0] = $row['inizioMattina'];
            $schedule[1] = $row['fineMattina'];
            if($row['inizioPomeriggio'] != NULL){
                $schedule[2] = $row['inizioPomeriggio'];
                $schedule[3] = $row['finePomeriggio'];
            }
        }
    $stmt->close();
    return $schedule;
}

function getCompleteSchedule($mysqli){
    $schedule = array();
    $stmt = prepared_query($mysqli, "SELECT * FROM orario", []);
    $result = $stmt->get_result();
    if($result->num_rows>0)
        while($row = $result->fetch_assoc()){
            $id = $row['id'];
            $schedule[$id] = array();
            $schedule[$id][0] = $row['inizioMattina'];
            $schedule[$id][1] = $row['fineMattina'];
            $schedule[$id][2] = $row['inizioPomeriggio'];
            $schedule[$id][3] = $row['finePomeriggio'];
        }
    $stmt->close();
    return $schedule;
}

function getEmployeesScheduleForCurrentDay($selectedDay, $date, $mysqli){
    $dailyHairdressers = array();
    $hairdressers = array();
    $stmt = prepared_query($mysqli, "SELECT *
                                    FROM orario O INNER JOIN lavora L1 ON L1.idOrario = O.id INNER JOIN addetto A ON A.id = L1.idAddetto
                                    WHERE Giorno = ? AND (L1.data = ? OR L1.data IS NULL)
                                        AND NOT EXISTS (
                                                        SELECT *
                                                        FROM lavora L2
                                                        WHERE L2.id != L1.id AND L2.idAddetto = L1.idAddetto 
                                                            AND L2.idOrario = L1.idOrario 
                                                            AND L2.data = ?);", [$selectedDay, $date, $date] );
    $result = $stmt->get_result();
    if($result->num_rows>0)
        while($row = $result->fetch_assoc()){
            if((is_null($row['data']) && is_null($row['turnoMattina']) && is_null($row['turnoPomeriggio'])) || $row["Mostra"] == 0)
                    continue;
            $hairdresser = $row['nome'];
            $hairdressers[] = $hairdresser;
            setEmployeeSchedule($row, $dailyHairdressers, $hairdresser);
        }
    $stmt->close();
    return array( $dailyHairdressers, $hairdressers);
}

function getEmployeeSchedule($selectedDay, $date, $hairdresserId, $mysqli){
    $stmt = prepared_query($mysqli, "SELECT *
                                    FROM orario O INNER JOIN lavora L1 ON L1.idOrario = O.id INNER JOIN addetto A ON A.id = L1.idAddetto
                                    WHERE Giorno = ? AND (L1.data = ? OR L1.data IS NULL) AND A.id = ?
                                        AND NOT EXISTS (
                                                        SELECT *
                                                        FROM lavora L2
                                                        WHERE L2.id <> L1.id AND L2.idAddetto = L1.idAddetto 
                                                            AND L2.idOrario = L1.idOrario 
                                                            AND L2.data = ?);", [$selectedDay, $date, $hairdresserId, $date] );
    $result = $stmt->get_result();
    if($result->num_rows>0)
        while($row = $result->fetch_assoc()){
            $schedule[0] = $row['turnoMattina'];
            $schedule[1] = $row['fineTurnoMattina'];
            $schedule[2] = $row['turnoPomeriggio'];
            $schedule[3] = $row['fineTurnoPomeriggio'];
        }
    $stmt->close();
    return $schedule;
}

function getPrenotationsOfTheDay($mysqli, $date){
    $stmt = prepared_query($mysqli, "SELECT P.orario, P.orarioFine, P.taglio, A.nome, P.idCliente, C.nome AS nomeCliente
                                     FROM prenotazioni P INNER JOIN cliente C ON C.id = P.idCliente INNER JOIN addetto A ON A.id = P.idParrucchiere
                                    WHERE P.data = ?;", [$date]);
    $prenotationsOfTheDay = array();
    $result = $stmt->get_result();
    if($result->num_rows>0){
        while($row = $result->fetch_assoc()){
            $prenotationsOfTheDay[] = array(
                'start' => $row['orario'], 
                'end' => $row['orarioFine'],
                'cut' => $row['taglio'],
                'hairdresser' => $row['nome'],
                'client' => $row['idCliente'],
                'name' => $row['nomeCliente']
            );
        }
    }
    $stmt->close();
    return $prenotationsOfTheDay;
}

function getClientId($date ,$time, $hairdresser, $mysqli){
    $stmt = prepared_query($mysqli, "SELECT idCliente FROM prenotazioni P INNER JOIN addetto A ON A.id = P.idParrucchiere 
                                    WHERE P.data = ? AND P.orario = ? AND A.nome = ?;", [$date, $time, $hairdresser]);
    
    $idClient = NULL;
    $result = $stmt->get_result();
    if($result->num_rows>0){
        $row = $result->fetch_assoc();
        $idClient = $row['idCliente'];
    }
    $stmt->close();
    return $idClient;
}

function getScheduleId($day, $mysqli){
    $stmt = prepared_query($mysqli, "SELECT id FROM orario WHERE Giorno = ?;", [$day]);
    
    $dayId = NULL;
    $result = $stmt->get_result();
    if($result->num_rows>0){
        $row = $result->fetch_assoc();
        $dayId = $row['id'];
    }
    $stmt->close();
    return $dayId;
}

function getClientAndCut($startTime, $date, $hairdresser, $mysqli){
    $stmt = prepared_query($mysqli, "SELECT P.idCliente, P.taglio FROM prenotazioni P INNER JOIN addetto A ON A.id = P.idParrucchiere
                                     WHERE P.orario = ? AND data = ? AND A.nome = ?;", [$startTime, $date, $hairdresser]);
    $result = $stmt->get_result();
    if($result->num_rows>0){
        $row = $result->fetch_assoc();
        $cut = $row['taglio'];
        $clientId = $row['idCliente'];
    }
    $stmt->close();
    return array($clientId, $cut);
}

//return TRUE if another day schedule is present. FALSE otherwhise. 
function isEmployeeDaySchedulePresent($hairdresserId, $date, $mysqli){
    $stmt = prepared_query($mysqli, "SELECT * FROM lavora WHERE idAddetto = ? AND data = ?;", [$hairdresserId, $date]);
    $result = $stmt->get_result();
    $rows = $result->num_rows > 0 ? true : false;
    $stmt->close();
    return $rows;
}

function getEmployeeDaySchedule($hairdresserId, $date, $mysqli){
    $stmt = prepared_query($mysqli, "SELECT * FROM lavora WHERE idAddetto = ? AND data = ?;", [$hairdresserId, $date]);
    $result = $stmt->get_result();
    if($result->num_rows>0){
        $row = $result->fetch_assoc();
        $startAM = $row['turnoMattina'];
        $endAM = $row['fineTurnoMattina'];
        $startPM = $row['turnoPomeriggio'];
        $endPM = $row['fineTurnoPomeriggio'];
    } else
        return null;
    $stmt->close();
    return array($startAM, $endAM, $startPM, $endPM);
}

function getGeneralEmployeeSchedule($hairdresserId, $scheduleId, $mysqli){
    $stmt = prepared_query($mysqli, "SELECT * FROM lavora WHERE idAddetto = ? AND idOrario = ? AND data IS NULL;", [$hairdresserId, $scheduleId]);
    $result = $stmt->get_result();
    if($result->num_rows>0){
        $row = $result->fetch_assoc();
        $startAM = $row['turnoMattina'];
        $endAM = $row['fineTurnoMattina'];
        $startPM = $row['turnoPomeriggio'];
        $endPM = $row['fineTurnoPomeriggio'];
    } else
        return null;
    $stmt->close();
    return array($startAM, $endAM, $startPM, $endPM);
}

function checkAllHairdresserPrenotations($mysqli, $hairdresser, $newSchedule, $date, &$msg){ 
    $delete = isArrayNull($newSchedule);
    $stmt = prepared_query($mysqli, "SELECT P.orario, P.orarioFine FROM prenotazioni P INNER JOIN addetto A ON A.id = P.idParrucchiere
                                    WHERE P.data >= ? AND DAYOFWEEK(?) = DAYOFWEEK(P.data) 
                                    AND A.nome = ?;", [$date, $date, $hairdresser]);
    $result = $stmt->get_result();

    while($row = $result->fetch_assoc()){
        $start = $row['orario'];
        $end = $row['orarioFine'];
        if($delete || !checkOpeningTime($start, $end, $newSchedule)){
            $msg = "<div class='alert alert-danger'>Modifica non effettuata: Conflitti rilevati tra le prenotazioni del parrucchiere ed il nuovo orario.</div>";
            return false;
        }
    }
    
    $stmt->close();
    return true;
}

function hairdresserIsDeletable($mysqli, $hairdresserId){
    $stmt = prepared_query($mysqli, "SELECT * FROM prenotazioni P
                                    WHERE P.data >= CURRENT_DATE() 
                                    AND P.idParrucchiere = ?;", [$hairdresserId]);
    $result = $stmt->get_result();
    if($result->num_rows>0)
        return false;
    return true;

}

function getHairdresserPrenotationsOfTheDay($mysqli, $hairdresserId, $date){
    $stmt = prepared_query($mysqli, "SELECT * FROM prenotazioni P
                                    WHERE P.data = ? 
                                    AND P.idParrucchiere = ?;", [$date, $hairdresserId]);
    $result = $stmt->get_result();
    $employeePrenotations = array();
    if($result->num_rows>0)
        while($row = $result->fetch_assoc())
            $employeePrenotations[] = array($row['orario'], $row['orarioFine']);
    else
        return null;

    return $employeePrenotations;

}

/* --------------- END READ OPERATIONS --------------- */


/* --------------- START INSERT OPERATIONS --------------- */

function addNewClient($name, $mysqli){
    $stmt = prepared_query($mysqli, "INSERT INTO cliente (nome) VALUES (?);", [$name]);
    $stmt->close();
}

function insertNewUser($mysqli, $username, $hashedPassword){
    $stmt = prepared_query($mysqli, "INSERT INTO utenti (Username, Password) VALUES (?, ?);", [$username, $hashedPassword]);
    $stmt->close();
}

function addSinglePrenotation($startTime, $endTime, $client, $hairdresser, $cut, $date, $mysqli){
    $hairdresserId = getHairdresserId($mysqli, $hairdresser);
    $stmt = prepared_query($mysqli, "INSERT INTO prenotazioni (orario, orarioFine, idCliente, taglio, data, idParrucchiere) 
                                    VALUES (?,?,?,?,?,?);", [$startTime, $endTime, $client, $cut, $date, $hairdresserId]);
    if($stmt->get_result())
        return true;
    else
        return false;
    $stmt->close();
}

function insertNewEmployeeSchedule($hairdresserId, $scheduleId, $date, $newSchedule, $showHide, $mysqli){
    $stmt = prepared_query($mysqli, "INSERT INTO lavora(idAddetto, idOrario, data, turnoMattina, fineTurnoMattina, turnoPomeriggio, fineTurnoPomeriggio, Mostra) 
                                    VALUES (?,?,?,?,?,?,?,?);", [$hairdresserId, $scheduleId, $date, $newSchedule[0], $newSchedule[1], $newSchedule[2], $newSchedule[3], $showHide]);
    $stmt->close();
}

function addEmployee($hairdresser, $mysqli){
    $stmt = prepared_query($mysqli, "INSERT INTO addetto(nome) VALUES (?);", [$hairdresser]);
    $hairdresserId = getHairdresserId($mysqli, $hairdresser);
    $weekSchedule = getCompleteSchedule($mysqli);

    for($i = 1 ; $i <= count($weekSchedule); $i++){
        $showHide = 1; 
        if($i == 1 || $i == 7)
            $showHide = 0;                   
        $stmt = prepared_query($mysqli, "INSERT INTO lavora(idAddetto, idOrario, data, turnoMattina, fineTurnoMattina, turnoPomeriggio, fineTurnoPomeriggio, Mostra) 
                                 VALUES (?,?,NULL,?,?,?,?,?);", [$hairdresserId, $i, $weekSchedule[$i][0], $weekSchedule[$i][1], $weekSchedule[$i][2], $weekSchedule[$i][3], $showHide]);
    }
    $stmt->close();
}

/* --------------- END INSERT OPERATIONS --------------- */


/* --------------- START UPDATE OPERATIONS --------------- */

function movePrenotation($newHairdresser, $oldHairdresser, $startTime, $date, $mysqli){
    $newHairdresserId = getHairdresserId($mysqli, $newHairdresser);
    $oldHairdresserId = getHairdresserId($mysqli, $oldHairdresser);
    $stmt = prepared_query($mysqli, "UPDATE prenotazioni SET idParrucchiere = ? WHERE orario = ? AND data = ? AND idParrucchiere = ?;", [$newHairdresserId, $startTime, $date, $oldHairdresserId]);
    $stmt->close();
    header('Refresh: 0');
}

function updatePrenotationEndTime($newEndTime, $startTime, $date, $hairdresser, $mysqli){
    $hairdresserId = getHairdresserId($mysqli, $hairdresser);
    $stmt = prepared_query($mysqli, "UPDATE prenotazioni SET orarioFine = ? WHERE orario = ? AND data = ? AND idParrucchiere = ?;", [$newEndTime, $startTime, $date, $hairdresserId]);
    $stmt->close();
}

function updateHairdresserSchedule($hairdresserId, $newSchedule,  $showAM, $showPM, $date, $mysqli){
    if($showAM && $showPM)
        $stmt = prepared_query($mysqli, "UPDATE lavora 
                                        SET turnoMattina = ?, fineTurnoMattina = ?, turnoPomeriggio = ?, fineTurnoPomeriggio = ? 
                                        WHERE idAddetto = ? AND data = ?;",
                                        [$newSchedule[0], $newSchedule[1], $newSchedule[2], $newSchedule[3], $hairdresserId, $date]);
    else if ($showAM)
        $stmt = prepared_query($mysqli, "UPDATE lavora 
                                        SET turnoMattina = ?, fineTurnoMattina=?
                                        WHERE idAddetto = ? AND data = ?;",
                                        [$newSchedule[0], $newSchedule[1], $hairdresserId, $date]);
    else //showPM --> TRUE
        $stmt = prepared_query($mysqli, "UPDATE lavora 
                                        SET turnoPomeriggio = ?, fineTurnoPomeriggio = ? 
                                        WHERE idAddetto = ? AND data = ?;",
                                        [$newSchedule[2], $newSchedule[3], $hairdresserId, $date]);       
 
    $stmt->close(); 
    header('Refresh: 0');
}

function updateEverydayHairdresserSchedule($day, $hairdresserId, $newSchedule,  $showAM, $showPM, $mysqli){
    $scheduleId = getScheduleId($day, $mysqli);
    if($showAM && $showPM)
        $stmt = prepared_query($mysqli, "UPDATE lavora 
                                        SET turnoMattina = ?, fineTurnoMattina=?, turnoPomeriggio = ?, fineTurnoPomeriggio = ? 
                                        WHERE idAddetto = ? AND idOrario = ? AND data IS NULL;",
                                        [$newSchedule[0], $newSchedule[1], $newSchedule[2], $newSchedule[3], $hairdresserId, $scheduleId]);
    else if ($showAM)
        $stmt = prepared_query($mysqli, "UPDATE lavora 
                                        SET turnoMattina = ?, fineTurnoMattina=?
                                        WHERE idAddetto = ? AND idOrario = ? AND data IS NULL;",
                                        [$newSchedule[0], $newSchedule[1], $hairdresserId, $scheduleId]);
        
    else //showPM --> TRUE
        $stmt = prepared_query($mysqli, "UPDATE lavora 
                                        SET turnoPomeriggio = ?, fineTurnoPomeriggio = ? 
                                        WHERE idAddetto = ? AND idOrario = ? AND data IS NULL;",
                                        [$newSchedule[2], $newSchedule[3], $hairdresserId, $scheduleId]); 
    $stmt->close();
    header('Refresh: 0');
}

function updateShowHideEmployee($hairdresserId, $date, $mysqli){
    $stmt = prepared_query($mysqli, "SELECT Mostra FROM lavora WHERE idAddetto = ? AND data = ?;", [$hairdresserId, $date]);
    $result = $stmt->get_result();
    if($result->num_rows>0){
        $row = $result->fetch_assoc();
        $showHide = $row['Mostra'];
    }
    $showHide = 1 - $showHide;
    $stmt->close();
    $stmt = prepared_query($mysqli, "UPDATE lavora 
                                        SET Mostra = ?
                                        WHERE idAddetto = ? AND data = ?",
                                        [$showHide, $hairdresserId, $date]);
    $stmt->close();
}


/* --------------- END UPDATE OPERATIONS --------------- */


/* --------------- START DELETE OPERATIONS --------------- */

function removePrenotationAndRelatedClient($date, $clientId, $mysqli){
    $stmt = prepared_query($mysqli, "DELETE FROM prenotazioni WHERE data = ? AND idCliente = ?;", [$date, $clientId]);
    $stmt->close();

    $stmt = prepared_query($mysqli, "DELETE FROM cliente WHERE id = ?;", [$clientId]);
    $stmt->close();

    $mysqli->close();
    header('Refresh: 0');
}

function removeOldPrenotationsAndClients($mysqli){
    $stmt = prepared_query($mysqli, "DELETE FROM prenotazioni WHERE data < CURRENT_DATE;", []);
    $stmt->close();

    if ($mysqli -> connect_errno)
        return header("location: ./index.php?msg=-1");

    $stmt = prepared_query($mysqli, "DELETE FROM cliente WHERE id NOT IN (SELECT id FROM ( SELECT id from cliente C INNER JOIN prenotazioni P ON C.id = P.idCliente ) AS c );", []);
    $stmt->close();

    if ($mysqli -> connect_errno)
        header("location: ./index.php?msg=-1");

    $stmt = prepared_query($mysqli, "DELETE FROM lavora WHERE data IS NOT NULL AND data < CURRENT_DATE;", []);
    $stmt->close();

    if ($mysqli -> connect_errno)
        header("location: ./index.php?msg=-1");
    $mysqli->close();
}

function removeHairdresserDaySchedule($mysqli, $hairdresserId, $date){
    $stmt = prepared_query($mysqli, "DELETE FROM lavora WHERE idAddetto = ? AND data = ?;", [$hairdresserId, $date]);
    $stmt->close();
}

function removeEmployee($hairdresserId, $mysqli){
    if(!hairdresserIsDeletable($mysqli, $hairdresserId))
        return false;
    
    $stmt = prepared_query($mysqli, "DELETE FROM addetto WHERE id = ?;", [$hairdresserId]);
    $stmt->close();
    return true;
}

/* --------------- END DELETE OPERATIONS --------------- */


/* --------------- START COMBINED OPERATIONS --------------- */

function addCompletePrenotationInfo($startTime1, $endTime1, $startTime3, $endTime3, $firstHairdresser, $secondHairdresser, $name, $cut, $date, $mysqli){
    addNewClient($name, $mysqli);
    $clientId = $mysqli->insert_id;
    addSinglePrenotation($startTime1, $endTime1, $clientId, $firstHairdresser, $cut, $date, $mysqli);
    
    if($startTime3 != $endTime3)
        addSinglePrenotation($startTime3, $endTime3, $clientId, $secondHairdresser,  $cut, $date, $mysqli);
    $mysqli->close();
    header('Refresh: 0');
}

function addNewEmployeeSchedule($hairdresserId, $newSchedule, $date, $selectedDay, $mysqli){
    $scheduleId = getScheduleId($selectedDay, $mysqli);
    insertNewEmployeeSchedule($hairdresserId, $scheduleId, $date, $newSchedule, 1, $mysqli);
    header('Refresh: 0');
}

/* --------------- END COMBINED OPERATIONS --------------- */

?>