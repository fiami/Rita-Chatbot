<?php

namespace Rita\Connector;

class OpenWeatherMap {

	/**
	 * 
	 */
	const FIELD_CITY = "city";
	const FIELD_TEMPERATURE = "temp";
	const FIELD_PRESSURE = "pres";
	const FIELD_HUMIDITY = "humi";
	const FIELD_DESCRIPTION = "descr";
	const FIELD_ICON = "icon";
	const FIELD_DATE = "date";

	protected $appId;

	public function __construct($appId) {
		$this->appId = $appId;
	}

	/**
	 * 
	 */
	public function getCurrentWeather( $cityid ) {

		$data = $this->callOpenWeatherMapApi("weather", "id=" . $cityid . "&units=metric");

		return $this->createWeatherDataObject(
			(new \DateTime())->format('Y-m-d H:i'),
			$data->name,
			round(floatval($data->main->temp)),
			$data->main->pressure,
			$data->main->humidity,
			$data->weather[0]->description,
			$data->weather[0]->icon
		);
	}

	/**
	 * 
	 */
	public function getForcastHours( $cityid ) {

		$data = $this->callOpenWeatherMapApi("forecast", "id=" . $cityid . "&units=metric&cnt=5");

		$hours = array();
		foreach( $data->list as $datapoint ) {
			$hours[] = $this->createWeatherDataObject(
				(new \DateTime( "@" . $datapoint->dt ))->format('H:i'),
				$data->city->name,
				round(floatval($datapoint->main->temp)),
				$datapoint->main->pressure,
				$datapoint->main->humidity,
				$datapoint->weather[0]->description,
				$datapoint->weather[0]->icon
			);
		}

		return $hours;
	}

	/**
	 * 
	 */
	public function getForcastDays( $cityid ) {

		$data = $this->callOpenWeatherMapApi("forecast/daily", "id=" . $cityid . "&units=metric");

		$days = array();
		foreach( $data->list as $datapoint ) {
			$days[] = $this->createWeatherDataObject(
				(new \DateTime( "@" . $datapoint->dt ))->format('l, Y-m-d'),
				$data->city->name,
				round(floatval($datapoint->temp->min)) . "-" . round(floatval($datapoint->temp->max)),
				$datapoint->pressure,
				$datapoint->humidity,
				$datapoint->weather[0]->description,
				$datapoint->weather[0]->icon
			);
		}

		return $days;
	}

	/**
	 * 
	 */
	protected function callOpenWeatherMapApi( $endpoint, $querystring ) {

		$callUrl = "http://api.openweathermap.org/data/2.5/" . $endpoint . "?" . $querystring . "&appid=" . $this->appId;

		$process = curl_init($callUrl);
		curl_setopt($process, CURLOPT_HTTPHEADER, array('Accept: application/json'));
		curl_setopt($process, CURLOPT_HEADER, 0);
		curl_setopt($process, CURLOPT_TIMEOUT, 5);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
		$text = curl_exec($process);
		curl_close($process);
		return json_decode($text);
	}

	/**
	 * 
	 */
	protected function createWeatherDataObject($datetime, $cityname, $temp, $pressure, $humidity, $description, $icon) {
		return 	array(
				self::FIELD_DATE => $datetime,
				self::FIELD_CITY => $cityname,
				self::FIELD_TEMPERATURE => $temp,
				self::FIELD_PRESSURE => $pressure,
				self::FIELD_HUMIDITY => $humidity,
				self::FIELD_DESCRIPTION => $description,
				self::FIELD_ICON => "https://openweathermap.org/img/w/" . $icon . ".png"
		);
	}
}