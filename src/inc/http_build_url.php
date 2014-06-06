<?php
/**
 * @package HttpUtil
 */

/**
 * Replace every part of the first URL when there's one of the second URL.
 * @var int
 */
define('HTTP_URL_REPLACE', 1);

/**
 * Join relative paths.
 * @var int
 */
define('HTTP_URL_JOIN_PATH', 2);

/**
 * Join query strings.
 * @var int
 */
define('HTTP_URL_JOIN_QUERY', 4);

/**
 * Strip any user authentication information.
 * @var int
 */
define('HTTP_URL_STRIP_USER', 8);

/**
 * Strip any password authentication information.
 * @var int
 */
define('HTTP_URL_STRIP_PASS', 16);

/**
 * Strip any authentication information.
 * @var int
 */
define('HTTP_URL_STRIP_AUTH', 32);

/**
 * Strip explicit port numbers.
 * @var int
 */
define('HTTP_URL_STRIP_PORT', 64);

/**
 * Strip complete path.
 * @var int
 */
define('HTTP_URL_STRIP_PATH', 128);

/**
 * Strip query string.
 * @var int
 */
define('HTTP_URL_STRIP_QUERY', 256);

/**
 * Strip any fragments (#identifier).
 * @var int
 */
define('HTTP_URL_STRIP_FRAGMENT', 512);

/**
 * Strip anything but scheme and host.
 * @var int
 */
define('HTTP_URL_STRIP_ALL', 1024);

/**
 * Takes an associative array in the layout of parse_url, and constructs a URL
 * from it.
 *
 * @author FuelPHP
 * @see http://www.php.net/manual/en/function.http-build-url.php
 *
 * @param mixed (Part(s) of) an URL in form of a string or associative array like
 * parse_url() returns
 * @param mixed Same as the first argument
 * @param int A bitmask of binary or'ed HTTP_URL constants (Optional)
 * HTTP_URL_REPLACE is the default
 * @param array If set, it will be filled with the parts of the composed url like
 * parse_url() would return
 * @return string constructed URL
 */
function http_build_url($url, $parts = array(), $flags = HTTP_URL_REPLACE, &$new_url = false) {

	$keys = array('user', 'pass', 'port', 'path', 'query', 'fragment');

	if ($flags & HTTP_URL_STRIP_ALL) {
		// HTTP_URL_STRIP_ALL becomes all the HTTP_URL_STRIP_Xs
		$flags |= HTTP_URL_STRIP_USER;
		$flags |= HTTP_URL_STRIP_PASS;
		$flags |= HTTP_URL_STRIP_PORT;
		$flags |= HTTP_URL_STRIP_PATH;
		$flags |= HTTP_URL_STRIP_QUERY;
		$flags |= HTTP_URL_STRIP_FRAGMENT;
	} else if ($flags & HTTP_URL_STRIP_AUTH) {
		// HTTP_URL_STRIP_AUTH becomes HTTP_URL_STRIP_USER and HTTP_URL_STRIP_PASS
		$flags |= HTTP_URL_STRIP_USER;
		$flags |= HTTP_URL_STRIP_PASS;
	}

	// parse the original URL
	$parsed = is_array($url) ? $url : parse_url($url);

	// make sure we always have a scheme, host and path
	if (empty($parsed['scheme']))
		$parsed['scheme'] = 'http'.(http_env('ssl') ? 's' : '');
	if (empty($parsed['host']))
		$parsed['host'] = http_env('host');
	if (! isset($parsed['path']))
		$parsed['path'] = '';

	// make the path absolute if needed
	if (! empty($parsed['path']) && '/' !== substr($parsed['path'], 0, 1)) {
		$parsed['path'] = '/'.$parsed['path'];
	}

	// scheme and host are always replaced
	if (isset($parts['scheme']))
		$parsed['scheme'] = $parts['scheme'];
	if (isset($parts['host']))
		$parsed['host'] = $parts['host'];

	// replace the original URL with it's new parts (if applicable)
	if ($flags & HTTP_URL_REPLACE) {
		foreach ( $keys as $key ) {
			if (isset($parts[$key])) {
				$parsed[$key] = $parts[$key];
			}
		}
	} else {
		// join the original URL path with the new path
		if (isset($parts['path']) && ($flags & HTTP_URL_JOIN_PATH)) {
			if (isset($parsed['path'])) {
				$parsed['path'] = rtrim(str_replace(basename($parsed['path']), '', $parsed['path']), '/').'/'.ltrim($parts['path'], '/');
			} else {
				$parsed['path'] = $parts['path'];
			}
		}

		// join the original query string with the new query string
		if (isset($parts['query']) && ($flags & HTTP_URL_JOIN_QUERY)) {
			if (isset($parsed['query'])) {
				$parsed['query'] .= '&'.$parts['query'];
			} else {
				$parsed['query'] = $parts['query'];
			}
		}
	}

	// strips all the applicable sections of the URL
	// note: scheme and host are never stripped
	foreach ( $keys as $key ) {
		if ($flags & (int)constant('HTTP_URL_STRIP_'.strtoupper($key))) {
			unset($parsed[$key]);
		}
	}

	$new_url = $parsed;

	$url = isset($parsed['scheme']) ? $parsed['scheme'].'://' : '';
	if (isset($parsed['user'])) {
		$pass = isset($parsed['pass']) ? ':'.$parsed['pass'] : '';
		$url .= $parsed['user'].$pass.'@';
	}
	$url .= isset($parsed['host']) ? $parsed['host'] : '';
	$url .= isset($parsed['port']) ? ':'.$parsed['port'] : '';
	$url .= isset($parsed['path']) ? $parsed['path'] : '';
	$url .= isset($parsed['query']) ? '?'.$parsed['query'] : '';
	$url .= isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';

	return $url;
}
