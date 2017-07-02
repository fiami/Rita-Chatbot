Rita is a personal assistent bot for Slack. It is connecting via Websockets and supports different modules. The configuration is done via JSON files and can be user specific. The bot support pull and push messages.

# Getting started

In order to run the bot, make sure that everything is configured (see next paragraph), install composer and run it via executing:

```
php ./rita.php
```

As soon as the Bot is live, it prompts "Connected!" in your console and should be marked as "Online" in your Slack team. Congrats!

# Ask Rita

As soon as Rita is online, you can start asking Rina things like "How is the current weather?", "Who is Albert Einstein?", "What is a banana?", "How are my servers doing?" or "What is the plan for tomorrow?". The available functionality is based on your configuration. If you are not sure what you could ask Rita, try sending "help", for seeing a quick summary, or "q" for seeing the most important quick commands for the acitvated modules.

# Pull and Push Methods

Modules can interact with the user in two different ways: Reacting on commands or pushing notifications. The pushing notifications are internally called "periodics", since the modules get called with a timer and can then decide to notify the user or not. A user that should receive these updates, needs to be activated in the application configuration (to activate the mechanism in general) and will need some user specific settings in order to initialize the modules with the required parameters.

# Configuration

All configurations are stored in the "config" directory of the application. The minimum setup requires two files:

* app.json - which holds basic information about the application
* default.json - which holds the default configuration for every user

In order to change the configuration depending on environment, the bot always searches for the configuration in your ENV variable first and only reads it from disk, if it can not be found in ENV. The name for config files in ENV is "CONFIG_" + "APP" (for the app configuration) or the User id, for a user specific configuration.

## App Configuration

The minimum configuration for the app looks like that:

```
{
	"token": "xoxb-ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789",
	"periodicUsers": []
}
```

The token fields contains the slack token. In "periodicUsers" you will have an array of users that are enabled for push notifications.

## User Configuration

A sample user configuration looks like that:

```
{
	"pushnotifications": {
		"channel": "D3ABCDEFG"
	},
	"modules": {
		"Basics": {},
		"Weather": {},
		"Wikipedia": {},
		"Server": {}		
	},
	"periodics": {
		"Server": {}		
	}
}
```

In "pushnotifications" you can define the channel, where all notifications should be sent to. This usually is the direct channel to user the configuration belongs to.

Under "modules" you can put the configuration for the modules, by using the module names as the key. Only modules that are configured here are activated for that user. So if a module doesn't need a configuration, just configure it with an empty object, so that it gets activated. The configuration is passed to every module on initilisation.

The "periodics" section is similar to the modules section. All modules that are put in here, will be executed on regular level and might push notification to the channel that is configured in the "pushnotifications" section.

For a more detailed documentation of the different modules, please see the module section below.

## Push Configurations to ENV on Heroku

If you are using heroku and prefer to push your configuration to the ENV variables instead of having config files in your repository, just add the config file to git-ignore and use the following command to push it to the server (after replacing <USERID> with your slack ID):

```
heroku config:add CONFIG_<USERID>="$(cat config/<USERID>.json | tr '\n' ' ')"
```

# Existing modules

Below you will find a list of the currently available modules, including their required configurations:

## Basics

**Purpose:** This module is required for the main interaction with Rita and is providing things like the general help function.

**Module Config:** None

**Periodics Config:** None

## Events

**Purpose:** Next meetings / calendar entries and plans for today, tomorrow, the whole week or any other day, by connecting to your Zimbra account.

**Module Config:**

```
"Events": {
	"zimbra" : {
		"username": "<zimbra-username>",
		"password": "<zimbra-password>",
		"baseUrl": "https://<zimbra-domain>/home/"
	}
```

**Periodics Config:** None

## Server

**Purpose:** Collecting the current number of log entries from Graylog. It can combine different errors counters and will inform you as soon as they reach a certain threshold.

**Module Config:**

```
"Server": {
	"graylog": {
		"base": {
			"path": "https://<graylog-url>/api/search/universal/relative",
			"username": "<graylog-username>",
			"password": "<graylog-password>",
			"timeframe": 900 /*time window that it will ask graylog for, in seconds*/
		},
		"counters": [
			{
				"name": "Application 1",
				"query": "<graylog query string with conditions>",
				"filter": "<graylog filter strings like the stream information>"
			},
			{
				"name": "Application 2",
				"query": "<graylog query string with conditions>",
				"filter": "<graylog filter strings like the stream information>"
			}
		]
	}
}
```

The names in the "counters" section are just for letting you know where the errors belong to.

**Periodics Config:**

```
"Server": {
	"threshold": 100, /* minimum number of errors before getting notified */
	"reminderAfter": 600 /* number of seconds after which Rita re-triggers a notification if the number is still too high */
}
```

## Weather

**Purpose:** Shows the current weather status, the forcast for the next hours or a forecast for the next days by using openweathermap.org

**Module Config:**

```
"Weather": {
	"openweathermap": {
		"apikey": "asdfghjkl12345678", /* openweathermap.org api key */
		"cityid": "6545310" /* cityids from openweathermap.org */
	}
}
```

**Periodics Config:** None

## Wikipedia

**Purpose:** Searches for People and Things on Wikipedia and returns the top ten links.

**Module Config:** None

**Periodics Config:** None

# Application structure

The main application structures are:

* /rita.php - main file that launches the whole bot
* /config - directory that holds all configuration files
* /classes - directory that holds all application classes
* /classes/Modules - all modules are put in here
* /classes/InputMapper - component that maps your text to module routes
* /Dockerfile - configuration for running the application in a docker (more details below)
* /Procfile - configuration for running the application via Heroku (more details below)

Short overview: The /rita.php files initializes an Instance of Rita\Manager that takes care of the whole handling. The manager holds a singleton instance of the main class Rita\Rita for every user, that is in contact with Rita. The instances are getting created the first time the user triggeres Rita or the first time a peridoc call is fired for that user. Pull- and Push requests are using the same instances, so communication between the different requests and kind of requests is possible.

# Create your own module

In order to create a module, create a class in /classes/Rita/Modules. The class needs to be in the namespace "Rita\Modules" and needs to inherit from "Rita\Module".

## Fixed name functions

There are a couple of fixed name functions, you can implement in order to provide additional service & functionality.

**Static Function IamAbleTo**

This function return things the module is able to take care of. It should return an array with all functions it can execute. Every line in the array should complete the sentence: "I am able to ...". These commands pop up, when somebody asked Rita for "help".

**Static Function MostImportantCommands**

This function returns the most important commands, that should be available via quick commands. If a user asked Rita for quick commands by sending "q", all the most important commands from all modules are collected and numbered. The user is then able to only send a number, instead of the whole string to Rita. For the modules nothing changes, since the numbers are just re-translated into the exact commands before being processed. Be careful to not put in too many commands for one module.

**Fuction periodicCall**

This function is called, when a module is enabled for periodic calls. The function is called for every enabled user in a very regular way, depending on the interval set in /rita.php. It should either return null (if there is nothing you would like to send to the user) or an Rita\Answer object, if you want to let the user something know.

All requests are executed on the same singleton instance per user, that the normal pull-requests - which means, you are able to store states and have communication between pull- and push-requests.

## Configurations

The constructor is provided from the parent class and takes care about storing the configurations. Via the following class attributes, you are able to access the different configurations:

* $this->moduleConfig - the configuration part from the user's configuration file for that module
* $this->appConfig - the app configuration for the user, consisting of a list of all activated modules (index "modules") and a summary of the user's profile (index "info").

## Communication with user

The communication with the user on pull-requests always follows the same pattern:

1. A user asks something,
1. The question is mapped to a command,
1. The command is executed and,
1. The answer is sent back.

Rita is taking care of the points 1 and 4. The module needs to take care of 2 & 3.

The command mapping is usually listed in the "cmd" function in the class Rita\InputMapper\Simple. Just add your stuff there. The idea is, that the function receives the full user message and converts it into an array with the fields "module" (the module that should be called), "action" (the function that will be called) and "params" (if some parameters should be passed). The function that will then later be called, always need to return an Rita\Answer object.

# Executing and Deployment

There are currently three ways of running this application locally or a hosted provider:

**Plain**

Run the application via plain php - best way for local debugging:

```
php ./rita.php
```

**Docker**

Create a docker image with the compiled code compiled inside. You can run it like that on your local machine or use the image for shipping:

```
docker build -t rita .
docker run rita
```

**Heroku**

For running this project on Heroku, just push the master branch to your heroku application. The "Procfile" file will configure a worker-dyno for running the bot. You might need to activate the worker manually in your heroku setup.

# Next steps

The current next steps are planned:

* Move command mapping into module context
* Move connectors to own repositories and link them via composer
* Add connection to Evernote for creating ToDo lists