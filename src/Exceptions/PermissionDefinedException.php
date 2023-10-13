<?php

namespace H2o\PermissionManager\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class PermissionDefinedException extends HttpException
{
    public function __construct()
    {
        parent::__construct($this->code, $this->message);
    }

    protected $code = 403;

    protected $message = "Permission defined";
}
