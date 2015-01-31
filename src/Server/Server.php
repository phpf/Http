<?php

namespace xpl\Http\Server;

class Server {
	
	use \xpl\Common\Singleton;
	
	/**
	 * @var \xpl\Http\Server\RequestHeaders
	 */
	protected $headers;
	
	/**
	 * @var \xpl\Http\Server\Streams
	 */
	protected $streams;
	
	/**
	 * Constructor auto-opens streams if set to do so.
	 * @return void
	 */
	protected function __construct() {
		$this->headers = new RequestHeaders($_SERVER);
		$this->streams = new Streams();
	}
	
	/**
	 * Checks whether the current HTTP request is a multipart/form-data POST.
	 *
	 * @return boolean True if request method is POST and Content-Type header
	 * is multipart/form-data, otherwise false.
	 */
	public function isMultipartFormData() {
	
		$method = $this->getRequestHeader('request-method');
		$content_type = $this->getRequestHeader('content-type');
		
		if ('POST' === $method && $content_type) {
			return stripos($content_type, 'multipart/form-data') !== false;
		}
		
		return false;
	}
	
	/** ================================
	 			Request Headers
	================================ **/
		
	/**
	 * Returns array of HTTP request headers.
	 *
	 * Cross-platform function to retrive the current HTTP request headers.
	 * 
	 * Uses apache_request_headers() if available, otherwise uses $_SERVER global.
	 * Normalizes header labels (array keys) to lowercased values, stripped of
	 * any "http" prefix and with underscores converted to dashes (e.g. "accept-language").
	 *
	 * @return array HTTP request headers, keys stripped of "HTTP_" and lowercase.
	 */
	public function getRequestHeaders() {
		return $this->headers->getAll();
	}
	
	/**
	 * Fetches a single HTTP request header.
	 *
	 * @param string $name		Header name, lowercase, without 'HTTP_' prefix.
	 * @return string|null		Header value, if set, otherwise null.
	 */
	public function getRequestHeader($name) {
		return $this->headers->get($name);
	}
	
	/**
	 * Matches the contents of a given HTTP request header.
	 *
	 * @param string $name		Header name, lowercase, without 'HTTP_'.
	 * @param string $value		Value to match.
	 * @param bool $match_case	Whether to match case-sensitively, default false.
	 * @return boolean			True if match, otherwise false.
	 */
	public function matchRequestHeader($name, $value, $match_case = false) {
		return $this->headers->match($name, $value, $match_case);
	}
	
	/**
	 * Determines if $value is in the contents of $name request header.
	 *
	 * @param string $name		Header name, lowercase, without 'HTTP_'.
	 * @param string $value		Value to search for.
	 * @param bool $match_case	Whether to search case-sensitive, default false.
	 * @return boolean			True if found, otherwise false.
	 */
	public function inRequestHeader($name, $value, $match_case = false) {
		return $this->headers->in($name, $value, $match_case);
	}
	
	/**
	 * Determines response content-type by matching the 'Accept'
	 * request header to an accepted content-type.
	 *
	 * Returns the first content-type in the header that matches
	 * one of the given types. If none is matched, returns the
	 * default content-type (first array item).
	 *
	 * @param array $accept	Indexed array of accepted content-types.
	 * @return string		Matched content-type, or first array item if no match.
	 */
	public function negotiateContentType(array $accept) {
		return $this->headers->negotiateContentType($accept);
	}
		
	/**
	 * Determines best language from the 'Accept-Language' request header
	 * given an array of accepted languages.
	 *
	 * Tries to find a direct match (e.g. 'en-US' to 'en-US') but if none is
	 * found, finds the best match determined by prefix (e.g. "en").
	 * 
	 * @param array $accept Indexed array of accepted languages.
	 * @param array &$result If given an array, will be populated with negotiation results.
	 * @return string Best-match language, or first language given if no match.
	 */
	public function negotiateLanguage(array $accept, &$result = null) {
		return $this->headers->negotiateLanguage($accept, $result);
	}
	
	/**
	 * Parses an arbitrary request header to determine which value to use in
	 * response.
	 *
	 * This is a general-use function; specific implementations exist for
	 * content-type and language negotiation.
	 *
	 * @see http_negotiate_content_type()
	 * @see http_negotiate_language()
	 *
	 * @param string $name	Request header name, lowercase.
	 * @param array $accept Indexed array of accepted values.
	 * @return string 		Matched value (selected by quality, then position),
	 * 						or first array value if no match found.
	 */
	public function negotiateRequestHeader($name, array $accept) {
		return $this->headers->negotiate($name, $accept);
	}
	
	/** ================================
	 			I/O Streams
	================================ **/
		
	/**
	 * Returns a read-only file handle for the request body stream.
	 * 
	 * Uses fopen("php://input") with binary flag.
	 * 
	 * @return resource Read-only file handle for php://input stream.
	 */
	public function getInputStream() {
		return $this->streams->getInputStream();
	}
	
	/** Alias of getInputStream() */
	public function getRequestBodyStream() {
		return $this->getInputStream();
	}
	
	/**
	 * Returns a write-only file handle for the response body stream.
	 * 
	 * Uses fopen("php://output") with binary flag.
	 * 
	 * @return resource Write-only file handle for php://output stream.
	 */
	public function getOutputStream() {
		return $this->streams->getOutputStream();
	}
	
	/** Alias of getOutputStream() */
	public function getResponseBodyStream() {
		return $this->getOutputStream();
	}
	
	/**
	 * Returns request body string.
	 *
	 * This function will not work if php://input is read before calling
	 * (e.g. via file_get_contents(), fopen(), etc.).
	 * 
	 * Also, POST requests with "multipart/form-data" will not work with this
	 * function, as it relies on php://input.
	 *
	 * @see \HttpUtil\Request\Body::contents()
	 * 
	 * @return string HTTP request body.
	 */
	public function getRequestBody() {
		return $this->streams->getRequestBody();
	}
	
	/**
	 * Returns the response body as string.
	 * 
	 * @return string Response body using stream_get_contents().
	 */
	public function getResponseBody() {
		return $this->streams->getResponseBody();
	}
	
}
