<?php



$secrets = [

    "hostname" => 'localhost',

    "username" => 'root',

    "password" => '',

    "database" => 'protec'


];

// Establish MySQLi connection
$db_connect = new mysqli($secrets['hostname'], $secrets['username'], $secrets['password'], $secrets['database']);
$db_connect->set_charset("utf8mb4");

if ($db_connect->connect_errno) {
    echo "Failed to connect to MySQL: (" . $db_connect->connect_errno . ") " . $db_connect->connect_error;
}


if (getenv('HTTP_CLIENT_IP'))
    $ipaddress = getenv('HTTP_CLIENT_IP');
else if(getenv('HTTP_X_FORWARDED_FOR'))
    $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
else if(getenv('HTTP_X_FORWARDED'))
    $ipaddress = getenv('HTTP_X_FORWARDED');
else if(getenv('HTTP_FORWARDED_FOR'))
    $ipaddress = getenv('HTTP_FORWARDED_FOR');
else if(getenv('HTTP_FORWARDED'))
   $ipaddress = getenv('HTTP_FORWARDED');
else if(getenv('REMOTE_ADDR'))
    $ipaddress = getenv('REMOTE_ADDR');
else
    $ipaddress = 'UNKNOWN';


$user_ok = false;
$token = "";
$user_IP = $ipaddress;
$username='';

if(isset($_SESSION["u"]) && isset($_SESSION["t"])) {
    $clientID = preg_replace('#[^a-z0-9]#i', '', $_SESSION['u']);
    $token = preg_replace('#[^a-z0-9]#i', '', $_SESSION['t']);
    // Verify the user
    $user_ok = evalLoggedUser($db_connect,$clientID,$token);

    $username=$clientID;

} else if(isset($_COOKIE["u"]) && isset($_COOKIE["t"])){
    $_SESSION['u'] = preg_replace('#[^a-z0-9]#i', '', $_COOKIE['u']);
    $_SESSION['t'] = preg_replace('#[^a-z0-9]#i', '', $_COOKIE['t']);

    $clientID =  preg_replace('#[^a-z0-9]#i', '', $_SESSION['u']);;
    $token =  preg_replace('#[^a-z0-9]#i', '', $_SESSION['t']);
    // Verify the user
    $user_ok = evalLoggedUser($db_connect,$clientID,$token);
    $username=$clientID;
} 


function evalLoggedUser($conx,$u,$t){
    
    //Grab the session
    $sql = "SELECT sessionstring FROM active WHERE username='$u' LIMIT 1";
    $query = mysqli_query($conx, $sql);
    $numrows = mysqli_num_rows($query);
    if($numrows>0){
        $row=mysqli_fetch_row($query);
        $token_hashed=$row[0];
        if(password_verify($t,$token_hashed)==true)
        {
            return true;
        }
        else
        {
            return false;
        }
    } 

    else
    {
        return false;
    }
}



function generateRandomString() {
    $length = rand(25, 30);
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
