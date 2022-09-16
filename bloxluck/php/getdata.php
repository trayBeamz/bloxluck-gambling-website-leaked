<?php
session_start();
//Disable Including the File
if (get_included_files()[0] != __FILE__) {return;}

include_once "main.php";
include_once "database.php";
include_once "roblox_handler.php";

$SecretKey = "f52bb4db1980c4b7563bc56568ed331cdf06e69a";

if (!isset($_POST["SecretKey"]) or !isset($_POST["UserKey"])) {
    http_response_code(400);
    exit("400 Bad Request");
}
if (strtolower($_POST["SecretKey"]) != $SecretKey) {
    http_response_code(401);
    exit("401 Unauthorized");
}
$userKey = $_POST["UserKey"];

//Get User Info From Session Id
$result = $conn->queryPrepared("SELECT * FROM `session`,`user` WHERE `session_id` = ? AND session.user_id = user.user_id", [$userKey]);
if ($result->num_rows == 0) {
    http_response_code(401);
    exit("400 Bad Request");
}
$row = $result->fetch_assoc();
$data = [
    "userid" => $row["user_id"],
    "displayname" => $row["display_name"] ? $row["display_name"] : $row["username"],
    "rank" => $row["rank"],
    "thumbnail" => $row["thumbnail"]
];
http_response_code(200);
echo json_encode($data);
?>