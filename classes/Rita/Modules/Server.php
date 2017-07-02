<?php

namespace Rita\Modules;

use Rita\Answer;
use Rita\Module;

class Server extends Module {

	protected $reachedThreshold = 0;

	/**
	 * This function return things this module is able to take care of. It should return an array
	 * with all functions it can execute. Every line in the array should complete the sentence: "I am able to ..."
	 */
	public static function IamAbleTo() {
		return array(
			"let you know about the server status",
			"notify you when the number of errors reaches a certain level"
		);
	}

	/**
	 * This function returns the most important commands, that should be available via quick commands.
	 * Please don't put too many in it.
	 */
	public static function MostImportantCommands() {
		return array(
			"How are the servers doing?"
		);
	}

	public function overview($params) {

		try {
			$errors = $this->getCounters();
		} catch( \Exception $e ) {
			$answer = new Answer();
			$answer->addText("I got an issue while fetching data from Graylog: " . $e->getMessage());
			return $answer;			
		}

		$numbers = $errors["numbers"];
		$numbersTotal = $errors["numbersTotal"];

		if( $numbersTotal == 0) {
			$answer = new Answer();
			$answer->addText("Wow ... no errors at all! How did this happen? :)");
			return $answer;
		}

		$answer = new Answer();
		$answer->addText(
			"There are currently *" . $numbersTotal . "* errors from the last " .
			intval($this->moduleConfig["graylog"]["base"]["timeframe"] / 60) . "  minutes (" . implode(" / ", $numbers) . ")."
		);
		return $answer;
	}

	public function periodicCall( $params ) {

		/**
		 * try to get data from graylog
		 * we sometime don't get valid data back
		 * so we are trying it several times
		 */
		$tries = 0;
		$maxTries = 5;
		$successfulMatch = false;
		while($tries < $maxTries) {
			$tries++;
			try {
				$errors = $this->getCounters();
				$successfulMatch = true;
				break;
			} catch( \Exception $e ) {
				echo "try again ...\n";
				sleep(5);
			}
		}

		/**
		 * If we still not able to get good data,
		 * we will let the user know
		 */
		if(!$successfulMatch) {
				$answer = new Answer();
				$answer->addText("I got an issue while fetching data from Graylog. I tried it " . $tries . " times.");
				return $answer;
		}

		$numbers = $errors["numbers"];
		$numbersTotal = $errors["numbersTotal"];

		if($numbersTotal >= $params["threshold"]) {

			// discovered issued for the first time
			if($this->reachedThreshold == 0) {
				$this->reachedThreshold = time();
				$answer = new Answer();
				$answer->addText(
					sprintf($answer->getRandomText(array(
						"Ohoh ... We currently got *%s* errors! You should have a look.",
						"I just saw *%s* errors. That is too much.",
						"With having *%s* errors, we should check the systems."
					)), $numbersTotal)
				);
				return $answer;

			// send reminder after x minutes
			} else {

				if(time() - $this->reachedThreshold >= $params["reminderAfter"]) {
					$this->reachedThreshold = time();
					$answer = new Answer();
					$answer->addText(
						$answer->getRandomText(array(
							"The number of errors is still higher than the threshold. :warning:",
							"Is anybody taking care about the current errors? :warning:",
							"Should we ask someone to assists with the current errors? :warning:",
							"Is the current number of errors urgent? We should escalate then. :warning:"
						))
					);
					return $answer;
				}

			}

		} elseif ($this->reachedThreshold > 0) {
			$this->reachedThreshold = 0;
			$answer = new Answer();
			$answer->addText(
				$answer->getRandomText(array(
					"Ok - error counters are back below thresholds.",
					"Error situation seems to be fine for now.",
					"We are back to normal - error counter are down."
				))
			);
			return $answer;
		}

		return null;
	}

	protected function getCounters() {
		$cockpit = new \Rita\Connector\Graylog($this->moduleConfig["graylog"]["base"]);

		$numbers = array();
		$numbersTotal = 0;

		foreach($this->moduleConfig["graylog"]["counters"] as $counter) {
			$errorsForCounter = $cockpit->numberOfErrors($counter["query"], $counter["filter"]);
			if($errorsForCounter === null) {
				throw new \Exception("The request for " . $counter["name"] ." did not return data from Graylog.");
			}
			$numPerQuery = intval($errorsForCounter);
			$numbers[] = $counter["name"] . ": " . $numPerQuery;
			$numbersTotal += $numPerQuery;
		}

		return array(
			"numbers" => $numbers,
			"numbersTotal" => $numbersTotal
		);
	}
}