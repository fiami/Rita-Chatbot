<?php

namespace Rita\Connector;

class Zimbra {

	protected $username = null;
	protected $password = null;
	protected $baseUrl = null;

	/**
	 * 
	 */
	public function __construct( $username, $password, $baseUrl ) {
		$this->username = $username;
		$this->password = $password;
		$this->baseUrl = $baseUrl;
	}

	/**
	 * returns all events for a certain day - ordered by startdate
	 */
	public function getEventsByDay( $day ) {

		$rangestart = strtotime($day) * 1000; // zimbra always works with milliseconds in timestamps
		$rangeend = $rangestart + (24 * 60 * 60 * 1000);

		/**
		* build full call url
		*/
		$calendar = $this->getFromZimbra("/calendar?start=" . $rangestart . "&end=" . $rangeend);

		$events = array();
		if( !property_exists($calendar, "appt") ) return $events;

		foreach ($calendar->appt as $meetingSeries) {

			/**
			* collect values for the current appointment
			*/
			$finalstartdate = null;
			$finalenddate = null;
			$finalname = null;
			$finallocation = null;

			/**
			* iterate over all sub events in order to find the right one
			* single events just have one item = easy, we just take this one
			* series of events contain the original item plus all execptions, more difficult to handle
			*/
			foreach ($meetingSeries->inv as $meeting) {

				$startdate = isset($meeting->comp[0]->s[0]->u) ? $meeting->comp[0]->s[0]->u : "";
				$enddate = isset($meeting->comp[0]->e[0]->u) ? $meeting->comp[0]->e[0]->u: "";
				$name = $meeting->comp[0]->name;
				$location = $meeting->comp[0]->loc;

				/**
				* we take the original, if nothing is set yet
				* the original events hold the time from the original day only, so we need to recalculate
				* since we are only getting the data from one day, we can kill the day information on extract the hours only
				*/
				if( $finalstartdate == null && !property_exists($meeting->comp[0], "recurId")) {
					$finalstartdate = date("Y-m-d", $rangestart/1000) . " " . date("H:i", $startdate / 1000);
					$finalenddate = date("Y-m-d", $rangestart/1000) . " " . date("H:i", $enddate / 1000);
					$finalname = $name;
					$finallocation = $location;
				}

				/**
				* overwrite if got an exception of the series taking place on the current day
				*/
				if( property_exists($meeting, "recurId") &&
					($startdate <= $rangeend && $enddate >= $rangestart)) {
					$finalstartdate = date("Y-m-d H:i", $startdate / 1000);
					$finalenddate = date("Y-m-d H:i", $enddate / 1000);
					$finalname = $name;
					$finallocation = $location;                
				}
			}

			$events[]= array(
				"name" => $finalname,
				"location" => $finallocation,
				"startdate" => $finalstartdate, // timestamp has milli seconds as well
				"enddate" => $finalenddate
			);
		}

		usort($events, array("self", "cmp"));
		return $events;
	}

	/**
	 * 
	 */
	protected function getFromZimbra( $url ) {

		$callUrl = $this->baseUrl . $this->username . $url . "&fmt=json&auth=ba";

		/**
		* Create curl request and send it
		*/
		$process = curl_init($callUrl);
		curl_setopt($process, CURLOPT_HTTPHEADER, array('Content-Type: text/html; charset=utf-8'));
		curl_setopt($process, CURLOPT_HEADER, 0);
		curl_setopt($process, CURLOPT_USERPWD, $this->username . ":" . $this->password);
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
		$text = curl_exec($process);
		curl_close($process);

		/**
		* convert result to json
		*/
		return json_decode($text);
	}

	public static function cmp($a, $b) {
		return strcmp($a["startdate"], $b["startdate"]);
	}
}