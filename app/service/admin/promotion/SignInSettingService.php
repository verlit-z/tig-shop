<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 积分签到
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\promotion;

use app\model\promotion\SignInSetting;
use app\service\common\BaseService;
use exceptions\ApiException;
use log\AdminLog;
use utils\Config as UtilsConfig;

/**
 * 积分签到服务类
 */
class SignInSettingService extends BaseService
{
    private string $integralName;
    public function __construct()
    {
        $this->integralName = UtilsConfig::get('integralName');
    }

    /**
     * 获取筛选结果
     *
     * @param array $filter
     * @return array
     */
    public function getFilterResult(array $filter): array
    {
        $query = $this->filterQuery($filter);
        $result = $query->page($filter['page'], $filter['size'])->select();
        return $result->toArray();
    }

    /**
     * 获取筛选结果数量
     *
     * @param array $filter
     * @return int
     */
    public function getFilterCount(array $filter): int
    {
        $query = $this->filterQuery($filter);
        $count = $query->count();
        return $count;
    }

    /**
     * 筛选查询
     *
     * @param array $filter
     * @return object
     */
    public function filterQuery(array $filter): object
    {
        $query = SignInSetting::query();
        // 处理筛选条件

        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $query->where('name', 'like', '%' . $filter['keyword'] . '%');
        }

        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }
        return $query;
    }

    /**
     * 获取详情
     *
     * @param int $id
     * @return SignInSetting
     * @throws ApiException
     */
    public function getDetail(int $id): SignInSetting
    {
        $result = SignInSetting::where('id', $id)->find();

        if (!$result) {
            throw new ApiException(/** LANG */ $this->integralName . '签到不存在');
        }

        return $result;
    }

    /**
     * 获取名称
     *
     * @param int $id
     * @return string|null
     */
    public function getName(int $id): ?string
    {
        return SignInSetting::where('id', $id)->value('name');
    }

    /**
     * 添加积分签到
     * @param array $data
     * @return int
     */
    public function createSignInSetting(array $data): int
    {
        $result = SignInSetting::create($data);
        AdminLog::add('新增'. $this->integralName .'签到:' . $data['name']);
        return $result->getKey();
    }


    /**
     * 执行积分签到更新
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function updateSignInSetting(int $id, array $data): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */'#id错误');
        }
        $result = SignInSetting::where('id', $id)->save($data);
        AdminLog::add('更新'.$this->integralName.'签到:' . $this->getName($id));
        return $result !== false;
    }

    /**
     * 删除积分签到
     *
     * @param int $id
     * @return bool
     */
    public function deleteSignInSetting(int $id): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */'#id错误');
        }
        $get_name = $this->getName($id);
        $result = SignInSetting::destroy($id);

        if ($result) {
            AdminLog::add('删除'.$this->integralName.'签到:' . $get_name);
        }

        return $result !== false;
    }
}
