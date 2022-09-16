<?php
if (false and !isset($_GET["lemmein"])) { //Change To True for Maintenance;
    include_once "error.php";
    exit();
}
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

include_once "php/main.php";
include_once "php/session_handler.php";
include_once "php/roblox_handler.php";
include_once "php/giveaway_handler.php";

checkGiveaways()
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Meta Tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="Deposit items and coinflip them on the worlds first MM2 coinflipping site, BloxLuck.">
    <meta name="keywords" content="mm2, bloxluck, blox, luck, murder, mystery, 2, murder mystery 2, gamble, coinflip, items, bet">
    <meta name="theme-color" content="#252525">
    <meta property="og:title" content="BloxLuck - The First MM2 Coinflipping Site" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://bloxluck.com" />
    <meta property="og:image" content="https://bloxluck.com/img/favicon.png" />
    <meta property="og:description" content="Deposit items and coinflip them on the worlds first MM2 coinflipping site, BloxLuck." />

    <!-- Title -->
    <title>BloxLuck - The First MM2 Coinflipping Site</title>

    <!-- Scripts -->
    <script src="js/jquery.min.js"></script>
    <script src="js/sweetalert2.all.min.js"></script>
    <script src="js/textFit.min.js"></script>
    <script src="js/socket.io.min.js"></script>

    <!-- Links -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/sweetalert2-dark.css">
    <link rel="stylesheet" href="css/coin.css">

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
                <!-- Logged In Only -->
                <button onclick='window.location.href = "inventory"' class="btn-primary <?php if ($isMobile) {
                                                                                            echo "mobile";
                                                                                        } ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" id="footer-sample-full" width="1em" height="1em" preserveAspectRatio="xMidYMid meet" viewBox="0 0 1024 1024" class="iconify iconify--simple-line-icons">
                        <g transform="translate(1024 0) scale(-1 1)">
                            <path fill="currentColor" d="M1023.65 290.48c.464-23.664-5.904-78.848-77.84-98.064L223.394 47.794c-52.944 0-96 43.055-96 96v128.704l-32-.08c-52.752.223-95.632 43.15-95.632 95.967v511.808c0 52.945 43.056 96 96 96h832.464c52.945 0 96-43.055 96-96zM191.393 143.793c0-16.72 12.88-30.463 29.216-31.871l706 142.88c.256.128-5.248 17.935-30.88 17.6H191.393zM960.24 880.21c0 17.664-14.336 32-32 32H95.76c-17.664 0-32-14.336-32-32V368.386c0-17.664 14.336-32 32-32h800.064c31.408 0 64.4-10.704 64.4-31.888V880.21h.016zM191.824 560.498c-35.344 0-64 28.656-64 64s28.656 64 64 64s64-28.656 64-64s-28.656-64-64-64z"></path>
                        </g>
                    </svg>
                    <?php
                    if (!$isMobile) {
                        echo "Inventory";
                    }
                    ?>
                </button>
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
            <a href="./" class="item">
                <svg xmlns="http://www.w3.org/2000/svg" width="60px" viewBox="0 0 97.52 103.71">
                    <path xmlns="http://www.w3.org/2000/svg" d="M97.52,0a43.86,43.86,0,0,1-3.09,13.93c-5.75,14.2-21,24.76-26.88,31-3,3.19-6.27,6.1-9.19,9.35a7.81,7.81,0,0,0-2,4.43c-.12,2.39-1.19,3.49-3.27,3.16-7.3-1.16-10.25,4.41-13.81,8.85-1.63,2.05-2.18,5.05-4,6.85A26.92,26.92,0,0,1,27.5,82.7,28.22,28.22,0,0,0,13.68,95.6c-.59,1.13-.39,2.7-.47,4.08-.2,3.43-2.2,4.79-5.38,3.62C3.47,101.7-.61,96,.08,91.5a12,12,0,0,1,3.2-6.09Q18.27,70,33.61,55c6.94-6.77,14.24-13.16,21.26-19.85,5.51-5.25,10.63-10.93,16.29-16A167,167,0,0,1,85,8.52C88.83,5.75,92.81,3.19,97.52,0Z" fill="currentColor" />
                </svg>
                MM2
            </a>
            <!--<a href="#" class="item">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" id="footer-sample-full" width="60" height="60" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24" class="iconify iconify--bxs">
                    <path fill="currentColor" d="M21 5H3a1 1 0 0 0-1 1v4h.893c.996 0 1.92.681 2.08 1.664A2.001 2.001 0 0 1 3 14H2v4a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1v-4h-1a2.001 2.001 0 0 1-1.973-2.336c.16-.983 1.084-1.664 2.08-1.664H22V6a1 1 0 0 0-1-1zM11 17H9v-2h2v2zm0-4H9v-2h2v2zm0-4H9V7h2v2z"></path>
                </svg>
                Codes
            </a>-->
        </div>
        <a href="https://discord.gg/bloxluck" class="item">
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
    <div class="main">