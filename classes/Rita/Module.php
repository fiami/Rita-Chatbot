<?php

namespace Rita;

class Module {

	protected $moduleConfig;
	protected $userConfig;

	public function __construct($moduleConfig, $userConfig) {
		$this->moduleConfig = $moduleConfig;
		$this->userConfig = $userConfig;
	}
}