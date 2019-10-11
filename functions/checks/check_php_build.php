<?php

/**
 *
 * Script to check if all required extensions are compiled and loaded in PHP
 *
 *
 * We need the following mudules:
 *      - session
 *      - gmp
 *      - json
 *      - gettext
 *      - PDO
 *      - pdo_mysql
 *
 ************************************/


# Required extensions
$requiredExt  = array("PDO", "pdo_mysql", "mbstring", "iconv", "ctype", "gettext", "gmp", "json", "filter");

# Available extensions
$availableExt = get_loaded_extensions();

# Empty missing array to prevent errors
$missingExt[0] = "";

# if not all are present create array of missing ones
foreach ($requiredExt as $extension) {
    if (!in_array($extension, $availableExt)) {
        $missingExt[] = $extension;
    }
}

# check for PEAR functions
if ((@include_once 'PEAR.php') != true) {
	$missingExt[] = "php PEAR support";
}

# if any extension is missing print error and die!
if (sizeof($missingExt) != 1 || (phpversion() < "5.4") || PHP_INT_SIZE==4) {

    /* remove dummy 0 line */
    unset($missingExt[0]);

    /* Extensions error */
    if(sizeof($missingExt)>0) {
        $error[] = _('The following required PHP extensions are missing');
        foreach ($missingExt as $missing) {
            $error[] = $missing;
        }
        $error[] = _('Please recompile PHP to include missing extensions.');
    }
    /* php version error */
    elseif(phpversion() < "5.4") {
        $error[] = _('Unsupported PHP version');
        $error[] = _('From release 1.3.2+, at least PHP version 5.4 is required!');
        $error[] = _("Detected PHP version: ").phpversion();
    }
    /* 32-bit systems */
    else {
        $error[] = _('From phpIPAM release 1.4 onwards, 64bit system is required!');
    }

    $error[] = "";

    die(implode("\n", $error));
}