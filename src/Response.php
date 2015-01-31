<?php

namespace xpl\Http;

use xpl\Foundation\ResponseInterface;

class Response implements ResponseInterface
{
		
	protected $charset = 'UTF-8';
	protected $status = 200;
	protected $body;
	protected $headers = array();
	protected $send_body = true;
	private $sent = false;
	
	/**
	 * Marks the response as sent and hence prevents it being sent (again).
	 * 
	 * The response does not actually have to have been sent.
	 * 
	 * @return $this
	 */
	final public function setSent() {
		$this->sent = true;
		return $this;
	}
	
	/**
	 * Checks whether the response has been sent.
	 * 
	 * @return boolean
	 */
	final public function isSent() {
		return $this->sent;
	}
	
	/**
	 * Sets the body content.
	 * 
	 * @param mixed $body
	 * @return $this
	 */
	public function setBody($body) {
		$this->body = $body;
		return $this;
	}
	
	/**
	 * Returns the body content.
	 * 
	 * @return mixed
	 */
	public function getBody() {
		return $this->body;
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
	 * Sets the Content-Type header.
	 * 
	 * @param string $mimetype Content-type mime to send.
	 * @return $this
	 */
	public function setContentType($mimetype) {
		$this->setHeader('Content-Type', $mimetype, true);
		return $this;
	}
	
	/**
	 * Returns the Content-Type header set, if any.
	 * 
	 * @return string|null
	 */
	public function getContentType() {
		return $this->getHeader('Content-Type');
	}
	
	/**
	 * Sets the "Access-Control-Allow-Origin" header to the given value.
	 * 
	 * Used to allow CORS requests. Set to "*" to allow all hosts.
	 * 
	 * @param string $value
	 * @return $this
	 */
	public function setAccessControlAllowOrigin($value) {
		$this->setHeader('Access-Control-Allow-Origin', $value);
		return $this;
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

		$headers = Util::buildCacheHeaders($expires_offset);
		
		// empty("0") is false negative
		if (empty($expires_offset) || '0' === $expires_offset) {
			unset($this->headers['Last-Modified']);
		}

		$this->setHeaders($headers);

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
	public function redirect($url, $status = 0) {
		
		$this->setHeader('Location', $url, true);
		
		$this->send_body = false;
		
		if ($status && ((300 < $status && 400 > $status) || 201 == $status)) {
			$this->setStatus($status);
		}
		
		$this->send();
		
		exit();
	}
	
	/**
	 * Sends the response headers and body.
	 * 
	 * @return void
	 */
	public function send() {
		
		if ($this->sent) return;
		
		ob_get_level() and ob_start();
		
		if (! isset($this->status)) {
			$this->status = $this->hasHeader('Location') ? 302 : http_response_code();
		}
		
		Util::sendStatus($this->status);
		
		$this->sendHeaders();
		
		if ($this->send_body) {
			echo $this->body;
		}
		
		ob_get_level() and ob_flush();
		
		$this->sent = true;
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
			$this->headers[$name] = array($value);
		} else {
			array_push($this->headers[$name], $value);
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
	
	public function hasHeader($name) {
		return isset($this->headers[$name]);
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
		return $this->setHeaders($headers, false);
	}

	/**
	 * Returns assoc. array of currently set headers.
	 * 
	 * @return array Associative array of currently set headers.
	 */
	public function getHeaders() {
		return $this->headers;
	}
	
	public function getHeader($name) {
		return $this->hasHeader($name) ? $this->headers[$name] : null;
	}
	
	/**
	 * Sends the headers, handling multiples and string values.
	 * @return void
	 */
	public function sendHeaders() {
		foreach ($this->headers as $name => $value) {
			foreach ($value as $val) {
				is_string($name) and $val = "{$name}: {$val}";
				header($val, true);
			}
		}
	}
	
}
