<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 装修
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\decorate;

use app\model\common\Locales;
use think\Model;
use utils\Time;

class Decorate extends Model
{
    protected $pk = 'decorate_id';
    protected $table = 'decorate';
    protected $json = ['data', 'draft_data'];
    // 设置JSON数据返回数组
    protected $jsonAssoc = true;
    const TYPE_H5 = 1; //H5首页
    const TYPE_PC = 2; //PC页面
    protected const TYPE_MAP = [
        self::TYPE_H5 => 'H5首页',
        self::TYPE_PC => 'PC页面',
    ];
    public function getUpdateTimeAttr($value): string
    {
        return Time::format($value);
    }

    public function locale()
    {
        return $this->hasOne(Locales::class, 'id', 'locale_id');
    }

    public function bindLocaleName()
    {
        return $this->hasOne(Locales::class, 'id', 'locale_id')->bind(['language']);
    }

    public function children()
    {
        return $this->hasMany(Decorate::class, 'parent_id', 'decorate_id');
    }

    public function getTypeNameAttr($value, $data): string
    {
        return isset($data['decorate_type']) && self::TYPE_MAP[$data['decorate_type']] ?? '';
    }

    public function getDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value, true);
    }

    public function setDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function getDraftDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }

    public function setDraftDataAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return camelCase($value);
    }
}
