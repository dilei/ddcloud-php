<?php
require __DIR__ . "/../vendor/autoload.php";

$consul = new DDCloud\Consul("10.255.242.227:8500");
$service = <<<EOD
	{
	  "ID": "redis1",
	  "Name": "redis",
	  "Tags": [
	    "primary",
	    "v1"
	  ],
	  "Address": "127.0.0.1",
	  "Port": 8000,
	  "Meta": {
	    "redis_version": "4.0"
	  },
	  "EnableTagOverride": false,
	  "Check": {
	    "DeregisterCriticalServiceAfter": "90m",
	    "HTTP": "http://localhost:5000/health",
	    "Interval": "10s"
	  },
	  "Weights": {
	    "Passing": 10,
	    "Warning": 1
	  }
	}
EOD;

// $consul->registerService($service);
var_dump($consul->getService("ddframe-promotion-api"));


/*
$log = new Logger();
$sf = new SensioLabs\Consul\ServiceFactory(["base_uri" => "10.255.242.227:8500"], $log);

$kv = $sf->get(\SensioLabs\Consul\Services\KVInterface::class);
$kv->put('test/foo/bar', 'bazinga');
var_dump($kv->get('test/foo/bar', ['raw' => true]));
$kv->delete('test/foo/bar');
exit();
*/

/*
$agent = $sf->get(\SensioLabs\Consul\Services\AgentInterface::class);
$res = $agent->members();
var_dump($res);
$res = $agent->checks();
$res = $agent->services();
$res = $agent->self();
*/

/*
$health = $sf->get(\SensioLabs\Consul\Services\HealthInterface::class);
$res = $health->service("spdc2", ["passing" => 1]);
$res = $health->service("spdc2");
var_dump(json_decode($res->getBody(), true));
$nodeArr = json_decode($res->getBody(), true);
foreach ($nodeArr as $val) {
    echo $val['Node']['Address']."\n";
}
*/

/*
$catalog = $sf->get(\SensioLabs\Consul\Services\CatalogInterface::class);
$res = $catalog->services();
var_dump(json_decode($res->getBody()));
*/
