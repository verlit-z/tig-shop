<?php

namespace app\model\salesman;


use app\model\BaseModel;

class Config extends BaseModel
{
    protected $pk = 'id';
    protected $table = 'salesman_config';
    protected $json = ['data'];
    protected $jsonAssoc = true;

}