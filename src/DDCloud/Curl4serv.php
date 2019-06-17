<?php
namespace DDCloud;

use \Curl\Curl;


class Curl4serv {

	public $uri = null;

	public function __construct($serviceName, $consulClient="") {
		$consul = new Consul($consulClient);
		$this->uri = $consul->getService($serviceName);
		if ($this->uri == "") {
			$this->logger->info('Error:  '.$serviceName." is null\n");
		}
		$this->logger = new Logger();
	}

	public function get($urlSuffix, $params) {
		if ($this->uri == "") {
			return;
		}

		$url = "http://" . $this->uri . $urlSuffix ."?".http_build_query($params);
		$curl = new Curl();
		$curl->get($url);

		if ($curl->error) {
		    $this->logger->info('Error: ' . $curl->errorCode . ': ' . $curl->errorMessage);
		} else {
		    return $curl->response;
		}
	}

	public function post($urlSuffix, $data) {
		if ($this->uri == "") {
			return;
		}

		$url = "http://" . $this->uri . $urlSuffix;
		$curl = new Curl();
		$curl->post($url, $data);

		if ($curl->error) {
			$this->logger->info('Error: ' . $curl->errorCode . ': ' . $curl->errorMessage);
		} else {
		    return $curl->response;
		}
	}
}