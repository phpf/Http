<?php

namespace xpl\Http;

class Util {
	
	protected static $mimetypes = array(
        'html'		=> 'text/html',
        'xml'		=> 'text/xml',
        'csv'		=> 'text/csv',
        'css'		=> 'text/css',
        'vcard'		=> 'text/vcard',
        'txt'		=> 'text/plain',
        'text'		=> 'text/plain',
        'md'		=> 'text/x-markdown',
        'js'		=> 'text/javascript',
        'jsonp'		=> 'text/javascript',
        'json'		=> 'application/json',
        'xhtml'		=> 'application/html+xml',
        'rss'		=> 'application/rss+xml',
        'atom'		=> 'application/atom+xml',
        'rdf' 		=> 'application/rdf+xml',
        'dtd'		=> 'application/xml-dtd',
        'zip'		=> 'application/zip',
        'gzip'		=> 'application/gzip',
        'woff'		=> 'application/font-woff',
        'soap'		=> 'application/soap+xml',
        'pdf'		=> 'application/pdf',
        'ddl'		=> 'application/octet-stream',
        'download'	=> 'application/octet-stream',
        'form'		=> 'application/x-www-form-urlencoded',
        'xls'		=> 'application/vnd.ms-excel',
        'xlxs'		=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt'		=> 'application/vnd.ms-powerpoint',
        'pptx'		=> 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'docx'		=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'odt'		=> 'application/vnd.oasis.opendocument.text',
        'odp'		=> 'application/vnd.oasis.opendocument.presentation',
        'ods'		=> 'application/vnd.oasis.opendocument.spreadsheet',
        'xps'		=> 'application/vnd.ms-xpsdocument',
        'kml'		=> 'application/vnd.google-earth.kml+xml',
        'flash'		=> 'application/x-shockwave-flash',
        'swf'		=> 'application/x-shockwave-flash',
        'dart'		=> 'application/dart',
        'upload'	=> 'multipart/form-data',
        'gif'		=> 'image/gif',
        'jpeg'		=> 'image/jpeg',
        'png'		=> 'image/png',
        'svg'		=> 'image/svg+xml',
        'mp4'		=> 'audio/mp4',
        'mp3'		=> 'audio/mpeg',
        'mpeg'		=> 'audio/mpeg',
        'ogg'		=> 'audio/ogg',
        'flac'		=> 'audio/ogg',
        'wav'		=> 'audio/vnd.wave',
        'message'	=> 'message/http',
    );
	
	protected static $statusCodes = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		226 => 'IM Used',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => 'Reserved',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		426 => 'Upgrade Required',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		510 => 'Not Extended'
	);
	
	/**
	 * Returns a valid HTTP date using given timestamp or current time if none given.
	 *
	 * @param int $timestamp Unix timestamp
	 * @return string Date formatted regarding RFC 1123.
	 */
	public static function date($timestamp = null) {
		return gmdate('D, d M Y H:i:s \G\M\T', isset($timestamp) ? $timestamp : time());
	}
		
	/**
	 * Redirects browser via Location header to given URL and exits.
	 *
	 * @param string $url Redirect URL - used in "Location" header.
	 * @param int $status [Optional] HTTP status code to send - should be 201 a 30x.
	 * @param boolean $exit [Optional] Whether to 'exit' after sending headers. Default true.
	 * @return void
	 * @throws RuntimeException if headers already sent.
	 */
	public static function redirect($url, $status = 0, $exit = true) {
			
		if (headers_sent($filename, $line)) {
			throw new \RuntimeException("Cannot redirect to '$url' - Output already started in $filename on line $line.");
		}
	
		header('Expires: Mon, 12 Dec 1982 06:00:00 GMT');
		header('Cache-Control: no-cache, must-revalidate, max-age=0');
		header('Pragma: no-cache');
		header_remove('Last-Modified');
		
		if (0 !== $status) {
			if ((300 < $status && $status < 308) || 201 === $status) {
				static::sendStatus($status);
			}
			// 302 sent automatically unless 201 or 3xx set
		}
	
		// no space is a fix for msie
		header("Location:$url");
	
		$exit and exit;
	}
		
	/**
	 * Sends response status code, as well as an additional "Status" header.
	 *
	 * @param int $code HTTP status code to send
	 * @return void
	 */
	public static function sendStatus($code) {
		
		http_response_code($code);
		
		// don't replace in case rfc2616
		header("Status: $code ".static::getStatusCodeText($code), false);
	}
		
	/**
	 * Sends the content-type header.
	 *
	 * @param string $content_type Content-type, must contain both primary/secondary.
	 * @param string|null $charset Optional charset to send.
	 * @return boolean True if sent, false/warning error if missing a part.
	 */
	public static function sendContentType($mimetype = 'application/octet-stream', $charset = null) {
			
		if (false === strpos($mimetype, '/')) {
				
			$mimetype = static::getMimetype(strtolower($mimetype), 'application/octet-stream');
			
			if (null === $mimetype) {
				trigger_error('Mimetype should contain primary and secondary parts.');
				return false;
			}
		}
	
		$header = 'Content-Type: '.$mimetype;
		
		if (isset($charset)) {
			$header .= '; charset='.strtoupper($charset);
		}
	
		header($header, true);
		
		return true;
	}
		
	/**
	 * Send the "Content-Disposition" header.
	 * 
	 * @param string $disposition Content disposition (e.g. "attachment").
	 * @param string $filename [Optional] Populates "filename" in header.
	 * @param string $name [Optional] Populates "name" in header.
	 */
	public static function sendContentDisposition($disposition, $filename = null, $name = null) {
			
		$string = 'Content-Disposition: '.$disposition;
	
		if (isset($filename)) {
			$string .= '; filename="'.$filename.'"';
		}
		
		if (isset($name)) {
			$string .= '; name="'.$name.'"';
		}
	
		header($string, false);
	}
		
	/**
	 * Sends a file download, invoking the browser's "Save As..." dialog.
	 *
	 * Exits after sending. Unlike the HTTP extension version, this function
	 * also sends Content-Type, Content-Disposition, and "no-cache" headers.
	 *
	 * @param string $file Filepath to file to send.
	 * @param string $filetype File type to send as, default is 
	 * 'application/octet-stream'.
	 * @param string $filename Optional name to show to user - defaults to
	 * basename($file).
	 * @return void
	 * @throws RuntimeException if headers already sent, or file is unreadable.
	 */
	public static function sendFile($file, $filetype = 'download', $filename = null) {
		
		if (headers_sent($_file, $_line)) {
			throw new \RuntimeException("Cannot send file - output started in '$_file' on line '$_line'.");
		}
		
		if (! is_readable($file)) {
			throw new \RuntimeException("Cannot send unreadable file '$file'.");
		}
	
		isset($filename) OR $filename = basename($file);
	
		static::sendContentType(static::getMimetype(strtolower($filetype), 'application/octet-stream'));
	
		static::sendContentDisposition('attachment', $filename);
	
		header('Expires: 0');
		header('Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
	
		// invalid without Content-Length
		header('Content-Length: '.filesize($file));
		header('Content-Transfer-Encoding: binary');
		header('Connection: close');
	
		readfile($file);
	
		exit;
	}
		
	/** ================================
	 			Extra Methods
	================================ **/
		
	/**
	 * Returns an associative array of cache headers suitable for use in header().
	 *
	 * Returns the 'Cache-Control', 'Expires', and 'Pragma' headers given the
	 * expiration offset in seconds (from current time). If '0' or a value which
	 * evaluates to empty is given, returns "no-cache" headers, with Cache-Control
	 * set to 'no-cache, must-revalidate, max-age=0', 'Expires' set to a date in the
	 * past, and 'Pragma' set to 'no-cache'.
	 *
	 * @param int $expires_offset Expiration in seconds from now.
	 * @return array Associative array of cache headers.
	 */
	public static function buildCacheHeaders($expires_offset = 86400) {
			
		$headers = array();
		
		if (empty($expires_offset) || '0' === $expires_offset) {
			$headers['Cache-Control'] = 'no-cache, must-revalidate, max-age=0';
			$headers['Expires'] = 'Thu, 19 Nov 1981 08:52:00 GMT';
			$headers['Pragma'] = 'no-cache';
		} else {
			$headers['Cache-Control'] = "Public, max-age=$expires_offset";
			$headers['Expires'] = static::date(time() + $expires_offset);
			$headers['Pragma'] = 'Public';
		}
		
		return $headers;
	}
	
	/**
	 * Returns a HTTP status header description.
	 *
	 * @param int $code		HTTP status code.
	 * @return string		Status description string, or empty string if invalid.
	 */
	public static function getStatusCodeText($code, $default = null) {
		$code = (int) $code;
		return isset(static::$statusCodes[$code]) ? static::$statusCodes[$code] : $default;
	}
	
	public static function getMimetype($ext, $default = 'application/octet-stream') {
		return isset(static::$mimetypes[$ext]) ? static::$mimetypes[$ext] : $default;
	}
	
	public static function lookupStatusCode($description) {
		
		if ($key = array_search($description, static::$statusCodes, true)) {
			return $key;
		}
		
		return null;
	}
	
	public static function lookupMimetypeExtension($mimetype) {
		
		if ($key = array_search($mimetype, static::$mimetypes, true)) {
			return $key;
		}
		
		return null;
	}
	
}
