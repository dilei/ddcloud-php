<?php
namespace DDCloud;

use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger {

	private $filePath = "";

	public function __construct($filePath="")
	{
		if ($filePath == "") {
			$filePath = "./DDCloud.log";
		}
		$this->filePath = $filePath;
	}

	/**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function log($level, $message, array $context = array())
    {
        file_put_contents($this->filePath, $level."--".$message."\n");
    }
}