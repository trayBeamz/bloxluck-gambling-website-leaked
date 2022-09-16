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

include_once "roblox_handler.php";
include_once "inventory_handler.php";

$webhooks = [
    "games" => "https://discord.com/api/webhooks/1005444992833167392/7pczzAEkyoEGmw_JppfsSNRO_B2u5s8diJEUDudjKCj15KnpAcDYSbae5wtQcYIHLGgq",
    "error" => "https://discord.com/api/webhooks/1005445353224536135/1baiHT4V5E-2vS7k84kBTpzsgnjXL4zufd_S679usNHX0nVgNXNEudgdRgslSpuSa0zR",
    "tax_logs" => "https://discord.com/api/webhooks/1008238891033174036/KudXhzG0YioMV12gLV-_eQP2pAqFzhhR1JPvjT69Vsl3nI711zHa4u9GWKoSKRIiYBu-",
    "giveaways" => "https://discord.com/api/webhooks/1009022856555937802/ZRfV4mAoEsGCLSM84IkDfQn__Tr6N6z0oOt-KVY3i8W6x8vKYyG5FzWx3DqVCs8wk9eg"
];

//Send Discord Webhook with Data
function sendWebhook($webhook, $data)
{
    global $webhooks;
    $data = json_encode($data);
    $ch = curl_init($webhooks[$webhook]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json'
    ));
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}

//Send Webhook after adding Custom Profile
function sendCustomWebhook($webhook, $data)
{
    if (!isset($data["avatar_url"])) {
        $data["avatar_url"] = "https://morbius.ml/bloxluck/img/favicon.png";
    }
    if (!isset($data["username"])) {
        $data["username"] = "BloxLuck";
    }
    sendWebhook($webhook, $data);
}

//Send Plain Text Discord Webhook
function sendWebhookText($webhook, $data)
{
    sendCustomWebhook($webhook, ["content" => $data]);
}

//Send Embed Discord Webhook
function sendWebhookEmbed($webhook, $data)
{
    sendCustomWebhook($webhook, ["embeds" => [$data]]);
}

//Send New Game Webhook
function sendNewGameWebhook($UserId,$side, $InventoryIds)
{
    $value = 0;
    $itemsArray = [];
    $inv = getInventory($UserId);
    foreach ($InventoryIds as $item) {
        if (array_key_exists($item, $inv)) {
            $itemsArray[] = $inv[$item]["display_name"]." - ".$inv[$item]["item_value"];
            $value += $inv[$item]["item_value"];
        }
    }
    $data = [
        "title" => "New Coinflip Game",
        "description" => "A new game has been started by " . getName($UserId) . ".",
        "color" => 0x00ff00,
        "fields" => [
            [
                "name" => "Value",
                "value" => $value,
                "inline" => true
            ],
            [
                "name" => "Starter's Side",
                "value" => $side == 0? "Red":"Blue",
                "inline" => true
            ],
            [
                "name" => "Items",
                "value" => "```".implode("\n", $itemsArray)."```",
                "inline" => false
            ]
        ]
    ];
    sendWebhookEmbed("games", $data);
}

function sendGamePlayedWebhook($game_id,$coinflipticket = NULL) {
    include_once "game_handler.php";
    $gameInfo = getGameData($game_id);
    if (!$gameInfo) {
        sendErrorEmbedWebhook("Game Not Found", "Game with ID " . $game_id . " was not found, while trying to send Game Played Webhook.");
        return;
    }
    $data = [
        "title" => "Coinflip Game Played",
        "description" => "A game has been played.",
        "color" => 0xffff00,
        "fields" => [
            [
                "name" => "Starter Name",
                "value" => getName($gameInfo["starter_id"]),
                "inline" => true
            ],
            [
                "name" => "Starter Side",
                "value" => $gameInfo["starter_side"] == 0? "Red":"Blue",
                "inline" => true
            ],
            [
                "name" => "Starter Value",
                "value" => $gameInfo["starter_value"],
                "inline" => true
            ],
            [
                "name" => "Player Name",
                "value" => getName($gameInfo["player_id"]),
                "inline" => true
            ],
            [
                "name" => "Player Side",
                "value" => $gameInfo["starter_side"] == 1? "Red":"Blue",
                "inline" => true
            ],
            [
                "name" => "Player Value",
                "value" => $gameInfo["player_value"],
                "inline" => true
            ],
            [
                "name" => "Winner Side",
                "value" => $gameInfo["winner_side"]==0?"Red":"Blue",
                "inline" => true
            ],
            [
                "name" => "Winner Name",
                "value" => getName($gameInfo["winner_side"]==$gameInfo["starter_side"]?$gameInfo["starter_id"]:$gameInfo["player_id"]),
                "inline" => true
            ]
        ]
    ];
    if ($coinflipticket) {
        $data["description"] = "A game has been played - Coinflip Id: [$coinflipticket](https://api.random.org/tickets/form?ticket=$coinflipticket)";
    }
    sendWebhookEmbed("games", $data);
}

//send Error Webhook
function sendErrorEmbedWebhook($title,$error,$webhook="error") {
    $data = [
        "title" => $title,
        "description" => $error,
        "color" => 0xff0000
    ];
    sendWebhookEmbed($webhook, $data);
}

function sendErrorWebhook($error,$webhook="error")
{
    sendErrorEmbedWebhook("An Error Occured",$error,$webhook);
}

//Send Game Tax Webhook
function sendGameTaxWebhook($gameData,$inventory_id,$webhook="tax_logs") {
    $taxItem = getInventoryItem($inventory_id);
    $data = [
        "title" => "Game Tax",
        "description" => "Got Tax From Game, Game Id " . $gameData["game_id"],
        "color" => 0xffff00,
        "fields" => [
            [
                "name" => "Starter Name",
                "value" => getName($gameData["starter_id"]),
                "inline" => true
            ],
            [
                "name" => "Player Name",
                "value" => getName($gameData["player_id"]),
                "inline" => true
            ],
            [
                "name" => "Game Value",
                "value" => $gameData["player_value"] + $gameData["starter_value"],
                "inline" => true
            ],
            [
                "name" => "Taxed Item Value",
                "value" => $taxItem["item_value"],
                "inline" => true
            ],
            [
                "name" => "Taxed Item",
                "value" => $taxItem["display_name"],
                "inline" => true
            ]
        ]
    ];
    sendWebhookEmbed($webhook, $data);
}