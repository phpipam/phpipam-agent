<?php

/**
 * Dummy logging class to make importing code from phpIPAM codebase eaasier.
 */

class Logging {

	/**
	 * write log function
	 *
	 * @access public
	 * @param mixed $command
	 * @param mixed $details (default: NULL)
	 * @param int $severity (default: 0)
	 * @param mixed $username (default: NULL)
	 * @return void
	 */
	public function write ($command, $details = NULL, $severity = 0, $username = null) {
	}

	/**
	 * Write new changelog to db or send to syslog
	 *
	 * @access public
	 * @param string $object_type
	 * @param string $action
	 * @param string $result
	 * @param array $old (default: array())
	 * @param array $new (default: array())
	 * @param bool $mail_changelog (default: true)
	 * @return boolean|null
	 */
	public function write_changelog ($object_type, $action, $result, $old = array(), $new = array(), $mail_changelog = true) {
	}

}
