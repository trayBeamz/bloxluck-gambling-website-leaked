<?php
//Stop Direct Access to the File
//Works only in PHP 5.0 and Up
if (get_included_files()[0] == __FILE__) {http_response_code(403);die('Forbidden');}

//Stop Including This File Twice
if (defined(strtoupper(basename(__FILE__,".php"))."_PHP")) {return True;}
define(strtoupper(basename(__FILE__,".php"))."_PHP", True);

include_once "database.php";
include_once "inventory_handler.php";

//Get Raw History of User
function getHistory($user_id)
{
    global $conn;
    $result = $conn->queryPrepared("SELECT * FROM history WHERE user_id = ?", [$user_id]);
    $history = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $history[$row["history_id"]] = $row;
        }
    }
    return $history;
}

//Get Raw History of User in Descending Order of Date
function getHistoryDescending($user_id)
{
    global $conn;
    $result = $conn->queryPrepared("SELECT * FROM history WHERE user_id = ? ORDER BY date DESC", [$user_id]);
    $history = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
    }
    return $history;
}

//Add History Entry
function addHistory($user_id, $item_id, $data)
{
    global $conn;
    $conn->queryPrepared("INSERT INTO history (user_id, item_id, `data`) VALUES (?, ?, ?)", [$user_id, $item_id, $data]);
}

//Add Withdraw Complete Entry
function addWithdrawComplete($user_id, $item_id, $display_name)
{
    $data = json_encode([
        "type"=>"withdraw",
        "text"=>"Successfully Withdrawed ".$display_name
    ]);
    addHistory($user_id, $item_id, $data);
}

//Add Deposit Complete Entry
function addDepositComplete($user_id, $item_id, $display_name)
{
    $data = json_encode([
        "type"=>"deposit",
        "text"=>"Successfully Deposited ".$display_name
    ]);
    addHistory($user_id, $item_id, $data);
}

function addGameHistory($user_id, $win, $item_id,$display_name)
{
    $data = json_encode([
        "type"=>"game",
        "win"=>$win,
        "text"=>"You ".($win ? "won" : "lost")." ".$display_name
    ]);
    addHistory($user_id, $item_id, $data);
}

function addGiveawayHistory($user_id, $item_id, $display_name)
{
    $data = json_encode([
        "type"=>"giveaway",
        "action" => "win",
        "text"=>"You won ".$display_name." in a Giveaway"
    ]);
    addHistory($user_id, $item_id, $data);
}

function addNewGiveawayHistory($user_id, $item_id, $display_name)
{
    $data = json_encode([
        "type"=>"giveaway",
        "action"=>"create",
        "text"=>"You created a Giveaway with ".$display_name
    ]);
    addHistory($user_id, $item_id, $data);
}
?>