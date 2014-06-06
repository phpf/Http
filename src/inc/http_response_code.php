<?php

/**
 * Returns/sends HTTP response status code.
 * 
 * @param null|int $code	HTTP response status code.
 * @return int				Current response code.
 */
function http_response_code($code = null) {
	
	if (null === $code) {
		return isset($GLOBALS['HTTP_RESPONSE_CODE']) 
			? $GLOBALS['HTTP_RESPONSE_CODE'] 
			: 200;
	}
	
	$code = intval($code);
	$description = http_response_code_desc($code);
	
	if (empty($description)) {
		trigger_error("Invalid HTTP response status code given: '$code'.", E_USER_WARNING);
		return null;
	}
	
	// RFC2616 for PHP CGI
	// @link http://us3.php.net/manual/en/ini.core.php#ini.cgi.rfc2616-headers
	if (1 === ini_get('cgi.rfc2616_headers')) {
		$protocol = 'Status:';
	} else {
		$protocol = isset($_SERVER['SERVER_PROTOCOL']) 
			? $_SERVER['SERVER_PROTOCOL'] 
			: 'HTTP/1.0';
	}
	
	header("$protocol $code $description", true, $code);
	
	return $GLOBALS['HTTP_RESPONSE_CODE'] = $code;
}