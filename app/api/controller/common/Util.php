<?php
//**---------------------------------------------------------------------+
//** 通用接口控制器文件 -- 通用
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\api\controller\common;

use app\api\IndexBaseController;
use app\service\admin\oauth\WechatOAuthService;
use chillerlan\QRCode\{QRCode, QROptions};
use think\App;
use think\Response;

/**
 * 工具组件
 */
class Util extends IndexBaseController
{
    /**
     * 构造函数
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**
     * 二维码
     *
     * @return Response
     */
    public function qrCode(): Response
    {
        $data = $this->request->all('url');
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG, // 设置输出为 PNG
        ]);
        $qrcode = (new QRCode($options))->render($data);
        return $this->success($qrcode);
    }

    /**
     * 获取小程序二维码
     * @return Response
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getMiniProgramCode(): Response
    {
        $path = $this->request->all('path', '');
        $product_id = $this->request->all('id', 0);
        $buffer = app(WechatOAuthService::class)->getMiniCode($path,$product_id);
        return $this->success($buffer);
    }

}
