<?php

/* @debugging functions ------------------- */
ini_set('display_errors', 1);
error_reporting(E_ALL ^ E_NOTICE ^ E_STRICT);

/* @classes - general required ---------------------- */
require( dirname(__FILE__) . '/classes/class.PDO.php' );			//Class PDO - wrapper for database
require( dirname(__FILE__) . '/classes/class.Common.php' );			//Class common - common functions
require( dirname(__FILE__) . '/classes/class.phpipamAgent.php' );	//Class for phpipam agent
require( dirname(__FILE__) . '/classes/class.Thread.php');			//Threading
require( dirname(__FILE__) . '/classes/class.Scan.php' );			//Class for Scanning and pinging
//require( dirname(__FILE__) . '/classes/class.Mail.php' );			//Class for Mailing

/* get version */
include('version.php');

?>