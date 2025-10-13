<?php

namespace app\service\admin\print;

abstract class AbstractPrintService
{
    /**
     * 添加打印机
     * @return mixed
     */
    abstract public function add(array $order): array;

    /**
     * 删除打印机
     * @return mixed
     */
    abstract public function delete(array $order): array;

    /**
     * 打印
     * @return mixed
     */
    abstract public function print(array $order): array;


}
