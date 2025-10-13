<?php

namespace app\model\salesman;


use app\model\BaseModel;
use app\service\admin\salesman\ConfigService;

class SalesmanProduct extends BaseModel
{
    protected $pk = 'salesman_product_id';
    protected $table = 'salesman_product';
    protected $json = ['commission_data'];
    protected $jsonAssoc = true;
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'add_time';
    protected $updateTime = 'update_time';
    protected $append = ['product_commission'];


    public function product()
    {
        return $this->hasOne(\app\model\product\Product::class, 'product_id', 'product_id');
    }

    // 关联商品订单
    public function salesmanOrder()
    {
        return $this->hasMany(\app\model\salesman\SalesmanOrder::class, 'product_id', 'product_id');
    }

    // 推广类型
    const PRODUCT_TYPE_NOT_JOIN = 1;
    const PRODUCT_TYPE_CUSTOM_SCALE = 2;
    const PRODUCT_TYPE_JOIN = 3;
    const PRODUCT_TYPE_CUSTOM_AMOUNT = 4;
    const PRODUCT_TYPE_MAP = [
        self::PRODUCT_TYPE_NOT_JOIN => '不参与推广的商品',
        self::PRODUCT_TYPE_CUSTOM_SCALE => '自定义比例的商品',
        self::PRODUCT_TYPE_JOIN => '参与推广的商品',
        self::PRODUCT_TYPE_CUSTOM_AMOUNT => '自定义金额的商品',
    ];

    // 佣金计算方式
    const COMMISSION_TYPE_DEFAULT = 1;
    const COMMISSION_TYPE_CUSTOM_SCALE = 2;
    const COMMISSION_TYPE_CUSTOM_AMOUNT = 3;
    const COMMISSION_TYPE_MAP = [
        self::COMMISSION_TYPE_DEFAULT => '默认比例',
        self::COMMISSION_TYPE_CUSTOM_SCALE => '自定义比例',
        self::COMMISSION_TYPE_CUSTOM_AMOUNT => '自定义金额',
    ];

    // 商品佣金详情
    public function getProductCommissionAttr($value, $data)
    {
        $commission_info = [];
        $product_commission = $sub_commission = "";
        $salesman_config = app(ConfigService::class)->getDetail('salesmanConfig', $data['shop_id']);
        if ($data['is_join'] == 1 && $data['commission_type'] == 1 && empty($data['commission_data'])) {
            if (!empty($salesman_config)) {
                foreach ($salesman_config['level'] as  $v) {
                    $product_commission .= $v['name'] . "佣金:{$v['rate']}%;";
                    if ($salesman_config['saleType'] != 1) {
                        $sub_commission .= $v['name'] . "佣金: {$v['downSalesmanRate']}%;";
                    }
                }
            }
        } else {
            if ($data['is_join'] == 1 && !empty($data['commission_data']) && !empty($data['commission_type'])) {
                $commission_data = is_array($data['commission_data']) ? $data['commission_data'] : json_decode($data['commission_data'],
                    true);
                if (!empty($salesman_config) && !empty($commission_data)) {
                    foreach ($commission_data as $item) {
                        if (!empty($item['level_arr'])) {
                            foreach ($item['level_arr'] as $k => $val) {
                                if (in_array($data['commission_type'], [self::COMMISSION_TYPE_DEFAULT, self::COMMISSION_TYPE_CUSTOM_SCALE])) {
                                    $product_commission .= $salesman_config['level'][$k]['name'] . "佣金:{$val['rate']}%;";
                                    if ($salesman_config['saleType'] != 1) {
                                        $sub_commission .= $salesman_config['level'][$k]['name'] . "佣金: {$val['down_salesman_rate']}%;";
                                    }
                                } else {
                                    if(isset($salesman_config['level'][$k])) {
                                        $product_commission .= $salesman_config['level'][$k]['name'] . "佣金: {$val['rate']};";
                                        if ($salesman_config['saleType'] != 1) {
                                            $sub_commission .= $salesman_config['level'][$k]['name'] . "佣金: {$val['down_salesman_rate']};";
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $commission_info['product_commission'] = $product_commission;
        $commission_info['sub_commission'] = $sub_commission;
        return $commission_info;
    }
}