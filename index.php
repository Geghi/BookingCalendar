<?php
    include './php/calendarFunctions.php';
    include './php/calendarMain.php';

    $error = false;

    if(!isset($_SESSION))  
        session_start(); 

    if(!isset($_SESSION["username"]))
        return header("location: ./login.php"); 

    if(isset($_GET["msg"]))
        $error = $_GET["msg"];
?>
<!doctype html>
<html lang="it">
    <head>
        <title>Calendario</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
        <link rel="stylesheet" href="./css/index.css">
    </head>

    <body>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <?php
                        $dateComponents = getdate();
                        if(isset($_GET['month']) && isset($_GET['year'])){
                            $month = $_GET['month']; 			     
                            $year = $_GET['year'];
                        }else{
                            $month = $dateComponents['mon']; 			     
                            $year = $dateComponents['year'];
                        }
                        echo build_calendar($month,$year);
                    ?>
                    <div class="container">
                        <div class="row">
                            <div class="col-md-12">
                                <?php 
                                    if($error == -1)
                                        $msg = "<div class='alert alert-danger'>Rimozione non riuscita.</div>";
                                    if($error == 1)
                                        $msg = "<div class='alert alert-danger'>Selezionare un parrucchiere valido.</div>";
                                    if($error == 2)
                                        $msg = "<div class='alert alert-danger'>Operazione non riuscita.</div>";
                                    if($error == 3)
                                        $msg = "<div class='alert alert-danger'>Parrucchiere già esistente.</div>";    
                                    if($error == "success")
                                        $msg = "<div class='alert alert-success'>Operazione effettuata con successo.</div>";
                                    echo isset($msg)? $msg : "";   
                                ?>  
                            </div> 
                        </div>
                    </div>

                    <form action="./php/removeOldPrenotations.php" method="post"> 
                        <center><button id="remove" class="btn btn-primary removeOldPrenotations" type="submit" name="removePrenotations" onclick="return confirm('Sicuro di voler eliminare le vecchie prenotazioni?');">Rimuovi prenotazioni precedenti</button></center>
                    </form>
                    <br>

                    <center>
                        <a class='btn btn-primary' href='./addEmployeeForm.php'>Aggiungi dipendente</a>
                        <a class='btn btn-primary' href='./removeEmployeeForm.php'>Rimuovi dipendente</a>
                    </center><br> 
                    <center><a class='btn btn-primary' href='./php/logoutAction.php'>Log Out</a></center>
                </div>
            </div>
        </div>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
        <script type="text/javascript" src="./js/index.js"></script>
    </body>
</html>
