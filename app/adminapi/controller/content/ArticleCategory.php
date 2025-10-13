<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 文章分类
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\content;

use app\adminapi\AdminBaseController;
use app\service\admin\content\ArticleCategoryService;
use app\validate\content\ArticleCategoryValidate;
use exceptions\ApiException;
use think\App;
use think\exception\ValidateException;
use think\facade\Db;
use think\Response;

/**
 * 分类名称控制器
 */
class ArticleCategory extends AdminBaseController
{
    protected ArticleCategoryService $articleCatService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param ArticleCategoryService $articleCatService
     */
    public function __construct(App $app, ArticleCategoryService $articleCatService)
    {
        parent::__construct($app);
        $this->articleCatService = $articleCatService;
    }

    /**
     * 列表页面
     *
     * @return Response
     */
    public function list(): Response
    {
        $filter = $this->request->only([
            'keyword' => '',
            'parent_id' => 0,
            'is_show' => -1,
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'c.article_category_id',
            'sort_order' => 'desc',
        ], 'get');

        $filterResult = $this->articleCatService->getFilterResult($filter);
        $total = $this->articleCatService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 获取所有分类
     *
     * @return Response
     */
    public function tree(): Response
    {
        $pid =$this->request->all('id/d', 0);
        $cat_list = $this->articleCatService->catList($pid);
        return $this->success(
            $cat_list["children"],
        );
    }

    /**
     * 详情
     * @return Response
     */
    public function detail(): Response
    {
        $id =$this->request->all('id/d', 0);
        $item = $this->articleCatService->getDetail($id);
        return $this->success(
             $item
        );
    }

    /**
     * 请求数据
     * @return array
     */
    public function requestData():array
    {
        $data = $this->request->only([
            'parent_id' => 0,
            'article_category_name' => '',
            'category_sn' => '',
            'category_type' => 0,
            'keywords' => '',
            'description' => '',
            'sort_order/d' => 50,
        ], 'post');
        return $data;
    }

    /**
     * 添加
     * @return Response
     */
    public function create(): Response
    {
        $data = $this->requestData();
        try {
            validate(ArticleCategoryValidate::class)
                ->scene('create')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }
        $result = $this->articleCatService->createArticleCat($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'分类名称添加失败');
        }
    }

    /**
     * 执行添加或更新操作
     *
     * @return Response
     */
    public function update(): Response
    {
        $id =$this->request->all('id/d', 0);
        $data = $this->requestData();
        $data["article_category_id"] = $id;
        try {
            validate(ArticleCategoryValidate::class)
                ->scene('update')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->articleCatService->updateArticleCat($id, $data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'分类名称更新失败');
        }
    }

    /**
     * 更新单个字段
     *
     * @return Response
     */
    public function updateField(): Response
    {
        $id =$this->request->all('id/d', 0);
        $field =$this->request->all('field', '');

        if (!in_array($field, ['article_category_name', 'category_sn', 'sort_order'])) {
            return $this->error(/** LANG */'#field 错误');
        }

        $data = [
            'article_category_id' => $id,
            $field =>$this->request->all('val'),
        ];

        $this->articleCatService->updateArticleCategoryField($id, $data);

        return $this->success();
    }

    /**
     * 删除
     *
     * @return Response
     */
    public function del(): Response
    {
        $id =$this->request->all('id/d', 0);
        $this->articleCatService->deleteArticleCat($id);
        return $this->success();
    }

    /**
     * 批量操作
     *
     * @return Response
     */
    public function batch(): Response
    {
        if (empty($this->request->all('ids')) || !is_array($this->request->all('ids'))) {
            return $this->error(/** LANG */'未选择项目');
        }

        if ($this->request->all('type') == 'del') {
            try {
                //批量操作一定要事务
                Db::startTrans();
                foreach ($this->request->all('ids') as $key => $id) {
                    $id = intval($id);
                    $this->articleCatService->deleteArticleCat($id);
                }
                Db::commit();
            } catch (\Exception $exception) {
                Db::rollback();
                throw new ApiException($exception->getMessage());
            }

            return $this->success();
        } else {
            return $this->error(/** LANG */'#type 错误');
        }
    }
}
