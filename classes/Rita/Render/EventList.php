<?php

namespace Rita\Render;

class EventList {

	protected $data = array();

	public function __construct( $data ) {
		$this->data = $data;
	}

	public function render() {

		$r = array();
		foreach($this->data as $event) {
			$datesplitStart = explode(" ", $event["startdate"]);
			$datesplitEnd = explode(" ", $event["enddate"]);

			$line = $datesplitStart[1] . " - " . $datesplitEnd[1]. ": " . $event["name"];
			if(strlen((string) $event["location"]) > 0 ) {
				$line.= " (" . $event["location"]. ")";
			}
			$r[]= $line;
		}
		return $r;
	}

}