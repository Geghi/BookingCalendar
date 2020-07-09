<?php 

if(!isset($_SESSION)) 
    session_start(); 

if(!isset($_POST['submit']))
    return header("location: ../login.php?msg=submitError");

include_once './connect.php';
include_once './bookDbManager.php';

$username = mysqli_real_escape_string($mysqli, $_POST['username']);
$password = mysqli_real_escape_string($mysqli, $_POST['password']);

/*
//to hash a new password and add the user to the database
    $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
    insertNewUser($mysqli, $username, $hashedPwd);
    return;
*/

$query="SELECT * FROM utenti WHERE Username= '$username'";
$result = mysqli_query($mysqli, $query);    
$resultCheck = mysqli_num_rows($result);
    
if($resultCheck < 1)
    return header("location: ../login.php?msg=error");

if($row = mysqli_fetch_assoc($result)){
    //Password De-hashing 
    $hashedPwdCheck = password_verify($password, $row['Password']);
    
    if($hashedPwdCheck == false)
        return header("location: ../login.php?msg=error");
    else if($hashedPwdCheck == true){
        $_SESSION["username"] = $row['Username'];
        return header("location: ../index.php?msg=loggedIn");
    }
}
     
?>