<?php
//Stop Direct Access to the File
//Works only in PHP 5.0 and Up
if (get_included_files()[0] == __FILE__) {
    exit("<h1>Access Denied</h1>");
}

//Stop Including This File Twice
if (defined(strtoupper(basename(__FILE__, ".php")) . "_PHP")) {
    return True;
}
define(strtoupper(basename(__FILE__, ".php")) . "_PHP", True);

include_once "php/main.php"
?>
</div>
<div id='popupblur' class="blur hidden"></div>
<div id='selitem' class="popup hidden">
    <div style="display:flex;flex-direction:column;align-items:center;">
        <h2> Select Items </h2>
        <div id="valheader" style="color:cadetblue">(Must add up to 210 - 230 Value)</div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;width:calc(100% - 60px);">
        <?php
        if ($session) :
            include_once "php/inventory_handler.php";
            $inventory = getInventory($session["user_id"], false);
            foreach ($inventory as $item) :
        ?>
                <div id="item<?php echo $item["inventory_id"];?>" class='selitemcontainer' style="padding:10px;display:flex;flex-direction:column;align-items:center;gap:10px;background-color:#3e3d42;border-radius:0.25em;width:100px;">
                    <img src="<?php echo $item["item_image"];?>" height="80px" width="80px" loading="lazy">
                    <div style="font-size:20px;font-weight:bold;font-style:italic;"><?php echo $item["display_name"]; ?></div>
                    <div><?php echo $item["item_value"] ?> Value</div>
                    <button class="selectionbtn btn-secondary" onclick="addItem(<?php echo $item['inventory_id'].','.$item['item_value'];?>)">Select</button>
                </div>
        <?php
            endforeach;
        endif; 
        ?>
    </div>
    <div style="display:flex;justify-content:space-between;width:calc(100% - 60px);">
        <h2 id="valdiv">Value: 0</h2>
        <button id='okbtn' class="btn-primary" onclick="createMatchside()">Create Match</button>
    </div>
</div>
<script>
    function login() {
        Swal.fire({
            titleText: "Login",
            text: "Enter your Roblox Username",
            input: "text",
            inputPlaceholder: "Username",
            inputAttributes: {
                autocapitalize: 'off'
            },
            showCancelButton: true,
            confirmButtonText: "Login",
            cancelButtonText: "Cancel",
            showLoaderOnConfirm: true,
            preConfirm: function(username) {
                return new Promise(function(resolve, reject) {
                    $.ajax({
                        url: "php/login",
                        type: "POST",
                        data: {
                            username: username
                        },
                        success: function(data) {
                            if (data == "success") {
                                resolve()
                                setTimeout(() => {
                                    window.location.reload(true);
                                });
                            } else {
                                reject(data)
                            }
                        }
                    })
                }).catch(function(error) {
                    Swal.showValidationMessage(
                        `<div>Request failed: ${error}</div>`
                    )
                })
            }
        })
    }

    function logOut() {
        Swal.fire({
            titleText: "Logout",
            text: "Are you sure you want to logout?",
            showCancelButton: true,
            confirmButtonText: "Logout",
            cancelButtonText: "Cancel",
            showLoaderOnConfirm: true,
            preConfirm: function() {
                return new Promise(function(resolve, reject) {
                    $.ajax({
                        url: "php/logout",
                        type: "POST",
                        success: function(data) {
                            if (data == "success") {
                                resolve()
                                setTimeout(() => {
                                    window.location.reload(true);
                                });
                            } else {
                                reject(data)
                            }
                        }
                    })
                }).catch(function(error) {
                    Swal.showValidationMessage(
                        `<div>Request failed: ${error}</div>`
                    )
                })
            }
        })
    }
</script>
<script>
    var socket = io("https://<?php echo $socketurl; ?>");

    socket.on("chat message", function(msg) {
        var displayname = msg.displayname
        var userid = msg.userid
        var rank = msg.rank
        var thumbnail = msg.thumbnail
        var msg = msg.message
        var element = $('<div class="chatline">')
        element.append($('<img class="userthumb" src="' + thumbnail + '">'))
        var text = $('<div>')
        text.append($(`<div class='name ${rank == 3 ? "server":rank==2 ? "owner": rank==1 ? "mod": ""}'>` + displayname + "</div>"))
        text.append($("<div class='msg'></div>"))
        text.children(".msg").text(msg)
        element.append(text)
        $('#chatcontainer').append(element);
    });

    <?php if ($session) : ?>
        $("#chatform").submit(function() {
            if (socket.connected) {
                var msg = $("#chatmsg").val();
                if (msg != "") {
                    if (msg.length > 100) {
                        Swal.fire({
                            titleText: "Error",
                            text: "Message should be at max 100 Characters",
                            icon: "error"
                        })
                    } else {
                        socket.emit("chat message", msg);
                        $("#chatmsg").val("");
                    }
                } else {
                    Swal.fire({
                        titleText: "Error",
                        text: "You cannot send an empty message",
                        icon: "error"
                    })
                }
            } else {
                Swal.fire({
                    titleText: "Error",
                    text: "You are not connected to the chat",
                    icon: "error"
                })
            }
            return false;
        });
    <?php else : ?>
        $("#chatform").submit(function() {
            if (socket.connected) {
                Swal.fire({
                    titleText: "Error",
                    text: "You need to log in to send messages",
                    icon: "error"
                })
            } else {
                Swal.fire({
                    titleText: "Error",
                    text: "You are not connected to the chat",
                    icon: "error"
                })
            }
            return false;
        });
    <?php endif; ?>

    socket.on("connect", function() {
        socket.emit("count users")
        $('#chatcontainer').html("");
        //Logged In Only
        <?php if ($session) : ?>
            socket.emit("authenticate", "<?php echo getCookie(); ?>")
        <?php endif; ?>
    })

    socket.on("count users", function(data) {
        $('#chatindicator circle').attr("fill", "lime")
        $('#chatcount').text(data + " Online");
    });
    
    socket.on("delete game",function(data) {
        console.log("delete game",data)
        $("#game"+data).remove();
    })
    
    socket.on("new game",function(data) {
        if (typeof gamesavail !== 'undefined' && gamesavail) {
            console.log("new game",data)
            $.ajax({
                url: "./php/game",
                type: "POST",
                data: {
                    type: "gethtml",
                    game_id: data
                },
                success: function(data) {
                    if (data) {
                        data = $(data)
                        if ((data.hasClass('publicmatch') && !publicmatches) || (data.hasClass('mymatch') && publicmatches)) {
                            data.addClass('hidden')
                        }
                        data.insertAfter('#gamesheader')
                    }
                }
            });
        }
    })
    
    socket.on("new giveaway",function(data) {
        if (typeof gamesavail !== 'undefined' && gamesavail) {
            console.log("new giveaway",data)
            $.ajax({
                url: "./php/giveaway",
                type: "POST",
                data: {
                    type: "gethtml",
                    giveaway_id: data
                },
                success: function(data) {
                    if (data) {
                        data = $(data)
                        data.insertBefore('#gamesheader')
                    }
                }
            });
        }
    })
    
    socket.on("update giveaway joined",function(data) {
        if (typeof gamesavail !== 'undefined' && gamesavail) {
            console.log("update giveaway joined",data)
            var numplayers = data[0]
            var giveaway_id = data[1]
            $("#giveaway"+giveaway_id+" .numplayers").text(numplayers+" Joined")
        }
    })
    
    socket.on("giveaway ended",function(data) {
        if (typeof gamesavail !== 'undefined' && gamesavail) {
            console.log("giveaway ended",data)
            var winner = data[0]
            var giveaway_id = data[1]
            $("#giveaway"+giveaway_id+" .numplayers").text(winner+" Won")
            setTimeout(()=>{
                $("#game"+data["giveaway_id"]).remove();
            },30000);
        }
    })
    
    socket.on("giveaway win",function(item) {
        Swal.fire({
            title: "You Won a Giveaway!",
            text: "You Won "+item,
            icon: "success"
        }).then(()=>{
            window.location.reload();
        })
    })
    
    socket.on("item refund",function(item) {
        Swal.fire({
            title: "No one joined your Giveaway!",
            text: "You have been refunded "+item,
            icon: "info"
        }).then(()=>{
            window.location.reload();
        })
    })
    
    socket.on("update game",function(data) {
        if (typeof gamesavail !== 'undefined' && gamesavail) {
            console.log("update game",data)
            gameid = data
            $.ajax({
                url: "./php/game",
                type: "POST",
                data: {
                    type: "gethtml",
                    game_id: data
                },
                success: function(data) {
                    if (data) {
                        data = $(data)
                        if ((data.hasClass('publicmatch') && !publicmatches) || (data.hasClass('mymatch') && publicmatches)) {
                            data.addClass('hidden')
                        }
                        $("#game"+gameid).replaceWith(data);
                    }
                }
            });
        }
    })
    
    socket.on("game played",function(data) {
        console.log("game played",data["game_id"]);
        currentUser = <?php echo $session?$session["user_id"]:"false";?>;
        /*$("#game"+data["game_id"]+" .coin").addClass(`flip${data['winner_side']?'blue':'red'}`)
        $("#game"+data["game_id"]+" .coin").removeClass(`flip${data['winner_side']?'red':'blue'}`)*/
        if (currentUser && (currentUser == data["starter_id"] || currentUser == data["player_id"])) {
            Swal.close();
            Swal.fire({
                iconHtml:`<div class="coin ${data['winner_side']?'blue':'red'} flip${data['winner_side']?'blue':'red'}" style='width:160px;height:160px;'>
                        <div class='blue'>
                            <img src="./img/dog.png">
                        </div>
                        <div class='red'>
                            <img src="./img/gem.png">
                        </div>
                    </div>`,
                title: "Playing Match",
                showConfirmButton:false,
                background:"#ffffff00",
                allowOutsideClick: false,
                allowEscapeKey: false
            });
            setTimeout(()=>{
                Swal.close();
                if (data[`${data['winner_side']==data['starter_side']?'starter':'player'}_id`] == currentUser) {
                    Swal.fire({
                            title: 'Winner!',
                            text: 'You Won This Game.',
                            icon: 'success',
                            confirmButtonText: 'Continue'
                        }).then(function() {
                            window.location.reload()
                        })
                } else {
                    Swal.fire({
                            title: 'Loser :(',
                            text: 'You lost This Game.',
                            icon: 'warning',
                            confirmButtonText: 'Continue'
                        }).then(function() {
                            window.location.reload()
                        })
                }
            },5500)
        } else {
            setTimeout(()=>{
                $("#game"+data["game_id"]).remove();
            },30000);
        }
    });

    socket.on("reload",function() {
        window.location.reload();
    });

    socket.on("disconnect", function() {
        $('#chatindicator circle').attr("fill", "red")
        $('#chatcount').text("Disconnected");
    });
</script>
<script>
    function toggleMenu() {
        $('.rightsidebar').toggleClass("desktop-only");
        $('.leftsidebar').toggleClass("desktop-only");
        $('.main').toggleClass("desktop-only");
    }
</script>
<script>
    function closePopup() {
        $('.popup').addClass("hidden");
        $("#popupblur").addClass("hidden");
    }
    $("#popupblur").click(function() {
        closePopup();
    })

    function togglePopup(id) {
        $('.popup').addClass("hidden");
        $("#popupblur").removeClass("hidden");
        $("#" + id).removeClass("hidden");
    }
</script>
</body>

</html>
