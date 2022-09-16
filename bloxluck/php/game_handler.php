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
include_once "inventory_handler.php";
include_once "socket_handler.php";
include_once "discord_handler.php";
include_once "random_org.php";
include_once 'main.php';

//Create New Game from User Id, Side, Inventory Ids
//$side is either 0 or 1 (0 for Gem, 1 for Dog)
function createGame($userId, $side, $inventoryIds)
{
    global $conn;
    global $maxGameItems;
    $inventoryIds = array_unique($inventoryIds);
    if (count($inventoryIds) <= 0) {
        return [false, "You need to select some items"];
    }
    if (count($inventoryIds) > $maxGameItems) {
        return [false, "You can bet a maximum of $maxGameItems items."];
    }
    $value = 0;
    $itemsArray = [];
    $inv = getInventory($userId);
    foreach ($inventoryIds as $item) {
        if (array_key_exists($item, $inv)) {
            if ($inv[$item]["locked"]) {
                return [false, "You can not select a locked item!"];
            }
            if ($inv[$item]["item_value"] == NULL) {
                return [false, "You can not select an item without a value!"];
            }
            $itemsArray[] = ["inventory_id" => $item, "item_id" => $inv[$item]["item_id"]];
            $value += $inv[$item]["item_value"];
        } else {
            return [false, "Item does not exist in Inventory"];
        }
    }
    if ($value < 10) {
        return [false, "Minimum Value to create a game is 10"];
    }
    $result = $conn->queryPrepared("INSERT INTO games (starter_id, starter_side, starter_value, starter_items) VALUES (?, ?, ?,?)", [$userId, $side, $value, json_encode($itemsArray)], true);
    foreach ($itemsArray as $item) {
        lockItem($item["inventory_id"], true);
    }
    sendAll("new game", $result[0]);
    sendNewGameWebhook($userId, $side, $inventoryIds);
    return [true];
}

function getGameData($gameId)
{
    global $conn;
    $result = $conn->queryPrepared("SELECT * FROM games WHERE game_id = ?", [$gameId]);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row;
    } else {
        return false;
    }
}

function updateValues($gameId)
{
    global $conn;
    $gameInfo = getGameData($gameId);
    if ($gameInfo) {
        $items = json_decode($gameInfo["starter_items"], true);
        $value = 0;
        $changed = false;
        foreach ($items as $item) {
            $value += getItemInfo($item["item_id"])["item_value"];
        }
        if ($gameInfo["starter_value"] != $value) {
            $conn->queryPrepared("UPDATE games SET starter_value = ? WHERE game_id = ?", [$value, $gameId]);
            $changed = true;
        }
        if ($gameInfo["player_id"]) {
            $player_items = json_decode($gameInfo["player_items"], true);
            $player_value = 0;
            foreach ($player_items as $item) {
                $player_value += getItemInfo($item["item_id"])["item_value"];
            }
            if ($gameInfo["player_value"] != $player_value) {
                $conn->queryPrepared("UPDATE games SET player_value = ? WHERE game_id = ?", [$player_value, $gameId]);
                $changed = true;
            }
        }
        if ($changed) {
            sendAll("update game", $gameId);
        }
        return True;
    }
    return false;
}

function deleteGame($gameId)
{
    global $conn;
    $gameInfo = getGameData($gameId);
    if ($gameInfo) {
        if ($gameInfo["end_date"]) {
            return [false, "This game is already Completed!"];
        }
        if (isset($gameInfo["starter_items"])) {
            $items = json_decode($gameInfo["starter_items"], true);
            foreach ($items as $item) {
                lockItem($item["inventory_id"], false);
            }
        }
        if (isset($gameInfo["player_items"])) {
            $items = json_decode($gameInfo["player_items"], true);
            foreach ($items as $item) {
                lockItem($item["inventory_id"], false);
            }
        }
        $conn->queryPrepared("DELETE FROM games WHERE game_id = ?", [$gameId]);
        sendAll("delete game", $gameId);
    }
    return [true];
}

function checkGameItems($gameId)
{
    $gameInfo = getGameData($gameId);
    if ($gameInfo["end_date"]) {
        return true;
    }
    $items = json_decode($gameInfo["starter_items"], true);
    $inventory = getInventory($gameInfo["starter_id"]);
    foreach ($items as $index => $item) {
        if (!array_key_exists($item["inventory_id"], $inventory)) {
            array_splice($items, $index, 1);
        }
    }
    if (count($items) == 0) {
        deleteGame($gameId);
        return false;
    }
    if ($items != $gameInfo["starter_items"]) {
        global $conn;
        $conn->queryPrepared("UPDATE games SET starter_items = ? WHERE game_id = ?", [json_encode($items), $gameId]);
        updateValues($gameId);
        return false;
    }
    updateValues($gameId);
    return true;
}

function playGame($gameId, $userId, $inventoryIds)
{
    global $conn;
    global $maxGameItems;
    global $minimumTotalTaxItems;
    global $minimumTotalTaxValue;
    global $taxRecieverId;
    $inventoryIds = array_unique($inventoryIds);
    if (count($inventoryIds) <= 0) {
        return [false, "You need to select some items"];
    }
    if (count($inventoryIds) > $maxGameItems) {
        return [false, "You can bet a maximum of $maxGameItems items."];
    }
    $gameInfo = getGameData($gameId);
    if ($gameInfo) {
        if ($gameInfo["end_date"]) {
            return [false, "Game already ended"];
        }
        if ($gameInfo["starter_id"] == $userId) {
            return [false, "You can not play your own game"];
        }
        $conn->queryPrepared("UPDATE games SET end_date = NOW() WHERE game_id = ?", [$gameId]);
        checkGameItems($gameId);
        $gameInfo = getGameData($gameId);
        $value = 0;
        $minValue = $gameInfo["starter_value"] - 10;
        $maxValue = $gameInfo["starter_value"] + 10;
        $lowestValue = NULL;
        $lowestValueInventoryId = NULL;
        $highestValue = NULL;
        $highestValueInventoryId = NULL;
        $itemsArray = [];
        $inv = getInventory($userId);
        foreach ($inventoryIds as $item) {
            if (array_key_exists($item, $inv)) {
                if ($inv[$item]["locked"]) {
                    return [false, "You can not select a locked item!"];
                }
                if ($inv[$item]["item_value"] == NULL) {
                    return [false, "You can not select an item without a value!"];
                }
                $itemsArray[] = ["inventory_id" => $item, "item_id" => $inv[$item]["item_id"]];
                $value += $inv[$item]["item_value"];
                if (($lowestValue == NULL || $inv[$item]["item_value"] < $lowestValue) && $inv[$item]["item_value"] >= $gameInfo["starter_value"]*2*0.05) {
                    $lowestValue = $inv[$item]["item_value"];
                    $lowestValueInventoryId = $item;
                }
                if ($highestValue == NULL || $inv[$item]["item_value"] > $highestValue) {
                    $highestValue = $inv[$item]["item_value"];
                    $highestValueInventoryId = $item;
                }
            } else {
                return [false, "Item does not exist in Inventory"];
            }
        }
        if ($value < 10) {
            return [false, "Minimum Value to play a game is 10"];
        }
        if ($value < $minValue || $value > $maxValue) {
            return [false, "Your items should have total value between $minValue and $maxValue"];
        }
        global $userandomorg;
        if (!$userandomorg) {
            $ticketId = NULL;
            $side = rand(0, 9999) % 2;
        } else {
            $side = randomCoinFlip();
            $ticketId = $side[1];
            $side = $side[0];
            if ($side != 0 and $side != 1) {
                $side = rand(0,9999) % 2;
                $ticketId = NULL;
            }
        }
        $conn->queryPrepared("UPDATE games SET player_id = ?, winner_side = ?, player_value = ?, player_items = ?, end_date = NOW() WHERE game_id = ?", [$userId, $side, $value, json_encode($itemsArray), $gameId]);
        $gameData = getGameData($gameId);
        $starteritems = json_decode($gameInfo["starter_items"], true);
        $totalItemCount = count($itemsArray) + count($starteritems);
        if ($totalItemCount >= $minimumTotalTaxItems && $value + $gameInfo["starter_value"] >= $minimumTotalTaxValue) {
            foreach ($starteritems as $item) {
                $itemValue = getItemInfo($item["item_id"])["item_value"];
                if (($lowestValue == NULL || $itemValue < $lowestValue) && $itemValue >= ($gameInfo["starter_value"]+$value)*0.05) {
                    $lowestValue = $itemValue;
                    $lowestValueInventoryId = $item['inventory_id'];
                }
                if ($highestValue == NULL || $itemValue > $highestValue) {
                    $highestValue = $itemValue;
                    $highestValueInventoryId = $item['inventory_id'];
                }
            }
            if ($lowestValue == NULL) {
                $lowestValueInventoryId = $highestValueInventoryId;
                $lowestValue = $highestValue;
            }
            $conn->queryPrepared("UPDATE inventory SET user_id = ? WHERE inventory_id = ?", [$taxRecieverId, $lowestValueInventoryId]);
            sendGameTaxWebhook($gameData, $lowestValueInventoryId);
        } else {
            $lowestValueInventoryId = NULL;
        }
        if ($side == $gameInfo["starter_side"]) {
            $winner = $gameInfo["starter_id"];
            $loser = $userId;
            foreach (json_decode($gameInfo["starter_items"], true) as $item) {
                lockItem($item["inventory_id"], false);
            }
            foreach ($itemsArray as $item) {
                if ($item["inventory_id"] != $lowestValueInventoryId) {
                    $conn->queryPrepared("UPDATE inventory SET user_id = ?, locked = ? WHERE inventory_id = ?", [$winner, 0, $item["inventory_id"]]);
                    $iteminfo = getItemInfo($item["item_id"]);
                    addGameHistory($winner, true, $item["item_id"], $iteminfo["display_name"] . " (Value: " . $iteminfo["item_value"] . ")" . " - Coinflip Id: " . $ticketId);
                    addGameHistory($loser, false, $item["item_id"], $iteminfo["display_name"] . " (Value: " . $iteminfo["item_value"] . ")" . " - Coinflip Id: " . $ticketId);
                }
            }
        } else {
            $winner = $userId;
            $loser = $gameInfo["starter_id"];
            foreach (json_decode($gameInfo["starter_items"], true) as $item) {
                if ($item["inventory_id"] != $lowestValueInventoryId) {
                    $conn->queryPrepared("UPDATE inventory SET user_id = ?, locked = ? WHERE inventory_id = ?", [$winner, 0, $item["inventory_id"]]);
                    $iteminfo = getItemInfo($item["item_id"]);
                    addGameHistory($winner, true, $item["item_id"], $iteminfo["display_name"] . " (Value: " . $iteminfo["item_value"] . ")" . " - Coinflip Id: " . $ticketId);
                    addGameHistory($loser, false, $item["item_id"], $iteminfo["display_name"] . " (Value: " . $iteminfo["item_value"] . ")" . " - Coinflip Id: " . $ticketId);
                }
            }
        }
        sendAll("update game", $gameId);
        sendAll("game played", $gameData);
        sendGamePlayedWebhook($gameId, $ticketId);
        return [true, $side != $gameInfo["starter_side"]];
    }
    return [false, "Game does not exist"];
}

function getGames($userId, $self = false)
{
    global $conn;
    if ($self) {
        //$result = $conn->queryPrepared("SELECT * FROM games WHERE starter_id = ? OR player_id = ? ORDER BY start_date DESC", [$userId, $userId]);
        $result = $conn->queryPrepared("SELECT * FROM games WHERE (starter_id = ? OR player_id = ?) AND (end_date IS NULL OR end_date >= NOW() - INTERVAL 1 HOUR) ORDER BY -end_date", [$userId, $userId]);
    } else {
        $result = $conn->queryPrepared("SELECT * FROM games WHERE end_date IS NULL AND NOT(starter_id = ?) ORDER BY start_date DESC", [$userId]);
    }
    $games = [];
    while ($row = $result->fetch_assoc()) {
        $games[] = $row;
    }
    return $games;
}

function getProfit($userId, $enddate)
{
    global $conn;
    $result = $conn->queryPrepared("SELECT SUM(IF(winner,IF(starter,player_value,starter_value),IF(starter,-starter_value,-player_value))) as value FROM (
        SELECT starter_value,player_value,(starter_id = ?) as starter, ((starter_side=winner_side and starter_id = ?) or (NOT(starter_side=winner_side) and player_id=?)) as winner FROM games WHERE (starter_id = ? or player_id = ?) and end_date $enddate) AS A", [$userId, $userId, $userId, $userId, $userId]);
    $row = $result->fetch_assoc();
    return $row["value"] ? $row["value"] : 0;
}

function getAllProfit($userId)
{
    return getProfit($userId, "IS NOT NULL");
}
