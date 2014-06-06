<?php

namespace Phpf\Http\Header;

/**
 * Represents a single HTTP header.
 */
class Header implements HeaderInterface {
	
	/**
	 * Construct the header using the name and value.
	 * 
	 * @param string $name Header name.
	 * @param string|null $value Header value, if available.
	 */
	public function __construct($name, $value = null) {
		$this->setName($name);
		if (isset($value))
			$this->setValue($value);
	}
	
	/**
	 * implements HeaderInterface
	 */
	public function getName() {
		return $this->name;
	}
	
	/**
	 * implements HeaderInterface
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * implements HeaderInterface
	 */
	public function __toString() {
		return sprintf("%s: %s", $this->name, $this->value);
	}
	
	/**
	 * Sets header value
	 * 
	 * @param string $value Header value
	 * @return $this
	 */
	protected function setValue($value) {
		$this->value = $value;
		return $this;
	}
	
	/**
	 * Sets the header name.
	 * 
	 * Name is lowercased and stripped of 'http' prefixes. 
	 * Underscores are converted to dashes.
	 * 
	 * @param string $name Header name
	 * @return $this
	 */
	protected function setName($name) {
		$this->name = str_replace(array('http-','_'), array('', '-'), strtolower($name));
		return $this;
	}
	
}
