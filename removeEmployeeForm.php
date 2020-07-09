<?php
    include_once './php/bookDbManager.php';
    include_once './php/connect.php';
    include_once './php/bookFunctions.php';

    if(!isset($_SESSION))  
        session_start(); 

    if(!isset($_SESSION["username"]))
        return header("location: ./login.php"); 
?>

<!doctype html>
<html lang="it">
    <head>
        <title>Calendario</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
        <link rel="stylesheet" href="../css/index.css">
    </head>

    <body>
        <div class="container">
        <h1 class="text-center">Rimuovi Parrucchiere</h1><hr>
            <div class="row">
                <div class="col-md-12">
                    <form action="./php/employeeUtil/removeEmployee.php" method="post">
                    <div class="form-group row">
                    <center><label for="hairdresserInput" class="col-sm-2 col-form-label">Parrucchiere</label></center>
                    <select class="form-control" name="parrucchiere">
                        <?php 
                            $hairdressers = getHairdressers($mysqli);                             
                            setHairdresserNamesEditOptions($hairdressers);
                        ?>
                    </select>
                    <br>
                    <center><button type="submit" class="btn btn-primary">Rimuovi</button></center>
                    </form>
                </div>
            </div>
        </div>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
        <script type="text/javascript" src="../js/index.js"></script>
    </body>
</html>