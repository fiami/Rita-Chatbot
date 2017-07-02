<?php

/**
 * set timezone to berlin, since amazon always works with US something
 */
date_default_timezone_set("Europe/Berlin");

/**
 * init all autloaders from vendors
 */
require_once("vendor/autoload.php");
require_once("autoload.php");

/**
 * init rita manager and load config for all enabled users
 */
$ritaManager = new Rita\Manager();

/**
 * setup and init slack client
 */
$loop = React\EventLoop\Factory::create();
$client = new Slack\RealTimeClient($loop);
$client->setToken($ritaManager->getSlackToken());

/**
 * react on incoming messages
 */
$client->on('message', function ($data) use ($client, $ritaManager) {
	$ritaManager->dispatch($data, $client);
});

/**
 * echo confirmation if bot is connected
 */
$client->connect()->then(function () {
    echo "Connected!\n";
});

/**
 * Start periodic timers for all instances. It is running every minute.
 * The modules take care about different timings.
 */
$loop->addPeriodicTimer(60, function() use (&$ritaManager, &$client) {
	$ritaManager->periodicCalls( $client );
});

$loop->run();
