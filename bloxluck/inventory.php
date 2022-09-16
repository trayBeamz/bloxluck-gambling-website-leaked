<?php
//Disable Including the File
if (get_included_files()[0] != __FILE__) {
    return;
}
?>
<?php include_once "layout/head.php"; ?>
<?php if ($session) : ?>

    <?php include_once "php/inventory_handler.php" ?>

    <div class="section">
        <h2 style="margin-top:-10px;">
            <i>MM2</i>
            <button onclick="deposit()" class="btn-primary mobile" style="aspect-ratio:1/1; padding:5px;">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" id="footer-sample-full" width="24" height="24" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24" class="iconify iconify--akar-icons">
                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M12 20v-8m0 0V4m0 8h8m-8 0H4"></path>
                </svg>
            </button>
        </h2>
        <?php
        $inventory = getInventory($session["user_id"]);
        foreach ($inventory as $inventory_id => $item_info) : ?>

            <div class="row">
                <div style="display:flex;gap:10px;align-items:center;">
                    <img src="<?php echo $item_info['item_image'] ?>" style="width:7em;aspect-ratio:1/1;" loading="lazy">
                    <div style="display:flex;flex-direction:column;align-items:center;flex: 0 0 auto;">
                        <span style="font-size:1.5em;font-style:italic;font-weight:bold;"><?php echo $item_info['display_name'] ?></span>
                        <span style="font-size:1.2em;color:gold;margin-top:-0.2em;"><?php echo $item_info['item_value'] ? $item_info['item_value'] . " Value" : "Pending Valuation"; ?></span>
                    </div>
                </div>
                <div style="display:flex;gap:10px;align-items:center;">
                    <?php if (!$item_info["locked"]) : ?>
                        <button onclick="withdraw(<?php echo $item_info['inventory_id'] . "," . ($item_info['item_value'] ? $item_info['item_value'] : 0) ?>)" class="btn-primary"> Withdraw </button>
                        <button onclick="giveaway(<?php echo $item_info['inventory_id'] ?>)" class="btn-secondary"> Giveaway </button>
                    <?php else : ?>
                        <button class="btn-primary" disabled> Locked </button>
                        <button class="btn-secondary" disabled> Locked </button>
                    <?php endif; ?>
                </div>
            </div>

        <?php endforeach; ?>
    </div>
    <div class="section">
        <h2 style="margin-top:-10px;"><i>History</i></h2>
        <?php
        $history = getHistoryDescending($session["user_id"]);
        foreach ($history as $history_id => $history_info) :
        ?>
            <font style="font-size:1.2em;font-style:italic;font-weight:bold;"><?php echo date_format(date_create($history_info["date"]), "j/n/y g:i a") ?> - <?php echo json_decode($history_info['data'], true)["text"] ?></font>
        <?php endforeach; ?>
    </div>
    <script>
        botlink = "https://www.roblox.com/games/142823291?privateServerLinkCode=82881199126810239405676414117357"
        botusername = "Private Server"

        function withdrawok(inventory_id, robux) {
            if (robux) {
                $.ajax({
                    url: "php/withdraw",
                    type: "POST",
                    data: {
                        inventory_id: inventory_id,
                        robuxwithdraw: true
                    },
                    success: function(data) {
                        data = JSON.parse(data)
                        if (data.Error) {
                            Swal.fire({
                                title: "Error",
                                text: data.Error,
                                icon: "error",
                                confirmButtonText: "OK"
                            })
                        } else {
                            Swal.fire({
                                title: 'Sell Successful',
                                html: 'You have sold this item! Click on Checkout on the Inventory Page to recieve your robux!',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(function() {
                                window.location.reload()
                            })
                        }
                    }
                })
            } else {
                $.ajax({
                    url: "php/withdraw",
                    type: "POST",
                    data: {
                        inventory_id: inventory_id
                    },
                    success: function(data) {
                        data = JSON.parse(data)
                        if (data.Error) {
                            Swal.fire({
                                title: "Error",
                                text: data.Error,
                                icon: "error",
                                confirmButtonText: "OK"
                            })
                        } else {
                            Swal.fire({
                                title: 'Withdraw Successful',
                                html: 'You have withdrawn this item! Visit our Bot MM2HolderBot in this (<a href="' + botlink + '">' + botusername + "</a>), and trade them, to withdraw your items!",
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(function() {
                                window.location.reload()
                            })
                        }
                    }
                });
            }
        }

        function withdraw(inventory_id, value) {
            if (value < 1000) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You are about to withdraw this item!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, withdraw it!'
                }).then((result) => {
                    if (result.value) {
                        withdrawok(inventory_id, false)
                    }
                })
            } else {
                Swal.fire({
                    title: 'Do you want to Withdraw or Sell?',
                    icon: 'question',
                    showDenyButton: true,
                    denyButtonText: "Sell for Robux",
                    confirmButtonText: "Withdraw"
                }).then((result) => {
                    if (result.isDenied) {
                        Swal.fire({
                            title: "Feature Under Construction",
                            icon: "error"
                        })
                        /*
                        Swal.fire({
                            title: 'Are you sure?',
                            text: "You are about to sell this item, at a rate of 0.6 robux per value!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, sell it!'
                        }).then((result) => {
                            if (result.value) {
                                withdrawok(inventory_id,true)
                            }
                        })
                        */
                    } else if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Are you sure?',
                            text: "You are about to withdraw this item!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, withdraw it!'
                        }).then((result) => {
                            if (result.value) {
                                withdrawok(inventory_id, false)
                            }
                        })
                    }
                })
            }
        }

        function deposit() {
            Swal.fire("WARNING - Please READ! Fake bots with similar usernames to the real one are popping up in our private servers. ALWAYS make sure you send it to the correct user. Some of these bots may have very similar usernames so make sure to look closely! Stay safe out there - BloxLuck<br><br>Visit our Bot MM2HolderBot in this (<a href='" + botlink + "'>" + botusername + "</a>), and trade them, to deposit your items!")
        }

        function giveaway(inventory_id) {
            Swal.fire({
                title: "Are You Sure?",
                text: "This decision is irreversible. Your item will be removed from your inventory.",
                icon: "question",
                confirmButtonText: "Yes",
                showDenyButton: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "php/giveaway",
                        type: "POST",
                        data: {
                            type: "create",
                            item_id: inventory_id
                        },
                        success: function(data) {
                            data = JSON.parse(data)
                            if (data.Error) {
                                Swal.fire({
                                    title: "Error",
                                    text: data.Error,
                                    icon: "error",
                                    confirmButtonText: "OK"
                                })
                            } else {
                                Swal.fire({
                                    title: 'Giveaway Successful',
                                    html: 'New Giveaway Created!',
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(function() {
                                    window.location.reload()
                                })
                            }
                        }
                    });
                }
            })
        }
    </script>
<?php else : ?>

    <center>
        <h1>You are not Logged in. You are being redirected to the home page.</h1>
    </center>
    <script>
        setTimeout(function() {
            window.location.href = "./";
        }, 3000);
    </script>

<?php endif; ?>
<?php include_once "layout/foot.php"; ?>