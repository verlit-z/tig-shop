<?php

namespace app\api\controller\common;

use app\api\IndexBaseController;
use think\App;
use think\facade\Cache;

class Csrf extends IndexBaseController
{
    /**
     * 构造函数
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    public function create()
    {
        $md5Hex1 = md5(microtime(true));
        Cache::set($md5Hex1, 1, 600);
        return $this->success($md5Hex1);
    }
}