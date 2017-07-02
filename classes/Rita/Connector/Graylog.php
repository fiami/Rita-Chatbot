<?php

namespace Rita\Connector;

class Graylog {

	protected $basePath;
	protected $timeframe;
	protected $username;
	protected $password;

	public function __construct($base) {
		$this->basePath = $base["path"];
		$this->timeframe = $base["timeframe"];
		$this->username = $base["username"];
		$this->password = $base["password"];
	}

	public function numberOfErrors($query, $filter) {

		$callUrl = $this->basePath . "?" .
			"query=" . urlencode($query) . "&" .
			"range=" . $this->timeframe . "&" .
			"filter=" . $filter
		;

		/**
		* Create curl request and send it
		*/
		$process = curl_init($callUrl);
		curl_setopt($process, CURLOPT_HTTPHEADER, array('Accept: application/json'));
		curl_setopt($process, CURLOPT_HEADER, 0);
		curl_setopt($process, CURLOPT_USERPWD, $this->username . ":" . $this->password);
		curl_setopt($process, CURLOPT_TIMEOUT, 5);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
		$text = curl_exec($process);
		curl_close($process);

		/**
		* convert result to json
		*/
		$result = json_decode($text);
		if(!$result) {
			return null;
		}
		return $result->total_results;
	}
}