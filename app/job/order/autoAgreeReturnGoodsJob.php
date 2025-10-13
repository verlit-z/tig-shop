<?php

namespace app\job\order;

use app\job\BaseJob;
use app\model\order\Aftersales;

class autoAgreeReturnGoodsJob extends BaseJob
{
    public function doJob(array $data)
    {
        //只处理已发货的
        $model = new Aftersales;
        //更新售后字段
        $aftersales = $model->find($data['aftersale_id']);
        if($aftersales) {
            $aftersales->return_goods_tip = "您的售后申请已通过，请联系商家获取退货地址";
            $aftersales->status = Aftersales::STATUS_SEND_BACK;
            $aftersales->save();
        }
        return true;
    }

}