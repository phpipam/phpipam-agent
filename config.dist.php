<?php

/*	phpipam agent config file
 ******************************/

# set connection type
# 	api,mysql;
# ******************************/
$config['type'] = "mysql";

# set agent key
# ******************************/
$config['key'] = "aad984d8314fcf644d3fb46886ea461f";

# set scan method and path to ping file
#	ping, fping or pear
# ******************************/
//$config['method'] 	= "pear";
//$config['pingpath'] = "/sbin/ping";

$config['method'] 	= "fping";
$config['pingpath'] = "/usr/local/sbin/fping";

# permit non-threaded checks (default: false)
# ******************************/
$config['nonthreaded'] = false;

# how many concurrent threads (default: 32)
# ****************************************/
$config['threads']  = 32;

# api settings, if api selected
# ******************************/
$config['api']['key'] = "";

# send mail diff
# ******************************/
$config['sendmail'] = false;

# remove inactive DHCP addresses
#
# 	reset_autodiscover_addresses: will remove addresses if description -- autodiscovered -- and is offline
# 	remove_inactive_dhcp		: will remove inactive dhcp addresses
# ******************************/
$config['reset_autodiscover_addresses'] = false;
$config['remove_inactive_dhcp']         = false;


# mysql db settings, if mysql selected
# ******************************/
$config['db']['host'] = "localhost";
$config['db']['user'] = "phpipam";
$config['db']['pass'] = "phpipamadmin";
$config['db']['name'] = "phpipam";
$config['db']['port'] = 3306;