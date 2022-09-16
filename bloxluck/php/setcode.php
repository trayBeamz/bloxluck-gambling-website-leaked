<?php
//Disable Including the File
if (get_included_files()[0] != __FILE__) {
    return;
}

include_once "main.php";
include_once "database.php";

$GameId = "10366307400";

if (!isset($_POST["SecretKey"]) or !isset($_POST["UserId"]) or !isset($_POST["Code"])) {
    http_response_code(400);
    exit("400 Bad Request");
}
$SecretKey = strval($_POST["UserId"]).$GameId.strval($_POST["Code"]);
$SecretKey = hash("sha256", $SecretKey);
if (strtolower($_POST["SecretKey"]) != $SecretKey) {
    http_response_code(401);
    exit("401 Unauthorized");
}

if (str_word_count($_POST["Code"]) != 16) {
    exit(json_encode([
        "success" => false,
        "error" => "Code Must be 16 Words"
    ]));
}
$code = strtolower($_POST["Code"]);
$userId = strval($_POST["UserId"]);
$conn->queryPrepared("UPDATE `user` SET `description` = ? WHERE `user_id` = ?", [$code, $userId]);
exit(json_encode([
    "success" => true
]));
