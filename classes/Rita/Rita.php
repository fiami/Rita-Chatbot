<?php

namespace Rita;

class Rita {

    protected $rawConfig = array();
    protected $userConfig = array();
    protected $moduleInstances = array();

	public function __construct($rawConfig, $user) {
		$this->rawConfig = $rawConfig;
        $this->userConfig = array(
            "modules" => array_keys($rawConfig["modules"]),
            "info" => $user
        );
	}

    public function getUserInfo() {
        return $this->userConfig["info"];
    }

    public function dispatch($cmd) {

        $mapper = new InputMapper\Simple($this->userConfig);
        $route = $mapper->map($cmd);

        /**
         * send random text if we could not map the result
         */
         if( count($route) == 0) {
			$answer = new Answer();
			$answer->addText(
				$answer->getRandomText(array(
					"Mmm - I am not sure what you are talking about :confused:",
					"Could you maybe try to rephrase :question:",
					"Unfortunately I am not able to answer :unamused:"
				))
			);
			return $answer;
         }

        /**
         * otherwise  executing routing information
         */
        try {
            $moduleInst = $this->getModuleInstance($route["module"]);
            $action = $route["action"];
            $answer = $moduleInst->$action($route["params"]);
        } catch( \Exception $e ) {
            print_r($e);
            $answer = new Answer();
            $answer->addText("I'm sorry, but something bad happend, while executing your request. You might consider to ask an Administrator for some help.");
        }

        return $answer;
    }

    public function periodicCalls() {
        $answers = array();
        foreach( $this->rawConfig["periodics"] as $module => $params ) {
            $moduleInst = $this->getModuleInstance($module);
            $answer = $moduleInst->periodicCall($params);
            if($answer) {
                $answers[]= $answer;
            }
        }
        return $answers;
    }

    protected function getModuleInstance( $name ) {

        if( !in_array($name, $this->userConfig["modules"]) ) {
            throw new \Exception("Module access not allowed for this user.");
        }

        if( !isset($this->moduleInstances[$name]) ) {
            $moduleName = "Rita\\Modules\\" . $name;
            $this->moduleInstances[$name] = new $moduleName($this->rawConfig["modules"][$name], $this->userConfig);
        }
        return $this->moduleInstances[$name];
    }
}