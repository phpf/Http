<?php

namespace xpl\Http;

/**
 * Generic exception for errors that should send a specific HTTP status code.
 */
class Exception extends \RuntimeException
{

	protected $status_code = 404;
	protected $public_message;
	protected $allowed_values;
	protected static $allow_code_override = true;

	public function __construct($public_message = null, $code = 0, \Exception $previous = null) {
		if (isset($public_message)) {
			$this->setPublicMessage($public_message);
		}
		parent::__construct('An HTTP-related error occurred.', $code, $previous);
	}

	public function setStatusCode($code) {
		if (static::$allow_code_override) {
			$this->status_code = (int)$code;
		}
	}

	public function setAllowedValues($values) {
		$this->allowed_values = (array)$values;
	}

	public function setPublicMessage($str) {
		$this->public_message = $str;
	}

	public function hasStatusCode() {
		return isset($this->status_code);
	}

	public function hasAllowedValues() {
		return isset($this->allowed_values);
	}

	public function hasPublicMessage() {
		return isset($this->public_message);
	}

	public function getStatusCode() {
		return $this->status_code;
	}

	public function getAllowedValues() {
		return $this->allowed_values;
	}

	public function getPublicMessage() {
		return $this->public_message;
	}

}
