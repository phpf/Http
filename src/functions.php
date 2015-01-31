<?php

namespace xpl\Http {
	class functions {
		// dummy class
	}
}

namespace {
	
	use xpl\Http\Server\Server;
	use xpl\Http\Util;
	
	/**
	 * http_response_code
	 * for PHP < 5.4
	 * @see inc/http_response_code.php
	 */
	if (! function_exists('http_response_code')) {
		require __DIR__.'/inc/http_response_code.php';
	}
	
	/**
	 * http_build_url
	 * @see inc/http_build_url.php
	 */
	if (! function_exists('http_build_url')) {
		require __DIR__.'/inc/http_build_url.php';
	}
	
	/**
	 * Returns HTTP-relevant environment (server) information.
	 * 
	 * @param string $var One of 'ssl', 'host', 'domain', or 'protocol'.
	 * @return mixed 'ssl' returns boolean; the rest return a string.
	 */
	function http_env($var) {
		switch(strtolower($var)) {
			
			case 'ssl':
				static $ssl;
				if (! isset($ssl)) {
					if ($https = getenv('https')) {
						$ssl = ('on' === strtolower($https) || 1 == $https);
					} else if ('https' === getenv('http_x_forwarded_proto') || 443 == getenv('server_port')) {
						$ssl = true;
					} else {
						$ssl = false;
					}
				}
				return $ssl;
				
			case 'host':
				static $host;
				if (! isset($host)) {
					$host = rtrim(getenv('http_host'), '/\\').rtrim(dirname(getenv('script_name')), '/\\');
				}
				return $host;
			
			case 'domain':
				static $domain;
				if (! isset($domain)) {
					$domain = 'http'.(http_env('ssl') ? 's' : '').'://'.ltrim(http_env('host'), '/');
				}
				return $domain;
			
			case 'protocol':
				return http_env('ssl') ? 'https' : 'http';
			
			default:
				trigger_error("Invalid HTTP env variable '$var'.");
				return null;
		}
	}
	
	/**
	 * (Some) functions provided by PECL HTTP extension (v1):
	 * * http_date()
	 * * http_redirect()
	 * * http_send_status()
	 * * http_send_content_type()
	 * * http_send_content_disposition()
	 * * http_send_file()
	 * * http_get_request_body()
	 * * http_get_request_body_stream()
	 * * http_get_request_headers()
	 * * http_get_request_header()
	 * * http_match_request_header()
	 * * http_negotiate_content_type()
	 * * http_negotiate_language()
	 */
	
	if (! extension_loaded('http') || version_compare(phpversion('http'), "2.0", '<')) :
	
	/**
	 * Returns a valid HTTP date using given timestamp or current time if none given.
	 *
	 * @param int $timestamp Unix timestamp
	 * @return string Date formatted regarding RFC 1123.
	 */
	function http_date($timestamp = null) {
		return Util::date($timestamp);
	}
	
	/**
	 * Redirects browser via Location header to given URL and exits.
	 *
	 * @param string $url Redirect URL - used in "Location" header.
	 * @param int $status [Optional] HTTP status code to send - should be 201 a 30x.
	 * @param boolean $exit [Optional] Whether to 'exit' after sending headers. Default true.
	 * @return void
	 */
	function http_redirect($url, $status = 0, $exit = true) {
		Util::redirect($url, $status, $exit);
	}
	
	/**
	 * Sends response status code, as well as an additional "Status" header.
	 *
	 * @param int $code HTTP status code to send
	 * @return void
	 */
	function http_send_status($code) {
		Util::sendStatus($code);
	}
	
	/**
	 * Sends the content-type header.
	 *
	 * @param string $content_type Content-type, must contain both primary/secondary.
	 * @param string|null $charset Optional charset to send.
	 * @return boolean True if sent, false/warning error if missing a part.
	 */
	function http_send_content_type($content_type = 'application/x-octetstream', $charset = null) {
		return Util::sendContentType($content_type, $charset);
	}
	
	/**
	 * Send the "Content-Disposition" header.
	 * 
	 * @param string $disposition Content disposition (e.g. "attachment").
	 * @param string $filename [Optional] Populates "filename" in header.
	 * @param string $name [Optional] Populates "name" in header.
	 */
	function http_send_content_disposition($disposition = 'attachment', $filename = null, $name = null) {
		Util::sendContentDisposition($disposition, $filename, $name);
	}
	
	/**
	 * Sends a file download, invoking the browser's "Save As..." dialog.
	 *
	 * Exits after sending. Unlike the HTTP extension version, this function
	 * also sends Content-Type, Content-Disposition, and "no-cache" headers.
	 *
	 * @param string $file Filepath to file to send.
	 * @param string $filetype File type to send as, default is 
	 * 'application/octet-stream'.
	 * @param string $filename Optional name to show to user - defaults to
	 * basename($file).
	 * @return void
	 */
	function http_send_file($file, $filetype = 'download', $filename = null) {
		Util::sendFile($file, $filetype, $filename);
	}
	
	/**
	 * Returns request body string.
	 *
	 * This function will not work if php://input is read before calling
	 * (e.g. via file_get_contents(), fopen(), etc.).
	 * 
	 * Also, POST requests with "multipart/form-data" will not work with this
	 * function, as it relies on php://input.
	 *
	 * @see \HttpUtil\Request\Body::contents()
	 * 
	 * @return string HTTP request body.
	 */
	function http_get_request_body() {
		return Server::instance()->getRequestBody();
	}
	
	/**
	 * Returns a read-only file handle for the request body stream.
	 * 
	 * Uses fopen("php://input") with binary flag.
	 * 
	 * @see \HttpUtil\Request\Body::handle()
	 * 
	 * @return resource Read-only file handle for php://input stream.
	 */
	function http_get_request_body_stream() {
		return Server::instance()->getRequestBodyStream();
	}
	
	/**
	 * Returns array of HTTP request headers.
	 *
	 * Cross-platform function to retrive the current HTTP request headers.
	 * 
	 * Uses apache_request_headers() if available, otherwise uses $_SERVER global.
	 * Normalizes header labels (array keys) to lowercased values, stripped of
	 * any "http" prefix and with underscores converted to dashes (e.g. "accept-language").
	 *
	 * @return array HTTP request headers, keys stripped of "HTTP_" and lowercase.
	 */
	function http_get_request_headers() {
		return Server::instance()->getRequestHeaders();
	}
	
	/**
	 * Fetches a single HTTP request header.
	 *
	 * @param string $name		Header name, lowercase, without 'HTTP_' prefix.
	 * @return string|null		Header value, if set, otherwise null.
	 */
	function http_get_request_header($name) {
		return Server::instance()->getRequestHeader($name);
	}
	
	/**
	 * Matches the contents of a given HTTP request header.
	 *
	 * @param string $name		Header name, lowercase, without 'HTTP_'.
	 * @param string $value		Value to match.
	 * @param bool $match_case	Whether to match case-sensitively, default false.
	 * @return boolean			True if match, otherwise false.
	 */
	function http_match_request_header($name, $value, $match_case = false) {
		return Server::instance()->matchRequestHeader($name, $value, $match_case);
	}
	
	/**
	 * Determines response content-type by matching the 'Accept'
	 * request header to an accepted content-type.
	 *
	 * Returns the first content-type in the header that matches
	 * one of the given types. If none is matched, returns the
	 * default content-type (first array item).
	 *
	 * @param array $accept	Indexed array of accepted content-types.
	 * @return string		Matched content-type, or first array item if no match.
	 */
	function http_negotiate_content_type(array $accept) {
		return Server::instance()->negotiateContentType($accept);
	}
	
	/**
	 * Determines best language from the 'Accept-Language' request header
	 * given an array of accepted languages.
	 *
	 * Tries to find a direct match (e.g. 'en-US' to 'en-US') but if none is
	 * found, finds the best match determined by prefix (e.g. "en").
	 * 
	 * @param array $accept Indexed array of accepted languages.
	 * @param array &$result If given an array, will be populated with negotiation results.
	 * @return string Best-match language, or first language given if no match.
	 */
	function http_negotiate_language(array $accept, &$result = null) {
		return Server::instance()->negotiateLanguage($accept, $result);
	}
	
	// end if ! pecl_http v1
	endif;
	
	/**
	 * Returns an associative array of cache headers suitable for use in header().
	 *
	 * Returns the 'Cache-Control', 'Expires', and 'Pragma' headers given the
	 * expiration offset in seconds (from current time). If '0' or a value which
	 * evaluates to empty is given, returns "no-cache" headers, with Cache-Control
	 * set to 'no-cache, must-revalidate, max-age=0', 'Expires' set to a date in the
	 * past, and 'Pragma' set to 'no-cache'.
	 *
	 * @param int $expires_offset Expiration in seconds from now.
	 * @return array Associative array of cache headers.
	 */
	function http_build_cache_headers($expires_offset = 86400) {
		return Util::buildCacheHeaders($expires_offset);
	}
	
	/**
	 * Parses an arbitrary request header to determine which value to use in
	 * response.
	 *
	 * This is a general-use function; specific implementations exist for
	 * content-type and language negotiation.
	 *
	 * @see http_negotiate_content_type()
	 * @see http_negotiate_language()
	 *
	 * @param string $name	Request header name, lowercase.
	 * @param array $accept Indexed array of accepted values.
	 * @return string 		Matched value (selected by quality, then position),
	 * 						or first array value if no match found.
	 */
	function http_negotiate_request_header($name, array $accept) {
		return Server::instance()->negotiateRequestHeader($name, $accept);
	}
	
	/**
	 * Determines if $value is in the contents of $name request header.
	 *
	 * @param string $name		Header name, lowercase, without 'HTTP_'.
	 * @param string $value		Value to search for.
	 * @param bool $match_case	Whether to search case-sensitive, default false.
	 * @return boolean			True if found, otherwise false.
	 */
	function http_in_request_header($name, $value, $match_case = false) {
		return Server::instance()->inRequestHeader($name, $value, $match_case);
	}
	
	/**
	 * Returns a HTTP status header description.
	 *
	 * @param int $code		HTTP status code.
	 * @return string		Status description string, or empty string if invalid.
	 */
	function http_response_code_desc($code) {
		return Util::getStatusCodeText($code, '');
	}
	
	/**
	 * Returns Internet Media Type (MIME) for given filetype.
	 *
	 * @param string $filetype	Filetype (e.g. 'js', 'xls', 'ogg').
	 * @param string $default	Value to return if mime not found.
	 * @return string			MIME, if found, otherwise default.
	 */
	function mimetype($extension, $default = 'application/octet-stream') {
		return Util::getMimetype(strtolower(ltrim($filetype, '.')), $default);
	}
	
	/**
	 * Returns a filetype from MIME.
	 *
	 * @param string $mimetype MIME
	 * @param mixed $default Default value. default null
	 * @return string Filetype for MIME, or default if not found.
	 */
	function mime2filetype($mimetype, $default = null) {
		return Util::lookupMimetypeExtension($mimetype, $default);
	}
	
}
