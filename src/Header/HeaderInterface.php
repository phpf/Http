<?php

namespace Phpf\Http\Header;

/**
 * Interface to a single HTTP header.
 */
interface HeaderInterface {
	
	/**
	 * Returns header name.
	 * 
	 * @return string
	 */
	public function getName();
	
	/**
	 * Returns header value.
	 * 
	 * @return string
	 */
	public function getValue();
	
	/**
	 * Returns header name and value as string.
	 * 
	 * @return string
	 */
	public function __toString();

}
	