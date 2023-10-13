<?php

namespace H2o\PermissionManager\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class UnauthorizedException extends HttpException
{
    public function __construct()
    {
        parent::__construct($this->code, $this->message);
    }

    protected $code = 401;

    protected $message = 'Unauthorized';
}
