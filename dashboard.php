<?php
// if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
//     $location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
//     header('HTTP/1.1 301 Moved Permanently');
//     header('Location: ' . $location);
//     exit;
// }

ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('connect.php');


$curDate = date("Y-m-d H:i:s");

if($user_ok==false){
    header("Location: logout.php");
    exit();
} 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Protec Dashboard</title>
    <meta name="ms-edge-image-actions" content="none">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">

<link rel="stylesheet" href="master_job_list.css">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="header-left">
                <img src="logo.png" alt="Protec Logo" class="dashboard-logo">
                <h1>Project Management</h1>
            </div>
            <button id="mobile-menu-toggle" class="mobile-menu-button">
                <i class="fas fa-bars"></i>
            </button>
        </header>

        <!-- Navigation -->
        <nav class="dashboard-nav">
            <div id="tab-container">
                <!-- Tabs will be loaded here dynamically -->
            </div>
        </nav>

        <!-- Main Content -->
        <main class="dashboard-content">
            <div id="tab-content">
                <!-- Tab content will be loaded here -->
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    Loading...
                </div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="dashboard.js?r=<?php echo generateRandomString();?>"></script>
    <script src="contractors.js?r=<?php echo generateRandomString();?>"></script>
    <script src="master_job_list.js?r=<?php echo generateRandomString();?>"></script>
</body>
</html>