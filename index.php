<?php
// if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
//     $location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
//     header('HTTP/1.1 301 Moved Permanently');
//     header('Location: ' . $location);
//     exit;
// }

ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once('connect.php'); // Inclu// ini_set('display_errors', 1);


$curDate = date("Y-m-d H:i:s");



if($user_ok==false){


} else{
    header("Location: dashboard.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Protec</title>
    <meta name="ms-edge-image-actions" content="none">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="index.css">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <img src="logo.png" alt="Protec Logo">
        </div>
        <div class="input-group">
            <input type="text" id="username" placeholder="Username" autocomplete="username">
        </div>
        <div class="input-group">
            <input type="password" id="password" placeholder="Password" autocomplete="current-password">
        </div>
        <button id="login-button">Log In</button>
        <div id="login-error"></div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script src="index.js?r=<?php echo generateRandomString();?>"></script>
</body>
</html>