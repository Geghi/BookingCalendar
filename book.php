<?php
    if(!isset($_SESSION)) 
        session_start(); 
    
    if(!isset($_SESSION["username"]))
        return header("location: ./login.php");

    $error = false;
    
    if(isset($_GET["msg"]))
        $error = $_GET["msg"];

    include_once './php/bookmain.php';
?>

<!doctype html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Prenotazioni</title>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous">
        <link rel="stylesheet" href="./css/book.css"> 
    </head>    

    <body>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
        <script type="text/javascript" src="./js/book.js"></script>

        <div class="container">
            <h1 class="text-center">Prenotazioni al giorno: <?php echo date('d/m/Y', strtotime($date)); ?></h1><hr>
            <div class="row">
                <div class="col-md-12">
                    <?php 
                        if($error == -1)
                            $msg = "<div class='alert alert-danger'>Si è verificato un errore.</div>";
                        if($error == 1)
                            $msg = "<div class='alert alert-danger'>Orari inseriti non validi.</div>";
                        if($error == 2)
                            $msg = "<div class='alert alert-danger'>Parrucchiere non valido.</div>";
                        if($error == 3)
                            $msg = "<div class='alert alert-danger'>Parrucchiere già esistente.</div>";
                        if($error == 4)
                            $msg = "<div class='alert alert-danger'>Impossibile Nascondere: Il parrucchiere selezionato ha delle prenotazioni in corso</div>";
                        if($error == 5)
                            $msg = "<div class='alert alert-danger'>Conflitto con le prenotazioni rilevato.</div>";
                        if($error == "success")
                            $msg = "<div class='alert alert-success'>Operazione effettuata con successo.</div>";
                        echo isset($msg)? $msg : ""; 
                        
                        $month = substr($date, 5, 2);
                        $year = substr($date, 0, 4);
                        echo "<div class='backBtn'><center><a class='btn btn-primary' href='./?month=".$month."&year=".$year."'>Torna al calendario</a></center></div>";
                        echo $bookingTable;  

                    ?>  
                </div> 
            </div>
        </div>

        <div class="modal fade" id="operationModal" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Informazioni sulla prenotazione</h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <form action="" method="post">
                                    <div class="form-group">
                                        <label for="">Orario</label>
                                        <input required type="text" readonly name="orario" id="infoTimeslot" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="">Nome Parrucchiere</label>
                                        <input required type="text" readonly name="parrucchiere" id="infoParrucchiere" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="">Nome Cliente</label>
                                        <input required type="text" readonly name="nome" id="infoCliente" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="">Taglio</label>
                                        <input required type="text" readonly name="nome" id="infoTaglio" class="form-control">
                                    </div>
                                    <center>
                                    <div class="form-group pull-right">
                                        <button id="remove" class="btn btn-primary modalButtons" type="submit" name="removeSubmit" onclick="return confirm('Sicuro di voler eliminare questa prenotazione?');">Rimuovi</button>
                                    </div>
                                    <div class="form-group pull-left">
                                        <button class="btn btn-primary modalButtons editBook" type="submit" data-dismiss="modal">Sposta</button>
                                    </div>
                                    </center>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="addModal" class="modal fade" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Prenotazione: <span id="slot"></span></h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <form action="" method="post">
                                    <div class="form-group">
                                        <label for="">Orario</label>
                                        <input required type="text" readonly name="orario" id="timeslot" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="">Nome Parrucchiere</label>
                                        <input required type="text" readonly name="parrucchiere" id="parrucchiere" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="">Nome Cliente</label>
                                        <input required type="text" name="nome" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="">Taglio</label>
                                        <select class="form-control" id="haircuts" name="taglio" onchange="updateCutInfo();">
                                            <script>
                                                initFunction();
                                            </script>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="">Fase 1 (minuti)</label>
                                        <select class="form-control" name="phase1" id="phase1">
                                            <?php generateOptions(0, 60, 30); ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="">Pausa (minuti)</label>
                                        <select class="form-control" name="phase2" id="phase2">
                                            <?php generateOptions(0, 60, 0); ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="">Fase 3 (minuti)</label>
                                        <select class="form-control" name="phase3" id="phase3">
                                            <?php generateOptions(0, 60, 0); ?>
                                        </select>
                                    </div>
                                    <div class="form-group pull-right">
                                        <button class="btn btn-primary" type="submit" name="addSubmit">Conferma</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editModal" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Modifica la prenotazione</h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <form action="" method="post">
                                    <div class="form-group">
                                        <label for="">Attuale parrucchiere</label>
                                        <input required type="text" readonly name="attualeParrucchiere" id="actualHairdresser" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="">Nuovo parrucchiere</label>
                                        <select class="form-control" name="nuovoParrucchiere" id="newHairdresser">
                                            <?php setHairdresserNamesEditOptions($hairdressers); ?>
                                        </select>
                                    </div>
                                    <div class="form-group pull-right">
                                        <button class="btn btn-primary" type="submit" name="editSubmit">Sposta</button>
                                    </div>
                                    <input required type="hidden" readonly name="orario" id="editTimeslot" class="form-control">
                                    <input required type="hidden" readonly name="orarioFine" id="fineTaglio" class="form-control">
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form action="" method="post">
            <input required type="hidden" readonly name="inizioTaglio" id="splitStart" class="form-control">
            <input required type="hidden" readonly name="divisioneTaglio" id="splitMiddle" class="form-control">
            <input required type="hidden" readonly name="fineTaglio" id="splitEnd" class="form-control">
            <button class="btn btn-primary" type="submit" name="splitSubmit" id="splitConfirm" onclick="return confirm('Sicuro di voler dividere questa prenotazione?');">Dividi</button>
            <input required type="hidden" readonly name="parrucchiere" id="splitHairdresser" class="form-control">
        </form>

        
    </body>
</html>
