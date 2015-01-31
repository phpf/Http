<?php

namespace xpl\Http\Header;

/**
 * Represents a single HTTP header.
 */
class Header implements HeaderInterface {
	
	/**
	 * Construct the header using the name and value.
	 * 
	 * @param string $name Header name.
	 * @param string $value Header value.
	 */
	public function __construct($name, $value) {
		$this->setName($name);
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
	 * Sets the header name.
	 * 
	 * Name is lowercased and stripped of 'http' prefixes. 
	 * Underscores are converted to dashes.
	 * 
	 * @param string $name Header name
	 * @return $this
	 */
	protected function setName($name) {
		
		$name = strtolower($name);
		
		if (0 === strpos($name, 'http')) {
			$name = substr($name, 4);
		}
		
		$this->name = str_replace('_', '-', $name);
		
		return $this;
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
	
}
