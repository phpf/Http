<?php

namespace xpl\Http;

class CookieJar
{
	/**
	 * @var array
	 */
	protected $cookies;
	
	public function __construct(array $cookies = null) {
		$this->cookies = array();
		if (isset($cookies)) {
			$this->addArray($cookies);
		}
	}
	
	public function add(Cookie $cookie) {
		$this->cookies[$cookie->getName()] = $cookie;
		return $this;
	}
	
	public function addArray(array $cookies) {
		array_map(array($this, 'add'), $cookies);
		return $this;
	}
	
	public function get($name) {
		return isset($this->cookies[$name]) ? $this->cookies[$name] : null;
	}
	
	public function getAll() {
		return $this->cookies;
	}
	
	public function has($name) {
		return isset($this->cookies[$name]);
	}
	
	public function contains(Cookie $cookie) {
		return in_array($cookie, $this->cookies, true);
	}
	
	public function remove($name) {
		unset($this->cookies[$name]);
		return $this;
	}
	
}
