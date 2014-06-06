<?php
/**
 * @package Phpf\Http
 */

namespace Phpf\Http;

class Response {

	/**
	 * Value to use in Content-Type header.
	 * 
	 * @var string
	 */
	protected $content_type;
	
	/**
	 * Default value to use in Content-Type header.
	 * 
	 * Uses ini value 'default_mimetype', if set.
	 * 
	 * @var string
	 */
	protected $default_content_type;
	
	/**
	 * Charset to use in Content-Type header.
	 * 
	 * @var string
	 */
	protected $charset;

	/**
	 * HTTP response status code.
	 * 
	 * @var integer
	 */
	protected $status;

	/**
	 * Associative array of response headers.
	 * 
	 * @var array
	 */
	protected $headers = array();

	/**
	 * Response body string.
	 * 
	 * @var string
	 */
	protected $body;
	
	/**
	 * Whether to send a message body in the response.
	 * 
	 * False for HEAD requests as per RFC 3875.
	 * 
	 * @var boolean
	 */
	protected $send_body;
	
	/**
	 * Whether the response has been sent.
	 * 
	 * Used for auto-send on shutdown and preventing multiple responses.
	 * 
	 * @var boolean
	 */
	protected $sent = false;
	
	/**
	 * Whether to gzip the response body.
	 * 
	 * @var boolean
	 */
	protected $gzip_body;
	
	/**
	 * Associative array of permitted content types.
	 * 
	 * @var array
	 */
	protected $content_types = array(
		'html'	=> 'text/html',
		'xml'	=> 'text/xml',
		'jsonp' => 'text/javascript',
		'json'	=> 'application/json',
	);
	
	/**
	 * Sets defaults and registers the 'send()' method as a shutdown function.
	 * 
	 * The response will not sent be more than once - if send() is called before 
	 * shutdown, or multiple times otherwise, it will only be sent the first time.
	 * 
	 * @return void
	 */
	public function __construct() {
		
		$this->gzip_body = false;
		
		$this->charset = ini_get('default_charset') ?: 'UTF-8';
		
		$this->default_content_type = ini_get('default_mimetype') ?: 'text/html';
		
		// automatically send response
		register_shutdown_function(array($this, 'send'));
	}
	
	/**
	 * Uses Request data to set some properties.
	 * 
	 * @param Request $request Current Request object.
	 * @return $this
	 */
	public function setRequest(Request $request) {
		
		// send body if not a HEAD request
		$this->send_body = ! $request->is('HEAD');
		
		$http = Http::instance();
		
		// first try to set content type using parameter (if set)
		if (! isset($request->content_type) || ! $this->maybeSetContentType($request->content_type)) {
			// now try using header (if set)
			$this->content_type = $http->negotiateContentType(array_values($this->content_types));
		}
		
		// shall we gzip?
		if ($http->inRequestHeader('accept-encoding', 'gzip') && extension_loaded('zlib')) {
			$this->gzip_body = true;
		}
		
		// For XHR/AJAX requests, don't cache response, nosniff, and deny iframes
		if ($request->isXhr()) {
			$this->setCacheHeaders(false);
			$this->nosniff();
			$this->denyIframes();
		}
		
		return $this;
	}
	
	/**
	 * Redirects the user's browser to a new location.
	 * 
	 * @param string $url URL to redirect user to.
	 * @param int $status [Optional] Valid redirect status code to send. Default 0 (sends 302).
	 * @param boolean $send Whether to send the redirect response immediately. Default true.
	 * @return $this
	 */
	public function redirect($url, $status = 0, $send = true) {
			
		$this->setHeader('Location', $url, true);
		
		$this->send_body = false;
		
		if (300 < $status && 400 > $status) {
			$this->setStatus($status);
		}
		
		if ($send) {
			$this->send();
		}
		
		return $this;
	}

	/**
	 * Sends the response headers and body, then exits.
	 * 
	 * @return void
	 */
	public function send() {
		
		// don't send more than once
		if ($this->sent) return;
		
		// (maybe) start output buffering
		if (ob_get_level()) {
			#if (! $this->gzip_body || ! ob_start('ob_gzhandler'))
				ob_start();
		}
		
		// send at least some cache header
		if (! isset($this->headers['Cache-Control'])) {
			$this->nocache();
		}

		// send Status header
		if (! isset($this->status)) {
			if (isset($GLOBALS['HTTP_RESPONSE_CODE'])) {
				$this->status = $GLOBALS['HTTP_RESPONSE_CODE'];
			} else if (isset($this->headers['Location'])) {
				$this->status = 302;
			} else {
				$this->status = 200;
			}
		}
		
		$http = Http::instance();
		
		$http->sendStatus($this->status);
		
		// send Content-Type header
		$content_type = isset($this->content_type) ? $this->content_type : $this->default_content_type;
		
		$http->sendContentType($content_type, $this->getCharset());

		// send other headers
		foreach ( $this->headers as $name => $value ) {
			header(sprintf("%s: %s", $name, $value), true);
		}
		
		// output the body
		if ($this->send_body) {
			echo $this->body;
		}
		
		if (ob_get_level()) {
			ob_end_flush();
		}
		
		$this->sent = true;
		
		exit;
	}

	/**
	 * Sets the body content.
	 * 
	 * @param string|object $value String, or object with __toString() method.
	 * @param string $how How to set the body; one of 'replace' (default), 'append', or 'prepend'.
	 * @return $this
	 */
	public function setBody($value, $how = 'replace') {
			
		if (! is_scalar($value)) {
			if (! method_exists($value, '__toString')) {
				throw new \InvalidArgumentException('Cannot set var as body - given '.gettype($value));
			}
			$value = $value->__toString();
		}

		switch(strtolower($how)) {
			case 'replace' :
			default :
				$this->body = $value;
				break;
			case 'append' :
			case 'after' :
				$this->body .= $value;
				break;
			case 'prepend' :
			case 'before' :
				$this->body = $value.$this->body;
				break;
		}
		
		return $this;
	}

	/**
	 * Adds content to the body.
	 * 
	 * @param string|object $value String or object with __toString() method.
	 * @param string $how How to add the body. Default is 'append'.
	 * @return $this
	 */
	public function addBody($value, $how = 'append') {
		return $this->setBody($value, $how);
	}
	
	/**
	 * Returns body string.
	 * 
	 * @return string The body string.
	 */
	public function getBody(){
		return $this->body;
	}
	
	/**
	 * Sets output charset.
	 * 
	 * @param string $charset Charset to send w/ content-type.
	 * @return $this
	 */
	public function setCharset($charset) {
		$this->charset = $charset;
		return $this;
	}

	/**
	 * Returns output charset
	 * 
	 * @return string Charset
	 */
	public function getCharset() {
		return $this->charset;
	}

	/**
	 * Sets the HTTP response status code.
	 * 
	 * @param int $code HTTP status code.
	 * @return $this
	 */
	public function setStatus($code) {
		$this->status = (int)$code;
		return $this;
	}
	
	/**
	 * Returns the response status code.
	 * 
	 * @return int HTTP status code.
	 */
	public function getStatus() {
		return $this->status;
	}
	
	/**
	 * Sets the content type.
	 * 
	 * @param string $type Content-type MIME
	 * @return $this
	 */
	public function setContentType($type) {
		$this->content_type = $type;
		return $this;
	}
	
	/**
	 * Returns content type, if set.
	 * 
	 * @return string|null Content-type if set, otherwise null.
	 */
	public function getContentType() {
		return isset($this->content_type) ? $this->content_type : null;
	}
	
	/**
	 * Returns true if given response content-type/media type is known.
	 * 
	 * @param string $type Content-type MIME
	 * @return boolean True if valid as response format, otherwise false.
	 */
	public function isContentType($type) {
		return isset($this->content_types[$type]);
	}

	/**
	 * Sets $content_type, but only if $type is a valid content type.
	 * 
	 * @param string $type Content-type MIME
	 * @return boolean True if valid and set, otherwise false.
	 */
	public function maybeSetContentType($type) {

		if (isset($this->content_types[$type])) {
			$this->content_type = $this->content_types[$type];
			return true;
		}
		
		return false;
	}

	/**
	 * Sets a header. Replaces existing by default.
	 * 
	 * @param string $name Header name.
	 * @param string $value Header value.
	 * @param boolean $overwrite Whether to overwrite existing. Default true.
	 * @return $this
	 */
	public function setHeader($name, $value, $overwrite = true) {
		
		if (true === $overwrite || ! isset($this->headers[$name])) {
			$this->headers[$name] = $value;
		}

		return $this;
	}

	/**
	 * Sets array of headers.
	 * 
	 * @param array $headers Associative array of headers to set.
	 * @param boolean $overwrite True to overwrite existing. Default true.
	 * @return $this
	 */
	public function setHeaders(array $headers, $overwrite = true) {
		foreach ($headers as $name => $value) {
			$this->setHeader($name, $value, $overwrite);
		}
		return $this;
	}

	/**
	 * Adds a header. Does not replace existing.
	 * 
	 * @param string $name Header name.
	 * @param string $value Header value.
	 * @return $this
	 */
	public function addHeader($name, $value) {
		return $this->setHeader($name, $value, false);
	}

	/**
	 * Adds array of headers. Does not replace existing.
	 * 
	 * @param array Associative array of headers to set.
	 * @return $this
	 */
	public function addHeaders(array $headers) {
		$this->setHeaders($headers, false);
	}

	/**
	 * Returns assoc. array of currently set headers.
	 * 
	 * @return array Associative array of currently set headers.
	 */
	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * Sets the various cache headers. Auto unsets 'Last-Modified'
	 * if $expires_offset is falsy.
	 * 
	 * @param int|bool $expires_offset	Time in seconds from now to cache. 
	 * 									Pass 0 or false for no cache.
	 * @return $this
	 */
	public function setCacheHeaders($expires_offset = 86400) {

		$headers = http_build_cache_headers($expires_offset);
		
		// empty() returns false for zero as string
		if (empty($expires_offset) || '0' === $expires_offset) {
			header_remove('Last-Modified');
			unset($this->headers['Last-Modified']);
		}

		$this->setHeaders($headers);

		return $this;
	}

	/**
	 * Sets the "X-Frame-Options" header.
	 * 
	 * @param string $value One of 'sameorigin'/true or 'deny'/false.
	 * @return $this
	 */
	public function setFrameOptionsHeader($value) {

		switch($value) {
			case 'SAMEORIGIN' :
			case 'sameorigin' :
			case true :
			default :
				$value = 'SAMEORIGIN';
				break;
			case 'DENY' :
			case 'deny' :
			case false :
				$value = 'DENY';
				break;
		}

		return $this->setHeader('X-Frame-Options', $value);
	}

	/**
	 * Sets 'X-Frame-Options' header to 'DENY'.
	 * @return $this
	 */
	public function denyIframes() {
		return $this->setFrameOptionsHeader('DENY');
	}

	/**
	 * Sets 'X-Frame-Options' header to 'SAMEORIGIN'.
	 * @return $this
	 */
	public function sameoriginIframes() {
		return $this->setFrameOptionsHeader('SAMEORIGIN');
	}

	/**
	 * Sets no cache headers.
	 * @return $this
	 */
	public function nocache() {
		return $this->setCacheHeaders(false);
	}

	/**
	 * Sets 'X-Content-Type-Options' header to 'nosniff'.
	 * @return $this
	 */
	public function nosniff() {
		return $this->setHeader('X-Content-Type-Options', 'nosniff');
	}

	/** &Alias of setBody() */
	public function setContent($value) {
		return $this->setBody($value);
	}

	/** &Alias of addBody() */
	public function addContent($value, $how = 'append') {
		return $this->addBody($value, $how);
	}

}
