<?php
require_once('connect.php');

if (isset($_POST['login']) && isset($_POST['pass'])) {


    $attemptEmail = strtoupper(preg_replace('#[^a-z0-9]#i', '', $_POST['login']));
    $attemptPassword = $_POST['pass'];
    
    if (strlen($attemptEmail) > 50 || strlen($attemptEmail) < 2) {
        echo 'fail';
        exit();
    }
    
    $stmt = $db_connect->prepare("SELECT password, username FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $attemptEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $password_db = $row['password'];
        $username = $row['username'];
        
        if (password_verify($attemptPassword, $password_db)) {
            session_start();
            $token = bin2hex(random_bytes(32));
            $_SESSION['u'] = $username;
            $_SESSION['t'] = $token;
            setcookie("u", $username, strtotime( '+14 days' ), "/", "", true, true);
            setcookie("t", $token, strtotime( '+14 days' ), "/", "", true, true);

            $token_hash = password_hash($token, PASSWORD_DEFAULT);
            $ipaddress = $_SERVER['REMOTE_ADDR'];
            $curDate = date("Y-m-d H:i:s");
            
            $stmt = $db_connect->prepare("DELETE FROM active WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();

            $stmt = $db_connect->prepare("INSERT INTO active (username, sessionstring, ip, logindate) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $token_hash, $ipaddress, $curDate);
            $stmt->execute();

            echo 'success';
        } else {
            echo 'fail';
        }
    } else {
        echo 'fail';
    }
    
    $stmt->close();
}

$db_connect->close();