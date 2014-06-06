<?php

namespace Phpf\Http\Header;

/**
 * Represents a request header where the value is negotiated for the response.
 */
class NegotiatedHeader extends MultiValueHeader {
	
	/**
	 * @var array
	 */
	protected $values = array();
	
	/**
	 * Build the header object from string value.
	 * 
	 * @param string $name Header name (no 'HTTP_' prefix).
	 * @param string $strval Header value as a string (without name).
	 * @return $this
	 */
	public function __construct($name, $strval) {
		$this->setName($name);
		$this->setValue($strval);
	}
	
	/**
	 * Negotiate the value, given an array of accepted values, using the 
	 * parsed header value(s).
	 * 
	 * @param array $accepted Indexed array of accepted values.
	 * @return string The matched value, or default (first array item).
	 */
	public function negotiate(array $accepted, array &$results = null) {
		
		if (empty($this->values)) {
			$this->parse();
		}
		
		if (! isset($results)) {
			$results = array();
		}
		
		// default type is first array item
		$results['default'] = $accepted[0];
		
		// use isset()
		$accepted = array_fill_keys($accepted, true);
		
		foreach($this->values as $pos => $valObj) {
			if (isset($accepted[$valObj->value])) {
				return $results['best'] = $this->negotiated = $valObj->value;
			}
		}
		
		if ($found = $this->nomatch($results, $accepted)) {
			return $results['best'] = $this->negotiated = $found;
		}

		return $this->negotiated = $results['default'];
	}
	
	protected function nomatch(array &$results, array $accepted) {
		return null;
	}
	
	/**
	 * Only return a negotiated value.
	 * @return string
	 */
	public function __toString() {
		return isset($this->negotiated) ? $this->negotiated : '';
	}
	
	/**
	 * Parse the values from the header value string and populates $this->values.
	 * 
	 * Adapted from BulletPHP Request
	 * @see {@link https://github.com/vlucas/bulletphp/blob/master/src/Bullet/Request.php}
	 *
	 * @return $this
	 */
	protected function parse() {
		
		// extract each value from the string
		foreach (explode(',', $this->value) as $i => $term) {
	        	
	        $term = trim($term);
			
			$o = new Value($this);
			
	        // set the position in case two values have same q
			$o->position = intval($i);
			
			// extract value and q/level (float) from string like "primary/secondary;q=0.8"
	        if (preg_match("/^(\S+)\s*;\s*(?:q|level)=([0-9\.]+)/i", $term, $matches)) {
	            $o->value = $matches[1];
	            $o->quality = floatval($matches[2]);
	        } else {
	            $o->value = $term;
				$o->quality = 1; // default q=1
	        }
	        
	        $this->addValue($o);
	    }
		
		// sort values by quality then position
		usort($this->values, array($this, 'sortValues'));
		
		return $this;	
	}
	
	/**
	 * Sort the accepted values in quality then positional order.
	 * 
	 * Adapted from BulletPHP Request
	 * @see {@link https://github.com/vlucas/bulletphp/blob/master/src/Bullet/Request.php}
	 *
	 * @param HeaderValue $a
	 * @param HeaderValue $b
	 * @return int Value returned for usort()
	 */
	protected function sortValues(Value $a, Value $b) {
		// first tier: highest q factor wins
        $diff = $b->quality - $a->quality;
        if ($diff > 0) {
            $diff = 1;
        } else if ($diff < 0) {
            $diff = -1;
        } else { // tie-breaker: first listed item wins
            $diff = $a->position - $b->position;
        }
		return $diff;
	}
	
}
