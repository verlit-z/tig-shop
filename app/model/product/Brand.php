<?php
//**---------------------------------------------------------------------+
//**   品牌模型
//**---------------------------------------------------------------------+
//**   版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//**   作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//**   提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\product;

use app\model\merchant\Shop;
use think\Model;
use utils\Util;

class Brand extends Model
{
    protected $pk = 'brand_id';
    protected $table = 'brand';

    const AUDIT_PASS = 1;// 审核通过
    const AUDIT_WAIT = 0;// 待审核
    const AUDIT_REJECT = 2;//  审核拒绝

    protected $append = [
        'status_text',
    ];

    const STATUS_LIST = [
        self::AUDIT_WAIT => '待审核',
        self::AUDIT_PASS => '审核通过',
        self::AUDIT_REJECT => '审核拒绝'
    ];

    public function getStatusTextAttr($value, $data): string
    {
        if (isset($data['status'])) {
            return self::STATUS_LIST[$data['status']] ?: '';
        }
        return '';
    }

    public function getBrandNameAttr($value, $data)
    {
        if (empty($value)) {
            return $value;
        }
        if (php_sapi_name() != 'cli' && !empty(request()->header('X-Locale-Code'))) {
            $cache = Util::lang($value, '', [], 4);
            if ($cache) {
                return $cache;
            } else {
                return $value;
            }
        } else {
            return $value;
        }
    }

    public function shop()
    {
        return $this->hasOne(Shop::class, 'shop_id', 'shop_id');
    }

}
