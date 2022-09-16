<?php
//Stop Direct Access to the File
//Works only in PHP 5.0 and Up
if (get_included_files()[0] == __FILE__) {http_response_code(403);die('Forbidden');}

//Stop Including This File Twice
if (defined(strtoupper(basename(__FILE__,".php"))."_PHP")) {return True;}
define(strtoupper(basename(__FILE__,".php"))."_PHP", True);

//include_once Database
include_once "database.php";

//Create Cookie Session
function getCookie()
{
    if (isset($_COOKIE['BLOXLUCSECURITY'])) {
        $session_id = $_COOKIE['BLOXLUCSECURITY'];
    } else {
        $session_id = uniqid("",true);
        setcookie('BLOXLUCSECURITY', $session_id, time() + (86400 * 30), "/",null,true,true);
    }
    return $session_id;
}

//Logout Function
function Logout()
{
    global $conn;
    //Destroy Session
    $conn->queryPrepared("DELETE FROM session WHERE session_id = ?", [getCookie()]);   
    //Destroy Cookie
    setcookie('BLOXLUCSECURITY', '', time() - 3600, "/");
}

//Check Session
function CheckSession()
{
    global $conn;
    $session_id = getCookie();
    $result = $conn->queryPrepared("SELECT * FROM session, user WHERE session_id = ? AND session.user_id = user.user_id", [$session_id]);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $conn->queryPrepared("UPDATE user SET last_activity = NOW() WHERE user_id = ?", [$row["user_id"]]);
        $conn->queryPrepared("UPDATE session SET last_activity = NOW(), user_agent = ?, ip_address = ? WHERE session_id = ?", [$session_id, $_SERVER['HTTP_USER_AGENT'], $_SERVER['REMOTE_ADDR']]);
        return $row;
    } else {
        return false;
    }
}

//Start Session with UserId
function StartSession($user_id)
{
    global $conn;
    $session_id = getCookie();
    $conn->queryPrepared("INSERT INTO session (`session_id`, `user_id`, `last_activity`,`user_agent`,`ip_address`) VALUES (?, ?, NOW(),?,?)", [$session_id, $user_id, $_SERVER['HTTP_USER_AGENT'], $_SERVER['REMOTE_ADDR']]);
}

$session = CheckSession();
?>