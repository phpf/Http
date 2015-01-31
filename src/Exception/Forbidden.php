<?php

namespace xpl\Http\Exception;

class Forbidden extends \xpl\Http\Exception
{
	protected $status_code = 403;
	protected $public_message = 'Access forbidden.';
	protected static $allow_code_override = false;
}