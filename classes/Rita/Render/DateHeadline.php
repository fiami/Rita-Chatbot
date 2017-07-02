<?php

namespace Rita\Render;

class DateHeadline {

	protected $date = null;

	public function __construct( $date ) {
		$this->date = $date;
	}

	public function render() {
		return date("l, Y-m-d", strtotime($this->date));
	}

}