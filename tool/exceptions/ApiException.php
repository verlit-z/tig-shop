<?php

namespace exceptions;

use think\Exception;
use utils\ResponseCode;

class ApiException extends Exception
{
    protected $errorData = [];

    public function __construct($message = 'An error occurred', $code = ResponseCode::ERROR, $errorData = [])
    {
        $this->message = $message;
        $this->code = $code;
        $this->errorData = $errorData;
        parent::__construct($this->message, $this->code);
    }

    public function getErrorData()
    {
        return $this->errorData;
    }
}
