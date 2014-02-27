<?php
/**
 * @package Phpf.Http
 * @subpackage Request
 */

namespace Phpf\Http\Request;

use Phpf\Http\Http;

class Headers {
		
	/**
	* Returns a single HTTP request header if set.
	*/
	public static function get( $name, array $server = null ){
		$headers = self::getAll($server);
		$name = str_replace('_', '-', strtolower($name));
		return isset($headers[ $name ]) ? $headers[ $name ] : null;
	}
	
	/** 
	* Returns HTTP request headers as array.
	*/
	public static function getAll( array $server = null ){
		static $headers;
		
		if ( empty($server) || $server === $_SERVER ){
			$server =& $_SERVER;
			// get once per request
			if ( isset($headers) ) return $headers;
		}
		
		if ( function_exists('apache_request_headers') ){
			$_headers = apache_request_headers();
		} elseif ( extension_loaded('http') ){
			$_headers = http_get_request_headers();
		} else { // Manual labor
			$_headers = array();
			$misfits = array('CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5', 'PHP_AUTH_USER', 'PHP_AUTH_PW', 'PHP_AUTH_DIGEST', 'AUTH_TYPE');
			foreach ( $server as $key => $value ) {
				if ( 0 === strpos($key, 'HTTP_') ){
					$_headers[ $key ] = $value;
				} elseif ( in_array($key, $misfits) ){
					$_headers[ $key ] = $value;
				}
			}
		}
		
		// Normalize header keys
		$headers = array();
		foreach ( $_headers as $key => $value ) {
			$key = str_replace('http-', '', str_replace('_', '-', strtolower($key)));
			$headers[ $key ] = $value;
		}
		
		return $headers;
	}

	public static function acceptEncoding( $search = null ){
		
		$header = self::get('accept-encoding');
		
		if ( ! $header )
			return null;
		
		if ( empty($search) ){
			return $header;
		}
		
		if ( is_string($search) ){
			return false !== strpos($header, $search);
		}
		
		return self::findCsvInArray($header, (array) $search);
	}
	
	public static function accept( array $headers, $search = null ){
		
		$header = self::get('accept');
		
		if ( ! $header )
			return null;
		
		if ( empty($search) ){
			return $header;
		}
		
		if ( is_string($search) ){
			return false !== strpos($header, $search);
		}
		
		return self::findCsvInArray($header, (array) $search);
	}
	
	protected static function findCsvInArray($csv, array $arr){
		foreach ( explode(',', $csv) as $find ){
			if ( isset($arr[$find]) || in_array($find, $arr) )
				return $find;
		}
		return false;
	}
	
}
