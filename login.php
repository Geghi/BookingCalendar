<?php
if(!isset($_SESSION))  
	session_start(); 

?>
<!doctype html>
<html lang="it">
	<head>
		<title>Calendario</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
		<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
		<link rel="stylesheet" href="./css/login.css">
	</head>

	<body>
		<div class="wrapper fadeInDown">
			<div id="formContent">
				<br><br>
				<form action="./php/loginAction.php" method="post">
					<input type="text" id="login" name="username" placeholder="username">
					<input type="password" id="password" name="password" placeholder="password">
					<input type="submit" name="submit" value="Log In">
				</form>
			</div>
		</div>
  </body>
</html>