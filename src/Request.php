<?php

namespace xpl\Http;

use xpl\Foundation\RequestInterface;
use xpl\Session\SessionInterface;

class Request implements RequestInterface
{
	const METHOD_OVERRIDE_PARAM = '_method';
	const CONTENT_TYPE_PARAM = 'format';
	
	protected $uri;
	protected $method;
	protected $query;
	protected $headers;
	protected $cookies;
	protected $files;
	protected $session;
	protected $body;
	protected $mimetype;
	protected $query_params;
	protected $path_params;
	protected $body_params;
	protected $params;
	
	protected static $mimetypes = array(
		'xhtml'		=> 'application/html+xml',
		'html'		=> 'text/html',
        'xml'		=> 'text/xml',
        'csv'		=> 'text/csv',
        'txt'		=> 'text/plain',
        'jsonp'		=> 'text/javascript',
        'json'		=> 'application/json',
        'rss'		=> 'application/rss+xml',
        'atom'		=> 'application/atom+xml',
	);
	
	/**
	 * Build a request from the $_SERVER (and possibly $_POST) superglobal(s).
	 * 
	 * @return \xpl\Http\Request
	 */
	public static function createFromGlobals() {
		
		$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
		$query = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
		
		if (isset($_SERVER['PATH_INFO'])) {
			$uri = $_SERVER['PATH_INFO'];
		} else {
			$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		}
		
		$http = Server\Server::instance();
		$headers = $http->getRequestHeaders();
		
		if ('POST' === $method && $http->isMultipartFormData()) {
			// Use php://input except for POST with "multipart/form-data"
			// @see {@link http://us3.php.net/manual/en/wrappers.php.php}
			$body = $_POST;
		
		} else if ('HEAD' === $method) {
			$body = array();
		
		} else {
			$body = $http->getRequestBody();
		}
		
		return new static($method, $uri, $query, $headers, $body, $_COOKIE, $_FILES);
	}
	
	/**
	 * Construct the object
	 * 
	 * @param string $method HTTP method.
	 * @param string $uri URI path.
	 * @param string $query Query string.
	 * @param array $headers Associative array of request headers.
	 * @param mixed $body [Optional] Request body as string (form-urlencoded or json), array, or object.
	 * @param array $cookies [Optional] Request cookies.
	 * @param array $files [Optional] Request uploaded files.
	 */
	public function __construct($method, $uri, $query, array $headers, $body = null, array $cookies = array(), array $files = array()) {
			
		$this->body_params = array();
		$this->query_params = array();
		
		$this->headers	= $this->parseHeaders($headers);
		$this->cookies	= $this->parseCookies($cookies);
		$this->files	= $this->parseFiles($files);
		$this->body		= $this->parseBody($body);
		$this->query	= $this->parseQuery($query);
		$this->uri		= $this->parseUri($uri);
		$this->method	= $this->parseMethod($method);
		
		$this->init();
	}
	
	/**
	 * Do additional set up tasks.
	 * 
	 * Merges query and body params into $params
	 * 
	 * @return void
	 */
	protected function init() {
		$this->params = array_merge($this->query_params, $this->body_params);
	}
	
	/**
	 * Checks whether this is an HTTP request.
	 * 
	 * @return boolean
	 */
	final public function isHttp() {
		return true;
	}
	
	/**
	 * Checks whether this is an HMVC request.
	 * 
	 * @return boolean
	 */
	final public function isHmvc() {
		return false;
	}
	
	/**
	 * Returns a property or parameter value.
	 * 
	 * @param string $var Property or parameter name to retrieve.
	 * @return mixed Value of property or parameter, if set, otherwise null.
	 */
	public function __get($var) {
			
		if (isset($this->$var)) {
			return $this->$var;
		}
		
		if (isset($this->params[$var])) {
			return $this->params[$var];
		}
		
		return null;
	}
	
	/**
	 * Returns the request HTTP method.
	 * 
	 * @return string HTTP method.
	 */
	public function getMethod() {
		return $this->method;
	}
	
	/**
	 * Returns the raw body content as passed to the constructor.
	 * 
	 * @return mixed
	 */
	public function getBody() {
		return $this->body;
	}
	
	/**
	 * Sets the session object.
	 * 
	 * @param \xpl\Session\SessionInterface $session
	 * @return $this
	 */
	public function setSession(SessionInterface $session) {
		$this->session = $session;
		return $this;
	}
	
	/**
	 * Returns session object.
	 * 
	 * @param return \xpl\Session\SessionInterface
	 */
	public function getSession() {
		return isset($this->session) ? $this->session : null;
	}
	
	/**
	 * Returns true if session is set, otherwise false.
	 * 
	 * @return boolean
	 */
	public function hasSession() {
		return isset($this->session);
	}
	
	/**
	 * Returns the request URI.
	 * 
	 * @return string URI
	 */
	public function getUri() {
		return $this->uri;	
	}
	
	public function getFullUri() {
		if (empty($this->query)) {
			return $this->uri;
		}
		return $this->uri.'?'.$this->query;
	}
	
	/**
	 * Returns the request query string if set.
	 * 
	 * @return string Query
	 */
	public function getQuery() {
		return $this->query;	
	}
	
	/**
	 * Returns array of parsed headers.
	 * 
	 * @return array HTTP request headers.
	 */
	public function getHeaders() {
		return $this->headers;	
	}
	
	/**
	 * Returns a single HTTP header if set.
	 * 
	 * @param string $name Header name (lowercase).
	 * @return string Header value if set, otherwise null.
	 */
	public function getHeader($name) {
		return isset($this->headers[$name]) ? $this->headers[$name] : null;	
	}
	
	/**
	 * Returns the request cookies.
	 * 
	 * @return array Associative array of cookies sent with request.
	 */
	public function getCookies() {
		return $this->cookies;
	}
	
	/**
	 * Returns the request files.
	 * 
	 * @return array Files from $_FILES superglobal.
	 */
	public function getFiles() {
		return $this->files;
	}
	
	/**
	 * Returns all parameter values.
	 * 
	 * @return array Query, path, and body parameters.
	 */
	public function getParams() {
		return $this->params;
	}
	
	/**
	 * Returns a parameter value if set and not null.
	 * 
	 * @param string $name Parameter name.
	 * @return mixed Parameter value.
	 */
	public function getParam($name) {
		return isset($this->params[$name]) ? $this->params[$name] : null;
	}
	
	/**
	 * Checks whether a parameter exists.
	 * 
	 * @param string $name Parameter name.
	 * @return boolean True if parameter exists, otherwise false.
	 */
	public function hasParam($name) {
		return array_key_exists($name, $this->params);
	}
	
	/**
	 * Alias of getParam()
	 */
	public function param($name) {
		return isset($this->params[$name]) ? $this->params[$name] : null;
	}
	
	/**
	 * Sets the parameters matched from the request route.
	 * 
	 * @param array $params Associative array of path parameters.
	 * @return $this
	 */
	public function setPathParams(array $params) {
		$this->path_params = $params;
		$this->params = array_merge($this->params, $this->path_params);
		return $this;
	}
	
	/**
	 * Returns the mimetype requested via file extension or parameter.
	 * 
	 * @return string Mimetype if set, otherwise null.
	 */
	public function getMimetype() {
		return isset($this->mimetype) ? $this->mimetype : null;
	}
	
	/**
	 * Checks whether request is an XML HTTP request.
	 * 
	 * @return boolean True if XMLHttpRequest, otherwise false.
	 */
	public function isXhr() {
		return isset($this->headers['x-requested-with']) && 'xmlhttprequest' === strtolower($this->headers['x-requested-with']);
	}
	
	/**
	 * Boolean method/xhr checker.
	 * 
	 * @param string $thing HTTP method name, or 'xhr' or 'ajax'.
	 * @return boolean True if request is given thing, or false if its not.
	 * @throws \DomainException if given argument is unknown.
	 */
	public function is($thing) {
		switch(strtoupper($thing)) {
			case 'POST' :
				return 'POST' === $this->method;
			case 'HEAD' :
				return 'HEAD' === $this->method;
			case 'GET' :
				return 'GET' === $this->method;
			case 'PUT' :
				return 'PUT' === $this->method;
			case 'DELETE' :
				return 'DELETE' === $this->method;
			case 'OPTIONS' :
				return 'OPTIONS' === $this->method;
			case 'PATCH' :
				return 'PATCH' === $this->method;
			case 'XHR' :
			case 'AJAX' :
				return $this->isXhr();
			default :
				throw new \DomainException("Unknown item '$thing'.");
		}
	}
	
	/**
	 * Returns the object's properties as an associative array.
	 * 
	 * @return array
	 */
	public function toArray() {
		return get_object_vars($this);
	}
	
	protected function parseHeaders(array $headers) {
		return $headers;
	}
	
	protected function parseCookies(array $cookies) {
		return $cookies;
	}
	
	protected function parseFiles(array $files) {
		return $files;
	}
	
	protected function parseBody($body) {
		
		if (! empty($body)) {
			
			if (is_string($body) && isset($this->headers['content-type'])) {
				
				if (false !== stripos($this->headers['content-type'], 'www-form-urlencoded')) {
					parse_str($body, $this->body_params);
				
				} else if (false !== stripos($this->headers['content-type'], 'application/json')) {
					$this->body_params = json_decode($body, true, 0, JSON_BIGINT_AS_STRING);
				}
			
			} else if (is_array($body)) {
				$this->body_params = $body;
			
			} else if (is_object($body)) {
				$this->body_params = is_callable(array($body, 'toArray')) ? $body->toArray() : (array)$body;
			}
		}
		
		return $body;
	}
	
	protected function parseQuery($query) {
		
		if (! empty($query)) {
			
			$query = filter_var(html_entity_decode(urldecode($query)), FILTER_SANITIZE_STRING);
			
			parse_str($query, $this->query_params);
		}
		
		return $query;
	}
	
	protected function parseUri($uri) {
		
		$uri = trim(filter_var($uri, FILTER_SANITIZE_URL), '/');
		
		$extensions = implode('|', array_keys(static::$mimetypes));
		
		if (preg_match("/(\.{$extensions})$/i", $uri, $matches)) {
			
			$ext = ltrim($matches[1], '.');
			$this->mimetype = static::$mimetypes[$ext];
			
			// remove extension and "."
			$uri = substr($uri, 0, strlen($uri)-strlen($matches[1])-1);
		}
		
		if (isset($this->query_params[static::CONTENT_TYPE_PARAM])) {
			
			$mime = strtolower($this->query_params[static::CONTENT_TYPE_PARAM]);
			
			if (isset(static::$mimetypes[$mime])) {
				$this->mimetype = static::$mimetypes[$mime];
			}
		}
		
		return $uri;
	}
	
	protected function parseMethod($method) {
		
		$method = strtoupper($method);
		
		if ('POST' === $method) {
			
			if (isset($this->headers['x-http-method-override'])) {
				$method = strtoupper($this->headers['x-http-method-override']); 
			}
			
			if (isset($this->query_params[static::METHOD_OVERRIDE_PARAM])) {
				$method = strtoupper($this->query_params[static::METHOD_OVERRIDE_PARAM]);
			}
		}
		
		return $method;
	}
	
}
