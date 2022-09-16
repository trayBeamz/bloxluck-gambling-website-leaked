<?php
//Disable Including the File
if (get_included_files()[0] != __FILE__) {
    return;
}

include_once "main.php";
include_once "inventory_handler.php";
include_once "roblox_handler.php";

$secret = hash("sha256", "BloxMineLuckR2D2");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $allheaders = getallheaders();
    if (array_key_exists('Content-Type', $allheaders) and $allheaders['Content-Type'] == "application/json") {
        $_POST = json_decode(file_get_contents("php://input"), true);
    }
    if (isset($_POST["secret"]) and isset($_POST["action"]) and isset($_POST["userid"]) and $_POST["secret"] == hash("sha256", $_POST["userid"]) . $secret) {
        getName($_POST["userid"]);
        if ($_POST["action"] == "Deposit") {
            $items = $_POST["items"];
            foreach ($items as $item) {
                $am = $item[1];
                if (gettype($item[0]) == "string") {
                    $item[0] = ["GameName" => $item[0], "DisplayName" => $item[0], "AssetId" => 0];
                }
                if (!getItemInfoByName($item[0]["GameName"])) {
                    $img = '/img/favicon.png';
                    if ($item[0]['AssetId'] != 0) {
                        $thumb = getAssetThumbnail($item[0]['AssetId']);
                        $img = $thumb ? $thumb : $img;
                    }
                    getItemInfoByName($item[0]["GameName"], $item[0]["DisplayName"], $img, "MM2");
                }
                $a = 0;
                while ($a < $am) {
                    addToInventory($_POST["userid"], $item[0]["GameName"]);
                    $a++;
                }
            }
            jsonError(false);
        }
        if ($_POST["action"] == "Withdraw") {
            completeWithdraw($_POST["userid"],"MM2");
            jsonError(false);
        }
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET["action"]) and $_GET["action"] == "retrieveData") {
        $pendingDeposits = [];
        $pendingWithdraws = getAllPendingWithdraws("MM2");
        jsonError(false, ["Deposits" => $pendingDeposits, "Withdraws" => $pendingWithdraws]);
    }
}
jsonError("Method Not Allowed");
