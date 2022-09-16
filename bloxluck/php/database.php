<?php
//Stop Direct Access to the File
//Works only in PHP 5.0 and Up
if (get_included_files()[0] == __FILE__) {http_response_code(403);die('Forbidden');}

//Stop Including This File Twice
if (defined(strtoupper(basename(__FILE__,".php"))."_PHP")) {return True;}
define(strtoupper(basename(__FILE__,".php"))."_PHP", True);

/**
* Custom {@link \mysqli} class with additional functions.
*/
class MySQLConn extends \mysqli
{
    /**
     * Creates a prepared query, binds the given parameters and returns the result of the executed
     * {@link \mysqli_stmt}.
     * @param string $query
     * @param array $args
     * @return bool|\mysqli_result
     */
    public function queryPrepared($query, array $args=[],$getId=false)
    {
        $stmt   = $this->prepare($query);
        $params = [];
        $types  = array_reduce($args, function ($string, $arg) use (&$params) {
            $params[] = $arg;
            if (is_float($arg))         $string .= 'd';
            elseif (is_integer($arg))   $string .= 'i';
            elseif (is_string($arg))    $string .= 's';
            else                        $string .= 'b';
            return $string;
        }, '');
        array_unshift($params, $types);
        $stmt->bind_param(...$params);

        $result = $stmt->execute() ? $stmt->get_result() : false;
        if ($getId) {
            $result = [$stmt->insert_id,$result];
        }

        $stmt->close();

        return $result;
    }
}


//Database Credentials
$db_host = "localhost";
$db_user = "bloxebiq_rbxuser";
$db_pass = "8qMl*L,6Nd.e";
$db_name = "bloxebiq_rbxdata"; //Set to "" if you want to use a default database (if set)
$db_port = 2083; //Set to NULL if you want to use default port

//Connect To MySQL Database
$conn = new MySQLConn($db_host, $db_user, $db_pass, $db_name, $db_port);

//Display Error if Failed to Connect to MySQL Database
if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}

//Unset the Database Credentials
unset($db_host, $db_user, $db_pass, $db_name);

//Initialize User Table in Database
$conn->query("CREATE TABLE IF NOT EXISTS `user` (
    `user_id` bigint NOT NULL,
    `username` varchar(255) NOT NULL,
    `display_name` varchar(255) NULL DEFAULT NULL,
    `description` varchar(255) NULL DEFAULT NULL,
    `thumbnail` varchar(255) NULL DEFAULT NULL,
    `last_activity` datetime NULL,
    `rank` int NOT NULL DEFAULT '0',
    PRIMARY KEY (`user_id`)
)");

//Initialize Session Table in Database
$conn->query("CREATE TABLE IF NOT EXISTS `session` (
    `session_id` varchar(255) NOT NULL,
    `user_id` bigint NOT NULL,
    `last_activity` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ip_address` varchar(255) NOT NULL,
    `user_agent` text NOT NULL,
    PRIMARY KEY (`session_id`),
    FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
)");

//Initialize Item Dictionary Table in Database
$conn->query("CREATE TABLE IF NOT EXISTS `item_dictionary` (
    `item_id` int NOT NULL AUTO_INCREMENT,
    `item_name` varchar(255) NOT NULL,
    `display_name` varchar(255) NOT NULL,
    `item_value` int NULL DEFAULT NULL,
    `item_image` varchar(255) NULL DEFAULT NULL,
    `game` varchar(255) NULL DEFAULT NULL,
    PRIMARY KEY (`item_id`)
)");

//Initialize Inventory Table in Database
$conn->query("CREATE TABLE IF NOT EXISTS `inventory` (
    `inventory_id` int NOT NULL AUTO_INCREMENT,
    `user_id` bigint NOT NULL,
    `item_id` int NOT NULL,
    `locked` bool NOT NULL DEFAULT '0',
    PRIMARY KEY (`inventory_id`),
    FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`item_id`) REFERENCES `item_dictionary` (`item_id`) ON DELETE CASCADE
)");

//Initialize Withdraws Table in Database
$conn->query("CREATE TABLE IF NOT EXISTS `withdraws` (
    `withdraw_id` int NOT NULL AUTO_INCREMENT,
    `user_id` bigint NOT NULL UNIQUE,
    `item_id` int NOT NULL,
    PRIMARY KEY (`withdraw_id`),
    FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`item_id`) REFERENCES `item_dictionary` (`item_id`) ON DELETE CASCADE
)");

//Initialize History Table in Database
$conn->query("CREATE TABLE IF NOT EXISTS `history` (
    `history_id` int NOT NULL AUTO_INCREMENT,
    `user_id` bigint NOT NULL,
    `data` JSON NOT NULL,
    `item_id` int NULL DEFAULT NULL,
    `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`history_id`),
    FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`item_id`) REFERENCES `item_dictionary` (`item_id`) ON DELETE CASCADE
)");

//Initialize Games Table
$conn->query("CREATE TABLE IF NOT EXISTS `games` (
    `game_id` int NOT NULL AUTO_INCREMENT,
    `starter_id` bigint NOT NULL,
    `starter_side` bool NOT NULL DEFAULT '0',
    `starter_items` JSON NOT NULL,
    `starter_value` int NOT NULL,
    `player_id` bigint NULL DEFAULT NULL,
    `player_items` JSON NULL DEFAULT NULL,
    `player_value` int NULL DEFAULT NULL,
    `winner_side` bool NULL DEFAULT NULL,
    `start_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `end_date` datetime NULL DEFAULT NULL,
    PRIMARY KEY (`game_id`),
    FOREIGN KEY (`starter_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`player_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
)");

//Initialize Giveaways Table
$conn->query("CREATE TABLE IF NOT EXISTS `giveaways` (
    `giveaway_id` int NOT NULL AUTO_INCREMENT,
    `user_id` bigint NOT NULL,
    `item_id` int NOT NULL,
    `startdate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `enddate` datetime NOT NULL,
    `winner` bigint NULL DEFAULT NULL,
    PRIMARY KEY (`giveaway_id`),
    FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`item_id`) REFERENCES `item_dictionary` (`item_id`) ON DELETE CASCADE,
    FOREIGN KEY (`winner`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
)");

//Initialize Giveaways Participants Table
$conn->query("CREATE TABLE IF NOT EXISTS `giveaways_participants` (
    `giveaway_id` int NOT NULL,
    `user_id` bigint NOT NULL,
    PRIMARY KEY (`giveaway_id`, `user_id`),
    FOREIGN KEY (`giveaway_id`) REFERENCES `giveaways` (`giveaway_id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
)");

//Initialize Robux Withdraws Table in Database
$conn->query("CREATE TABLE IF NOT EXISTS `robux_withdraws` (
    `withdraw_id` int NOT NULL AUTO_INCREMENT,
    `user_id` bigint NOT NULL UNIQUE,
    `item_id` int NOT NULL,
    `completed` bool NOT NULL DEFAULT 0,
    PRIMARY KEY (`withdraw_id`),
    FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`item_id`) REFERENCES `item_dictionary` (`item_id`) ON DELETE CASCADE
)");
?>