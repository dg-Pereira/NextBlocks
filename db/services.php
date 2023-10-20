<?php

$services = array(
    'mypluginservice' => array(                      //the name of the web service
        'functions' => array ('mod_nextblocks_save_workspace'), //web service functions of this service
        'requiredcapability' => '',                //if set, the web service user need this capability to access
        //any function of this service. For example: 'some/capability:specified'
        'restrictedusers' => 0,                      //if enabled, the Moodle administrator must link some user to this service
        //into the administration
        'enabled' => 1,                               //if enabled, the service can be reachable on a default installation
        'shortname'=>'nextblocksservice' //the short name used to refer to this service from elsewhere including when fetching a token
    )
);

$functions = array(
    'mod_nextblocks_save_workspace' => array(
        'classname' => 'mod_nextblocks_external',
        'methodname' => 'save_workspace',
        'classpath' => 'mod/nextblocks/externallib.php',
        'description' => 'Save current workspace',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ),
);
