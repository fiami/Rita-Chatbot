<?php

namespace Rita\Modules;

use Rita\Answer;
use Rita\Attachment;
use Rita\Module;
use Rita\Connector\OpenWeatherMap;

class Weather extends Module {

	/**
	 * This function return things this module is able to take care of. It should return an array
	 * with all functions it can execute. Every line in the array should complete the sentence: "I am able to ..."
	 */
	public static function IamAbleTo() {
		return array(
			"give information about the current weather",
			"provide weather forcasts for the next days"
		);
	}

	/**
	 * This function returns the most important commands, that should be available via quick commands.
	 * Please don't put too many in it.
	 */
	public static function MostImportantCommands() {
		return array(
			"How is the current weather?",
			"How will the weather be the next days?"
		);
	}

	/**
	 * 
	 */
	public function current( $params ) {

		$api = new OpenWeatherMap($this->moduleConfig["openweathermap"]["apikey"]);
		$current = $api->getCurrentWeather($this->moduleConfig["openweathermap"]["cityid"]);
		$dates = $api->getForcastHours($this->moduleConfig["openweathermap"]["cityid"]);
		array_unshift($dates, $current);

		$answer = new Answer();
		$answer->addText("Please see the current weather and forcasts for the next hours below:");

		foreach( $dates as $date ) {
			$answer->addAttachment( $this->getSlackAttachmentForWeatherObject($date));
		}

		return $answer;
	}

	/**
	 * 
	 */
	public function nextDays( $params ) {

		$api = new OpenWeatherMap($this->moduleConfig["openweathermap"]["apikey"]);
		$dates = $api->getForcastDays($this->moduleConfig["openweathermap"]["cityid"]);

		$answer = new Answer();
		$answer->addText("Please see the weather forecast for the next days below:");

		foreach( $dates as $date ) {
			$answer->addAttachment( $this->getSlackAttachmentForWeatherObject($date));
		}

		return $answer;
	}

	/**
	 * 
	 */
	protected function getSlackAttachmentForWeatherObject( $date ) {
		return new Attachment(
			"Weather for " . $date[OpenWeatherMap::FIELD_CITY] . " at " . $date[OpenWeatherMap::FIELD_DATE],
			ucfirst($date[OpenWeatherMap::FIELD_DESCRIPTION]) . " at a temperature of " . $date[OpenWeatherMap::FIELD_TEMPERATURE] . " °C.\n" .
				"Humidity: " . $date[OpenWeatherMap::FIELD_HUMIDITY] . "% / Pressure: " . $date[OpenWeatherMap::FIELD_PRESSURE]. " hpa",
			"",
			"",
			"",
			$date[OpenWeatherMap::FIELD_ICON]
		);
	}
}