<?php

namespace xpl\Http\Exception;

class NotFound extends \xpl\Http\Exception
{
	protected $status_code = 404;
	protected $public_message = 'Page not found.';
	protected static $allow_code_override = false;
}