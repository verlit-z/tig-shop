<?php

namespace app\model\setting;

use think\Model;

class AreaCode extends Model
{
	protected $pk = 'id';
	protected $table = 'area_code';


    protected $append = [
        'label'
    ];

    public function getLabelAttr($value, $data)
    {
        return '+' . $data['code'];
    }
}