<?php

namespace Phpf\Http\Request;

use Phpf\Http\Http;

class Headers {
	
	public static function acceptEncoding( array $headers, $search = null ){
		
		if ( !isset($headers['accept-encoding']) )	
			return null;
		
		if ( empty($search) )
			return $headers['accept-encoding'];
				
		return false !== strpos($headers['accept-encoding'], $search);
	}
	
	public static function accept( array $headers, array $match_content_types = null ){
		
		if ( !isset($headers['accept']) )
			return null;
		
		if ( empty($match_content_types) )
			return $headers['accept'];
		
		foreach ( explode(',', $headers['accept']) as $type ){
			if ( isset($match_content_types[$type]) 
				|| in_array($type, $match_content_types) )
			{
				return $type;
			}
		}
		
		return null;
	}
}
