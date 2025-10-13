<?php

namespace app\adminapi\controller\common;

use app\BaseController;
use think\facade\Cache;

class Csrf extends BaseController
{
    public function __construct()
    {
    }

    public function create()
    {
        $md5Hex1 = md5(microtime(true));
        Cache::set($md5Hex1, 1, 600);
        return $this->success($md5Hex1);
    }

}