<?php

namespace Rita\Modules;

use Rita\Answer;
use Rita\Module;

class Basics extends Module {

	static protected $cachedModuleFeatures = array();
	static protected $cachedQuickCommands = array();

	/**
	 *
	 */
	public function help($params) {

		$features = self::getModuleFeatures( $this->userConfig );
		$lastFeature = array_pop( $features );
		$featureString = implode( ", ", $features );
		if( strlen( $featureString ) > 0 ) $featureString.= " and ";
		$featureString.= $lastFeature;

		$answer = new Answer();
		$answer->addText("Hey, I'm Rita - your personal assistent. I am able to " . $featureString . ".");
		$answer->addLineBreak();
		$answer->addLineBreak();
		$answer->addText("Feel free to ask me anything - if you are in a hurry, just send me a single `q` ");
		$answer->addText("or `Q` for getting a quick overview about most important tasks.");
		return $answer;
	}

	/**
	 *
	 */
	public function quickCommands($params) {

		$commands = self::getQuickCommands( $this->userConfig );

		$answer = new Answer();
		$answer->addText("Please select a quick command, by just entering the number:");
		$answer->addLineBreak();
		$answer->addLineBreak();
		for( $i = 0; $i < count($commands); $i++ ) {
			$answer->addText($i+1 . ") " . $commands[$i] . "\n");
		}
		return $answer;
	}

	/**
	 *
	 */
	public function notAbleToFindCommand($params) {

		$answer = new Answer();
		$answer->addText("Uuuh - I don't have any quick command linked to " . $params["input"] . ". ");
		$answer->addText("Please check the list with `q` or `Q`.");
		return $answer;
	}

	/**
	 *
	 */
	public static function getCommandByNumber($no, $userConfig) {

		$idx = $no - 1;
		$commands = self::getQuickCommands($userConfig);

		if( !isset($commands[$idx]) ) {
			throw new \Exception("Command not found.");
		}
		return $commands[$idx];
	}

	/**
	 *
	 */
	protected static function getModuleFeatures($userConfig) {

		if(!count(self::$cachedModuleFeatures) > 0 ) {
			self::$cachedModuleFeatures = self::getFunctionResultFromModules("IamAbleTo", $userConfig);
		}
		return self::$cachedModuleFeatures;		
	}

	/**
	 *
	 */
	protected static function getQuickCommands($userConfig) {

		if(!count(self::$cachedQuickCommands) > 0 ) {
			self::$cachedQuickCommands = self::getFunctionResultFromModules("MostImportantCommands", $userConfig);
		}
		return self::$cachedQuickCommands;		
	}

	/**
	 *
	 */
	protected static function getFunctionResultFromModules( $functionName, $userConfig ) {

		$results = array();
		foreach ($userConfig["modules"] as $filename) {
			$moduleName = str_replace(".php", "", str_replace(dirname(__FILE__) . "/", "", $filename));
			$moduleClass = "\\Rita\\Modules\\" . $moduleName;
			if(method_exists($moduleClass, $functionName)) {
				$results = array_merge($results, $moduleClass::$functionName());
			}
		}
		return $results;
	}
}