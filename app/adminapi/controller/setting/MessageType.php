<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 消息设置
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\setting;

use app\adminapi\AdminBaseController;
use app\service\admin\setting\MessageTypeService;
use exceptions\ApiException;
use think\App;
use think\facade\Db;
use think\Response;

/**
 * 消息设置控制器
 */
class MessageType extends AdminBaseController
{
    protected MessageTypeService $messageTypeService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param MessageTypeService $messageTypeService
     */
    public function __construct(App $app, MessageTypeService $messageTypeService)
    {
        parent::__construct($app);
        $this->messageTypeService = $messageTypeService;
    }

    /**
     * 列表页面
     * @return Response
     */
    public function list(): Response
    {
        $filter = $this->request->only([
            'keyword' => '',
            'send_type/d' => 0,
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'message_id',
            'sort_order' => 'desc',
        ], 'get');

        $filterResult = $this->messageTypeService->getFilterResult($filter);
        $total = $this->messageTypeService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 详情
     * @return Response
     */
    public function detail(): Response
    {
        $id =$this->request->all('id/d', 0);
        $item = $this->messageTypeService->getDetail($id);
        return $this->success(
            $item
        );
    }

    /**
     * 获取请求数据
     * @return array
     */
    public function requestData(): array
    {
        $data = $this->request->only([
            'describe' => '',
            'is_wechat' => '',
            'is_mini_program' => '',
            'is_message' => '',
            'is_msg' => '',
            'is_app' => '',
            'is_ding' => '',
            'template_message' => [],
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
        $result = $this->messageTypeService->createMessageType($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '消息设置添加失败');
        }
    }

    /**
     * 执行更新操作
     * @return Response
     */
    public function update(): Response
    {
        $id =$this->request->all('id/d', 0);
        $data = $this->requestData();
        $data["message_id"] = $id;
        $result = $this->messageTypeService->updateMessageType($id, $data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '消息设置更新失败');
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

        if (!in_array($field,
            ['name', 'is_wechat', 'is_mini_program', 'is_message', 'is_msg', 'is_app', 'is_ding', 'add_time'])) {
            return $this->error(/** LANG */ '#field 错误');
        }

        $data = [
            'message_id' => $id,
            $field =>$this->request->all('val'),
        ];

        $this->messageTypeService->updateMessageTypeField($id, $data);

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
        $this->messageTypeService->deleteMessageType($id);
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
            return $this->error(/** LANG */ '未选择项目');
        }

        if ($this->request->all('type') == 'del') {
            try {
                //批量操作一定要事务
                Db::startTrans();
                foreach ($this->request->all('ids') as $key => $id) {
                    $id = intval($id);
                    $this->messageTypeService->deleteMessageType($id);
                }
                Db::commit();
            } catch (\Exception $exception) {
                Db::rollback();
                throw new ApiException($exception->getMessage());
            }

            return $this->success();
        } else {
            return $this->error(/** LANG */ '#type 错误');
        }
    }

    /**
     * 生成小程序消息模板
     * @return \think\Response
     * @throws \exceptions\ApiException
     */
    public function miniProgramMessageTemplate()
    {
        $this->messageTypeService->generateMiniProgramMessageTemplate();
        return $this->success();
    }

    /**
     * 同步小程序消息模板
     * @return \think\Response
     * @throws \exceptions\ApiException
     */
    public function miniProgramMessageTemplateSync()
    {
        $this->messageTypeService->generateMiniProgramMessageTemplateSync();
        return $this->success();
    }

    /**
     * 生成公众号消息模板
     * @return \think\Response
     * @throws \exceptions\ApiException
     */
    public function wechatMessageTemplate(): Response
    {
        $this->messageTypeService->generateWechatMessageTemplate();
        return $this->success();
    }

    /**
     * 同步公众号消息模板
     * @return \think\Response
     * @throws \exceptions\ApiException
     */
    public function wechatMessageTemplateSync()
    {
        $this->messageTypeService->generateWechatMessageTemplateSync();
        return $this->success();
    }


}
