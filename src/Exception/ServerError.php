<?php

namespace xpl\Http\Exception;

class ServerError extends \xpl\Http\Exception
{
	protected $status_code = 500;
	protected $public_message = 'A server error occurred.';
	protected static $allow_code_override = false;
}