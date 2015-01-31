<?php

/**
 * Returns/sends HTTP response status code.
 * 
 * @param null|int $code	HTTP response status code.
 * @return int				Current response code.
 */
function http_response_code($code = null) {
	
	static $_code;
	
	if (! isset($code)) {
		return isset($_code) ? $_code : 200;
	}
	
	$code = intval($code);
	$description = http_response_code_desc($code);
	
	if (empty($description)) {
		trigger_error("Invalid HTTP response status code given: '$code'.", E_USER_WARNING);
		return null;
	}
	
	// @see http://us3.php.net/manual/en/ini.core.php#ini.cgi.rfc2616-headers
	if (1 == ini_get('cgi.rfc2616_headers')) {
		$protocol = 'Status:';
	} else {
		$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
	}
	
	header(sprintf('%s %s %s', $protocol, $code, $description), true, $code);
	
	return $_code = $code;
}