<?php

namespace Rita;

class Manager {

	/**
	 * Holds the app configuration as a cache, to avoid too many reads
	 */
	protected $appConfig;

	/**
	 * Holds the configurations of all users, with the user ID as key
	 * Configurations will be added with addConfiguration calls and can
	 * be read with readConfigForUser calls.
	 */
	protected $config;

	/**
	 * All instances of Rita objects, linked to one user each. The userid
	 * is used as the key in this associative array
	 */
	protected $instances;

	/**
	 * This function is called to dispatch the data object from Slack with a certain client
	 * It is used as the main entrance function and manages the Rita instances per user
	 */
	public function dispatch( $data, &$client ) {
		$rita = $this->getInstanceForUser($data["user"], $client);
		if( $rita ) {

			/**
			 * Don't react on bot messages from e.g. rita, slackbot, etc.
			 */
			$userInfo = $rita->getUserInfo();
			if( $userInfo["is_bot"] == true ) {
				return ;
			}

			/**
			 * search for the right channel and send answer back
			 */
			$client->getDMById($data["channel"])->then(function (\Slack\DirectMessageChannel $channel) use ($client, $data, $rita) {
				$answer = $rita->dispatch($data["text"]);
				$this->sendAnswer( $client, $answer, $channel );
			});
		}
	}

	/**
	 * The seconds entrace to use the Manager. It fires all push notifications for all
	 * user that listes in the app configuration. One call triggers the complete mechanism.
	 */
	public function periodicCalls(&$client) {

		$config = $this->readAppConfig();

		if(!isset($config["periodicUsers"]) || count($config["periodicUsers"]) == 0) {
			return ;
		}

		$users = $config["periodicUsers"];
		$userAnswers = array();
		foreach( $users as $userid ) {

			/**
			 * get instance so that the user is loaded and config exists
			 */
			$userInstance = $this->getInstanceForUser($userid, $client);
			$channelid = $this->config[$userid]["pushnotifications"]["channel"];
			if(!isset($userAnswers[$channelid])) $userAnswers[$channelid] = array();
			$userAnswers[$channelid] = array_merge(
				$userAnswers[$channelid],
				$this->getInstanceForUser($userid, $client)->periodicCalls()
			);
		}

		foreach($userAnswers as $channelid => $answers) {
			foreach($answers as $answer) {
				$client->getDMById($channelid)->then(function (\Slack\DirectMessageChannel $channel) use ($client, $answer) {
					$this->sendAnswer( $client, $answer, $channel );
					//$client->send($answer->getText(), $channel);
				});			
			}
		}
	}

	/**
	 * Add a new configuration for a certain user.
	 * Could be used from inside this class or from outside for filling with
	 * a preset of configurations
	 */
	public function addConfiguration($userid, $config) {
		$this->config[$userid] = $config;
	}

	/**
	 * Single method to the rita instance, that is associated with a certain user
	 */
	protected function getInstanceForUser($userid, &$client) {
		if(!isset($this->instances[$userid])) {
			$configForUser = $this->readConfigForUser($userid);
			$client->getUserById($userid)->then(function ($user) use ($userid, $configForUser) {
				$this->instances[$userid] = new Rita($configForUser, $user->data);
			});

		}
		return $this->instances[$userid];
	}

	/**
	 * Sends a message to a certain channel by using the passed client
	 * It takes care about blank message and attachments to messages
	 */
	protected function sendAnswer( &$client, $answer, $channel) {

		/**
		* build up base message by using the meassge builder
		*/
		$messageBuilder = $client->getMessageBuilder()
			->setText($answer->getText())
			->setChannel($channel);

		/**
		* add attachment if available
		*/
		$attachments = $answer->getAttachments();
		if(count($attachments) > 0) {
			foreach($attachments as $attachment) {
				$messageBuilder = $messageBuilder->addAttachment(
					\Slack\Message\Attachment::fromData($attachment->getData())
				);
			}
		}

		/**
		* create and sent the message to the client
		*/
		$message = $messageBuilder->create();
		$client->postMessage($message);		
	}

	/**
	 * Get the configuration for a certain user (checking $_ENV first, then files in /config) 
	 * or use the default configuration (from file) if no specific configuration can be found.
	 */
	protected function readConfigForUser($userid) {

		/**
		 * Did we read the config already?
		 */
		if( !isset($this->config[$userid]) ) {

			/**
			 * Otherwise try to read config for user with default-fallback.
			 * Order of <approaches:></approaches:>
			 *	1) User Config from ENV
			 *	2) User Config from file
			 *	3) Default Config from ENV
			 *	4) Default Config from file
			 *	5) Exception
			 */
			$config = $this->readConfigFromEnvOrFile($userid);
			if($config === null) {
				$config = $this->readConfigFromEnvOrFile("default");
				if($config === null) {
					throw new \Exception("Couldn't find the default config anywhere.");
				}
			}

			$this->addConfiguration($userid, $config);
		}

		return $this->config[$userid];	
	}

	/**
	 * read configuration for a certain user
	 * Check ENV variable first, otherwise check the files
	 */
	protected function readConfigFromEnvOrFile( $userid ) {

			$directory = dirname(__FILE__) . "/../../config/";
			$filename = $directory . $userid . ".json";
			$envvar = $_ENV["CONFIG_" . strtoupper($userid)];

			if( isset( $envvar )) {
				return json_decode($envvar, true);

			} else if (file_exists( $filename )) {
				$json = file_get_contents($filename);
				return json_decode($json, true);

			} else {
				return null;
			}
	}

	/**
	 * Get the app config. Try to find it in ENV first, otherwise
	 * read from files
	 */
	protected function readAppConfig() {
		if(!isset($this->appConfig)) {
			if( isset( $_ENV["CONFIG_APP"] )) {
				$this->appConfig = json_decode($_ENV["CONFIG_APP"], true);
			} else {
				$directory = dirname(__FILE__) . "/../../config/";
				$filename = $directory . "app.json";

				if(!file_exists($filename)) {
					$this->appConfig = array();
				} else {
					$json = file_get_contents($filename);
					$this->appConfig = json_decode($json, true);
				}
			}			
		}
		return $this->appConfig;
	}

	/**
	 * return the bot-token for slack connection
	 * create it here: https://<your slack team>.slack.com/apps/new/A0F7YS25R-bots
	 */
	public function getSlackToken() {
		$config = $this->readAppConfig();
		return $config["token"];
	}
}