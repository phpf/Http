<?php

namespace xpl\Http\Server;

use xpl\Http\Header\NegotiatedHeader;
use xpl\Http\Header\AcceptLanguage;

class RequestHeaders {
	
	/**
	 * @var array
	 */
	protected $_server;
	
	/**
	 * @var array
	 */
	protected $headers;
	
	public function __construct(array $server) {
		$this->_server = $server;
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
	public function getAll() {
			
		if (! isset($this->headers)) {
				
			$this->headers = array();
			
			if (function_exists('apache_request_headers')) {
				$_headers = apache_request_headers();
			
			} else {
				
				if (isset($this->_server['CONTENT_TYPE'])) {
					$this->headers['content-type'] = $this->_server['CONTENT_TYPE'];
				}
				if (isset($this->_server['CONTENT_LENGTH'])) {
					$this->headers['content-length'] = $this->_server['CONTENT_LENGTH'];
				}

				$_headers = array();
				$misfits = array('CONTENT_MD5'=>1, 'AUTH_TYPE'=>1, 'PHP_AUTH_USER'=>1, 'PHP_AUTH_PW'=>1, 'PHP_AUTH_DIGEST'=>1);
				
				foreach ($this->_server as $key => $value) {
					if (0 === strpos($key, 'HTTP_')) {
						$_headers[$key] = $value;
					} else if (isset($misfits[$key])) {
						$_headers[$key] = $value;
					}
				}
			}
			
			foreach ($_headers as $key => $value) {
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
	public function get($name) {
		isset($this->headers) OR $this->getAll();
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
	public function match($name, $value, $match_case = false) {
			
		if (null === $header = $this->get($name)) {
			return false;
		}
		
		return $match_case 
			? 0 === strcmp($header, $value) 
			: 0 === strcasecmp($header, $value);
	}
	
	/**
	 * Determines if $value is in the contents of $name request header.
	 *
	 * @param string $name		Header name, lowercase, without 'HTTP_'.
	 * @param string $value		Value to search for.
	 * @param bool $match_case	Whether to search case-sensitive, default false.
	 * @return boolean			True if found, otherwise false.
	 */
	public function in($name, $value, $match_case = false) {
			
		if (null === $header = $this->get($name)) {
			return false;
		}
		
		return $match_case 
			? false !== strpos($header, $value) 
			: false !== stripos($header, $value);
	}
	
	/**
	 * Parses an arbitrary request header to determine which value to use in
	 * response.
	 *
	 * This is a general-use function; specific implementations exist for
	 * content-type and language negotiation.
	 *
	 * @param string $name	Request header name, lowercase.
	 * @param array $accept Indexed array of accepted values.
	 * @return string 		Matched value (selected by quality, then position),
	 * 						or first array value if no match found.
	 */
	public function negotiate($name, array $accept) {
			
		if (null === $header = $this->get($name)) {
			return $accept[0];
		}
		
		$object = new NegotiatedHeader($name, $header);
		
		return $object->negotiate($accept);
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
			
		if (null === $header = $this->get('accept')) {
			return $accept[0];
		}
		
		$object = new NegotiatedHeader('accept', $header);
		
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
		
		if (null === $header = $this->get('accept-language')) {
			return $accept[0];
		}
		
		$object = new AcceptLanguage('accept-language', $header);
		
		return $object->negotiate($accept, $result);
	}
	
}
