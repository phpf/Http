<?php

namespace Phpf\Http;

abstract class StaticStorage {
	
	protected static $store;
	
	public static function get($name, $default = null) {
		return isset(static::$store[$name]) 
			? static::$store[$name] 
			: $default;
	}
	
	public static function lookup($value, $default = null) {
		if ($key = array_search($value, static::$store, true)) {
			return $key;
		}
		return $default;
	}
}
