<?php
session_start();
//Disable Including the File
if (get_included_files()[0] != __FILE__) {
    return;
}


include_once "main.php";
include_once "giveaway_handler.php";
include_once "session_handler.php";
include_once "roblox_handler.php";

if (!$session) {
    jsonError("You are not Logged In!");
}
if (!isset($_POST["type"])) {
    jsonError("400 Bad Request");
}
if ($_POST["type"] == "create") {
    if (!isset($_POST["item_id"])) {
        jsonError("400 Bad Request");
    }
    $itemId = intval($_POST["item_id"]);
    $giveAwayId = createGiveaway($session["user_id"], $itemId);
    if (!$giveAwayId[0]) {
        jsonError($giveAwayId[1]);
    }
    jsonError(false);
} elseif ($_POST["type"] == "join") {
    if (!isset($_POST["giveaway_id"])) {
        exit();
    }
    $giveAwayId = joinGiveaway($_POST["giveaway_id"], $session["user_id"]);
    if (!$giveAwayId[0]) {
        jsonError($giveAwayId[1]);
    }
    jsonError(false);
} elseif ($_POST["type"] == "gethtml") {
    if (!isset($_POST["giveaway_id"])) {
        exit();
    }
    $match = getGiveaway($_POST["giveaway_id"]);
    if (!$match) {
        exit();
    }
    $item_info = getItemInfo($match["item_id"]);
        $joined = $session ? isJoined($match["giveaway_id"], $session["user_id"]) : false;
        try {
            $players = $conn->queryPrepared("SELECT COUNT(*) AS players FROM giveaways_participants WHERE giveaway_id = ?",[$match["giveaway_id"]])->fetch_assoc()["players"];
        }
        
        //catch exception
        catch(Exception $e) {
            $players = 0;
            sendErrorEmbedWebhook("Problem Getting Joined Players in Giveaway",$e->getMessage());
        }
    ?>
        <div id='giveaway<?php echo $match["giveaway_id"]; ?>' class="row" style="justify-content:space-between;">
            <img src="<?php echo $item_info["item_image"]; ?>" width="80px" height="80px">
            <div style="display:flex;flex-direction:column;align-items:center;flex: 0 0 auto;">
                <span style="font-size:1.5em;font-style:italic;font-weight:bold;"><?php echo $item_info['display_name'] ?></span>
                <span style="font-size:1.2em;color:gold;margin-top:-0.2em;"><?php echo $item_info['item_value'] ? $item_info['item_value'] . " Value" : "Pending Valuation"; ?></span>
                <span style="font-size:1.2em;color:lime;margin-top:-0.2em;" class="endtime">Ends in 10 Minutes</span>
            </div>
            <div style="display:flex;flex-direction:column;align-items:center;flex: 0 0 auto;">
                <span style="font-size:1.5em;font-style:italic;font-weight:bold;" class='numplayers'><?php echo $players; ?> Joined</span>
            </div>
            <button class="btn btn-primary" <?php echo $session?(($joined or $match['winner']) ? "disabled" : "onclick='joinGiveaway($match[giveaway_id])'"):"onclick='login()'"; ?>;><?php echo $match['winner'] ? "Ended" : ($session?($joined ? "Joined" : "Join"):"Log In"); ?></button>
            <script>
                countDown(<?php echo strtotime($match['enddate']); ?> * 1000 , "#giveaway<?php echo $match["giveaway_id"]; ?> .endtime");
            </script>
        </div>
<?php
    exit();
} elseif ($_POST["type"] == "check") {
    checkGiveaways();
    jsonError(false);
}
jsonError("400 Bad Request");
?>
