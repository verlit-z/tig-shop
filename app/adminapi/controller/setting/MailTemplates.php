<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 邮件模板设置
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\setting;

use app\adminapi\AdminBaseController;
use app\service\admin\setting\MailTemplatesService;
use app\validate\setting\MailTemplatesValidate;
use think\App;
use think\exception\ValidateException;
use think\Response;

/**
 * 邮件模板设置控制器
 */
class MailTemplates extends AdminBaseController
{
    protected MailTemplatesService $mailTemplatesService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param MailTemplatesService $mailTemplatesService
     */
    public function __construct(App $app, MailTemplatesService $mailTemplatesService)
    {
        parent::__construct($app);
        $this->mailTemplatesService = $mailTemplatesService;
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
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'template_id',
            'sort_order' => 'desc',
        ], 'get');

        $filterResult = $this->mailTemplatesService->getFilterResult($filter);
        //$total = $this->mailTemplatesService->getFilterCount($filter);
        return $this->success(
            $filterResult

        );

//        return $this->success([
//            'records' => $filterResult,
//            'total' => $total,
//        ]);
    }

    /**
     * 执行更新操作
     * @return Response
     */
    public function update(): Response
    {
        $id =$this->request->all('id/d', 0);
        $data = $this->request->only([
            'template_id' => $id,
            'template_subject' => '',
            'is_html' => '',
            "template_content" => '',
        ], 'post');

        try {
            validate(MailTemplatesValidate::class)
                ->scene('update')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        $result = $this->mailTemplatesService->updateMailTemplates($id, $data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'邮件模板设置更新失败');
        }
    }

    /**
     * 获取所有的邮件模板
     * @return Response
     */
    public function getAllMailTemplates(): Response
    {
        $item =  $this->mailTemplatesService->getAllMailTemplates();
        return $this->success(
            $item
        );
    }
}
