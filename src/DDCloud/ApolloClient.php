<?php
namespace DDCloud;

use \Org\Multilinguals\Apollo\Client\ApolloClient as client;

class ApolloClient {

	protected $appllo;
	protected $cache = null;
	protected $configServer; //apollo服务端地址
	protected $appId; //apollo配置项目的appid
	protected $cluster = 'default';
	protected $clientIp = '127.0.0.1'; //绑定IP做灰度发布用
	protected $pullTimeout = 10; //获取某个namespace配置的请求超时时间

	public function __construct($server, $appId, $namespaces, CacheInterface $cache = null) {
		$this->configServer = $server;
		$this->appId = $appId;
		$namespaces = explode(',', $namespaces);
		$this->apollo = new client($server, $appId, $namespaces);
		if ($cache) {
			$this->cache = $cache;
		}

		$this->save_dir = dirname($_SERVER['SCRIPT_FILENAME']);
		$this->logger = new Logger();
	}

	public function callback() {
		if ($this->cache == null) {
			return;
		}
		define('SAVE_DIR', __DIR__);
		$list = glob(SAVE_DIR.DIRECTORY_SEPARATOR.'apolloConfig.*');
		$apollo = [];
		foreach ($list as $l) {
			$config = require $l;
			if (is_array($config) && isset($config['configurations'])) {
				$apollo = array_merge($apollo, $config['configurations']);
			}
		}
		if (!$apollo) {
			$this->logger->info("Load Apollo Config Failed, no config available");
			return;
		}

		foreach ($apollo as $key => $value) {
			$this->cache->put($key, $value, 300);
		}
	}

	public function start() {
		ini_set('memory_limit','128M');
		$pid = getmypid();
		echo "start [$pid]\n";
		$restart = true; //auto start if failed
		do {
			$error = $this->apollo->start($this->callback());
			if ($error) {
				$this->logger->info('error:'.$error);
			}
		}while($error && $restart);
	}

	private function _getReleaseKey($config_file) {
		$releaseKey = '';
		if (file_exists($config_file)) {
			$last_config = require $config_file;
			is_array($last_config) && isset($last_config['releaseKey']) && $releaseKey = $last_config['releaseKey'];
		}
		return $releaseKey;
	}

	//获取单个namespace的配置文件路径
	public function getConfigFile($namespaceName) {
		return $this->save_dir.DIRECTORY_SEPARATOR.'apolloConfig.'.$namespaceName.'.php';
	}

	public function pullConfig($namespaceName) {
		$base_api = rtrim($this->configServer, '/').'/configs/'.$this->appId.'/'.$this->cluster.'/';
		$api = $base_api.$namespaceName;

		$args = [];
		$args['ip'] = $this->clientIp;
		// $config_file = $this->getConfigFile($namespaceName);
		// $args['releaseKey'] = $this->_getReleaseKey($config_file);

		$api .= '?' . http_build_query($args);

		$ch = curl_init($api);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->pullTimeout);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$body = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if ($httpCode == 200) {
			// $this->logger->info('success:'.$body);
			return json_decode($body, true);
		}elseif ($httpCode != 304) {
			// echo $body ?: $error."\n";
			$this->logger->info('error:'.$error."--".$body);
			return false;
		}
		return true;
	}

	public function get($key, $namespace="application") {
		if ($this->cache != null) {
			if ($this->get($key) == false) {
				$configs = $this->pullConfig($namespace);
				foreach ($configs["configurations"] as $key => $val) {
					$this->cache->set($key, $val, 300);
				}

				return isset($configs["configurations"][$key]) ? $configs["configurations"][$key] : false;
			} else {
				return $this->get($key);
			}
		}

		$configs = $this->pullConfig($namespace);
		return isset($configs["configurations"][$key]) ? $configs["configurations"][$key] : false;
	}
}