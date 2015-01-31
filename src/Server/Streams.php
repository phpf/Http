<?php

namespace xpl\Http\Server;

/**
 * Wrapper for the web server's input/output streams.
 */
class Streams {
	
	/**
	 * @var resource
	 */
	protected $input_handle;
	
	/**
	 * @var resource
	 */
	protected $output_handle;
	
	/**
	 * @var string
	 */
	protected $input_content;
	
	/**
	 * @var string
	 */
	protected $output_content;
	
	/**
	 * @var boolean
	 */
	protected $auto_open_streams;
	
	public function __construct($auto_open_streams = false) {
		
		$this->auto_open_streams = (bool) $auto_open_streams;
		
		if ($this->auto_open_streams) {
			$this->getInputStream();
			$this->getOutputStream();
		}
	}
	
	/**
	 * Returns a read-only file handle for the request body stream.
	 * 
	 * Uses fopen("php://input") with binary flag.
	 * 
	 * @return resource Read-only file handle for php://input stream.
	 */
	public function getInputStream() {
		
		if (! isset($this->input_handle)) {
			$this->input_handle = fopen('php://input', 'rb');
		}
		
		return $this->input_handle;
	}
	
	/**
	 * Returns a write-only file handle for the response body stream.
	 * 
	 * Uses fopen("php://output") with binary flag.
	 * 
	 * @return resource Write-only file handle for php://output stream.
	 */
	public function getOutputStream() {
		
		if (! isset($this->output_handle)) {
			$this->output_handle = fopen('php://output', 'wb');
		}
		
		return $this->output_handle;
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
	 * @return string HTTP request body.
	 */
	public function getRequestBody() {
		
		if (! isset($this->input_content)) {
			$this->input_content = stream_get_contents($this->getInputStream());
		}
		
		return $this->input_content;
	}
	
	/**
	 * Returns the response body as string.
	 * 
	 * @return string Response body using stream_get_contents().
	 */
	public function getResponseBody() {
		
		if (! isset($this->output_content)) {
			$this->output_content = stream_get_contents($this->getOutputStream());
		}
		
		return $this->output_content;
	}
	
	/**
	 * Closes any open streams on destruct.
	 */
	public function __destruct() {
		is_resource($this->input_handle) AND fclose($this->input_handle);
		is_resource($this->output_handle) AND fclose($this->output_handle);
	}
	
}
