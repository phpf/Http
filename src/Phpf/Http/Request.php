<?php
/**
 * @package Phpf.Util
 * @subpackage Http.Request
 */

namespace Phpf\Http;

use Phpf\Util\Str;

class Request {
	
	/**
	 * HTTP request method
	 * @var string
	 */
	public $method;
	
	/**
	 * Request URI
	 * @var string
	 */
	public $uri;
	
	/**
	 * Request query string
	 * @var string
	 */
	public $query;
	
	/**
	 * Request HTTP headers
	 * @var array
	 */
	public $headers = array();
	
	/**
	 * Query parameters.
	 * @var array
	 */
	public $query_params = array();
	
	/**
	 * Path parameters
	 * @var array
	 */
	public $path_params = array();
	
	/**
	 * Request body parameters.
	 * @var array
	 */
	public $body_params = array();
	
	/**
	 * All parameters (query, path, body) combined.
	 * @var array
	 */
	public $params = array();
	
	/**
	 * Whether request is an XML HTTP request
	 * @var boolean
	 */
	public $xhr = false;
	
	/**
	 * Whether request has been built yet
	 * @var boolean
	 */
	public static $built;
	
	/**
	 * Whether to allow method override via Header or param
	 * @var boolean
	 */
	public static $allow_method_override = true;
	
	/**
	 * Built request from $server array
	 */
	public function __construct( array $server = null ){
		
		if ( empty($server) ){
			$server =& $_SERVER;
		}
		
		$uri	= $this->clean(urldecode($server['REQUEST_URI']));
		$query	= $this->clean(urldecode($server['QUERY_STRING']));
		
		// Remove query string from uri and convert to array
		if ( !empty($query) ){
			$uri = str_replace("?$query", '', $uri);
			parse_str($query, $this->query_params);
		}
		
		$this->uri		= $uri;
		$this->query	= $query;
		$this->headers	= Http::requestHeaders($server);
		
		$method = $server['REQUEST_METHOD'];
		
		if ( 'GET' === $method || 'POST' === $method ){
			$this->body_params =& $_POST;
		} else {
			parse_str($this->clean(file_get_contents('php://input')), $this->body_params);
		}
		
		// Override method if permitted
		if ( self::$allow_method_override ){
			
			if ( isset($this->headers['x-http-method-override'] ) )
				$method = $this->headers['x-http-method-override'];
			
			if ( isset($this->query_params['_method']) )
				$method = $this->query_params['_method'];
		}
		
		$this->method = strtoupper(trim($method));
		
		// Is this an XHR request?
		if ( isset($this->headers['x-requested-with']) ){
			$this->xhr = (bool) 'XMLHttpRequest' === $this->headers['x-requested-with'];
		}
	}
	
	/**
	* Import array of data as object properties
	*/
	public function import( array $vars = null ){
		
		if ( !empty($vars) ){
			
			foreach( $vars as $var => $val ){
				
				$this->set(urldecode($var), Str::esc(urldecode($val)));
			}
		}
	}
		
	/**
	* Returns property or parameter value if exists
	*/
	public function get( $var ){
		
		if ( isset($this->$var) )
			return $this->$var;
		
		if ( isset($this->params[ $var ]) )
			return $this->params[ $var ];
		
		return null;	
	}
	
	/**
	* Set a property or parameter
	*/
	public function set( $var, $val ){
		
		if ( empty($var) || is_numeric($var) )
			$this->setParam(null, $val);
		else 
			$this->$var = $val;
		
		return $this;
	}
	
	/**
	* Set a parameter
	*/
	public function setParam( $var, $val ){
		
		if ( empty($var) || is_numeric($var) )
			$this->params[] = $val;
		else 
			$this->params[ $var ] = $val;
			
		return $this;
	}
	
	/**
	* Set an array of data as parameters
	*/
	public function setParams( array $args ){
		
		foreach( $args as $k => $v ){
			$this->setParam($k, $v);
		}
		
		return $this;	
	}
	
	public function disallowMethodOverride(){
		self::$allow_method_override = false;
		return $this;
	}
	
	public function allowMethodOverride(){
		self::$allow_method_override = true;
		return $this;
	}
	
	/**
	* Returns the request HTTP method.
	*/
	public function getMethod(){
		return $this->method;
	}
	
	/**
	* Returns the request URI.
	*/
	public function getUri(){
		return $this->uri;	
	}
	
	/**
	* Returns the request query string if set.
	*/
	public function getQuery(){
		return $this->query;	
	}
	
	/**
	* Returns all parameter values
	*/
	public function getParams(){
		return $this->params;
	}
	
	/**
	* Returns a parameter value
	*/
	public function getParam( $name ){
		return isset( $this->params[ $name ] ) ? $this->params[ $name ] : null;
	}
	
	/**
	 * Alias for Request\Request::get_param()
	 * @see Request\Request::get_param()
	 */
	public function param( $name ){
		return $this->getParam( $name );	
	}
	
	/**
	* Returns array of parsed headers
	*/
	public function getHeaders(){
		return $this->headers;	
	}
	
	/**
	* Returns a single HTTP header if set.
	*/
	public function getHeader( $name ){
		return isset( $this->headers[ $name ] ) ? $this->headers[ $name ] : null;	
	}
	
	/**
	 * Sets current route query vars.
	 */
	public function setVars( array $vars ){
		$this->vars = $vars;
		return $this;
	}
	
	/**
	* Returns array of matched query var keys and values
	*/
	public function getVars(){
		return $this->vars;
	}
	
	/**
	* Returns a query var value
	*/
	public function getVar( $var ){
		return isset( $this->vars[ $var ] ) ? $this->vars[ $var ] : null;	
	}
	
	/**
	* Returns true if is a XML HTTP request
	*/
	public function isXhr(){
		return (bool)$this->xhr;	
	}
	
	/**
	 * Alias for Request\Request::is_xhr()
	 * @see Request\Request::is_xhr()
	 */
	public function isAjax(){
		return $this->isXhr();	
	}
	
	/**
	* Strips naughty text and slashes from uri components
	*/
	protected function clean( $str ){
		return trim(htmlentities(strip_tags($str), ENT_COMPAT), '/');	
	}
	
}