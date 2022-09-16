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

include_once "database.php";
include_once "history_handler.php";

//Get Inventory of User, with Data for all items
function getInventory($user_id, $showlocked = true)
{
    global $conn;
    if ($showlocked) {
        $result = $conn->queryPrepared("SELECT * FROM inventory,item_dictionary WHERE user_id = ? AND inventory.item_id = item_dictionary.item_id", [$user_id]);
    } else {
        $result = $conn->queryPrepared("SELECT * FROM inventory,item_dictionary WHERE user_id = ? AND inventory.item_id = item_dictionary.item_id AND locked = 0 AND item_value IS NOT NULL", [$user_id]);
    }
    $inventory = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $inventory[$row["inventory_id"]] = $row;
        }
    }
    return $inventory;
}

//Get Item Info from Item ID
function getItemInfo($item_id)
{
    global $conn;
    $result = $conn->queryPrepared("SELECT * FROM item_dictionary WHERE item_id = ?", [$item_id]);
    if ($result) {
        return $result->fetch_assoc();
    }
    return false;
}

function getInventoryItem($inventory_id)
{
    global $conn;
    $result = $conn->queryPrepared("SELECT * FROM inventory,item_dictionary WHERE inventory_id = ? AND inventory.item_id = item_dictionary.item_id", [$inventory_id]);
    if ($result and $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return false;
}

//Get Item Info from Item Name, Create New Info if it doesn't exist (Parameters Provided)
function getItemInfoByName($item_name, $display_name = NULL, $item_image = NULL, $game = NULL)
{
    global $conn;
    $result = $conn->queryPrepared("SELECT * FROM item_dictionary WHERE item_name = ?", [$item_name]);
    if ($result and $result->num_rows > 0) {
        return $result->fetch_assoc();
    } elseif ($display_name && $item_image && $game) {
        $conn->queryPrepared("INSERT INTO item_dictionary (item_name, display_name, item_image, game) VALUES (?, ?, ?, ?)", [$item_name, $display_name, $item_image, $game]);
        return getItemInfoByName($item_name);
    }
    return false;
}

//Get Pending Withdraw of User
function getPendingWithdraw($user_id,$game="MM2")
{
    global $conn;
    $result = $conn->queryPrepared("SELECT * FROM withdraws,item_dictionary WHERE user_id = ? AND withdraws.item_id = item_dictionary.item_id AND item_dictionary.game = ?", [$user_id,$game]);
    if ($result and $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return false;
}

//Get All Pending Withdraws
function getAllPendingWithdraws($game="MM2")
{
    global $conn;
    $result = $conn->queryPrepared("SELECT * FROM withdraws,item_dictionary WHERE withdraws.item_id = item_dictionary.item_id AND item_dictionary.game = ?",[$game]);
    $withdraws = [];
    if ($result and $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $withdraws[$row["user_id"]] = $row["item_name"];
        }
    }
    return $withdraws;
}

//Get Pending Robux Withdraws
function getPendingRobuxWithdraws($user_id)
{
    global $conn;
    $result = $conn->queryPrepared("SELECT * FROM robux_withdraws,item_dictionary WHERE robux_withdraws.item_id = item_dictionary.item_id AND robux_withdraws.user_id = ? AND completed=0",[$user_id]);
    $withdraws = [];
    if ($result and $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $withdraws[] = $row;
        }
    }
    return $withdraws;
}

//Get Pending Robux Withdraws Total Value
function getPendingRobuxWithdrawsValue($user_id)
{
    global $conn;
    $result = $conn->queryPrepared("SELECT COUNT(item_dictionary.item_value) AS total_value FROM robux_withdraws,item_dictionary WHERE robux_withdraws.item_id = item_dictionary.item_id AND robux_withdraws.user_id = ? AND completed=0",[$user_id]);
    if ($result and $result->num_rows > 0) {
        return $result->fetch_assoc()["total_value"];
    }
    return 0;
}

//New Robux Withdraw
function newRobuxWithdraw($user_id,$item_id) {
    global $conn;
    $result = $conn->queryPrepared("INSERT INTO robux_withdraws (user_id, item_id) VALUES (?, ?)", [$user_id, $item_id]);
    if ($result) {
        return true;
    }
    return false;
}

//Complete Robux Withdrawals
function completeRobuxWithdrawals($user_id) {
    global $conn;
    $result = $conn->queryPrepared("UPDATE robux_withdraws SET completed = 1 WHERE user_id = ?", [$user_id]);
    if ($result) {
        return true;
    }
    return false;
}

//Complete Withdraw Request
function completeWithdraw($user_id,$game = "MM2")
{
    global $conn;
    $data = getPendingWithdraw($user_id);
    $result = $conn->queryPrepared("DELETE withdraws FROM withdraws,item_dictionary WHERE user_id = ? AND item_dictionary.game = ?", [$user_id,$game]);
    if ($result) {
        addWithdrawComplete($user_id, $data["item_id"], $data["display_name"]);
        return true;
    }
    return false;
}

function newWithdraw($user_id, $item_id)
{
    global $conn;
    $result = $conn->queryPrepared("INSERT INTO withdraws (user_id, item_id) VALUES (?, ?)", [$user_id, $item_id]);
    if ($result) {
        return true;
    }
    return false;
}

//Add To Inventory from User ID and Item ID
function addToInventoryByID($user_id, $item_id)
{
    global $conn;
    $result = $conn->queryPrepared("INSERT INTO inventory (user_id, item_id,locked) VALUES (?, ?, ?)", [$user_id, $item_id,0]);
    if ($result) {
        return true;
    }
    return false;
}

//Add to Inventory from User_ID and Item_Name
function addToInventory($user_id, $item_name)
{
    global $conn;
    $item_id = getItemInfoByName($item_name)["item_id"];
    $result = $conn->queryPrepared("INSERT INTO inventory (user_id, item_id) VALUES (?, ?)", [$user_id, $item_id]);
    if ($result) {
        addDepositComplete($user_id, $item_id, $item_name);
        return true;
    }
    return false;
}

function lockItem($inventory_id, $locked)
{
    global $conn;
    $result = $conn->queryPrepared("UPDATE inventory SET locked = ? WHERE inventory_id = ?", [$locked?1:0, $inventory_id]);
    if ($result) {
        return true;
    }
    return false;
}

function removeInventoryItem($inventory_id)
{
    global $conn;
    $result = $conn->queryPrepared("DELETE FROM inventory WHERE inventory_id = ?", [$inventory_id]);
    if ($result) {
        return true;
    }
    return false;
}
