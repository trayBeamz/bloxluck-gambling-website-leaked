<?php
session_start();
//Disable Including the File
if (get_included_files()[0] != __FILE__) {return;}


include_once "main.php";
include_once "inventory_handler.php";
include_once "session_handler.php";

if (!$session) {
    jsonError("You are not Logged In!");
}
if (!isset($_POST["inventory_id"])) {
    jsonError("400 Bad Request");
}
if (isset($_POST["robuxwithdraw"]) and $_POST["robuxwithdraw"] == true) {
    $itemInfo = getInventoryItem($_POST["inventory_id"]);
    if (!$itemInfo) {
        jsonError("Item not found!");
    }
    if ($itemInfo["user_id"] != $session["user_id"]) {
        jsonError("This item is not owned by you!");
    }
    if ($itemInfo["locked"]) {
        jsonError("You can not sell this item!");
    }
    if ($itemInfo["item_value"] and $itemInfo["item_value"] < 1000) {
        jsonError("You can only sell Items with value more than 1000.");
    }
    removeInventoryItem($_POST["inventory_id"]);
    newRobuxWithdraw($session["user_id"], $itemInfo["item_id"]);
} else {
    if (getPendingWithdraw($session["user_id"],"MM2")) {
        jsonError("You have a pending withdraw! Please join the private server and withdraw your items. You can get the current private server link by clicking the deposit button! ");
    }
    $itemInfo = getInventoryItem($_POST["inventory_id"]);
    if (!$itemInfo) {
        jsonError("Item not found!");
    }
    if ($itemInfo["user_id"] != $session["user_id"]) {
        jsonError("This item is not owned by you!");
    }
    if ($itemInfo["locked"]) {
        jsonError("You can not withdraw this item!");
    }
    removeInventoryItem($_POST["inventory_id"]);
    newWithdraw($session["user_id"], $itemInfo["item_id"]);
}
jsonError(false);
?>