<?php
namespace DDCloud;

class Consul {

	private $sf;
	private $cache = null;
	/* for register service
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
	    "Args": ["/usr/local/bin/check_redis.py"],
	    "HTTP": "http://localhost:5000/health",
	    "Interval": "10s",
	    "TTL": "15s"
	  },
	  "Weights": {
	    "Passing": 10,
	    "Warning": 1
	  }
	}
	*/


	function __construct($uri="", CacheInterface $cache=null) {
		if ($uri == "") {
			$uri = "10.255.242.227:8500";
		}
		$this->sf = new \SensioLabs\Consul\ServiceFactory(["base_uri" => $uri]);
		$this->cache = $cache;
	}

	public function getHealth() {
		return $this->sf->get(\SensioLabs\Consul\Services\HealthInterface::class);
	}

	public function getAgent() {
		return $this->sf->get(\SensioLabs\Consul\Services\AgentInterface::class);
	}

	/**
	 * 随机返回一个可用的service
	 * @param $serviceName
	 * @return string
	 */
	public function getService($serviceName) {
		if ($this->cache) {
			$serviceArr = $this->cache->get($serviceName);
			if ($serviceArr) {
				return $serviceArr[mt_rand(0, count($serviceArr) - 1)];
			}
		}
		$res = $this->getHealth()->service($serviceName, ["passing" => 1]);
		$nodeArr = json_decode($res->getBody(), true);

		$serviceArr = [];
		foreach ($nodeArr as $val) {
			if ($val['Service']['Port']) {
				$serviceArr[] = $val['Service']['Address'].":".$val['Service']['Port'];
			} else {
				$serviceArr[] = $val['Service']['Address'];
			}
		}
		if (count($serviceArr) > 0) {
			if ($this->cache) {
				$this->cache->put($serviceName, $serviceArr, 300);
			}
			// 随机返回一个
			return $serviceArr[mt_rand(0, count($serviceArr) - 1)];
		}

		return "";
	}

	/**
	 *
	 * 注册一个服务
	 * @param $service
	 *
	 */
	public function registerService($service) {
		$this->getAgent()->registerService($service);
	}
}
