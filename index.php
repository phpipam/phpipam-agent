<?php

/**
 *
 * phpipam-agent
 *
 *	phpipam scan agent to scan selected subnets and report back
 *	to main phpipam server.
 *
 *	Script must be called with update/discover argument
 *
 *	Documentation available here :
 *
 */

/* require classes */
require('functions/functions.php');
require('config.php');

# start phpipam-agent class
try {

	// for database connections
	if ($config['type']=="mysql") {
		// open db connection
		$Database = new Database_PDO ($config['db']['user'], $config['db']['pass'], $config['db']['host'], $config['db']['port'], $config['db']['name']);
		// test connection, will throw exception if it fails
		$Database->connect ();
		// new scan object
		$Scan = new Scan ($Database);
	}
	else {
		// scan without DB connection
		$Database = false;
	}

	// initialize and make default checks
	$phpipam_agent = new phpipamAgent ($Database);

	// set scan type - status update (update) or discover, must be provided via argv[1]
	$phpipam_agent->set_scan_type ($argv[1]);

	// execute
	$phpipam_agent->execute ();

	// update scan time
	$phpipam_agent->update_agent_scantime ();
}
//catch any exceptions and report the problem
catch ( Exception $e ) {
	print "--------------------\n";
	print $e->getMessage()."\n";
	print "--------------------\n";
	// die
	die();
}
?>
