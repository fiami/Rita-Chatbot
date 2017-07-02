<?php

namespace Rita\InputMapper;

class Simple {

	protected $userConfig = array();

	public function __construct($userConfig) {
		$this->userConfig = $userConfig;
	}

	public function map($cmd) {

		/**
		 * =======================================================
		 * Basic functions
		 * =======================================================
		 */

		/**
		 * Who are you?
		 * help
		 * h
		 */
		if( preg_match("/who are you/i", $cmd) === 1 ||
			preg_match("/help/i", $cmd) === 1 ||
			strtolower($cmd) === "h" ) {
			return array(
				"module" => "Basics",
				"action" => "help",
				"params" => array()
			);
		}

		/**
		 * q or Q
		 */
		if( strtolower($cmd) === "q" ) {
			return array(
				"module" => "Basics",
				"action" => "quickCommands",
				"params" => array()
			);
		}

		/**
		 * pure integers will get caughts, since we expect them to be a quick command
		 */
		if( is_numeric($cmd) ) {

			try {
				$realCommand = \Rita\Modules\Basics::getCommandByNumber(intval($cmd), $this->userConfig);
				return $this->map($realCommand);

			} catch (\Exception $e) {
				return array(
					"module" => "Basics",
					"action" => "notAbleToFindCommand",
					"params" => array(
						"input" => intval($cmd)
					)
				);
			}
		}

		/**
		 * =======================================================
		 * Weather
		 * =======================================================
		 */

		/**
		 * How is the current weather?
		 */
		$matches = array();
		if( preg_match("/current/i", $cmd, $matches) === 1 && preg_match("/weather/i", $cmd, $matches) === 1) {
			return array(
				"module" => "Weather",
				"action" => "current",
				"params" => array()
			);
		}

		/**
		 * How will the weather be the next days?
		 */
		$matches = array();
		if( (preg_match("/next/i", $cmd, $matches) === 1 || preg_match("/forecast/i", $cmd, $matches) === 1) &&
		    preg_match("/weather/i", $cmd, $matches) === 1) {
			return array(
				"module" => "Weather",
				"action" => "nextDays",
				"params" => array()
			);
		}

		/**
		 * =======================================================
		 * Zimbra & Meeting mappings
		 * =======================================================
		 */

		/**
		 * What is the plan for this week?
		 */
		if( preg_match("/this week/i", $cmd) === 1 ) {
			return array(
				"module" => "Events",
				"action" => "forThisWeek",
				"params" => array()
			);
		}

		/**
		 * What is the plan for next week?
		 */
		if( preg_match("/next week/i", $cmd) === 1 ) {
			return array(
				"module" => "Events",
				"action" => "forNextWeek",
				"params" => array()
			);
		}

		/**
		 * What is the plan for next Monday/Tuesday/...
		 */
		$matches = array();
		if( preg_match("/next (Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)/i", $cmd, $matches) === 1 ) {
			return array(
				"module" => "Events",
	            "action" => "byDate",
				"params" => array(
					"day" => date("Y-m-d", strtotime("next ".$matches[1]))
				)
			);
		}

		/**
		 * What is the next event now?
		 */
		if( preg_match("/next/i", $cmd) === 1 ||
		    preg_match("/now/i", $cmd) === 1 ) {
			return array(
				"module" => "Events",
				"action" => "nextUpcoming",
				"params" => array()
			);
		}

		/**
		 * What is going on today?
		 */
		if( preg_match("/today/i", $cmd) === 1 ) {
			return array(
				"module" => "Events",
	            "action" => "byDate",
				"params" => array(
					"day" => date("Y-m-d")
				)
			);
		}

		/**
		 * What is going on tomorrow?
		 */
		if( preg_match("/tomorrow/i", $cmd) === 1 ) {
			return array(
				"module" => "Events",
	            "action" => "byDate",
				"params" => array(
					"day" => date("Y-m-d", strtotime("+1 day"))
				)
			);
		}

		/**
		 * What is going on 2016-12-02?
		 */
		$matches = array();
		if( preg_match("/on (.*)\?/i", $cmd, $matches) === 1 ) {
			return array(
				"module" => "Events",
	            "action" => "byDate",
				"params" => array(
					"day" => date("Y-m-d", strtotime($matches[1]))
				)
			);
		}

		/**
		 * =======================================================
		 * Graylog
		 * =======================================================
		 */
		if( preg_match("/server/i", $cmd) === 1 ||
			preg_match("/error/i", $cmd) === 1 ||
		    preg_match("/state/i", $cmd) === 1 ) {
			return array(
				"module" => "Server",
				"action" => "overview",
				"params" => array()
			);
		}

		/**
		 * =======================================================
		 * Wikipedia
		 * =======================================================
		 */

		/**
		 * Who is Albert Einstein?
		 */
		$matches = array();
		if( preg_match("/who is (.*)\?/i", $cmd, $matches) === 1) {
			return array(
				"module" => "Wikipedia",
				"action" => "search",
				"params" => array(
					"q" => $matches[1]
				)
			);
		}

		/**
		 * What is fruit?
		 * What is a banana?
		 * What is an apple?
		 */
		$matches = array();
		if( preg_match("/what is( a| an)? (.*)\?/i", $cmd, $matches) === 1) {
			return array(
				"module" => "Wikipedia",
				"action" => "search",
				"params" => array(
					"q" => $matches[2]
				)
			);
		}

		/**
		 * default is nothing
		 */
		return array();
	}
}