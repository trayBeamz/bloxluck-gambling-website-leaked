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

include_once "discord_handler.php";

//Constants
$apiKey = "f42261bc-57b6-4dad-b889-d9f99be2c7e3"; // Your API key
$hashedApiKey = "k7SD4ZgbHxvyWKcBfw4vIp+Uc5OL6/U/Lct+rj7HGZvj/lIzAVCxraovSTBKOCOW3BxFG5j3hBE3vSg1OJuasQ=="; // Your Hashed Api Key
$userandomorg = true; // Set to true to use Random.org for random numbers

function sendPOSTrequest($url, $data,$content_type="application/json",$headers=NULL) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($content_type) {
        if ($headers) {
            $headers[] = "Content-Type: ".$content_type;
        } else {
            $headers = array("Content-Type: ".$content_type);
        }
    }
    if ($headers) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function sendJSONRPC($url,$method, $params, $id=NULL) {
    if (!gettype($params) == "array") {
        return [false,"Params must be an array"];
    }
    if (!gettype($method) == "string") {
        return [false,"Method must be a string"];
    }
    if ($id == null) {
        $id = rand(1,9999);
    }
    if (!gettype($id) == "integer") {
        return [false,"ID must be an integer"];
    }
    if (!gettype($url) == "string") {
        return [false,"URL must be a string"];
    }
    if ($id < 1) {
        return [false,"ID must be greater than 0"];
    }
    $data = array(
        "jsonrpc" => "2.0",
        "method" => $method,
        "params" => $params,
        "id" => $id
    );
    $resp = sendPOSTrequest($url, json_encode($data));
    if ($resp) {
        $resp = json_decode($resp, true);
        if (!$resp) {
            return [false,"Invalid Response"];
        }
        if (isset($resp["error"])) {
            return [false,json_encode($resp["error"])];
        } else {
            return [true,$resp["result"]];
        }
    } else {
        return [false,"No Response"];
    }
}

function sendGETrequest($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function verifySignature($random, $signature) {
    if (gettype($signature) != "string") {
        return [false, "Signature is not a string"];
    }
    if (gettype($random) != "array") {
        return [false, "Random is not an array"];
    }
    $url = "https://api.random.org/json-rpc/4/invoke";
    $result = sendJSONRPC($url, "verifySignature", array(
        "random" => $random,
        "signature"=> $signature
    ));
    if (!$result[0]) {
        return $result;
    }
    if (!array_key_exists("authenticity", $result[1])) {
        return [false, "Invalid Result"];
    }
    return [true, $result[1]["authenticity"]];
}

function createTickets($n,$showResult=true) {
    global $apiKey;
    if (gettype($n) != "integer") {
        return [false, "Number of tickets is not an integer"];
    }
    if ($n < 1) {
        return [false, "Number of tickets is less than 1"];
    }
    if (gettype($showResult) != "boolean") {
        return [false, "Show Result is not a boolean"];
    }
    $url = "https://api.random.org/json-rpc/4/invoke";
    $result = sendJSONRPC($url, "createTickets", array(
        "apiKey" => $apiKey,
        "n" => $n,
        "showResult" => $showResult,
    ));
    return $result;
}

function createTicket($showResult = true) {
    return createTickets(1,$showResult);
}

function getRandomSigned($n, $min, $max,$ticket = null, $replacement = true, $base = 10) {
    global $apiKey;
    global $hashedApiKey;
    $url = "https://api.random.org/json-rpc/4/invoke";
    $result = sendJSONRPC($url, "generateSignedIntegers", array(
        "apiKey" => $apiKey,
        "n" => $n,
        "min" => $min,
        "max" => $max,
        "replacement" => $replacement,
        "base" => $base,
        "ticketId" => $ticket,
        "userData" => "Blox Luck Coinflip Generator. 0 is Red and 1 is Blue."
    ));
    if (!$result[0]) {
        return $result;
    }
    $result = $result[1];
    if (!array_key_exists("signature",$result)) {
        return [false,"No Signature in Response"];
    }
    if (array_key_exists("random",$result)) {
        $verification = verifySignature($result["random"], $result["signature"]);
        if (!$verification[0]) {
            return $verification;
        } elseif (!$verification[1]) {
            return [false, "Invalid Signature"];
        }
        if (array_key_exists("hashedApiKey",$result["random"])) {
            if ($result["random"]["hashedApiKey"] == $hashedApiKey) {
                if (array_key_exists("data",$result["random"])) {
                    if (array_key_exists("ticketData",$result["random"])) {
                        $ticketData = $result["random"]["ticketData"]?$result["random"]["ticketData"]["ticketId"]:null;
                        if (array_key_exists("requestsLeft",$result)) {
                            if (array_key_exists("bitsLeft",$result)) {
                                if (array_key_exists("cost",$result)) {
                                    return [true,["data"=>$result["random"]["data"],"ticketId"=>$ticketData,"requestsLeft"=>$result["requestsLeft"],"bitsLeft"=>$result["bitsLeft"],"cost"=>$result["cost"]]];
                                } else {
                                    return [false, "No Cost in Response"];
                                }
                            } else {
                                return [false, "No Bits Left in Response"];
                            }
                        } else {
                            return [false, "No Requests Left in Response"];
                        }
                    } else {
                        return [false, "No Ticket Data in Response"];
                    }
                } else {
                    return [false,"No data in Response"];
                }
            } else {
                return [false,"Invalid Hashed Api Key"];
            }
        } else {
            return [false,"No Hashed Api Key in Response"];
        }
    } else {
        return [false, "No Random Info in Response"];
    }
}

function getRandomSignedBinary($n, $min, $max,$ticket=null, $replacement = true) {
    return getRandomSigned($n, $min, $max,$ticket, $replacement, 2);
}

function randomCoinFlip() {
    $ticketId = createTicket();
    if (!$ticketId[0]) {
        sendErrorEmbedWebhook("Error Creating Random.Org Ticket",$ticketId[1]);
        return [rand(0, 9999) % 2,NULL];
    }
    $ticketId = $ticketId[1][0]["ticketId"];
    $data = getRandomSignedBinary(1, 0, 1, $ticketId);
    if (!$data[0]) {
        sendErrorEmbedWebhook("Error Getting Random.Org Result From Ticket",$ticketId[1]." - Ticket Id: ".$ticketId);
        return [rand(0, 9999) % 2,NULL];
    } else {
        return [$data[1]["data"][0],$data[1]["ticketId"]];
    }
}
?>