<?php

namespace Rita;

class Answer {

	protected $text = "";
	protected $attachments = array();

	public function getRandomText($possibilities) {
		return $possibilities[rand(0, count($possibilities)-1)];
	}

	public function addText($text) {
		$this->text.= $text;
	}

	public function addBlock($text) {
		$this->text.= "\n```".$text."```\n";
	}

	public function addLineBreak() {
		$this->text.= "\n";
	}

	public function getText() {
		return $this->text;
	}

	public function addAttachment(/*Rita\Attachment*/ $attachment) {
		$this->attachments[] = $attachment;
	}

	public function getAttachments() {
		return $this->attachments;
	}
}