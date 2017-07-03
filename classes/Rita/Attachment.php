<?php

namespace Rita;

class Attachment {

	protected $data = array();

	public function __construct($title, $text, $titleLink = "", $color = "", $imageUrl = "", $thumbUrl= "") {
		$this->data["title"] = $title;
		$this->data["text"] = $text;
		$this->data["title_link"] = $titleLink;
		$this->data["color"] = $color;
		$this->data["image_url"] = $imageUrl;
		$this->data["thumb_url"] = $thumbUrl;
		$this->data["mrkdwn_in"] = array("text");
	}

	public function getData() {
		return $this->data;
	}
}