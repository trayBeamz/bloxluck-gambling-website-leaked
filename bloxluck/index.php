<?php
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');

//Disable Including the File
if (get_included_files()[0] != __FILE__) {
    return;
}
?>
<?php include "layout/head.php"; ?>
<?php include_once "php/game_handler.php"; ?>
<?php
echo "<div class='hidden'>" . uniqid() . "</div>";
?>
<script>
    function countDown(timestamp,selector) {
        let x = setInterval(function() {

                    // Get today's date and time
                    var now = new Date().getTime();

                    // Find the distance between now and the count down date
                    var distance = timestamp - now;

                    // Time calculations for days, hours, minutes and seconds
                    var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    var seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    // Display the result in the element with id="demo"
                    TimeRemaining = minutes + "m " + seconds + "s ";
                    TimeRemaining = "Ends in " + TimeRemaining
                    $(selector).text(TimeRemaining);

                    // If the count down is finished, write some text
                    if (distance < 0) {
                        clearInterval(x);
                        $(selector).text("Giveaway has Ended")
                        setTimeout(function () {
                            $.ajax({
                                url: "./php/giveaway",
                                type: "POST",
                                data: {
                                    type: "check",
                                }
                            });
                        }, Math.floor(Math.random() * 2000));
                    }
                }, 1000);
    }
</script>
<div class="section" style="width:calc(100% - 20px);">
    <?php
    $giveaways = getActiveGiveaways();
    $giveaways = $giveaways?$giveaways:[];
    foreach ($giveaways as $match) :
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
    endforeach;
    ?>
    <div id='gamesheader' class="row" style="justify-content:space-between;">
        <div style="display:flex;gap:20px;align-items:center;">
            <button onclick='<?php echo $session ? "createMatch()" : "login()"; ?>' class="btn-primary">Create Match</button>
            <?php if ($session) : ?>
                <h2 style="text-align:center;">Your Profit: <?php echo getAllProfit($session["user_id"]); ?></h2>
            <?php endif; ?>
        </div>
        <div style="display:flex;gap:20px;align-items:center;">
            <?php if ($session) : ?>
                <button onclick='toggleMatches()' id='matchbtn' class="btn-secondary">My Matches</button>
            <?php endif; ?>
        </div>
    </div>
    <?php
    if ($session) {
        $matches = getGames($session["user_id"]);
    } else {
        $matches = getGames("NULL");
    }
    foreach ($matches as $match) :
    ?>
        <div id='game<?php echo $match["game_id"]; ?>' class="publicmatch row" style="justify-content:space-between;">
            <div style="display:flex;flex-direction:column;gap:10px;align-items:center;width:calc(100% - 100px);">
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;justify-content:space-between;width:100%;">
                    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                        <img src="<?php echo $match["starter_side"] == 0 ? "./img/gem.png" : "./img/dog.png"; ?>" alt="<?php echo $match["starter_side"] == 0 ? "Gem" : "Dog"; ?>" width="32px" height="32px" loading="lazy">
                        <img class="userthumb" src="<?php echo getUserThumbnail($match["starter_id"]); ?>" width="32px" height="32px" loading="lazy">
                        <div style="font-size:24px;"><?php echo getName($match["starter_id"]); ?></div>
                        <?php
                        foreach (json_decode($match["starter_items"], true) as $item) :
                        ?>
                            <img src="<?php echo getItemInfo($item["item_id"])["item_image"] ?>" width="32px" height="32px" loading="lazy">
                        <?php endforeach; ?>
                    </div>
                    <?php if ($match["end_date"]) : ?> <div style="font-size:24px;">Value: <?php echo $match["starter_value"]; ?></div> <?php endif; ?>
                </div>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;justify-content:space-between;width:100%;">
                    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                        <img src="<?php echo $match["starter_side"] == 1 ? "./img/gem.png" : "./img/dog.png"; ?>" alt="<?php echo $match["starter_side"] == 1 ? "Gem" : "Dog"; ?>" width="32px" height="32px" loading="lazy">
                        <?php if ($match["end_date"]) : ?>
                            <img class="userthumb" src="<?php echo getUserThumbnail($match["player_id"]); ?>" width="32px" height="32px" loading="lazy">
                            <div style="font-size:24px;"><?php echo getName($match["player_id"]); ?></div>
                            <?php
                            foreach (json_decode($match["player_items"], true) as $item) :
                            ?>
                                <img src="<?php echo getItemInfo($item["item_id"])["item_image"] ?>" width="32px" height="32px" loading="lazy">
                            <?php endforeach; ?>
                    </div>
                    <div style="font-size:24px;">Value: <?php echo $match["player_value"]; ?></div>
                </div>
            <?php else : ?>
                <?php if (!$session) : ?>
                    <button onclick="login()" class="btn-primary">Join Match (<?php echo $match["starter_value"] - 10 ?> - <?php echo $match["starter_value"] + 10 ?>)</button>
                <?php elseif ($match["starter_id"] != $session["user_id"]) : ?>
                    <button onclick='joinMatch(<?php echo $match["game_id"] . "," . $match["starter_value"]; ?>)' class="btn-primary">Join Match (<?php echo $match["starter_value"] - 10 ?> - <?php echo $match["starter_value"] + 10 ?>)</button>
                <?php else : ?>
                    <button onclick='cancelMatch(<?php echo $match["game_id"]; ?>)' class="btn-primary">Cancel Match</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<div style="display:flex;flex-direction:column;text-align:center;padding-right:10px;">
    <?php if (!$match["end_date"]) : ?>
        <h2>Value <br><?php echo $match["starter_value"]; ?></h2>
        <div style="color:cadetblue">(<?php echo $match["starter_value"] - 10 >= 10 ? $match["starter_value"] - 10 : 10; ?> - <?php echo $match["starter_value"] + 10; ?>)</div>
    <?php else : ?>
        <div class="coin <?php echo $match["winner_side"] == 0 ? "red" : "blue"; ?>">
            <div class='blue'>
                <img src="./img/dog.png" loading="lazy">
            </div>
            <div class='red'>
                <img src="./img/gem.png" loading="lazy">
            </div>
        </div>
        <img style="border-radius:50%;" class="hidden" src="<?php echo $match["winner_side"] == 0 ? "./img/gem.png" : "./img/dog.png"; ?>" width="80px" height="80px" loading="lazy">
    <?php endif; ?>
</div>
</div>
<?php endforeach; ?>
<?php
if ($session) {
    $matches = getGames($session["user_id"], true);
} else {
    $matches = [];
}
foreach ($matches as $match) :
?>
    <div id='game<?php echo $match["game_id"]; ?>' class="mymatch row hidden" style="justify-content:space-around;">
        <div style="display:flex;flex-direction:column;gap:10px;align-items:center;width:calc(100% - 100px);">
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;justify-content:space-between;width:100%;">
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <img src="<?php echo $match["starter_side"] == 0 ? "./img/gem.png" : "./img/dog.png"; ?>" alt="<?php echo $match["starter_side"] == 0 ? "Gem" : "Dog"; ?>" width="32px" height="32px" loading="lazy">
                    <img class="userthumb" src="<?php echo getUserThumbnail($match["starter_id"]); ?>" width="32px" height="32px" loading="lazy">
                    <div style="font-size:24px;"><?php echo getName($match["starter_id"]); ?></div>
                    <?php
                    foreach (json_decode($match["starter_items"], true) as $item) :
                    ?>
                        <img src="<?php echo getItemInfo($item["item_id"])["item_image"] ?>" width="32px" height="32px" loading="lazy" loading="lazy">
                    <?php endforeach; ?>
                </div>
                <?php if ($match["end_date"]) : ?> <div style="font-size:24px;">Value: <?php echo $match["starter_value"]; ?></div> <?php endif; ?>
            </div>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;justify-content:space-between;width:100%;">
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <img src="<?php echo $match["starter_side"] == 1 ? "./img/gem.png" : "./img/dog.png"; ?>" alt="<?php echo $match["starter_side"] == 1 ? "Gem" : "Dog"; ?>" width="32px" height="32px" loading="lazy">
                    <?php if ($match["end_date"]) : ?>
                        <img class="userthumb" src="<?php echo getUserThumbnail($match["player_id"]); ?>" width="32px" height="32px" loading="lazy">
                        <div style="font-size:24px;"><?php echo getName($match["player_id"]); ?></div>
                        <?php
                        $player_items = json_decode($match["player_items"], true);
                        if (!$player_items) {
                            $player_items = [];
                        }
                        foreach ($player_items as $item) :
                        ?>
                            <img src="<?php echo getItemInfo($item["item_id"])["item_image"] ?>" width="32px" height="32px" loading="lazy">
                        <?php endforeach; ?>
                </div>
                <div style="font-size:24px;">Value: <?php echo $match["player_value"]; ?></div>
            </div>
        <?php else : ?>
            <?php if (!$session) : ?>
                <button onclick="login()" class="btn-primary">Join Match (<?php echo $match["starter_value"] - 10 ?> - <?php echo $match["starter_value"] + 10 ?>)</button>
            <?php elseif ($match["starter_id"] != $session["user_id"]) : ?>
                <button onclick='joinMatch(<?php echo $match["game_id"] . "," . $match["starter_value"]; ?>)' class="btn-primary">Join Match (<?php echo $match["starter_value"] - 10 ?> - <?php echo $match["starter_value"] + 10 ?>)</button>
            <?php else : ?>
                <button onclick='cancelMatch(<?php echo $match["game_id"]; ?>)' class="btn-primary">Cancel Match</button>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
</div>
<div style="display:flex;flex-direction:column;text-align:center;padding-right:10px;">
    <?php if (!$match["end_date"]) : ?>
        <h2>Value <br><?php echo $match["starter_value"]; ?></h2>
        <div style="color:cadetblue">(<?php echo $match["starter_value"] - 10; ?> - <?php echo $match["starter_value"] + 10; ?>)</div>
    <?php else : ?>
        <div class="coin <?php echo $match["winner_side"] == 0 ? "red" : "blue"; ?>">
            <div class='blue'>
                <img src="./img/dog.png" loading="lazy">
            </div>
            <div class='red'>
                <img src="./img/gem.png" loading="lazy">
            </div>
        </div>
        <img style="border-radius:50%;" class="hidden" src="<?php echo $match["winner_side"] == 0 ? "./img/gem.png" : "./img/dog.png"; ?>" width="80px" height="80px" loading="lazy">
    <?php endif; ?>
</div>
</div>
<?php endforeach; ?>
</div>

<script>
    value = 0
    minval = 0
    maxval = 0
    items = []
    publicmatches = true;
    gamesavail = true;

    function toggleMatches() {
        $("#matchbtn").text($("#matchbtn").text() == "Active Matches" ? "My Matches" : "Active Matches");
        $(".publicmatch").toggleClass("hidden");
        $(".mymatch").toggleClass("hidden");
        publicmatches = !publicmatches;
    }

    function cancelMatchOK(game_id) {
        Swal.fire({
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false
        })
        Swal.showLoading()
        $.ajax({
            url: "./php/game",
            type: "POST",
            data: {
                type: "cancel",
                game_id: game_id
            },
            success: function(data) {
                data = JSON.parse(data)
                Swal.close()
                if (data.Error) {
                    Swal.fire({
                        title: "Error",
                        text: data.Error,
                        icon: "error",
                        confirmButtonText: "OK"
                    })
                } else {
                    Swal.fire({
                        title: 'Cancel Successful',
                        text: 'Game has been Successfully Cancelled',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(function() {
                        window.location.reload()
                    })
                }
            }
        });
    }

    function cancelMatch(game_id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to cancel this game!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, cancel it!'
        }).then((result) => {
            if (result.value) {
                cancelMatchOK(game_id)
            }
        })
    }

    function createMatchOK(side, items) {
        Swal.fire({
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false
        })
        Swal.showLoading()
        $.ajax({
            url: "./php/game",
            type: "POST",
            data: {
                type: "create",
                side: side,
                item_ids: JSON.stringify(items)
            },
            success: function(data) {
                data = JSON.parse(data)
                Swal.close()
                if (data.Error) {
                    Swal.fire({
                        title: "Error",
                        text: data.Error,
                        icon: "error",
                        confirmButtonText: "OK"
                    })
                } else {
                    Swal.fire({
                        title: 'Match Created',
                        text: 'Game has been Successfully Created',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(function() {
                        window.location.reload()
                    })
                }
            }
        });
    }


    function joinMatchOK(game_id, items) {
        Swal.fire({
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false
        })
        Swal.showLoading()
        $.ajax({
            url: "./php/game",
            type: "POST",
            data: {
                type: "play",
                game_id: game_id,
                item_ids: JSON.stringify(items)
            },
            success: function(data) {
                data = JSON.parse(data)
                if (data.Error) {
                    Swal.close()
                    Swal.fire({
                        title: "Error",
                        text: data.Error,
                        icon: "error",
                        confirmButtonText: "OK"
                    })
                }
            }
        });
    }
    
    function joinGiveaway(id) {
        Swal.fire({
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false
        })
        Swal.showLoading()
        $.ajax({
            url: "./php/giveaway",
            type: "POST",
            data: {
                type: "join",
                giveaway_id: id,
            },
            success: function(data) {
                data = JSON.parse(data)
                Swal.close()
                if (data.Error) {
                    Swal.fire({
                        title: "Error",
                        text: data.Error,
                        icon: "error",
                        confirmButtonText: "OK"
                    })
                } else {
                    Swal.fire({
                        title: 'Match Created',
                        text: 'You have joined this giveaway!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(function() {
                        window.location.reload()
                    })
                }
            }
        });
    }

    function createMatch() {
        value = 0
        minval = 10
        maxval = 0
        items = []
        togglePopup('selitem')
        $("#valheader").text("(Value must be greater than 10)")
        $("#valdiv").text("Value: 0")
        $("#okbtn").attr("onclick", "createMatchside()")
        $("#okbtn").text("Choose Side")
        $("#okbtn").attr("disabled", true)
        $(".selectionbtn").each(function() {
            $(this).removeClass("btn-primary")
            $(this).addClass("btn-secondary")
            $(this).text("Select")
        })
    }

    function joinMatch(game_id, val) {
        value = 0
        minval = val - 10
        if (minval < 10) {
            minval = 10;
        }
        maxval = val + 10
        items = []
        togglePopup('selitem')
        $("#valheader").text("(Value must be between " + minval + " and " + maxval + ")")
        $("#valdiv").text("Value: 0")
        $("#okbtn").attr("onclick", "joinMatchconf(" + game_id + ")")
        $("#okbtn").text("Join Match")
        $("#okbtn").attr("disabled", true)
        $(".selectionbtn").each(function() {
            $(this).removeClass("btn-primary")
            $(this).addClass("btn-secondary")
            $(this).text("Select")
        })
    }

    function joinMatchconf(game_id) {
        togglePopup('selitem')
        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to join this game!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, join it!'
        }).then((result) => {
            if (result.value) {
                joinMatchOK(game_id, items)
            }
        })
    }

    function addItem(invid, val) {
        if (items.includes(invid)) {
            items.splice(items.indexOf(invid), 1)
            value = value - val
            $("#item" + invid + " .selectionbtn").each(function() {
                $(this).removeClass("btn-primary")
                $(this).addClass("btn-secondary")
                $(this).text("Select")
            })
        } else {
            if (items.length >= <?php echo $maxGameItems ?>) {
                Swal.fire("Error", "You can bet maximum of <?php echo $maxGameItems ?> items!", "error")
                return;
            }
            items.push(invid)
            value = value + val
            $("#item" + invid + " .selectionbtn").each(function() {
                $(this).removeClass("btn-secondary")
                $(this).addClass("btn-primary")
                $(this).text("Unselect")
            })
        }
        $("#valdiv").text("Value: " + value)
        if ((value >= minval || minval == 0) && (value <= maxval || maxval == 0)) {
            $("#okbtn").attr("disabled", false)
        } else {
            $("#okbtn").attr("disabled", true)
        }
    }

    function createMatchside() {
        togglePopup('selitem')
        Swal.fire({
            title: 'Choose a Side',
            html: `<div style='display:flex;justify-content:space-around;flex-wrap:wrap;gap:10px;'>
                    <button onclick='createMatchOK(0,items)' class="btn-primary"><img src='./img/gem.png' width='100px' height='100px'>Red</button>
                    <button onclick='createMatchOK(1,items)' class="btn-primary"><img src='./img/dog.png' width='100px' height='100px'>Blue</button>
                </div>`,
            showCancelButton: true,
            showConfirmButton: false,
        })
    }
</script>

<?php include "layout/foot.php"; ?>
