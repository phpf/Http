<?php

namespace xpl\Http\Exception;

class MethodNotAllowed extends \xpl\Http\Exception
{
	protected $status_code = 405;
	protected $public_message = 'The requested HTTP method is not allowed.';
	protected static $allow_code_override = false;
}