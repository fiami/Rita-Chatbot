<?php

namespace Rita\Modules;

use Rita\Connector\Zimbra;
use Rita\Render\EventList;
use Rita\Render\DateHeadline;
use Rita\Answer;
use Rita\Module;

class Events extends Module {

	/**
	 * This function return things this module is able to take care of. It should return an array
	 * with all functions it can execute. Every line in the array should complete the sentence: "I am able to ..."
	 */
	public static function IamAbleTo() {
		return array(
			"inform about your next meetings",
			"read your Zimbra calendar"
		);
	}

	/**
	 * This function returns the most important commands, that should be available via quick commands.
	 * Please don't put too many in it.
	 */
	public static function MostImportantCommands() {
		return array(
			"What is the plan for today?",
			"What is the plan for tomorrow?",
			"What's up next?"
		);
	}

	public function byDate($params, $onlyData = false) {

		$day = $params["day"];

		$zimbra = new Zimbra($this->moduleConfig["zimbra"]["username"], $this->moduleConfig["zimbra"]["password"], $this->moduleConfig["zimbra"]["baseUrl"]);
		$events = $zimbra->getEventsByDay($day);

		/**
		 * if we only want to have the data, just return here
		 */
		if($onlyData) return $events;

		/**
		 * No events found on that day.
		 */
		if(count($events) == 0) {
			$answer = new Answer();
			$answer->addText(
				sprintf($answer->getRandomText(array(
					"No plans yet for *%s*.",
					"Lucky you - nothing planned so far on *%s*.",
					"I couldn't find anything on *%s*."
				)), (new DateHeadline( $day ))->render())
			);
			return $answer;
		}

		/**
		 * We did find event on that day
		 */
		$answer = new Answer();
		$answer->addText(
			sprintf($answer->getRandomText(array(
				"This is the plan for *%s*:",
				"Currently the plan for *%s* looks like that:",
				"Please see the agenda for *%s*:"
			)), (new DateHeadline( $day ))->render())
		);
		$answer->addBlock(implode("\n", (new EventList( $events ))->render()));
		return $answer;
	}

	public function forThisWeek($params) {
		return $this->getAnswerForWeek("this");
	}

	public function forNextWeek($params) {
		return $this->getAnswerForWeek("next");
	}

	protected function getAnswerForWeek($which) {

		$weekstart = strtotime('monday ' . $which . ' week');
		$answer = new Answer();
		$answer->addText(
			$answer->getRandomText(array(
				"Here the plan:",
				"It currently looks like that:",
				"Plans so far:"
			))
		);
		$answer->addLineBreak();

		for($i=0; $i < 7; $i++) {
			$day = date("Y-m-d", $weekstart + ($i * 24 * 60 * 60));
			$answer->addText("*".(new DateHeadline( $day ))->render()."*:");

			$events = $this->byDate(array("day" => $day), true);
			if( count($events) == 0) {
				$answer->addBlock("Nothing planned");
			} else {
				$answer->addBlock(implode("\n", (new EventList( $events ))->render()));
			}
			$answer->addLineBreak();
		}

		return $answer;
	}

	public function nextUpcoming($params) {

		$meetingsToday = $this->byDate(array(
			"day" => date("Y-m-d")
		), true);

		$now = time();

		$meetingsNow = array();
		$meetingsNext = array();

		/**
		 * the logic in this functon is based on a ordere
		 * list of events - ordered by startdate
		 */
		$foundCurrentMeetings = false;
		foreach($meetingsToday as $meeting) {

			$startdate = strtotime($meeting["startdate"]);
			$enddate = strtotime($meeting["enddate"]);

			/**
			 * check if a meeting is currently already in place
			 * and set found indicator
			 */
			if( $startdate <= $now && $enddate >= $now ) {
				$meetingsNow[] = $meeting;
				$foundCurrentMeetings = true;
			}

			/**
			 * if we found a current meeting, take the
			 * next occuring meeting as "next"
			 */
			elseif( $foundCurrentMeetings && $startdate > $now ) {
				$meetingsNext[] = $meeting;
				$foundCurrentMeetings = false;
			}

			/**
			 * also add other meetings that have the same
			 * startdate as the already added "next" meeting
			 */
			elseif(count($meetingsNext) > 0 && strtotime($meetingsNext[0]["startdate"]) == $startdate) {
				$meetingsNext[] = $meeting;
			}

		}

		$answer = new Answer();
		if( count($meetingsNow) > 0) {
			$answer->addText("*Current meetings*:");
			$answer->addBlock(implode("\n", (new EventList( $meetingsNow ))->render()));
		} else {
			$answer->addText(
				$answer->getRandomText(array(
					"Nothing going on at the moment.",
					"You are not missing any meeting atm.",
					"Nothing to worry about now."
				))
			);
		}
		$answer->addLineBreak();

		if( count($meetingsNext) > 0) {
			$answer->addText("*Next meetings*:");
			$answer->addBlock(implode("\n", (new EventList( $meetingsNext ))->render()));
		} else {
			$answer->addText(
				$answer->getRandomText(array(
					"Nothing left for today.",
					"Congrats - that was it for today.",
					"Well done - meetings over."
				))
			);
		}

		return $answer;
	}
}