<?php
include_once "php/main.php";
include_once "php/session_handler.php";
include_once "php/roblox_handler.php";

if (isset($_SERVER['REDIRECT_STATUS'])) {
    $code = $_SERVER['REDIRECT_STATUS'];
} else {
    $code = 200;
}
$codes = array(
    "200" => "Under Construction",
    "400" => "Bad Request",
    "401" => "Unauthorized",
    "402" => "Payment Required",
    "403" => "Forbidden",
    "404" => "Not Found",
    "405" => "Method Not Allowed",
    "406" => "Not Acceptable",
    "407" => "Proxy Authentication Required",
    "408" => "Request Timeout",
    "409" => "Conflict",
    "410" => "Gone",
    "411" => "Length Required",
    "412" => "Precondition Failed",
    "413" => "Request Entity Too Large",
    "414" => "Request-URI Too Long",
    "415" => "Unsupported Media Type",
    "416" => "Requested Range Not Satisfiable",
    "417" => "Expectation Failed",
    "418" => "I'm a teapot",
    "422" => "Unprocessable Entity",
    "423" => "Locked",
    "424" => "Method Failure",
    "426" => "Upgrade Required",
    "428" => "Precondition Required",
    "429" => "Too Many Requests",
    "431" => "Request Header Fields Too Large",
    "451" => "Unavailable For Legal Reasons",
    "500" => "Internal Server Error",
    "501" => "Not Implemented",
    "502" => "Bad Gateway",
    "503" => "Service Unavailable",
    "504" => "Gateway Timeout",
    "505" => "HTTP Version Not Supported",
    "506" => "Variant Also Negotiates",
    "507" => "Insufficient Storage",
    "508" => "Loop Detected",
    "510" => "Not Extended",
    "511" => "Network Authentication Required"
);
$source_url = 'http' . ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
if (array_key_exists($code, $codes) && is_numeric($code)) {
    $description = $codes[$code];
} else {
    $description = "Unknown Error";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Meta Tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="theme-color" content="#252525">

    <!-- Title -->
    <title>BloxLuck</title>

    <!-- Scripts -->
    <script src="js/jquery.min.js"></script>
    <script src="js/sweetalert2.all.min.js"></script>
    <script src="js/textFit.min.js"></script>
    <script src="js/socket.io.min.js"></script>

    <!-- Links -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/sweetalert2-dark.css">

    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-YOURTAGHERE"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());
        gtag('config', 'G-YOURTAGHERE');
    </script>
</head>

<body>
    <header>
        <div class="logo">
            <img src="img/favicon.png" alt="BloxLuck">
        </div>
        <nav>
            <button onclick="toggleMenu()" class="btn-primary mobile mobile-only">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" id="footer-sample-full" width="25" height="25" preserveAspectRatio="xMidYMid meet" viewBox="0 0 16 16" class="iconify iconify--bi">
                    <path fill="currentColor" fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"></path>
                </svg>
            </button>
            <?php if ($session) : ?>
                <img onclick="logOut()" class="clickable userthumb" src="<?php echo getUserThumbnail($session["user_id"]) ?>">
            <?php else : ?>
                <!-- Logged Out Only -->
                <button onclick='login()' class="btn-primary <?php if ($isMobile) {
                                                                    echo "mobile";
                                                                } ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" id="footer-sample-full" width="1em" height="1em" preserveAspectRatio="xMidYMid meet" viewBox="0 0 1024 1024" class="iconify iconify--simple-line-icons">
                        <g transform="translate(1024 0) scale(-1 1)">
                            <path fill="currentColor" d="M532.528 661.408c-12.512 12.496-12.513 32.752-.001 45.248c6.256 6.256 14.432 9.376 22.624 9.376s16.368-3.12 22.624-9.376l189.008-194L577.775 318.64c-12.496-12.496-32.752-12.496-45.248 0c-12.512 12.496-12.512 32.752 0 45.248l115.744 115.76H31.839c-17.68 0-32 14.336-32 32s14.32 32 32 32h618.448zM960.159 0h-576c-35.36 0-64.017 28.656-64.017 64v288h64.432V103.024c0-21.376 17.344-38.72 38.72-38.72h496.704c21.408 0 38.72 17.344 38.72 38.72l1.007 818.288c0 21.376-17.311 38.72-38.72 38.72H423.31c-21.376 0-38.72-17.344-38.72-38.72V670.944l-64.432.08V960c0 35.344 28.656 64 64.017 64h576c35.344 0 64-28.656 64-64V64c-.016-35.344-28.672-64-64.016-64z"></path>
                    </svg>
                    <?php
                    if (!$isMobile) {
                        echo "Login";
                    }
                    ?>
                </button>
            <?php endif; ?>
        </nav>
    </header>
    <div class="leftsidebar desktop-only">
        <div class="widthcontainer">
        </div>
        <a href="https://discord.gg/DISCORDLINK" class="item">
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="60" height="60" aria-hidden="true" role="img" id="footer-sample-full" width="1em" height="1em" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24" class="iconify iconify--simple-icons">
                <path fill="currentColor" d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515a.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0a12.64 12.64 0 0 0-.617-1.25a.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057a19.9 19.9 0 0 0 5.993 3.03a.078.078 0 0 0 .084-.028a14.09 14.09 0 0 0 1.226-1.994a.076.076 0 0 0-.041-.106a13.107 13.107 0 0 1-1.872-.892a.077.077 0 0 1-.008-.128a10.2 10.2 0 0 0 .372-.292a.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127a12.299 12.299 0 0 1-1.873.892a.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028a19.839 19.839 0 0 0 6.002-3.03a.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419c0-1.333.956-2.419 2.157-2.419c1.21 0 2.176 1.096 2.157 2.42c0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419c0-1.333.955-2.419 2.157-2.419c1.21 0 2.176 1.096 2.157 2.42c0 1.333-.946 2.418-2.157 2.418Z"></path>
            </svg>
        </a>
    </div>
    <div class="rightsidebar desktop-only">
        <div class="widthcontainer" style="height:50px;font-size:24px;flex-direction:row;justify-content:center;">
            <svg id="chatindicator" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" id="footer-sample-full" width="25" height="25" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24" class="iconify iconify--akar-icons">
                <circle cx="12" cy="12" r="11" fill="red"></circle>
            </svg>
            <p id="chatcount">Offline</p>
        </div>
        <div class="widthcontainer" id="chatcontainer">

        </div>
        <form id='chatform' class="widthcontainer" style="height:50px;gap:10px;flex-direction:row;justify-content:space-between;margin-block-end:0em;">
            <input type="text" class="chatbox" id="chatmsg">
            <button type='submit' class="btn-primary mobile" id='chatbtn' style="padding:10px;">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" id="footer-sample-full" width="25" height="25" preserveAspectRatio="xMidYMid meet" viewBox="0 0 16 16" class="iconify iconify--bi">
                    <path fill="currentColor" d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576L6.636 10.07Zm6.787-8.201L1.591 6.602l4.339 2.76l7.494-7.493Z"></path>
                </svg>
            </button>
        </form>
    </div>
    <div class="main mobile-only" style="display:flex;align-items:center;justify-content:center;position:absolute;width:auto;left:10px;right:10px;bottom:0;text-align:center;">
        <?php echo $code != 200 ? $code . "<br>" . $description : "Website Under Maintenance, we'll be back in a few hours / discord.gg/bloxluck"; ?>
    </div>
    <div class="main desktop-only" style="display:flex;align-items:center;justify-content:center;text-align:center;">
        <?php echo $code != 200 ? $code . "<br>" . $description : "Website Under Maintenance, we'll be back in a few hours / discord.gg/bloxluck"; ?>
    </div>
    <script>
        textFit(document.getElementsByClassName('main'), {
            multiLine: true
        });

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
        var socket = io("https://socket.jaybz.repl.co");

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
            text.append($("<div class='msg'>" + msg + "</div>"))
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
            $(".blur").addClass("hidden");
        }
        $(".blur").click(function() {
            closePopup();
        })

        function togglePopup(id) {
            $('.popup').addClass("hidden");
            $(".blur").removeClass("hidden");
            $("#" + id).removeClass("hidden");
        }
    </script>
</body>
</html>