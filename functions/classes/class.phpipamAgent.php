<?php

/**
 *	phpIPAM agent class
 */

class phpipamAgent extends Common_functions {

	/**
	 * object holders
	 */

	# general
	private $types = null;					// (array) all possible connections
	private $config = null;					// (object) config settings
	private	$send_mail = true;				// (bool) set mail override flag
	public $address_change = array();		// array of address changes

	// agent details
	private $agent_details  = null;			// (object) agent details
	// connection types
	public 	$conn_type = null;				// connection type - selected
	private $conn_types = null;				// all connection types
	// scan types
	public 	$scan_type = null;				// scan type - selected
	private $scan_types = null;				// all scan types
	// ping methods
	public 	$ping_type = null;				// ping type - selected
	public 	$ping_types = null;				// all ping types

	// database object
	protected $Database;					//for Database connection
	protected $Scan;						//scan object



	/**
	 * __construct method
	 *
	 * @access public
	 * @return void
	 */
	public function __construct ($Database) {
		// read config file
		$this->read_config ();
		// set valid connection types
		$this->set_valid_conn_types ();
		// set valid scan types
		$this->set_valid_scan_types ();
		// set valid scan types
		$this->set_valid_ping_types ();
		// set conn type
		$this->set_conn_type ();
		// set ping type
		$this->set_ping_type ();
		// validate php build
		$this->validate_php_build ();
		// validate threading
		$this->validate_threading ();
		// validate ping path
		$this->validate_ping_path ();
		// save database
		$this->Database = $Database;
	}









	/**
	 * Read config from config.php file
	 *
	 * @access private
	 * @return void
	 */
	private function read_config () {
		// verify that config file exists
		if (!file_exists(dirname(__FILE__) . "/../../config.php")) {
			$this->throw_exception ("config.php file missing. Copy default from config.php.dist and set required settings!");
		} else {
			// get config
			require(dirname(__FILE__) . "/../../config.php");
			// save
			$this->config = (object) $config;
		}
	}

	/**
	 * Defines valid connection types to phpipam server
	 *
	 * @access private
	 * @return void
	 */
	private function set_valid_conn_types () {
		// set valid types
		$this->conn_types = array(
							'api',
							'mysql'
						);
	}

	/**
	 * Sets valid scan types (update, discover)
	 *
	 * @access private
	 * @return void
	 */
	private function set_valid_scan_types () {
		// set valid types
		$this->scan_types = array(
							'update',
							'discover'
							);
	}

	/**
	 * Sets valid ping types (ping, fping, pear)
	 *
	 * @access private
	 * @return void
	 */
	private function set_valid_ping_types () {
		// set valid types
		$this->ping_types = array(
							'ping',
							'fping',
							'pear'
							);
	}

	/**
	 * Validates connection type
	 *
	 * @access private
	 * @return void
	 */
	private function validate_conn_type () {
		//validate
		if (!in_array($this->config->type, $this->conn_types)) {
			$this->throw_exception ("Invalid connection type!");
		}
	}

	/**
	 * Sets connection type to database
	 *
	 * @access private
	 * @return void
	 */
	private function set_conn_type () {
		// validate
		$this->validate_conn_type ();
		// save
		$this->conn_type = $this->config->type;
	}

	/**
	 * Sets type of scan to be executed
	 *
	 * @access public
	 * @param mixed $type
	 * @return void
	 */
	public function set_scan_type ($type) {
		//validate
		if (!in_array($type,$this->scan_types)) {
			$this->throw_exception ("Invalid scan type - $type! Valid options are ".implode(", ", $this->scan_types));
		}
		// ok, save
		$this->scan_type = $type;
	}

	/**
	 * Sets type of ping to be executed
	 *
	 * @access public
	 * @param mixed $type
	 * @return void
	 */
	public function set_ping_type () {
		//validate
		if (!in_array($this->config->method, $this->ping_types)) {
			$this->throw_exception ("Invalid ping type - $this->ping_type!");
		}
		// ok, save
		$this->ping_type = $this->config->method;
	}

	/**
	 * Validates php build
	 *
	 * @access private
	 * @return void
	 */
	private function validate_php_build () {
		// set required extensions
		$required_ext = $this->set_required_extensions ();
		// get available ext
		$available_ext = get_loaded_extensions();
		// loop and check
		foreach ($required_ext as $e) {
			if (!in_array($e, $available_ext)) {
				$missing_ext[] = $e;
			}
		}

		// die if missing
		if (isset($missing_ext)) {
		    $error[] = "The following required PHP extensions are missing for phpipam-agent:";
		    foreach ($missing_ext as $missing) {
		        $error[] ="  - ". $missing;
		    }
		    $error[] = "Please recompile PHP to include missing extensions.";

			// die
		    $this->throw_exception ($error);
		}
	}

	/**
	 * Validates threading support
	 *
	 * @access private
	 * @return void
	 */
	private function validate_threading () {
		if(!Thread::available()) {
			$this->throw_exception ("Threading is required for scanning subnets. Please recompile PHP with pcntl extension");
		}
	}

	/**
	 * Validate path for ping file
	 *
	 * @access private
	 * @return void
	 */
	private function validate_ping_path () {
		if(!file_exists($this->config->pingpath)) {
			$this->throw_exception ("Invalid ping path!");
		}
	}

	/**
	 * Sets required extensions for agent to run
	 *
	 * @access private
	 * @return void
	 */
	private function set_required_extensions () {
		// general
		$required_ext = array("gmp", "json", "pcntl");

		// if mysql selected
		if ($this->type=="mysql") {
			$required_ext = array_merge($required_ext, array("PDO", "pdo_mysql"));
		}
		// if api selected

		// result
		return $required_ext;
	}

 	/**
 	 * Resolves hostname
 	 *
 	 * @access public
 	 * @param mixed $address		address object
 	 * @param boolena $override		override DNS resolving flag
 	 * @return void
 	 */
 	public function resolve_address ($address, $override=false) {
	 	# make sure it is dotted format
	 	$address->ip = $this->transform_address ($address->ip_addr, "dotted");
		# if dns_nameis set try to check
		if(empty($address->dns_name) || is_null($address->dns_name)) {
			# if permitted in settings
			if($this->settings->enableDNSresolving == 1 || $override) {
				# resolve
				$resolved = gethostbyaddr($address->ip);
				if($resolved==$address->ip)		$resolved="";			//resolve fails

				return array("class"=>"resolved", "name"=>$resolved);
			}
			else {
				return array("class"=>"", "name"=>"");
			}
		}
		else {
				return array("class"=>"", "name"=>$address->dns_name);
		}
	}

	/**
	 * Prints success
	 *
	 * @access private
	 * @param mixed $text
	 * @return void
	 */
	private function print_success ($text) {
		// array ?
		if (is_array($text)) {
			foreach ($text as $t) {
				$success[] = $t;
			}
			$success[] = "";
		} else {
			$success[] = $text."\n";
		}
		// throw exception
		print implode("\n",$success);
	}

	/**
	 * Executes scan / discover.
	 *
	 * @access public
	 * @return void
	 */
	public function execute () {
		// initialize proper function
		$init = initialize_.$this->conn_type;
		// init
		return $this->$init ();
	}


	/**
	 * Sets scan object
	 *
	 * @access private
	 * @return void
	 */
	private function scan_set_object () {
		# initialize objects
		$this->Scan	= new Scan ($this->Database);
		// set ping statuses
		$statuses = explode(";", $this->settings->pingStatus);
	}









	/**
	 * @api functions
	 * ---------------------------------
	 */

	/**
	 * Initialize API
	 *
	 * @access private
	 * @return void
	 */
	private function initialize_api () {
		$this->throw_exception ("API agent type not yet supported!");
	}









	/**
	 * @mysql functions
	 * ---------------------------------
	 */

	/**
	 * Initialized mysql connection
	 *
	 * @access private
	 * @return void
	 */
	private function initialize_mysql () {
		// test connection
		$this->mysql_test_connection ();

		// validate key and fetch agent
		$this->mysql_validate_key ();

		// fetch settings
		$this->mysql_fetch_settings ();

		// initialize scan object
		$this->scan_set_object ();

		// we have subnets, now check
		return $this->scan_type == "update" ? $this->mysql_scan_update_host_statuses () : $this->mysql_scan_discover_hosts ();
	}

	/**
	 * Test connection to database server.
	 *
	 *	Will throw exception if failure
	 *
	 * @access private
	 * @return void
	 */
	private function mysql_test_connection () {
		$this->Database->connect();
	}

	/**
	 * Validates key for phpipam agent and fetches id
	 *
	 * @access private
	 * @return void
	 */
	private function mysql_validate_key () {
		// fetch details
		try { $agent = $this->Database->getObjectQuery("select * from `scanAgents` where `code` = ? and `type` = 'mysql' limit 1;", array($this->config->key)); }
		catch (Exception $e) {
			$this->throw_exception ("Error: ".$e->getMessage());
		}
		// invalid
		if (is_null($agent)) {
			$this->throw_exception ("Error: Invalid agent code");
		}
		// save agent details
		else {
			$this->agent_details = $agent;
		}

		// update access time
		try { $agent = $this->Database->runQuery("update `scanAgents` set `last_access` = ? where `id` = ? limit 1;", array(date("Y-m-d H:i:s"), $this->agent_details->id)); }
		catch (Exception $e) {
			$this->throw_exception ("Error: ".$e->getMessage());
		}
	}

	/**
	 * Fetches settings from master database
	 *
	 * @access private
	 * @return void
	 */
	private function mysql_fetch_settings () {
		# fetch
		try { $settings = $this->Database->getObject("settings", 1); }
		catch (Exception $e) {
			$this->throw_exception ("Error: ".$e->getMessage());
		}
		# save
		$this->settings = $settings;
	}


	/**
	 * Check for last statuses of hosts
	 *
	 * @access public
	 * @return void
	 */
	public function mysql_scan_update_host_statuses () {
		// prepare addresses
		$subnets = $this->mysql_fetch_subnets ($this->agent_details->id);
		// fetch addresses
		$addresses = $this->mysql_fetch_addresses ($subnets, "update");
		// save existing and reindexed
		$addresses_tmp = $addresses[0];
		$addresses 	   = $addresses[1];

		// execute
		if ($this->ping_type=="fping")	{ $subnets = $this->mysql_scan_discover_hosts_fping ($subnets, $addresses_tmp, $addresses); }
		else							{ $subnets = $this->mysql_scan_discover_hosts_ping  ($subnets, $addresses); }

		// update database and send mail if requested
		$this->mysql_scan_update_write_to_db ($subnets);
	}

	/**
	 * Scan for new hosts in selected networks
	 *
	 * @access public
	 * @return void
	 */
	public function mysql_scan_discover_hosts () {
		// prepare addresses
		$subnets = $this->mysql_fetch_subnets ($this->agent_details->id);
		// fetch addresses
		$addresses = $this->mysql_fetch_addresses ($subnets, "discovery");
		// save existing and reindexed
		$addresses_tmp = $addresses[0];
		$addresses 	   = $addresses[1];

		// execute
		if ($this->ping_type=="fping")	{ $subnets = $this->mysql_scan_discover_hosts_fping ($subnets, $addresses_tmp, $addresses); }
		else							{ $subnets = $this->mysql_scan_discover_hosts_ping  ($subnets, $addresses); }

		// update database and send mail if requested
		$this->mysql_scan_discovered_write_to_db ($subnets);
	}

	/**
	 * This function fetches id, subnet and mask for all subnets
	 *
	 * @access private
	 * @param int $type (default:update) - pingSubnet, discoverSubnet
	 * @param int $agentId (default:null)
	 * @return void
	 */
	private function mysql_fetch_subnets ($agentId = null ) {
		# null
		if (is_null($agentId) || !is_numeric($agentId))	{ return false; }
		# get type
		$type = $this->get_scan_type_field ();
		# fetch
		try { $subnets = $this->Database->getObjectsQuery("SELECT `id`,`subnet`,`sectionId`,`mask` FROM `subnets` where `scanAgent` = ? and `$type` = 1 and `isFolder`= 0 and `mask` > '0' and subnet > 16843009;", array($agentId)); }
		catch (Exception $e) {
			$this->throw_exception ("Error: ".$e->getMessage());
		}
		# die if nothing to scan
		if (sizeof($subnets)==0)	{ die(); }
		# result
		return $subnets;
	}

	/**
	 * Fetches addresses to scan
	 *
	 * @access private
	 * @param mixed $subnets
	 * @param mixed $type (discovery, update)
	 * @return void
	 */
	private function mysql_fetch_addresses ($subnets, $type) {
		// loop through subnets and save addresses to scan
		foreach($subnets as $s) {
			// if subnet has slaves dont check it
			if ($this->mysql_check_slaves ($s->id) === false) {
				$addresses_tmp[$s->id] = $this->Scan->prepare_addresses_to_scan ($type, $s->id);
			}
		}
		// if false exit
		if(!isset($addresses_tmp))	{ die(); }

		// reindex
		if (isset($addresses_tmp)) {
			foreach($addresses_tmp as $s_id=>$a) {
				foreach($a as $ip) {
					$addresses[] = array("subnetId"=>$s_id, "ip_addr"=>$ip);
				}
			}
		}
		else {
			$addresses_tmp 	= array();
			$addresses 		= array();
		}
		// return result - $addresses_tmp = existing, $addresses = reindexed
		return array($addresses_tmp, $addresses);
	}

	/**
	 * Check if subnet has slaves
	 *
	 * @access private
	 * @param mixed $subnetId
	 * @return void
	 */
	private function mysql_check_slaves ($subnetId) {
		// check
		try { $count = $this->Database->numObjectsFilter("subnets", "masterSubnetId", $subnetid); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage());
			return false;
		}
		# result
		return $count>0 ? true : false;
	}

	/**
	 * Discover new host with fping
	 *
	 * @access private
	 * @param mixed $subnets
	 * @return void
	 */
	private function mysql_scan_discover_hosts_fping ($subnets, $addresses_tmp, $addresses) {
		$z = 0;			//addresses array index

		//run per MAX_THREADS
		for ($m=0; $m<=sizeof($subnets); $m += $this->config->threads) {
		    // create threads
		    $threads = array();
		    //fork processes
		    for ($i = 0; $i <= $this->config->threads && $i <= sizeof($subnets); $i++) {
		    	//only if index exists!
		    	if(isset($subnets[$z])) {
					//start new thread
		            $threads[$z] = new Thread( 'fping_subnet' );
					$threads[$z]->start_fping( $this->transform_to_dotted($subnets[$z]->subnet)."/".$subnets[$z]->mask );
		            $z++;				//next index
				}
		    }
		    // wait for all the threads to finish
		    while( !empty( $threads ) ) {
				foreach($threads as $index => $thread) {
					$child_pipe = "/tmp/pipe_".$thread->getPid();

					if (file_exists($child_pipe)) {
						$file_descriptor = fopen( $child_pipe, "r");
						$child_response = "";
						while (!feof($file_descriptor)) {
							$child_response .= fread($file_descriptor, 8192);
						}
						//we have the child data in the parent, but serialized:
						$child_response = unserialize( $child_response );
						//store
						$subnets[$index]->discovered = $child_response;
						//now, child is dead, and parent close the pipe
						unlink( $child_pipe );
						unset($threads[$index]);
					}
				}
		        usleep(200000);
		    }
		}

		//fping finds all subnet addresses, we must remove existing ones !
		foreach($subnets as $sk=>$s) {
			if (isset($s->discovered)) {
				foreach($s->discovered as $rk=>$result) {
					if(!in_array($this->transform_to_decimal($result), $addresses_tmp[$s->id])) {
						unset($subnets[$sk]->discovered[$rk]);
					}
				}
				//rekey
				$subnets[$sk]->discovered = array_values($subnets[$sk]->discovered);
			}
		}

		// return result
		return $subnets;
	}

	/**
	 * Discover new hosts with ping or pear
	 *
	 * @access private
	 * @param mixed $subnets
	 * @param mixed $addresses
	 * @return void
	 */
	private function mysql_scan_discover_hosts_ping ($subnets, $addresses) {
		$z = 0;			//addresses array index

		//run per MAX_THREADS
	    for ($m=0; $m<=sizeof($addresses); $m += $this->config->threads) {
	        // create threads
	        $threads = array();

	        //fork processes
	        for ($i = 0; $i <= $this->config->threads && $i <= sizeof($addresses); $i++) {
	        	//only if index exists!
	        	if(isset($addresses[$z])) {
					//start new thread
		            $threads[$z] = new Thread( 'ping_address' );
		            $threads[$z]->start( $this->transform_to_dotted( $addresses[$z]['ip_addr']) );
					$z++;			//next index
				}
	        }

	        // wait for all the threads to finish
	        while( !empty( $threads ) ) {
	            foreach( $threads as $index => $thread ) {
	                if( ! $thread->isAlive() ) {
						//unset dead hosts
						if($thread->getExitCode() != 0) {
							unset($addresses[$index]);
						}
	                    //remove thread
	                    unset( $threads[$index]);
	                }
	            }
	            usleep(200000);
	        }
		}

		//ok, we have all available addresses, rekey them
		if (sizeof($addresses)>0) {
			foreach($addresses as $a) {
				$add_tmp[$a['subnetId']][] = $this->transform_to_dotted($a['ip_addr']);
			}
			//add to scan_subnets as result
			foreach($subnets as $sk=>$s) {
				if(isset($add_tmp[$s->id])) {
					$subnets[$sk]->discovered = $add_tmp[$s->id];
				}
			}
		}

		// return result
		return $subnets;
	}

	/**
	 * Write discovered hosts to database
	 *
	 * @access private
	 * @param mixed $subnets
	 * @return void
	 */
	private function mysql_scan_discovered_write_to_db ($subnets) {
		# insert to database
		$discovered = 0;				//for mailing

		# reset db connection for ping / pear
		if ($this->can_type!=="fping") {
			unset($this->Database);
			$this->Database = new Database_PDO ($this->config->db['user'], $this->config->db['pass'], $this->config->db['host'], $this->config->db['port'], $this->config->db['name']);
		}
		// loop
		foreach($subnets as $s) {
			if(sizeof(@$s->discovered)>0) {
				foreach($s->discovered as $ip) {
					// try to resolve hostname
					$tmp = new stdClass();
					$tmp->ip_addr = $ip;
					$hostname = $this->resolve_address($tmp, true);
					//set update query
					$values = array("subnetId"=>$s->id,
									"ip_addr"=>$this->transform_address($ip, "decimal"),
									"dns_name"=>$hostname['name'],
									"description"=>"-- autodiscovered --",
									"note"=>"This host was autodiscovered on ".date("Y-m-d H:i:s"). " by agent ".$this->agent_details->name,
									"lastSeen"=>date("Y-m-d H:i:s"),
									"state"=>"2"
									);
					//insert
					$this->mysql_insert_address($values);

					//set discovered
					$discovered++;
				}
			}
		}

		// mail ?
		if($discovered>0 && $this->config->sendmail===true) {
			$this->scan_discovery_send_mail ();
		}

		// ok
		return true;
	}

	/**
	 * Inserts new address to database
	 *
	 * @access private
	 * @param mixed $insert
	 * @return void
	 */
	private function mysql_insert_address ($insert) {
		# execute
		try { $this->Database->insertObject("ipaddresses", $insert); }
		catch (Exception $e) {
			$this->throw_exception ("Error: ".$e->getMessage());
			return false;
		}
		# ok
		return true;
	}

	/**
	 * Update statuses for alive hosts
	 *
	 * @access private
	 * @param mixed $subnets
	 * @return void
	 */
	private function mysql_scan_update_write_to_db ($subnets) {
		# reset db connection for ping / pear
		if ($this->can_type!=="fping") {
			unset($this->Database);
			$this->Database = new Database_PDO ($this->config->db['user'], $this->config->db['pass'], $this->config->db['host'], $this->config->db['port'], $this->config->db['name']);
		}
		// loop
		foreach ($subnets as $s) {
			if (sizeof($s->discovered)>0) {
				foreach ($s->discovered as $ip) {
					# execute
					$query = "update `ipaddresses` set `lastSeen` = ? where `subnetId` = ? and `ip_addr` = ? limit 1;";
					$vars  = array(date("Y-m-d H:i:s"), $s->id, $this->transform_address($ip, "decimal"));

					try { $this->Database->runQuery($query, $vars); }
					catch (Exception $e) {
						$this->throw_exception("Error: ".$e->getMessage());
					}
				}
			}
		}

	}














	/**
	 * @common functions for both methods
	 * ---------------------------------
	 */

	/**
	 * Sets mysql field name from scan type
	 *
	 * @access private
	 * @return void
	 */
	private function get_scan_type_field () {
		if ($this->scan_type == "update")			{ return "pingSubnet"; }
		elseif ($this->scan_type == "discover")		{ return "discoverSubnet"; }
		else 										{ $this->throw_exception ("Invalid scan type!"); }
	}


	private function scan_discovery_send_mail () {

	}

}