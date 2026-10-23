<?php

require( dirname(__FILE__) .  '/checks/check_php_build.php' );

/* @config file ------------------ */
require_once( dirname(__FILE__) . '/classes/class.Config.php' );
$config = Config::ValueOf('config');

/**
 * timezone
 *
 *	lastSeen is written in PHP time, it must match phpIPAM server timezone.
 *	PHP falls back to UTC when date.timezone is not set in php.ini, so use
 *	$config['timezone'] if set, otherwise system timezone.
 ******************************/
if (!empty($config['timezone'])) {
	date_default_timezone_set($config['timezone']);
}
elseif (!get_cfg_var('date.timezone')) {		// ini_get() reports default "UTC" on PHP 8.2+, get_cfg_var() only php.ini/-d values
	$system_tz = getenv('TZ') ?: '';
	if ($system_tz === '' && is_link('/etc/localtime')) {
		$system_tz = preg_replace('#^.*/zoneinfo/#', '', readlink('/etc/localtime'));
	}
	if ($system_tz === '' && is_readable('/etc/timezone')) {
		$system_tz = trim(file_get_contents('/etc/timezone'));
	}
	if ($system_tz !== '' && in_array($system_tz, timezone_identifiers_list())) {
		date_default_timezone_set($system_tz);
	}
}

/**
 * proxy to use for every internet access like update check
 ******************************/
if (Config::ValueOf('proxy_enabled') == true) {
	$proxy_settings = [
		'proxy'           => 'tcp://'.Config::ValueOf('proxy_server').':'.Config::ValueOf('proxy_port'),
		'request_fulluri' => true];

	if (Config::ValueOf('proxy_use_auth') == true) {
		$proxy_auth = base64_encode(Config::ValueOf('proxy_user').':'.Config::ValueOf('proxy_pass'));
		$proxy_settings['header'] = "Proxy-Authorization: Basic ".$proxy_auth;
	}
	stream_context_set_default (['http' => $proxy_settings]);

	/* for debugging proxy config uncomment next line */
	// var_dump(stream_context_get_options(stream_context_get_default()));
}

/* global and missing functions */
require('global_functions.php');

/* @http only cookies ------------------- */
if(php_sapi_name()!="cli")
	ini_set('session.cookie_httponly', 1);

/* @debugging functions ------------------- */
if(Config::ValueOf('debugging')==true) {
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL & ~E_NOTICE);
}
else {
	ini_set('display_errors', 0);
	ini_set('display_startup_errors', 0);
	error_reporting(E_ERROR ^ E_WARNING);
}

// Fix JSON_UNESCAPED_UNICODE for PHP 5.3
defined('JSON_UNESCAPED_UNICODE') or define('JSON_UNESCAPED_UNICODE', 256);

/* @classes ---------------------- */

require( dirname(__FILE__) . '/classes/class.Common.php' );
require( dirname(__FILE__) . '/classes/class.PDO.php' );
require( dirname(__FILE__) . '/classes/class.Log.php' );
require( dirname(__FILE__) . '/classes/class.Addresses.php' );
require( dirname(__FILE__) . '/classes/class.Subnets.php' );
require( dirname(__FILE__) . '/classes/class.Result.php' );
require( dirname(__FILE__) . '/classes/class.Mail.php' );
require( dirname(__FILE__) . '/classes/class.Scan.php' );
require( dirname(__FILE__) . '/classes/class.Thread.php' );
require( dirname(__FILE__) . '/classes/class.phpipamAgent.php' );

/* get version */
include('version.php');
