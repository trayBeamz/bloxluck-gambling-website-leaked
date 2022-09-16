<?php
//Stop Direct Access to the File
//Works only in PHP 5.0 and Up
if (get_included_files()[0] == __FILE__) {
    http_response_code(403);
    die('Forbidden');
}

//Stop Including This File Twice
if (defined(strtoupper(basename(__FILE__, ".php")) . "_PHP")) {
    return True;
}
define(strtoupper(basename(__FILE__, ".php")) . "_PHP", True);

include_once "main.php";

$weburl = "https://$socketurl";
$SecretKey = "f52bb4db1980c4b7563bc56568ed331cdf06e69a";

//Send POST Data to web url
//Data will contain event name and event data
//Also send Header Secret Key to verify that the request is from the server
function sendUser($userId,$event, $data)
{
    global $SecretKey;
    global $weburl;
    $data = json_encode(["event" => $event, "data" => $data]);
    $ch = curl_init("$weburl/send/$userId");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'SecretKey: ' . $SecretKey
    ));
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result,true);
}

function sendAll($event, $data)
{
    sendUser("all",$event, $data);
}

function sendChatMessage($userId,$senderName,$message,$senderRank=0,$thumbnail=NULL,$senderId=0)
{
    if (strtoupper($senderName) == "SERVER") {
        $senderId = NULL;
        $thumbnail = NULL;
        $senderName = "SERVER";
        $senderRank = 3;
    }
    if (!$thumbnail) {
        $thumbnail = "https://tr.rbxcdn.com/c4265017c98559993061733b1125a23c/420/420/AvatarHeadshot/Png";
    }
    if (!$senderId) {
        $senderId = 0;
    }
    if (!$senderRank) {
        $senderRank = 0;
    }
    sendUser($userId,"chat message", ["userid"=>$senderId,"rank"=>$senderRank,"displayname"=>$senderName,"thumbnail"=>$thumbnail,"message"=>$message]);
}
?>