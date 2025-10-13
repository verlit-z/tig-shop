<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 对账单
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\finance;

use think\Model;
use utils\Util;

class Statement extends Model
{
    protected $pk = 'statement_id';
    protected $table = 'statement';
//    protected $createTime = 'add_time';
    protected $autoWriteTimestamp = true;

    const ACCOUNT_BALANCE=1;
    const  ACCOUNT_TYPE_NAME = [
            self::ACCOUNT_BALANCE => '账户余额',
        ];

    public static function getAccountTypeName($account_type)
    {
        return Util::lang(self::ACCOUNT_TYPE_NAME[$account_type]) ?? '';
    }



    const AUTO=1;

    const MANUAL=2;
    const ENTRY_TYPE_NAME = [
        self::AUTO => '自动',
        self::MANUAL => '手动',
    ];

    public static function getEntryTypeName($entry_type)
    {
        return Util::lang(self::ENTRY_TYPE_NAME[$entry_type]) ?? '';
    }


    const HANDLING_FEE=1;
    const SERVICE_FEE=2;
    const ORDER=3;
    const SHOP_WITHDRAWAL=4;
    const STORE_WITHDRAWAL=5;
    const SUPPLIER_WITHDRAWAL=6;

    const  STATEMENT_TYPE_NAME = [
        self::HANDLING_FEE => '手续费',
        self::SERVICE_FEE => '服务费',
        self::ORDER => '订单收支',
        self::SHOP_WITHDRAWAL => '店铺提现收支',
        self::STORE_WITHDRAWAL => '门店提现收支',
        self::SUPPLIER_WITHDRAWAL => '供应商提现收支',
    ];

    public static function getStatementTypeName($statement_type)
    {
        return Util::lang(self::STATEMENT_TYPE_NAME[$statement_type]) ?? '';
    }


    const WECHAT="wechat";
    const ALIPAY="alipay";
    const PAYPAL="paypal";
    const OFFLINE="offline";
    const BALANCE="balance";

    const PAY_TYPE_NAME = [
        self::WECHAT => '微信',
        self::ALIPAY => '支付宝',
        self::PAYPAL => 'paypal',
        self::OFFLINE => '线下支付',
        self::BALANCE => '余额'
    ];

    public static function getPayTypeName($pay_type)
    {
        return Util::lang(self::PAY_TYPE_NAME[$pay_type]) ?? '';
    }


    const DAY="day";
    const MONTH="month";
    const YEAR="year";
    const DATE_COMPONENT_TYPE = [
        self::DAY => '日',
        self::MONTH => '月',
        self::YEAR => '年',
    ];
    public static function getDateComponentType($date_component)
    {
        return Util::lang(self::DATE_COMPONENT_TYPE[$date_component]) ?? '';
    }

    const SETTLEMENT_TIME=1;
    const RECORD_TIME=2;
    const STATEMENT_TIME_TYPE = [
        self::SETTLEMENT_TIME => '入账时间',
        self::RECORD_TIME => '下单时间',
    ];
    public static function getStatementTimeType($date_component)
    {
        return Util::lang(self::STATEMENT_TIME_TYPE[$date_component]) ?? '';
    }



}
