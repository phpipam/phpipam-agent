<?php

/**
 *	phpIPAM agent class
 */

class phpipamAgent extends Common_functions {


	/**
	 * all possible connections
	 *
	 * (default value: null)
	 *
	 * @var null|array
	 * @access private
	 */
	private $types = null;

	/**
	 * config settings
	 *
	 * (default value: null)
	 *
	 * @var null|object
	 * @access protected
	 */
	protected $config = null;

	/**
	 * set mail override flag
	 *
	 * (default value: true)
	 *
	 * @var bool
	 * @access private
	 */
	private	$send_mail = true;

	/**
	 * array of address changes
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access public
	 */
	public $address_change = array();

	// set date for use throughout script

	/**
	 * time format
	 *
	 * (default value: false)
	 *
	 * @var bool
	 * @access private
	 */
	private $now     = false;

    /**
     * date format
     *
     * (default value: false)
     *
     * @var bool
     * @access private
     */
    private $nowdate = false;

	/**
	 *  agent details
	 *
	 * (default value: null)
	 *
	 * @var null|object
	 * @access private
	 */
	private $agent_details = null;

	/**
	 * connection type - selected
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access public
	 */
	public 	$conn_type = null;

	/**
	 * all connection types
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access private
	 */
	private $conn_types = null;

	/**
	 * scan type - selected
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access public
	 */
	public 	$scan_type = null;

	/**
	 * all scan types
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access private
	 */
	private $scan_types = null;

	/**
	 * ping type - selected
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access public
	 */
	public 	$ping_type = null;

	/**
	 * all ping types
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access public
	 */
	public 	$ping_types = null;

	/**
	 * for Database connection
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Database;

	/**
	 * scan object
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Scan;



	/**
	 * __construct method
	 *
	 * @param resource $Database
	 * @return void
	 */
	public function __construct ($Database) {
		parent::__construct();
		// Result
		$this->Result = new Result ();
		// read config file
		$this->config = (object) Config::ValueOf('config');
		// set time
		$this->set_now_time ();
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
	 * Sets execution start date in time and date format
	 *
	 * @access private
	 * @return void
	 */
	private function set_now_time () {
    	$this->nowdate  = date("Y-m-d H:i:s");
    	$this->now      = strtotime($this->nowdate);
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
			$this->Result->throw_exception (500, "Invalid connection type!");
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
			$this->Result->throw_exception (500, "Invalid scan type - $type! Valid options are ".implode(", ", $this->scan_types));
		}
		// ok, save
		$this->scan_type = $type;
	}

	/**
	 * Sets type of ping to be executed
	 *
	 * @access public
	 * @return void
	 */
	public function set_ping_type () {
		//validate
		if (!in_array($this->config->method, $this->ping_types)) {
			$this->Result->throw_exception (500, "Invalid ping method - \$config['method'] = \"".escape_input($this->config->method)."\"");
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
		    $this->Result->throw_exception (500, $error);
		}
	}

	/**
	 * Validates threading support
	 *
	 * @access private
	 * @return void
	 */
	private function validate_threading () {
		// only for threaded
		if($this->config->nonthreaded !== true) {
			// test to see if threading is available
			if(!PingThread::available($errmsg)) {
				$this->Result->throw_exception (500, "Threading is required for scanning subnets - Error: $errmsg\n");
			}
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
			$this->Result->throw_exception (500, "ping executable does not exist - \$config['pingpath'] = \"".escape_input($this->config->pingpath)."\"");
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
		// if non-threaded permitted remove pcntl requirement
		if ($this->config->nonthreaded === true) {
			unset($required_ext[2]);
		}
		// if api selected

		// result
		return $required_ext;
	}

 	/**
 	 * Resolves hostname
 	 *
 	 * @access public
 	 * @param object $address
 	 * @param boolean $override override DNS resolving flag
 	 * @return array
 	 */
 	public function resolve_address ($address, $override=false) {
	 	# make sure it is dotted format
	 	$address->ip = $this->transform_address ($address->ip_addr, "dotted");
		# if hostname is set try to check
		if(empty($address->hostname) || is_null($address->hostname)) {
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
				return array("class"=>"", "name"=>$address->hostname);
		}
	}

	/**
	 * Prints success
	 *
	 * @access private
	 * @param string|array $text
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
		// print
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
		$init = 'initialize_'.$this->conn_type;
		// init
		return $this->{$init} ();
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
		$this->Result->throw_exception (500, "API agent type not yet supported!");
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
			$this->Result->throw_exception (500, "Error: ".$e->getMessage());
		}
		// invalid
		if (is_null($agent)) {
			$this->Result->throw_exception (500, "Error: Invalid agent code");
		}
		// save agent details
		else {
			$this->agent_details = $agent;
		}
	}

	/**
	 * Update last check for agent
	 *
	 * @access public
	 * @return void
	 */
	public function update_agent_scantime () {
		// update access time
		try { $agent = $this->Database->runQuery("update `scanAgents` set `last_access` = ? where `id` = ? limit 1;", array($this->nowdate, $this->agent_details->id)); }
		catch (Exception $e) {
			$this->Result->throw_exception (500, "Error: ".$e->getMessage());
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
			$this->Result->throw_exception (500, "Error: ".$e->getMessage());
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
		$addresses 	   = (array) $addresses[1];

		// non-threaded?
		if ($this->config->nonthreaded === true) {
			// execute
			if ($this->ping_type=="fping")	{ $subnets = $this->mysql_scan_discover_hosts_fping_nonthreaded ($subnets, $addresses_tmp); }
			else							{ $subnets = $this->mysql_scan_discover_hosts_ping_nonthreaded  ($subnets, $addresses); }
		}
		else {
			// execute
			if ($this->ping_type=="fping")	{ $subnets = $this->mysql_scan_discover_hosts_fping ($subnets, $addresses_tmp); }
			else							{ $subnets = $this->mysql_scan_discover_hosts_ping  ($subnets, $addresses); }
		}

		// update database and send mail if requested
		$this->mysql_scan_update_write_to_db ($subnets);
		// reset dhcp
		if($this->config->remove_inactive_dhcp===true) {
			$this->reset_dhcp_addresses();
		}
		// reset autodiscovered DHCP addresses
		if($this->config->reset_autodiscover_addresses===true) {
			$this->reset_autodiscover_addresses();
		}

		// updatelast scantime
		foreach ($subnets as $s) {
			$this->update_subnet_status_scantime ($s->id);
		}
	}

	/**
	 * Update last scan date
	 *
	 * @method update_subnet_status_scantime
	 * @param  int $subnet_id
	 * @return void
	 */
	private function update_subnet_status_scantime ($subnet_id) {
		try { $this->Database->updateObject("subnets", array("id"=>$subnet_id, "lastScan"=>$this->nowdate), "id"); }
		catch (Exception $e) {}
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
		if ($this->ping_type=="fping")	{ $subnets = $this->mysql_scan_discover_hosts_fping ($subnets, $addresses_tmp); }
		else							{ $subnets = $this->mysql_scan_discover_hosts_ping  ($subnets, $addresses); }

		// update database and send mail if requested
		$this->mysql_scan_discovered_write_to_db ($subnets);
		// updatelast scantime
		foreach ($subnets as $s) {
			$this->update_subnet_discovery_scantime ($s->id);
		}
	}

	/**
	 * Update last discovery date
	 *
	 * @method update_subnet_discovery_scantime
	 * @param  int $subnet_id
	 * @return void
	 */
	private function update_subnet_discovery_scantime ($subnet_id) {
		try { $this->Database->updateObject("subnets", array("id"=>$subnet_id, "lastDiscovery"=>$this->nowdate), "id"); }
		catch (Exception $e) {}
	}

	/**
	 * This function fetches id, subnet and mask for all subnets
	 *
	 * @access private
	 * @param int $agentId (default:null)
	 * @return void
	 */
	private function mysql_fetch_subnets ($agentId = null ) {
		# null
		if (is_null($agentId) || !is_numeric($agentId))	{ return false; }
		# get type
		$type = $this->get_scan_type_field ();
		# fetch
		try { $subnets = $this->Database->getObjectsQuery("SELECT `id`,`subnet`,`sectionId`,`mask`,`resolveDNS`,`nameserverId` FROM `subnets` WHERE `scanAgent` = ? AND `$type` = 1 AND `isFolder` = 0 AND `mask` > 0;", array($agentId)); }
		catch (Exception $e) {
			$this->Result->throw_exception (500, "Error: ".$e->getMessage());
		}
		# die if nothing to scan
		if (sizeof($subnets)==0)	{ die(); }
        // if subnet has slaves dont check it
        foreach ($subnets as $k=>$s) {
    		try { $count = $this->Database->numObjectsFilter("subnets", "masterSubnetId", $s->id); }
    		catch (Exception $e) {
    			$this->Result->show("danger", _("Error: ").$e->getMessage());
    			return false;
    		}
        	if ($count>0) {
        		unset($subnets[$k]);
        	}
    	}
		# die if nothing to scan
		if (!isset($subnets))	   { die(); }
		# result
		return $subnets;
	}

	/**
	 * Fetches addresses to scan
	 *
	 * @access private
	 * @param array $subnets
	 * @param string $type (discovery, update)
	 * @return void
	 */
	private function mysql_fetch_addresses ($subnets, $type) {
		// array check
		if(!is_array($subnets))     { die(); }
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
	 * @param int $subnetId
	 * @return void
	 */
	private function mysql_check_slaves ($subnetId) {
		// int check
		if(!is_numeric($subnetId))	{ return false; }
		// check
		try { $count = $this->Database->numObjectsFilter("subnets", "masterSubnetId", $subnetId); }
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
	 * @param array $subnets
	 * @param array $addresses_tmp
	 * @return void
	 */
	private function mysql_scan_discover_hosts_fping ($subnets, $addresses_tmp) {
		$z = 0;			//addresses array index

		//run per MAX_THREADS
		for ($m=0; $m<sizeof($subnets); $m += $this->config->threads) {
		    // create threads
		    $threads = array();
		    //fork processes
		    for ($i = 0; $i < $this->config->threads; $i++) {
		    	//only if index exists!
		    	if(isset($subnets[$z])) {
					//start new thread
		            $threads[$z] = new PingThread( 'fping_subnet' );
					$threads[$z]->start_fping( $this->transform_to_dotted($subnets[$z]->subnet)."/".$subnets[$z]->mask );
				}
	            $z++;				//next index
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
		    }
		}

		//fping finds all subnet addresses, we must remove existing ones !
		foreach($subnets as $sk=>$s) {
			if (is_array($s->discovered)) {
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
        $num_of_addresses = sizeof($addresses);
        for ($m=0; $m<$num_of_addresses; $m += $this->config->threads) {

	        // create threads
	        $threads = array();

	        //fork processes
	        for ($i = 0; $i < $this->config->threads; $i++) {
	        	//only if index exists!
	        	if(isset($addresses[$z])) {
					//start new thread
		            $threads[$z] = new PingThread( 'ping_address' );
		            $threads[$z]->start( $this->transform_to_dotted( $addresses[$z]['ip_addr']) );
				}
				$z++;			//next index
	        }

	        // wait for all the threads to finish
	        while( !empty( $threads ) ) {
	            foreach( $threads as $index => $thread ) {
	                if( !$thread->isAlive() ) {
						//unset dead hosts
						if($thread->getExitCode() != 0) {
							unset($addresses[$index]);
						}
	                    //remove thread
	                    unset($threads[$index]);
	                }
	            }
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
	 * Discover new host with fping - nonthreaded
	 *
	 * @access private
	 * @param mixed $subnets
	 * @return void
	 */
	private function mysql_scan_discover_hosts_fping_nonthreaded ($subnets, $addresses_tmp) {
/*

		// run separately for each host
		foreach ($address as $a) {
			// ping
			$ping = fping_subnet ($this->transform_to_dotted($subnets[$z]->subnet)."/".$subnets[$z]->mask );
			// check result
			var_dump($ping);
		}

		//run per MAX_THREADS
		for ($m=0; $m<=sizeof($subnets); $m += $this->config->threads) {
		    // create threads
		    $threads = array();
		    //fork processes
		    for ($i = 0; $i <= $this->config->threads && $i <= sizeof($subnets); $i++) {
		    	//only if index exists!
		    	if(isset($subnets[$z])) {
					//start new thread
		            $threads[$z] = new PingThread( 'fping_subnet' );
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
			if (is_array($s->discovered)) {
				foreach($s->discovered as $rk=>$result) {
					if(!in_array($this->transform_to_decimal($result), $addresses_tmp[$s->id])) {
						unset($subnets[$sk]->discovered[$rk]);
					}
				}
				//rekey
				$subnets[$sk]->discovered = array_values($subnets[$sk]->discovered);
			}
		}
*/

		// return result
		return $subnets;
	}

	/**
	 * Discover new hosts with ping or pear - nonthreaded!
	 *
	 * @access private
	 * @param mixed $subnets
	 * @param mixed $addresses
	 * @return $subnets
	 */
	private function mysql_scan_discover_hosts_ping_nonthreaded ($subnets, $addresses) {

		for ($i = 0; $i <= sizeof($addresses); $i++) {
			ping_address( $this->transform_to_dotted( $addresses[$z]['ip_addr']) );
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
		if ($this->scan_type!=="fping") {
			unset($this->Database);
			$this->Database = new Database_PDO ();
		}
		// loop
		foreach($subnets as $s) {
			if(is_array($s->discovered)) {
				foreach($s->discovered as $ip) {
					// try to resolve hostname
					$tmp = new stdClass();
					$tmp->ip_addr = $ip;
					$hostname = $this->resolve_address($tmp, true);
					//set update query
					$values = array("subnetId"=>$s->id,
									"ip_addr"=>$this->transform_address($ip, "decimal"),
									"hostname"=>$hostname['name'],
									"description"=>"-- autodiscovered --",
									"note"=>"This host was autodiscovered on ".$this->nowdate. " by agent ".$this->agent_details->name,
									"lastSeen"=>$this->nowdate,
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
			$this->Result->throw_exception (500, "Error: ".$e->getMessage());
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
	 * @return voi
	 */
	private function mysql_scan_update_write_to_db ($subnets) {
		# reset db connection for ping / pear
		if ($this->can_type!=="fping") {
			unset($this->Database);
			$this->Database = new Database_PDO ();
		}
		// loop
		foreach ($subnets as $s) {
			if (is_array($s->discovered)) {
				foreach ($s->discovered as $ip) {
					# execute
					$query = "update `ipaddresses` set `lastSeen` = ? where `subnetId` = ? and `ip_addr` = ? limit 1;";
					$vars  = array($this->nowdate, $s->id, $this->transform_address($ip, "decimal"));

					try { $this->Database->runQuery($query, $vars); }
					catch (Exception $e) {
						$this->Result->throw_exception(500, "Error: ".$e->getMessage());
					}
				}
			}
		}

	}

	/**
	 * Resets DHCP Adresses
	 *
	 * @access private
	 * @return void
	 */
	private function reset_dhcp_addresses () {

		# reset db connection
		unset($this->Database);
		$this->Database = new Database_PDO ();

		# Get all used DHCP addresses
		$query = "SELECT `ip_addr`, `subnetId`, `lastSeen` FROM `ipaddresses` WHERE `state` = ? AND NOT `lastSeen` = ?;";
		$vars = array("4", "0000-00-00 00:00:00");

		// fetch details
                try { $DHCPAddresses = $this->Database->getObjectsQuery($query, $vars); }
		catch (Exception $e) {
		$this->Result->throw_exception (500, "Error: ".$e->getMessage());
		}

		# Get Warning and Offline time
		$query = "select `pingStatus` from `settings`;";

		// fetch details
                try { $statuses = $this->Database->getObjectsQuery($query); }
                catch (Exception $e) {
                $this->Result->throw_exception (500, "Error: ".$e->getMessage());
                }

		# Convert stdClass Objects to arrays
		$statuses = json_decode(json_encode($statuses), True);
		$DHCPAddresses = json_decode(json_encode($DHCPAddresses), True);

		$statuses = explode(";", $statuses['0']['pingStatus']);

		foreach ($DHCPAddresses as $addr) {
			$tDiff = time() - strtotime($addr['lastSeen']);

			if ( $tDiff > $statuses['1'])
			{
				$query = "UPDATE `ipaddresses` SET `lastSeen` = ?, hostname = '' WHERE `subnetId` = ? AND `ip_addr` = ? limit 1;";
				$vars  = array("0000-00-00 00:00:00", $addr['subnetId'], $addr['ip_addr']);

				try { $this->Database->runQuery($query, $vars); }
				catch (Exception $e) {
					$this->Result->throw_exception(500, "Error: ".$e->getMessage());
				}
			}
		}
	}


	/**
	 * Resets autodiscovered Adresses
	 *
	 * @access private
	 * @return void
	 */
	private function reset_autodiscover_addresses () {

		# reset db connection
		unset($this->Database);
		$this->Database = new Database_PDO ();

		# Get all autodiscoverd IPs
		$query = "SELECT `ip_addr`, `subnetId`, `lastSeen` FROM `ipaddresses` WHERE `description` = ? AND NOT `lastSeen` = ?;";
		$vars = array("-- autodiscovered --", "0000-00-00 00:00:00");

		// fetch details
        try { $AutoDiscAddresses = $this->Database->getObjectsQuery($query, $vars); }
		catch (Exception $e) {
			$this->Result->throw_exception (500, "Error: ".$e->getMessage());
		}

		# Get Warning and Offline time
		$query = "select `pingStatus` from `settings`;";

		// fetch details
        try { $statuses = $this->Database->getObjectsQuery($query); }
        catch (Exception $e) {
        	$this->Result->throw_exception (500, "Error: ".$e->getMessage());
        }

		# Convert stdClass Objects to arrays
		$statuses = json_decode(json_encode($statuses), True);
		$AutoDiscAddresses = json_decode(json_encode($AutoDiscAddresses), True);

		$statuses = explode(";", $statuses['0']['pingStatus']);

		foreach ($AutoDiscAddresses as $addr) {
			$tDiff = time() - strtotime($addr['lastSeen']);

			if ( $tDiff > $statuses['1']) {
				## Delete IP
				$field  = "subnetId";   $value  = $addr['subnetId'];
	            $field2 = "ip_addr";    $value2 = $addr['ip_addr'];

		        try { $this->Database->deleteRow("ipaddresses", $field, $value, $field2, $value2); }
				catch (Exception $e) {
                		$this->Result->throw_exception (500, "Error: ".$e->getMessage());
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
		else 										{ $this->Result->throw_exception (500, "Invalid scan type!"); }
	}


	private function scan_discovery_send_mail () {

	}

}
