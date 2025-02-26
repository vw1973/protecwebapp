<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
// if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
//     $location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
//     header('HTTP/1.1 301 Moved Permanently');
//     header('Location: ' . $location);
//     exit;
// } 

require_once('connect.php');


$sql="DELETE FROM active WHERE username='$username'";
$query=mysqli_query($db_connect, $sql);


// Set Session data to an empty array
$_SESSION = array();
// Expire their cookie files
if(isset($_COOKIE["u"]) && isset($_COOKIE["t"])) {
	setcookie("u", '', strtotime( '-5 days' ), '/');
    setcookie("t", '', strtotime( '-5 days' ), '/');
}
// Destroy the session variables
if(isset($_SESSION)){
	session_destroy();
}

header("location: index.php");
exit();

?>