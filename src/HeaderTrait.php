<?php

namespace xpl\Http;

trait HeaderTrait {
	
	protected $headers = array();
	
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
