<?php
require_once "./lib/Unirest.php";
$response = Unirest::get(
        "https://leafly.p.mashape.com/api2/dispensary/belltown-community-gardens",
        array(
        "X-Mashape-Authorization" => "E5L8ogBnjoTOMipgCTRRg4TCUE4leZMY"
        ));
$data = $response->body;
print_r($data);
?>
