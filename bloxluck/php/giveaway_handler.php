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
include_once "roblox_handler.php";
include_once "history_handler.php";
include_once "inventory_handler.php";
include_once "socket_handler.php";
include_once "discord_handler.php";
include_once 'main.php';


function giveawayCompletedWebhook($item, $winner, $starter)
{
    if ($winner) {
        $data = [
            "title" => "Giveaway Completed",
            "description" => "The giveaway for " . $item . " has been completed. The winner is " . $winner . ".",
            "color" => 0x00ff00,
            "footer" => [
                "text" => "Starter: " . $starter
            ]
        ];
    } else {
        $data = [
            "title" => "Giveaway Completed",
            "description" => "The giveaway for " . $item . " has been completed. No one participated in the giveaway, Item has been returned to starter.",
            "color" => 0xff0000,
            "footer" => [
                "text" => "Starter: " . $starter
            ]
        ];
    }
    sendWebhookEmbed("giveaways", $data);
}

function newGiveawayWebhook($item, $starter, $endtime)
{
    $data = [
        "title" => "New Giveaway",
        "description" => "A new giveaway has been created for " . $item . ". Giveaway Ends At: <t:" . $endtime . ":R>",
        "color" => 0x00ff00,
        "footer" => [
            "text" => "Starter: " . $starter
        ]
    ];
    sendWebhookEmbed("giveaways", $data);
}

function checkGiveaways()
{
    global $conn;
    $sql = "SELECT * FROM giveaways WHERE enddate < NOW() AND winner IS NULL";
    $result = $conn->query($sql);
    if ($result and $result->num_rows > 0) {
        while ($giveaway =  $result->fetch_assoc()) {
            $conn->queryPrepared("UPDATE `giveaways` SET `winner` = ? WHERE `giveaway_id` = ?", [0, $giveaway['giveaway_id']]);
            $players = $conn->queryPrepared("SELECT `user_id` FROM `giveaways_participants` WHERE giveaway_id = ? ORDER BY RAND() LIMIT 1",[$giveaway['giveaway_id']]);
            $itemInfo = getItemInfo($giveaway['item_id']);
            if (!$itemInfo) {
                sendErrorEmbedWebhook("Item Not Found In Dictionary", "giveaway: " . $giveaway['giveaway_id'] . "\nitem_id: " . $giveaway['item_id']);
                return;
            }
            $item = $itemInfo['display_name'] . " (" . $itemInfo['item_value'] . ")";
            $starter = getName($giveaway['user_id']);
            if ($players and $players->num_rows > 0) {
                $players = $players->fetch_assoc();
                $winner = [
                        "id" => $players['user_id'],
                        "name" => getName($players['user_id'])
                    ];
                $conn->queryPrepared("UPDATE `giveaways` SET `winner` = ? WHERE `giveaway_id` = ?", [$winner['id'], $giveaway['giveaway_id']]);
                addToInventoryByID($winner["id"], $giveaway["item_id"]);
                sendAll("giveaway ended",[$winner['name'],$giveaway['giveaway_id']]);
                sendUser($winner["id"], "giveaway win", $item);
                giveawayCompletedWebhook($item, $winner['name'], $starter);
                addGiveawayHistory($winner['id'],$giveaway['item_id'],$item);
            } else {
                $conn->queryPrepared("UPDATE `giveaways` SET `winner` = ? WHERE `giveaway_id` = ?", [$giveaway["user_id"], $giveaway['giveaway_id']]);
                addToInventoryByID($giveaway['user_id'], $giveaway['item_id']);
                sendAll("giveaway ended",["No One",$giveaway['giveaway_id']]);
                sendUser($giveaway['user_id'], "item refund", $item);
                giveawayCompletedWebhook($item, NULL, $starter);
                addGiveawayHistory($giveaway['user_id'],$giveaway['item_id'],$item);
            }
        }
    }
}

function joinGiveaway($giveawayID, $userID)
{
    global $conn;
    $giveawayID = intval($giveawayID);
    $userID = intval($userID);
    $sql = "SELECT * FROM giveaways WHERE giveaway_id = ? AND enddate > NOW()";
    $result = $conn->queryPrepared($sql, [$giveawayID]);
    if ($result and $result->num_rows > 0) {
        $giveaway = $result->fetch_assoc();
        if ($userID == $giveaway['user_id']) {
            return [false, "You can not join your own giveaway."];
        }
        if ($giveaway['winner'] == NULL) {
            $result = $conn->queryPrepared("SELECT * FROM giveaways_participants WHERE user_id = ? AND giveaway_id = ?", [$userID, $giveawayID]);
            if ($result and $result->num_rows == 0) {
                $conn->queryPrepared("INSERT INTO `giveaways_participants` (`giveaway_id`, `user_id`) VALUES (?, ?)", [$giveawayID, $userID]);
                try {
                    $players = $conn->queryPrepared("SELECT COUNT(*) AS players FROM giveaways_participants WHERE giveaway_id = ?",[$giveawayID])->fetch_assoc()["players"];
                    sendAll("update giveaway joined",[$players,$giveawayID]);
                }
                //catch exception
                catch(Exception $e) {
                    sendErrorEmbedWebhook("Problem Getting Joined Players in Giveaway in Giveaway Handler",$e->getMessage());
                }
                return [true];
            } else {
                return [false, "You have already joined this giveaway."];
            }
        } else {
            return [false, "Giveaway has already been completed."];
        }
    } else {
        return [false, "Giveaway not found."];
    }
}

function isJoined($giveawayID, $userID)
{
    global $conn;
    $giveawayID = intval($giveawayID);
    $userID = intval($userID);
    $result = $conn->queryPrepared("SELECT * FROM giveaways_participants WHERE user_id = ? AND giveaway_id = ?", [$userID, $giveawayID]);
    if ($result and $result->num_rows > 0) {
        return true;
    } else {
        return false;
    }
}

function getGiveaway($giveawayID)
{
    global $conn;
    $giveawayID = intval($giveawayID);
    $sql = "SELECT * FROM giveaways WHERE giveaway_id = ?";
    $result = $conn->queryPrepared($sql, [$giveawayID]);
    if ($result and $result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return NULL;
    }
}

function getActiveGiveaways()
{
    global $conn;
    $sql = "SELECT * FROM giveaways WHERE enddate > NOW() AND winner IS NULL";
    $result = $conn->query($sql);
    if ($result and $result->num_rows > 0) {
        $giveaways = [];
        while ($giveaway = $result->fetch_assoc()) {
            $giveaways[] = $giveaway;
        }
        return $giveaways;
    } else {
        return NULL;
    }
}

function createGiveaway($userID, $inventoryID)
{
    global $conn;
    $userID = intval($userID);
    $inventoryID = intval($inventoryID);
    $itemInfo = getInventoryItem($inventoryID);
    if (!$itemInfo) {
        return [false, "Item not found."];
    }
    if (!$itemInfo["user_id"] == $userID) {
        return [false, "You can not create a giveaway for another user's item."];
    }
    if ($itemInfo["locked"]) {
        return [false, "You can not giveaway a Locked Item"];
    }
    if ($itemInfo["item_value"] < 50) {
        return [false, "You can not create a giveaway for an item with a value less than 50."];
    }
    $giveaways = getActiveGiveaways();
    if ($giveaways and count($giveaways) >= 10) {
        return [false, "There can be a maximum of 10 giveaways at a time."];
    }
    $sql = "INSERT INTO `giveaways` (`user_id`, `item_id`, `enddate`) VALUES (?, ?, NOW() + INTERVAL 30 MINUTE)";
    $result = $conn->queryPrepared($sql, [$userID, $itemInfo["item_id"]], true);
    $endtime = time() + (1800);
    if ($result and $result[0]) {
        $giveawayID = $result[0];
        removeInventoryItem($inventoryID);
        newGiveawayWebhook($itemInfo['display_name'], getName($userID), $endtime);
        sendAll("new giveaway", $giveawayID);
        addNewGiveawayHistory($userID,$itemInfo["item_id"],$itemInfo['display_name']);
        return [true, $giveawayID];
    } else {
        return [false, "Unkown Error Occurred."];
    }
}
