<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 装修分类商品（宽）模块
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\decorate\modules;

use app\model\product\Category;
use app\service\common\BaseService;
use exceptions\ApiException;
use utils\Util;

/**
 * 装修服务类
 */
class PcCatWideService extends BaseService
{
    public function __construct()
    {
    }

    /**
     * 模块数据格式化
     *
     * @param array $module
     * @return array
     * @throws ApiException
     */
    public function formatData(array $module): array
    {
        $cat_ids = $module['catIds'];
        $filterResult = Category::whereIn('category_id', $cat_ids)->where('is_show', 1)->field([
            'category_id',
            'category_name',
            'category_pic',
        ])->select();
        foreach ($filterResult as &$item) {
            $item['category_name'] = Util::lang($item['category_name'], '', [], 3);
        }
        $module['cat_list'] = $filterResult;
        return $module;
    }

}
