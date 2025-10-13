<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 装修
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\decorate;

use app\model\common\Locales;
use app\model\decorate\Decorate;
use app\service\common\BaseService;
use exceptions\ApiException;
use utils\Time;
use utils\Util;

/**
 * 装修服务类
 */
class DecorateService extends BaseService
{
    public function __construct(Decorate $model)
    {
        $this->model = $model;
    }

    /**
     * 筛选查询
     *
     * @param array $filter
     * @return object
     */
    protected function filterQuery(array $filter): object
    {
        $query = $this->model->query();
        // 处理筛选条件

        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $query->where('decorate_title', 'like', '%' . $filter['keyword'] . '%');
        }

        // 页面类型检索
        if (isset($filter["decorate_type"]) && !empty($filter["decorate_type"])) {
            $query->where('decorate_type', $filter['decorate_type']);
        }

        if (isset($filter['is_show']) && $filter['is_show'] > -1) {
            $query->where('is_show', $filter['is_show']);
        }

        if (isset($filter['parent_id']) && $filter['parent_id'] > -1) {
            $query->where('parent_id', $filter['parent_id']);
        }

        if (isset($filter['locale_id']) && $filter['locale_id'] > -1) {
            $query->where('parent_id', $filter['parent_id']);
        }

        if (isset($filter['shop_id']) && $filter['shop_id'] > -1) {
            $query->where('shop_id', $filter['shop_id']);
        }

        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }
        return $query;
    }

    /**
     * 复制
     * @param int $id
     * @return bool
     * @throws ApiException
     */
    public function copy(int $id): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */ '#id错误');
        }
        $item = Decorate::where('decorate_id', $id)->find();
        if (!$item) {
            throw new ApiException(/** LANG */ '#未找到数据');
        }
        $new_data = $item->getData();
        unset($new_data['decorate_id']);
        $new_data['is_home'] = 0;
        $new_data['decorate_title'] = $new_data['decorate_title'] . '-复制';
        $new_data['update_time'] = Time::now();
        $mobileDecorate = new Decorate();
        $result = $mobileDecorate->save($new_data);
        return $result !== false;
    }

    /**
     * 设置首页
     * @param int $id
     * @return bool
     * @throws ApiException
     */
    public function setHome(int $id): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */ '#id错误');
        }
        $item = $this->model::where('decorate_id', $id)->find();
        if (!$item) {
            throw new ApiException(/** LANG */ '#不存在的模板');
        }
        Decorate::where('is_home', 1)->where('decorate_type', $item->decorate_type)->where('shop_id',
            $item->shop_id)->save([
            'is_home' => 0,
        ]);
        $result = $item->save([
            'is_home' => 1,
        ]);
        return $result !== false;
    }

    /**
     * 获得PC端首页模板标题
     * @return array
     */
    public function getPcIndexDecoratePageConfig(int $id): array
    {
        if ($id) {
            $decorate = $this->model->where('decorate_id', $id)->field(['draft_data'])->find();
            $data = $decorate ? $decorate->draft_data['pageModule'] : [];
        } else {
            $decorate = $this->model->where('shop_id', 0)->where('decorate_type', 2)->where('is_home',
                1)->where('status',
                1)->field(['data'])->find();
            $data = $decorate ? $decorate->data['pageModule'] : [];
        }

        return $data;
    }

    /**
     * 获取默认头部样式类型
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDefaultDecoratePageHeaderStyle(): int
    {
        $decorate = $this->model->where('shop_id', 0)->where('decorate_type', 2)->where('is_home',
            1)->where('status',
            1)->field(['data'])->find();

        return $decorate->data['pageModule']['headerStyle'] ?? 1;
    }

    /**
     * 获取详情
     *
     * @param int $id
     * @throws ApiException
     */
    public function getDetail(int $id, array $with = [])
    {
        $result = $this->model->where('decorate_id', $id);
        if ($with) {
            $result = $result->with($with);
        }
        $result = $result->find();

        return $result;
    }

    /**
     * 加载草稿数据
     * @param int $id
     * @return array
     * @throws ApiException
     */
    public function loadDraftData(int $id): array
    {
        $detail = $this->getDetail($id);
        return $detail['draft_data'] ?? [];
    }

    /**
     * 获取名称
     *
     * @param int $id
     * @return string|null
     */
    public function getName(int $id): ?string
    {
        return $this->model::where('decorate_id', $id)->value('decorate_title');
    }

    /**
     * 添加装修
     * @param array $data
     * @return int
     */
    public function createDecorate(array $data): int
    {
        $data['update_time'] = Time::now();
        $result = $this->model->save($data);
        return $this->model->getKey();
    }

    /**
     * 执行装修更新
     *
     * @param int $id
     * @param array $data
     * @param bool $isAdd
     * @return int|bool
     * @throws ApiException
     */
    public function updateDecorate(int $id, array $data)
    {
        $data['update_time'] = Time::now();
        if (!empty($id)) {
            $result = $this->model->where('decorate_id', $id)->save($data);
        } else {
            //查询该父级和语言的是否有数据
            $exist = $this->model::where('parent_id', $data['parent_id'])->where('locale_id',
                $data['locale_id'])->find();
            if ($exist) {
                $result = $this->model->where('decorate_id', $exist['decorate_id'])->save($data);
            } else {
                //获得父级的详情
                $parent = $this->model::where('decorate_id', $data['parent_id'])->find();
                //创建模板
                $createData = [
                    'decorate_title' => $parent['decorate_title'],
                    'decorate_type' => $parent['decorate_type'],
                    'parent_id' => $data['parent_id'],
                    'shop_id' => $parent['shop_id'],
                    'locale_id' => $data['locale_id'],
                    'data' => $data['data'],
                ];
                if (isset($data['status'])) {
                    $createData['status'] = $data['status'];
                }
                $createData['update_time'] = Time::now();
                $result = $this->createDecorate($createData);
            }

        }

        return $result !== false;
    }

    /**
     * 发布并保存模板
     *
     * @param integer $id
     * @param array $data
     * @return bool
     */
    public function publishDecorate(int $id, array $data): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */ '#id错误');
        }
        $data['status'] = 1;
        $data['draft_data'] = '';
        $data['update_time'] = Time::now();
        $result = $this->model->where('decorate_id', $id)->save($data);
        return $result !== false;
    }

    /**
     * 保存草稿
     * @param int $id
     * @param array $draft_data
     * @return bool
     * @throws ApiException
     */
    public function saveDecoratetoDraft(int $id, array $draft_data, array $data): bool
    {
        if ($id) {
            $data = [
                'draft_data' => $draft_data,
            ];
            $result = $this->model->where('decorate_id', $id)->save($data);
        } else {
            //查询该父级和语言的是否有数据
            $exist = $this->model::where('parent_id', $data['parent_id'])->where('locale_id',
                $data['locale_id'])->find();
            if ($exist) {
                $result = $this->model->where('decorate_id', $exist['decorate_id'])->save($data);
            } else {
                //获得父级的详情
                $parent = $this->model::where('decorate_id', $data['parent_id'])->find();
                //创建模板
                $createData = [
                    'decorate_title' => $parent['decorate_title'],
                    'decorate_type' => $parent['decorate_type'],
                    'parent_id' => $data['parent_id'],
                    'shop_id' => $parent['shop_id'],
                    'locale_id' => $data['locale_id'],
                    'draft_data' => $data['data'],
                ];
                if (isset($data['status'])) {
                    $createData['status'] = $data['status'];
                }
                $result = $this->createDecorate($createData);
            }
        }

        return $result !== false;
    }

    /**
     * 更新单个字段
     *
     * @param int $id
     * @param array $data
     * @return int|bool
     * @throws ApiException
     */
    public function updateDecorateField(int $id, array $data)
    {
        if (!$id) {
            throw new ApiException(/** LANG */ '#id错误');
        }
        $result = $this->model::where('decorate_id', $id)->save($data);
        return $result !== false;
    }

    /**
     * 删除装修
     *
     * @param int $id
     * @return bool
     */
    public function deleteDecorate(int $id): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */ '#id错误');
        }
        $result = $this->model::destroy($id);
        return $result !== false;
    }

    /**
     * 获取移动端首页发布的页面
     * @return array
     * @throws ApiException
     */
    public function getAppHomeDecorate(): array
    {
        return $this->getDecorateModule(Decorate::TYPE_H5, true);
    }

    /**
     * 获取PC端首页发布的页面
     * @return array
     * @throws ApiException
     */
    public function getPcHomeDecorate(): array
    {
        return $this->getDecorateModule(Decorate::TYPE_PC, true);
    }

    /**
     * 获取PC端预览数据
     * @param int $decorate_id
     * @return array
     * @throws ApiException
     */
    public function getPcPreviewDecorate(int $decorate_id): array
    {
        return $this->getPreviewDecorate(Decorate::TYPE_PC, $decorate_id);
    }

    /**
     * 获取移动端预览数据
     * @param int $decorate_id
     * @return array
     * @throws ApiException
     */
    public function getAppPreviewDecorate(int $decorate_id): array
    {
        return $this->getPreviewDecorate(Decorate::TYPE_H5, $decorate_id);
    }

    /**
     * 获取预览数据
     * @param int $type
     * @param int $decorate_id
     * @return array
     * @throws ApiException
     */
    public function getPreviewDecorate(int $type, int $decorate_id): array
    {
        $result = $this->model->where('decorate_type', $type)->where('decorate_id', $decorate_id)->find();
        $result = $result ? $result->toArray() : $result;
        if (!$result) {
            throw new ApiException(/** LANG */ Util::lang('模板不存在') . $decorate_id);
        }
        foreach ($result['draft_data']['moduleList'] as $key => $item) {
            $result['draft_data']['moduleList'][$key]['module'] = $this->formatModule($item['type'], $item['module']);
        }
        return [
            'decorate_id' => $result['decorate_id'],
            'module_list' => $result['draft_data']['moduleList'],
            'page_module' => $result['data']['pageModule'],
        ];
    }

    /**
     * 获取数据
     * @param int $type
     * @param int $decorate_id
     * @return array
     * @throws ApiException
     */
    public function getDecorate(int $type, int $decorate_id): array
    {
        $result = $this->model->where('decorate_type', $type)->where('decorate_id', $decorate_id)->find();
        $result = $result ? $result->toArray() : $result;
        if (!$result) {
            throw new ApiException(/** LANG */ Util::lang('模板不存在') . $decorate_id);
        }
        foreach ($result['data']['moduleList'] as $key => $item) {
            $result['data']['moduleList'][$key]['module'] = $this->formatModule($item['type'], $item['module']);
        }
        return [
            'decorate_id' => $result['decorate_id'],
            'module_list' => $result['data']['moduleList'],
            'page_module' => $result['data']['pageModule'],
        ];
    }

    /**
     * 获取店铺模块信息
     * @param integer $shop_id
     * @param integer $status 是否已发布
     * @return array
     */
    public function getShopDecorateModule(int $shop_id, int $status = 1): array
    {
        $result = $this->model
            ->where('shop_id', $shop_id)
            ->where('is_home', 1)
            ->where('status', $status)->find();
        $result = $result ? $result->toArray() : $result;
        if (!$result) {
            throw new ApiException(/** LANG */ Util::lang('模板不存在'));
        }
        foreach ($result['data']['moduleList'] as $key => $item) {
            $result['data']['moduleList'][$key]['module'] = $this->formatModule($item['type'], $item['module']);
        }
        return [
            'decorate_id' => $result['decorate_id'],
            'pageModule' => $result['data']['pageModule'],
            'module_list' => $result['data']['moduleList'],
        ];
    }

    /**
     * 获取页面模块信息
     * @param string $type
     * @param boolean $is_home 是否首页
     * @param integer $status 是否已发布
     * @return array
     */
    public function getDecorateModule(string $type, bool $is_home = false, int $status = 1, bool $is_draft = false): array
    {
        $result = $this->model->where('decorate_type', $type)->where('shop_id', 0)->where('is_home', $is_home)
            ->where('status', $status)->where('locale_id', 0)->find();

        if (!empty(request()->header('X-Locale-Code'))) {
            $locale = Locales::where('locale_code', request()->header('X-Locale-Code'))->find();
            if ($locale && $locale->is_default != 1) {
                $localeResult = $this->model->where('parent_id', $result->decorate_id)->where('locale_id',
                    $locale->id)->find();
                if ($localeResult) {
                    $result = $localeResult;
                }
            }
        }
        $result = $result ? $result->toArray() : $result;
        if (!$result) {
            throw new ApiException(/** LANG */ Util::lang('模板不存在'));
        }
        foreach ($result['data']['moduleList'] as $key => $item) {
            $result['data']['moduleList'][$key]['module'] = $this->formatModule($item['type'], $item['module']);
            if (!$item['isShow']) {
                unset($result['data']['moduleList'][$key]);
            }
        }
        $result['data']['moduleList'] = array_values($result['data']['moduleList']);
        return [
            'decorate_id' => $result['decorate_id'],
            'module_list' => $result['data']['moduleList'],
            'page_module' => $result['data']['pageModule'],
        ];
    }

    /**
     * 格式化模块
     * @param string $type
     * @param array $module
     * @param array|null $params
     * @return array
     */
    public function formatModule(string $type, array $module, array $params = null, $decorate = null): array
    {
        //类型与数据模型查询对应，例如pc_cat_product_simple装修类型跟product数据结构一样
        $typeGroup = [
            'pc_cat_product_simple' => 'product'
        ];
        if (isset($typeGroup[$type])) {
            $type = $typeGroup[$type];
        }
        $class = __NAMESPACE__ . '\\modules\\' . str_replace('_', '', ucwords($type, '_') . 'Service');
        if (class_exists($class)) {
            $moduleClass = new $class();
            $module = $moduleClass->formatData($module, $params, $decorate);
        }
        return $module;
    }

    /**
     * 获取指定模块的数据
     * @param $decorate_id
     * @param $module_index
     * @param array $params
     * @return array
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDecorateModuleData($decorate_id, $module_index, array $params = []): array
    {
        $result = $this->model->find($decorate_id);
        $result = $result ? $result->toArray() : $result;
        if (!$result) {
            throw new ApiException(/** LANG */ '模板不存在');
        }
        $module = [];
        foreach ($result['data']['moduleList'] as $key => $item) {
            if (isset($item['moduleIndex']) && $item['moduleIndex'] == $module_index) {
                $module = $this->formatModule($item['type'], $item['module'], $params, $result);
            }
        }
        return $module;
    }

    /**
     * 获取装修预览指定模块的数据
     * @param $decorate_id
     * @param $module_index
     * @param array $params
     * @return array
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPreviewDecorateModuleData($decorate_id, $module_index, array $params = []): array
    {
        $result = $this->model->find($decorate_id);
        $result = $result ? $result->toArray() : $result;
        if (!$result) {
            throw new ApiException(/** LANG */ Util::lang('模板不存在'));
        }
        $module = [];
        foreach ($result['draft_data']['moduleList'] as $key => $item) {
            if (isset($item['moduleIndex']) && $item['moduleIndex'] == $module_index) {
                $module = $this->formatModule($item['type'], $item['module'], $params, $result);
            }
        }
        return $module;
    }
}
