<?php

namespace xpl\Http;

class Cookie extends \xpl\Common\Object {
	
	protected $name;
	protected $value;
	protected $expire;
	protected $path;
	protected $domain;
	protected $secure;
	protected $httponly;
	
	public static function createFromArray(array $array) {
		
		$args = array_replace(array(
			'name' => null,
			'value' => null,
			'expire' => 0,
			'path' => '/',
			'domain' => null,
			'secure' => false,
			'httponly' => false,
		), $array);
		
		return new static($args['name'], $args['value'], $args['path'], $args['domain'], $args['secure'], $args['httponly']);
	}
	
	public function __construct($name, $value, $expire = 0, $path = '/', $domain = null, $secure = false, $httponly = false) {
		$this->name = $name;
		$this->value = $value;
		$this->expire = $expire;
		$this->path = $path;
		$this->domain = $domain;
		$this->secure = $secure;
		$this->httponly = $httponly;
	}
	
	public function send() {
		return setcookie($this->name, $this->value, (int)$this->expire, $this->path, $this->domain, $this->secure, $this->httponly);
	}
	
	public function delete() {
		return setcookie($this->name, '', $this->expire - 86400, $this->path, $this->domain, $this->secure, $this->httponly);
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getValue() {
		return $this->value;
	}
	
	public function getExpire() {
		return $this->expire;
	}
	
	public function getPath() {
		return $this->path;
	}
	
	public function getDomain() {
		return $this->domain;
	}
	
	public function isSecure() {
		return $this->secure;
	}
	
	public function isHttpOnly() {
		return $this->httponly;
	}
	
}
