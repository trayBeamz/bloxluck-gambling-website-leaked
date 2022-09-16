<?php
session_start();
//Disable Including the File
if (get_included_files()[0] != __FILE__) {return;}
include_once "session_handler.php";

if (!$session) {
    exit("You are Already Logged Out!");
}
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    exit("400 Bad Request");
}
Logout();
echo "success";
?>