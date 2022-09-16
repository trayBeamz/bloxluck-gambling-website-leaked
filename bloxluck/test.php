<?php
include "php/session_handler.php";
if ($session and $session["rank"] == 3) {
    include "php/socket_handler.php";
    sendUser(613993127, "reload",false);
}
?>