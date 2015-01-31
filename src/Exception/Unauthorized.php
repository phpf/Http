<?php

namespace xpl\Http\Exception;

class Unauthorized extends \xpl\Http\Exception
{
	protected $status_code = 401;
	protected $public_message = 'Unauthorized.';
	protected static $allow_code_override = false;
}