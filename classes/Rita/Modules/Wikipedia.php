<?php

namespace Rita\Modules;

use Rita\Answer;
use Rita\Attachment;
use Rita\Module;

class Wikipedia extends Module {

	/**
	 * This function return things this module is able to take care of. It should return an array
	 * with all functions it can execute. Every line in the array should complete the sentence: "I am able to ..."
	 */
	public static function IamAbleTo() {
		return array(
			"search on wikipedia for people and things"
		);
	}

	public function search($params) {

		// get results from wikipedia
		$callUrl = "https://en.wikipedia.org/w/api.php?action=query&list=search&utf8=&format=json&srlimit=5&srsearch=".urlencode($params["q"]);

		/**
		* Create curl request and send it
		*/
		$process = curl_init($callUrl);
		curl_setopt($process, CURLOPT_HTTPHEADER, array('Accept: application/json'));
		curl_setopt($process, CURLOPT_HEADER, 0);
		curl_setopt($process, CURLOPT_TIMEOUT, 5);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
		$text = curl_exec($process);
		curl_close($process);
		$raw = json_decode($text);

		/**
		 * return message, if we could not get any results
		 */
		if( count($raw->query->search) <= 0 ) {
			$answer = new Answer();
			$answer->addText("Unfortunately I was not able to find anything for *" . $params["q"] . "*.");
			return $answer;
		}

		/**
		 * get results into list
		 */
		$answer = new Answer();
		$answer->addText("Here are the top wikipedia articles for *" . $params["q"] . "*: ");

		foreach($raw->query->search as $res) {
			$answer->addAttachment(
				new Attachment(
					$res->title,
					strip_tags($res->snippet),
					"https://en.wikipedia.org/wiki/" . rawurlencode($res->title)
				)
			);
		}
		return $answer;
	}
}