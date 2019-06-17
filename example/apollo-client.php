<?php
/*
require '../vendor/autoload.php';

use Org\Multilinguals\Apollo\Client\ApolloClient;

//specify address of apollo server
$server = "10.255.242.227:8080";

//specify your appid at apollo config server
$appid = 666666;

//specify namespaces of appid at apollo config server
$namespaces = "application";
$namespaces = explode(',', $namespaces);

$apollo = new ApolloClient($server, $appid, $namespaces);

define('SAVE_DIR', __DIR__);

$callback = function() {
	$list = glob(SAVE_DIR.DIRECTORY_SEPARATOR.'apolloConfig.*');
	$apollo = [];
	foreach ($list as $l) {
		$config = require $l;
		if (is_array($config) && isset($config['configurations'])) {
			$apollo = array_merge($apollo, $config['configurations']);
		}
	}
	if (!$apollo) {
		echo 'Load Apollo Config Failed, no config available';
	}

	foreach ($apollo as $item) {


	}
};

ini_set('memory_limit','128M');
$pid = getmypid();
echo "start [$pid]\n";
$restart = true; //auto start if failed
do {
    $error = $apollo->start($callback);
    if ($error) echo('error:'.$error."\n");
}while($error && $restart);
*/
require __DIR__ . "/../vendor/autoload.php";

$client = new DDCloud\ApolloClient("10.255.242.227:8080", 666666, "application");
// $client->start();
// var_dump($client->pullConfig("application"));
// var_dump($client->pullConfig("prod-db.json"));
var_dump($client->get("db.prod"));
