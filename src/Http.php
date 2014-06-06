<?php

namespace Phpf\Http;

use RuntimeException;

class Http {
	
	
	protected $headers;
	
	protected $inputHandle;
	protected $inputContents;
	
	protected $outputHandle;
	protected $outputContents;
	
	protected static $auto_open_streams = false;
	
	protected static $instance;
	
	/**
	 * Returns singleton instance.
	 * 
	 * @return $this
	 */
	public static function instance() {
		if (! isset(static::$instance))
			static::$instance = new static();
		return static::$instance;
	}
	
	/**
	 * Set/get whether to automatically open request and response stream handles.
	 * 
	 * @param boolean|null [Optional] True/false to set value.
	 * @return boolean True if set to auto-open stream handles, otherwise false (default).
	 */
	public static function autoOpenStreams($boolval = null) {
		isset($boolval) and static::$auto_open_streams = (bool)$boolval;
		return static::$auto_open_streams;
	}
	
	/**
	 * Constructor auto-opens streams if set to do so.
	 * @return void
	 */
	protected function __construct() {
		if (static::$auto_open_streams) {
			$this->openInputStream();
			$this->openOutputStream();
		}
	}
	
	/** ================================
	 			pecl_http
	================================ **/
	
	/**
	 * Returns a valid HTTP date using given timestamp or current time if none given.
	 *
	 * @param int $timestamp Unix timestamp
	 * @return string Date formatted regarding RFC 1123.
	 */
	public function date($timestamp = null) {
		return gmdate('D, d M Y H:i:s \G\M\T', isset($timestamp) ? $timestamp : time());
	}
		
	/**
	 * Redirects browser via Location header to given URL and exits.
	 *
	 * @param string $url Redirect URL - used in "Location" header.
	 * @param int $status [Optional] HTTP status code to send - should be 201 a 30x.
	 * @param boolean $exit [Optional] Whether to 'exit' after sending headers. Default true.
	 * @return void
	 * @throws RuntimeException if headers already sent.
	 */
	public function redirect($url, $status = 0, $exit = true) {
			
		if (headers_sent($filename, $line)) {
			throw new RuntimeException("Cannot redirect to '$url' - Output already started in $filename on line $line.");
		}
	
		if (0 !== $status) {
			if ((300 < $status && $status < 308) || 201 === $status) {
				$this->sendStatus($status);
			}
			// 302 sent automatically unless 201 or 3xx set
		}
	
		header('Expires: Mon, 12 Dec 1982 06:00:00 GMT');
		header('Cache-Control: no-cache, must-revalidate, max-age=0');
		header('Pragma: no-cache');
		
		header_remove('Last-Modified');
		
		// no space is a fix for msie
		header("Location:$url");
	
		$exit and exit;
	}
		
	/**
	 * Sends response status code, as well as an additional "Status" header.
	 *
	 * @param int $code HTTP status code to send
	 * @return void
	 */
	public function sendStatus($code) {
			
		http_response_code($code);
		
		// don't replace in case we're rfc2616
		header("Status: $code ".$this->statusCodeDescription($code), false);
	}
		
	/**
	 * Sends the content-type header.
	 *
	 * @param string $content_type Content-type, must contain both primary/secondary.
	 * @param string|null $charset Optional charset to send.
	 * @return boolean True if sent, false/warning error if missing a part.
	 */
	public function sendContentType($content_type = 'application/octet-stream', $charset = null) {
			
		if (false === strpos($content_type, '/')) {
			if (null === $content_type = Mime::get(strtolower($content_type), 'application/octet-stream')) {
				trigger_error('Content type should contain primary and secondary parts.');
				return false;
			}
		}
	
		$header = 'Content-Type: '.$content_type;
		if (isset($charset)) {
			$header .= '; charset='.strtoupper($charset);
		}
	
		header($header, true);
		return true;
	}
		
	/**
	 * Send the "Content-Disposition" header.
	 * 
	 * @param string $disposition Content disposition (e.g. "attachment").
	 * @param string $filename [Optional] Populates "filename" in header.
	 * @param string $name [Optional] Populates "name" in header.
	 */
	public function sendContentDisposition($disposition, $filename = null, $name = null) {
			
		$string = 'Content-Disposition: '.$disposition;
	
		if (isset($filename)) {
			$string .= '; filename="'.$filename.'"';
		}
		if (isset($name)) {
			$string .= '; name="'.$name.'"';
		}
	
		header($string, false);
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
	 * @throws RuntimeException if headers already sent, or file is unreadable.
	 */
	public function sendFile($file, $filetype = 'download', $filename = null) {
		
		if (headers_sent($_file, $_line)) {
			throw new RuntimeException("Cannot send file - output started in '$_file' on line '$_line'.");
		}
		
		if (! file_exists($file) || ! is_readable($file)) {
			throw new RuntimeException("Cannot send unknown or unreadable file $file.");
		}
	
		if (! isset($filename)) {
			$filename = basename($file);
		}
	
		header('Expires: 0');
		header('Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
	
		$this->sendContentType(MIME::get(strtolower($filetype), 'application/octet-stream'));
	
		$this->sendContentDisposition('attachment', $filename);
	
		// invalid without Content-Length
		header('Content-Length: '.filesize($file));
		header('Content-Transfer-Encoding: binary');
		header('Connection: close');
	
		readfile($file);
	
		exit;
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
	public function getRequestHeaders() {
			
		if (! isset($this->headers)) {
				
			if (function_exists('apache_request_headers')) {
				$_headers = apache_request_headers();
			} else {
				$_headers = array();
				$misfits = array('CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5', 'AUTH_TYPE', 'PHP_AUTH_USER', 'PHP_AUTH_PW', 'PHP_AUTH_DIGEST');
				foreach ( $_SERVER as $key => $value ) {
					if (0 === strpos($key, 'HTTP_')) {
						$_headers[$key] = $value;
					} else if (in_array($key, $misfits, true)) {
						$_headers[$key] = $value;
					}
				}
			}
			
			$this->headers = array();
			foreach ( $_headers as $key => $value ) {
				$key = str_replace(array('http_', '_'), array('', '-'), strtolower($key));
				$this->headers[$key] = $value;
			}
		}
		
		return $this->headers;
	}
	
	/**
	 * Fetches a single HTTP request header.
	 *
	 * @param string $name		Header name, lowercase, without 'HTTP_' prefix.
	 * @return string|null		Header value, if set, otherwise null.
	 */
	public function getRequestHeader($name) {
		
		if (! isset($this->headers)) {
			$this->getRequestHeaders();
		}
		
		return isset($this->headers[$name]) ? $this->headers[$name] : null;
	}
	
	/**
	 * Matches the contents of a given HTTP request header.
	 *
	 * @param string $name		Header name, lowercase, without 'HTTP_'.
	 * @param string $value		Value to match.
	 * @param bool $match_case	Whether to match case-sensitively, default false.
	 * @return boolean			True if match, otherwise false.
	 */
	public function matchRequestHeader($name, $value, $match_case = false) {
			
		if (null === $header = $this->getRequestHeader($name)) {
			return false;
		}
		
		return $match_case 
			? 0 === strcmp($header, $value) 
			: 0 === strcasecmp($header, $value);
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
	public function negotiateContentType(array $accept) {
			
		if (null === $header = $this->getRequestHeader('accept')) {
			return $accept[0];
		}
		
		$object = new Header\NegotiatedHeader('accept', $header);
		
		return $object->negotiate($accept);
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
	public function negotiateLanguage(array $accept, &$result = null) {
		
		if (null === $header = $this->getRequestHeader('accept-language')) {
			return $accept[0];
		}
		
		$object = new Header\AcceptLanguage('accept-language', $header);
		
		return $object->negotiate($accept, $result);
	}
	
	/** ================================
	 			Extra Methods
	================================ **/
		
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
	public function buildCacheHeaders($expires_offset = 86400) {
			
		$headers = array();
		
		if (empty($expires_offset) || '0' === $expires_offset) {
			$headers['Cache-Control'] = 'no-cache, must-revalidate, max-age=0';
			$headers['Expires'] = 'Thu, 19 Nov 1981 08:52:00 GMT';
			$headers['Pragma'] = 'no-cache';
		} else {
			$headers['Cache-Control'] = "Public, max-age=$expires_offset";
			$headers['Expires'] = $this->date(time() + $expires_offset);
			$headers['Pragma'] = 'Public';
		}
		
		return $headers;
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
	public function negotiateRequestHeader($name, array $accept) {
			
		if (null === $header = $this->getRequestHeader($name)) {
			return $accept[0];
		}
		
		$object = new Header\NegotiatedHeader($name, $header);
		
		return $object->negotiate($accept);
	}
	
	/**
	 * Determines if $value is in the contents of $name request header.
	 *
	 * @param string $name		Header name, lowercase, without 'HTTP_'.
	 * @param string $value		Value to search for.
	 * @param bool $match_case	Whether to search case-sensitive, default false.
	 * @return boolean			True if found, otherwise false.
	 */
	public function inRequestHeader($name, $value, $match_case = false) {
			
		if (null === $header = $this->getRequestHeader($name)) {
			return false;
		}
		
		return $match_case 
			? false !== strpos($header, $value) 
			: false !== stripos($header, $value);
	}
	
	/**
	 * Returns a HTTP status header description.
	 *
	 * @param int $code		HTTP status code.
	 * @return string		Status description string, or empty string if invalid.
	 */
	public function statusCodeDescription($code) {
		return Status::get(intval($code), '');
	}
	
	/** ================================
	 			I/O Streams
	================================ **/
		
	/**
	 * Returns a read-only file handle for the request body stream.
	 * 
	 * Uses fopen("php://input") with binary flag.
	 * 
	 * @return resource Read-only file handle for php://input stream.
	 */
	public function getRequestBodyStream() {
		if (! isset($this->inputHandle)) {
			$this->openInputStream();
		}
		return $this->inputHandle;
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
	public function getRequestBody() {
		if (! isset($this->inputContents)) {
			$this->inputContents = stream_get_contents($this->getRequestBodyStream());
		}
		return $this->inputContents;
	}
	
	/**
	 * Returns a write-only file handle for the response body stream.
	 * 
	 * Uses fopen("php://output") with binary flag.
	 * 
	 * @return resource Write-only file handle for php://output stream.
	 */
	public function getResponseBodyStream() {
		if (! isset($this->outputHandle)) {
			$this->openOutputStream();
		}
		return $this->outputHandle;
	}
	
	/**
	 * Returns the response body as string.
	 * 
	 * @return string Response body using stream_get_contents().
	 */
	public function getResponseBody() {
		if (! isset($this->outputContents)) {
			$this->outputContents = stream_get_contents($this->getResponseBodyStream());
		}
		return $this->outputContents;
	}
	
	/**
	 * Opens the php://input stream for reading.
	 */
	public function openInputStream() {
		isset($this->inputHandle) or $this->inputHandle = fopen('php://input', 'rb');
	}
	
	/**
	 * Opens the php://output stream for writing.
	 */
	public function openOutputStream() {
		isset($this->outputHandle) or $this->outputHandle = fopen('php://output', 'wb');
	}
	
	/**
	 * Closes any open streams on destruct.
	 */
	public function __destruct() {
		is_resource($this->inputHandle) and fclose($this->inputHandle);
		is_resource($this->outputHandle) and fclose($this->outputHandle);
	}
	
	
}
