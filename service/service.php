<?php

include_once("TimeCard_Backend.php");


$config = "config.json";
$obj = new TimecardBackend($config);
$args = file_get_contents('php://input');
$args = json_decode($args);
$obj->service($args);

?>
