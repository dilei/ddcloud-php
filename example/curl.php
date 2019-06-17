<?php
require __DIR__ . "/../vendor/autoload.php";

$curl = new DDCloud\Curl4serv("ddframe-promotion-api");
var_dump($curl->get("/promotion/get", ["productIds" => "1,2,3"]));

