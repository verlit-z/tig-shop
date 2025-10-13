<?php
//**---------------------------------------------------------------------+
//** 后台控制器文件 -- 设置项
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\adminapi\controller\setting;

use app\adminapi\AdminBaseController;
use app\model\setting\Region;
use app\service\admin\file\FileStorage;
use app\service\admin\pay\CertificatesService;
use app\service\admin\setting\ConfigService;
use think\App;
use think\Response;
use utils\Config as ShopConfig;

/**
 * 设置项控制器
 */
class Config extends AdminBaseController
{
    protected ConfigService $configService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param ConfigService $configService
     */
    public function __construct(App $app, ConfigService $configService)
    {
        parent::__construct($app);
        $this->configService = $configService;
    }


    /**
     * 基础设置
     * @return Response
     */
    public function basicSettings()
    {
        $properties = [
            'shopLogo',
            'shopName',
            'shopCompany',
            'shopCompanyTxt',
            'poweredBy',
            'poweredByLogo',
            'poweredByStatus',
            'kefuAddress',
            'shopIcpNo',
            'shopIcpNoUrl',
            'shop110No',
            'shop110Link',
            'shopRegClosed',
            'closeOrder',
            'defaultCopyright',
            'defaultTechSupport',
            'poweredBy',
            'poweredByStatus',
            'lightShopLogo'
        ];
        $result = $this->configService->getConfigByBizCode($properties);
        $result['defaultTechSupport'] = empty($result['defaultTechSupport']) ? '/static/mini/images/common/default_tech_support.png': $result['defaultTechSupport'];
        return $this->success($this->configService->fuzzyConfig($result));
    }

    /**
     * 商品设置
     * @return Response
     */
    public function productSettings()
    {
        $properties = [
            'dollarSign',
            'dollarSignCn',
            'snPrefix',
            'showSelledCount',
            'showMarketprice',
            'marketPriceRate'
        ];
        $result = $this->configService->getConfigByBizCode($properties);
        return $this->success($this->configService->fuzzyConfig($result));
    }

    /**
     * 获取通知配置
     * @return Response
     */
    public function notifySettings()
    {
        $properties = [
            'smsKeyId',
            'smsKeySecret',
            'smsSignName',
            'smsShopMobile',
            'serviceEmail',
            'sendConfirmEmail',
            'orderPayEmail',
            'sendServiceEmail',
            'sendShipEmail'
        ];
        $result = $this->configService->getConfigByBizCode($properties);
        return $this->success($this->configService->fuzzyConfig($result));
    }


    /**
     * 购物设置
     * @return Response
     */
    public function shoppingSettings()
    {
        $properties = [
            'childAreaNeedRegion',
            //'autoCancelOrderMinute',
            'integralName',
            'integralScale',
            'orderSendPoint',
            'integralPercent',
            'commentSendPoint',
            'showSendPoint',
            'useQiandaoPoint',
            'canInvoice',
            'invoiceAdded',
            'returnConsignee',
            'returnMobile',
            'returnAddress'
        ];
        $result = $this->configService->getConfigByBizCode($properties);
        return $this->success($this->configService->fuzzyConfig($result));
    }

    /**
     * 获取显示配置
     * @return Response
     */
    public function showSettings()
    {
        $properties = [
            'searchKeywords',
            'msgHackWord',
            'isOpenPscws',
            'selfStoreName',
            'shopDefaultRegions',
            'defaultCountry',
            'showCatLevel'
        ];

        $result = $this->configService->getConfigByBizCode($properties);
        $result = $this->configService->fuzzyConfig($result);
        $country = Region::where('parent_id', 0)->select();
        $result['countries'] = $country ? $country->toArray() : [];
        return $this->success($result);
    }

    /**
     * 获取客服配置
     * @return Response
     */
    public function kefuSettings()
    {
        $properties = [
            'kefuType',
            'kefuYzfType',
            'kefuYzfSign',
            'kefuWorkwxId',
            'corpId',
            'kefuCode',
            'kefuCodeBlank',
            'kefuPhone',
            'kefuTime'
        ];

        $result = $this->configService->getConfigByBizCode($properties);
        return $this->success($this->configService->fuzzyConfig($result));
    }

    /**
     * 保存客服配置
     * @return Response
     */
    public function saveKefu(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 获取分类页装修配置
     * @return Response
     */
    public function categoryDecorateSettings()
    {
        $properties = [
            'productCategoryDecorateType',
        ];

        $result = $this->configService->getConfigByBizCode($properties);
        return $this->success($this->configService->fuzzyConfig($result));
    }

    /**
     * 保存分类页装修配置
     * @return Response
     */
    public function saveCategoryDecorate(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }


    /**
     *
     * @return Response
     */
    public function themeStyleSettings()
    {
        $properties = [
            'themeStyle',
        ];


        $result = $this->configService->getConfigByBizCode($properties);
        return $this->success($this->configService->fuzzyConfig($result));
    }

    /**
     *
     * @return Response
     */
    public function saveThemeStyle(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }


    /**
     * 获取接口配置
     * @return Response
     */
    public function apiSettings()
    {
        $properties = [
            'wechatAppId',
            'wechatAppSecret',
            'wechatServerUrl',
            'wechatServerToken',
            'wechatServerSecret',
            'wechatMiniProgramAppId',
            'wechatMiniProgramSecret',
            'wechatPayAppId',
            'wechatPayAppSecret',
            'icoTigCss',
            'icoDefinedCss',
            'storageType',
            'storageLocalUrl',
            'storageOssUrl',
            'storageOssAccessKeyId',
            'storageOssAccessKeySecret',
            'storageOssBucket',
            'storageOssRegion',
            'storageCosUrl',
            'storageCosSecretId',
            'storageCosSecretKey',
            'storageCosBucket',
            'storageCosRegion',
            'langOn',
            'langType',
            'langVolcengineAccessKey',
            'langVolcengineSecret'
        ];


        $result = $this->configService->getConfigByBizCode($properties);
        return $this->success($this->configService->fuzzyConfig($result));
    }

    /**
     * 保存接口配置
     * @return Response
     */
    public function saveApi(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 获取会员认证配置
     * @return Response
     */
    public function authSettings()
    {
        $properties = [
            'type',
            'isIdentity',
            'isEnquiry',
            'smsNote',
            'tips'
        ];


        $result = $this->configService->getConfigByBizCode($properties);
        return $this->success($this->configService->fuzzyConfig($result));
    }

    /**
     * 获取邮箱配置
     * @return Response
     */
    public function mailSettings()
    {
        $properties = [
            'mailService',
            'smtpSsl',
            'smtpHost',
            'smtpPort',
            'smtpUser',
            'smtpPass',
            'smtpMail',
            'mailCharset',
            'testMailAddress'
        ];


        $result = $this->configService->getConfigByBizCode($properties);
        return $this->success($this->configService->fuzzyConfig($result));
    }

    /**
     * 获取物流配置
     * @return Response
     */
    public function shippingSettings()
    {
        $properties = [
            //'logisticsType',
            'kdniaoApiKey',
            'kdniaoBusinessId',
            'sender',
            'mobile',
            'provinceName',
            'cityName',
            'areaName',
            'address',
            'defaultLogisticsName'
        ];


        $result = $this->configService->getConfigByBizCode($properties);
        $res = $this->configService->fuzzyConfig($result);
        return $this->success(empty($res)? null : $res);
    }

    /**
     * 获取支付配置
     * @return Response
     */
    public function paySettings()
    {
        $paySaveParam = [
            'basicPaySettings' => [
                'useSurplus' => 0, // 是否启用余额支付；0-不支持，1-支持
                'usePoints' => 0, // 是否启用积分支付；0-不支持，1-支持
                'useCoupon' => 0, // 是否启用优惠券；0-不支持，1-支持
            ],
            'wechatPaySettings' => [
                'useWechat' => 0, // 是否启用微信支付；0-关闭，1-开启
                'wechatMchidType' => 1, // 微信商户号类型；1-普通商户模式，2-服务商模式
                'wechatPayMchid' => '', // 微信商户号
                'wechatPaySubMchid' => 0, // 微信子商户号
                'wechatPayKey' => '', // 商户API密钥
                'wechatPaySerialNo' => '', // 商户证书序列号
                'wechatPayCertificate' => 0, // 商户API证书
                'wechatPayPrivateKey' => 0, // 商户API证书密钥
                'wechatPayCheckType' => 1, // 验证微信支付方式；1-平台证书，2-微信支付公钥
                'wechatPayPlatformCertificate' => 0, // 平台证书
                'wechatPayPublicKeyId' => 0, // 微信支付公钥ID
                'wechatPayPublicKey' => 0, // 微信支付公钥文件
            ],
            'aliPaySettings' => [
                'useAlipay' => 0, // 是否启用支付宝支付；0-关闭，1-开启
                'alipayAppid' => '', // 支付宝APPID
                'alipayRsaPrivateKey' => '', // 应用私钥
                'alipayRsaPublicKey' => '', // 支付宝公钥
            ],
            'yaBandPaySettings' => [
                'useYabanpay' => 0, // 是否启用YaBand支付；0-关闭，1-开启
                'useYabanpayWechat' => 0, // 是否启用YaBand微信支付；0-关闭，1-开启
                'useYabanpayAlipay' => 0, // 是否启用YaBand支付宝支付；0-关闭，1-开启
                'yabanpayCurrency' => '', // YaBand支付货币类型
                'yabandpayUid' => '', // YaBand支付UID
                'yabandpaySecretKey' => '', // YaBand支付密钥
                'yabandPayCurrencyList' => [], // YaBand支付货币类型列表
            ],
            'offlinePaySettings' => [
                'useOffline' => 0, // 是否启用线下支付；0-关闭，1-开启
                'offlinePayBank' => '', // 银行汇款
                'offlinePayCompany' => '', // 企业汇款
            ],
            'payPalSettings' => [
                'usePaypal' => 0, // 是否启用PayPal支付；0-关闭，1-开启
                'paypalCurrency' => '', // PayPal货币类型
                'paypalClientId' => '', // PayPal客户端ID
                'paypalSecret' => '', // PayPal密钥
                'paypalCurrencyList' => [], // PayPal货币类型列表
            ],
            'yunPaySettings' => [
                'useYunpay' => 0, // 是否启用云支付；0-关闭，1-开启
                'yunpayUid' => '', // 商户号
                'yunpaySecretKey' => '', // 商户秘钥
            ],
        ];

        $allKey = [];
        foreach ($paySaveParam as $key => $value) {
            $allKey = array_merge($allKey, array_keys($value));
        }

        $result = $this->configService->getConfigByBizCode($allKey);
        $result = $this->configService->fuzzyConfig($result);
        foreach ($paySaveParam as $key => &$value) {
            foreach ($value as $k => $v) {
                if (isset($result[$k])) {
                    $value[$k] = $result[$k];
                }
            }
        }
        return $this->success($paySaveParam);
    }

    /**
     * 获取售后配置
     * @return Response
     */
    public function afterSalesSettings()
    {
        $properties = [
            'templateContent',
        ];


        $result = $this->configService->getConfigByBizCode($properties);
        return $this->success($this->configService->fuzzyConfig($result));
    }

    /**
     * 保存会员认证配置
     * @return Response
     */
    public function saveAuth(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 基础设置
     * @return Response
     */
    public function getBase(): Response
    {
        $code =$this->request->all('code');
        $config = $this->configService->getConfig($code);
        if ($code == 'payment') {
            //检测证书状态
            $public_key = app()->getRootPath() . '/runtime/certs/wechat/public_key.pem';
            $private_key = app()->getRootPath() . '/runtime/certs/wechat/apiclient_key.pem';
            $certificate = app()->getRootPath() . '/runtime/certs/wechat/apiclient_cert.pem';
            $platform_certs = app()->getRootPath() . '/runtime/certs/wechat/cert.pem';
            if (is_file($public_key)) {
                $config['wechatPayPublicKey'] = 1;
            }
            if (is_file($private_key)) {
                $config['wechatPayPrivateKey'] = 1;
            }
            if (is_file($certificate)) {
                $config['wechatPayCertificate'] = 1;
            }
            if (is_file($platform_certs)) {
                $config['wechatPayPlatformCertificate'] = 1;
            }
        }
        if (in_array($code, $this->configService->base_code)) {
            //基础设置项模糊处理
            $config = $this->configService->fuzzyConfig($config);
        }

        $result = [
            'item' => $config,
        ];
        if ($code == 'base') {
            $country = Region::where('parent_id', 0)->select();
            $result['countrys'] = $country ? $country->toArray() : [];
        }
        return $this->success($result);
    }

    /**
     * 后台需要的设置项
     * @return Response
     */
    public function getAdmin(): Response
    {
        $config = $this->configService->getAdmin();
        return $this->success($config);
    }

    public function getAdminBase(): Response
    {
        $shop_id = request()->shopId;
        $vendor_id = request()->vendorId;
        $config = $this->configService->getAdminConfig($shop_id, $vendor_id);
        return $this->success($config);
    }

    /**
     * 保存通知配置
     * @return Response
     */
    public function saveNotify(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 保存通知配置
     * @return Response
     */
    public function saveShow(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 保存商品配置
     * @return Response
     */
    public function saveProduct(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 保存购物配置
     * @return Response
     */
    public function saveShopping(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 基础设置更新
     * @return Response
     */
    public function savePay(): Response
    {
        $data = $this->request->all();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result = $this->configService->save($value);
            }
        }
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'设置项更新失败');
        }
    }

    /**
     * 编辑配置
     * @return Response
     * @throws \exceptions\ApiException
     */
    public function update(): Response
    {
        $code =$this->request->all('code');
        $data =$this->request->all("data/a", []);
        $result = $this->configService->saveConfig($code, $data);
        return $result ? $this->success() : $this->error(/** LANG */'设置项更新失败');
    }

    /**
     * 邮箱服务器设置
     * @return Response
     */
    public function saveMail(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 获取图标icon
     * @return Response
     */
    public function getIcon(): Response
    {
        $ico_tig = [];
        $tig_class = '';
        $ico_defined = [];
        $defined_class = '';

        //官方ico
        $ico_tig_css = 'https://at.alicdn.com/t/c/font_4441878_2rp6xtukhnh.css';
        if (!empty($ico_tig_css) && strpos($ico_tig_css, 'http') === 0 && substr($ico_tig_css, -4) === '.css') {
            $data = cache($ico_tig_css);
            if ($data === null) {
                $content = file_get_contents($ico_tig_css);
                preg_match_all("/" . '\.' . "(.*?)" . '\:before' . "/", $content, $return);
                $ico_tig = $return[1];
                unset($ico_tig[0]);
                preg_match('/font-family:\s*"([^"]+)";/', $content, $matches);
                $tig_class = $matches[1];
                $data['ico_tig'] = $ico_tig;
                $data['tig_class'] = $tig_class;
                cache($ico_tig_css, $data);
            } else {
                $ico_tig = $data['ico_tig'];
                $tig_class = $data['tig_class'];
            }
        }

        // 自定义ico
        $ico_defined_css = ShopConfig::get('icoDefinedCss');
        if (!empty($ico_defined_css) && strpos($ico_defined_css, 'http') === 0 && substr($ico_defined_css,
            -4) === '.css') {
            $data = cache($ico_defined_css);
            if ($data === null) {
                $content = file_get_contents($ico_defined_css);
                preg_match_all("/" . '\.' . "(.*?)" . '\:before' . "/", $content, $return);
                $ico_defined = $return[1];
                unset($ico_defined[0]);
                preg_match('/font-family:\s*"([^"]+)";/', $content, $matches);
                $defined_class = $matches[1];
                $data['ico_defined'] = $ico_defined;
                $data['defined_class'] = $defined_class;
                cache($ico_defined_css, $data);
            } else {
                $ico_defined = $data['ico_defined'];
                $defined_class = $data['defined_class'];
            }
        }

        return $this->success([
            'ico_tig' => $ico_tig,
            'tig_class' => $tig_class,
            'ico_defined' => $ico_defined,
            'defined_class' => $defined_class,
        ]);
    }

    /**
     * 发送测试邮件
     * @return Response
     * @throws \exceptions\ApiException
     */
    public function sendTestEmail(): Response
    {
        $email =$this->request->all("test_mail_address");
        $result = $this->configService->sendTestMail($email);
        return $result ? $this->success(/** LANG */'测试邮件已发送到' . $email) : $this->error(/** LANG */'邮件发送失败，请检查您的邮件服务器设置！');
    }

    /**
     * 上传API文件
     * @return Response
     * @throws \exceptions\ApiException
     */
    public function uploadFile(): Response
    {
        $type =$this->request->all('type/d');
        $rootPathName = app()->getRootPath() . '/runtime/certs/wechat/';
        $fileName = '';
        if ($type == 1) {
            $fileName = 'apiclient_cert.pem';
        }
        if ($type == 2) {
            $fileName = 'apiclient_key.pem';
        }
        if ($type == 3) {
            $fileName = 'public_key.pem';
        }
        if (empty($fileName)) {
            return $this->error(/** LANG */'未定义文件类型！');
        }
        $fileObj = request()->file('file');
        if (!$fileObj) {
            return $this->error(/** LANG */'未上传文件！');
        }
        $file = new FileStorage($fileObj, 0, $rootPathName, $fileName);
        $file->save();
        return $this->success();
    }

    /**
     * 生成平台证书
     * @return Response
     */
    public function createPlatformCertificate(): Response
    {
        try {
            app(CertificatesService::class)->getCertificates();
            return $this->success();
        } catch (\Exception $exception) {
            return $this->error($exception->getMessage());
        }
    }

    /**
     * 获取商城基础配置
     * @return Response
     */
    public function basicConfig(): Response
    {
        // 获取基础配置
        $result = $this->configService->getBasicConfig();
        return $this->success($result);
    }

    public function saveAfterSales(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    public function saveShipping(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 保存商城基础配置
     * @return Response
     */
    public function saveBasic(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */'设置项更新失败');
        }
    }

    /**
     * 获取主题切换配置
     * @return Response
     */
    public function layoutThemeSwitchSettings(): Response
    {
        $item = app(ConfigService::class)->getConfigByBizCode([
            'layout',
            'navTheme',
            'primaryColor',
            'uniqueOpened',
            'isMultiLabel'
        ]);
        return $this->success(
            $item
        );
    }

    /**
     * 获取商户配置
     * @return Response
     */
    public function merchantSettings(): Response
    {
        $item = app(ConfigService::class)->getConfigByBizCode([
            'personApplyEnabled',
            'merchantApplyNeedCheck',
            'maxShopCount',
            'shopAgreement',
            'shopProductNeedCheck',
            'maxRecommendProductCount',
            'shopRankDateRage',
            'enabledCommissionOrder',
            'defaultAdminPrefix',
            'maxSubAdministrator',
            'defaultShopName'
        ]);
        return $this->success(
            $item
        );
    }

    /**
     * 保存商户配置
     * @return Response
     */
    public function saveMerchant(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 获取店铺配置
     * @return Response
     */
    public function shopSettings()
    {
        $item = app(ConfigService::class)->getConfigByBizCode([
            'shopProductNeedCheck',
            'maxRecommendProductCount',
            'maxSubAdministrator',
            'defaultShopName'
        ]);
        return $this->success(
            $this->configService->fuzzyConfig($item)
        );
    }

    /**
     * 保存店铺配置
     * @return Response
     */
    public function saveShop(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 全局设置
     */
    public function saveGlobal(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 全局设置获取
     * @return Response
     */
    public function globalSettings(): Response
    {
        $item = app(ConfigService::class)->getConfigByBizCode([
            'layout',
            'navTheme',
            'primaryColor',
            'adminLightLogo',
            'versionInfoHidden',
            'pcDomain',
            'h5Domain',
            'adminDomain',
            'uploadMaxSize',
            'autoRedirect',
            'shopTitle',
            'shopTitleSuffix',
            'shopKeywords',
            'shopDesc',
            'defaultAvatar',
            'icoImg',
            'icoDefinedCss',
            'storageType',
            'storageLocalUrl',
            'storageOssUrl',
            'storageOssAccessKeyId',
            'storageOssAccessKeySecret',
            'storageOssBucket',
            'storageOssRegion',
            'storageCosUrl',
            'storageCosSecretId',
            'storageCosSecretKey',
            'storageCosBucket',
            'storageCosRegion',
            'langOn',
            'langType',
            'defaultCountry',
            'langVolcengineAccessKey',
            'langVolcengineSecret',
            'msgHackWord',
            'isOpenPscws',
            'shopDefaultRegions',
            'searchKeywords',
            'imDomain',
        ]);
        $shop_id = request()->shopId;
        if($shop_id > 0) {
            $item['layout'] = \app\model\setting\Config::SHOP_LAYOUT;
            $item['navTheme'] = \app\model\setting\Config::SHOP_NAVTHEME;
        }
        $country = Region::where('parent_id', 0)->select();
        $item['countries'] = $country ? $country->toArray() : [];
        return $this->success(
            $this->configService->fuzzyConfig($item)
        );
    }

    /**
     * 订单设置
     * @return Response
     */
    public function saveOrder(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 订单设置获取
     * @return Response
     */
    public function orderSettings(): Response
    {
        $item = app(ConfigService::class)->getConfigByBizCode([
            'autoDeliveryDays',
            'autoReturnGoods',
            'autoReturnGoodsDays',
            'afterSalesLimitDays',
            'autoCancelOrderMinute',
        //    'isChangeOrderStatus',
            'isPlatformCancelPaidOrder',
            'isPlatformCancelDeliverOrder',
            'isShopCancelDeliverOrder',
        ]);
        return $this->success(
            $this->configService->fuzzyConfig($item)
        );
    }

    /**
     * 登陆保存接口
     * @return Response
     */
    public function saveLogin(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 登陆设置获取
     * @return Response
     */
    public function loginSettings(): Response
    {
        $item = app(ConfigService::class)->getConfigByBizCode([
            'isOpenMobileAreaCode',
            'usernamePrefix',
            'openWechatRegister',
            'wechatRegisterBindPhone',
            'openWechatOauth',
            'googleLoginOn',
            'googleClientId',
            'googleClientSecret',
            'facebookLoginOn',
            'facebookClientId',
            'facebookClientSecret',
            'wechatAppId',
            'wechatAppSecret',
            'wechatServerUrl',
            'wechatServerToken',
            'wechatServerSecret',
            'openEmailRegister',
        ]);
        return $this->success(
            $this->configService->fuzzyConfig($item)
        );
    }

    /**
     * 基础支付设置保存
     * @return Response
     */
    public function saveBasicPay(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 基础支付设置获取
     * @return Response
     */
    public function basicPaySettings(): Response
    {
        $item = app(ConfigService::class)->getConfigByBizCode([
            'useSurplus',
            'usePoints',
            'useCoupon',
        ]);
        return $this->success(
            $item
        );
    }


    /**
     * 微信支付设置保存
     * @return Response
     */
    public function saveWechatPay(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 微信支付设置获取
     * @return Response
     */
    public function wechatPaySettings(): Response
    {
        $item = app(ConfigService::class)->getConfigByBizCode([
            'useWechat',
            'wechatMchidType',
            'wechatPayMchid',
            'wechatPaySubMchid',
            'wechatPayKey',
            'wechatPaySerialNo',
            'wechatPayCertificate',
            'wechatPayPrivateKey',
            'wechatPayCheckType',
            'wechatPayPlatformCertificate',
            'wechatPayPublicKeyId',
            'wechatPayPublicKey',
            'wechatMiniProgramAppId',
            'wechatMiniProgramSecret',
            'wechatPayAppId',
            'wechatPayAppSecret'
        ]);
        return $this->success(
            $this->configService->fuzzyConfig($item)
        );
    }

    /**
     * 支付宝支付设置保存
     * @return Response
     */
    public function saveAliPay(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 支付宝支付设置获取
     * @return Response
     */
    public function aliPaySettings(): Response
    {
        $item = app(ConfigService::class)->getConfigByBizCode([
            'useAlipay',
            'alipayAppid',
            'alipayRsaPrivateKey',
            'alipayRsaPublicKey',
        ]);
        return $this->success(
            $this->configService->fuzzyConfig($item)
        );
    }

    /**
     * 支付设置保存
     * @return Response
     */
    public function saveYaBandPay(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * @return Response
     */
    public function yaBandPaySettings(): Response
    {
        $item = app(ConfigService::class)->getConfigByBizCode([
            'useYabanpay',
            'useYabanpayWechat',
            'useYabanpayAlipay',
            'yabanpayCurrency',
            'yabandpayUid',
            'yabandpaySecretKey',
        ]);
        return $this->success(
            $this->configService->fuzzyConfig($item)
        );
    }

    /**
     * 线下支付设置保存
     * @return Response
     */
    public function saveOfflinePay(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 线下支付设置获取
     * @return Response
     */
    public function offlinePaySettings(): Response
    {
        $item = app(ConfigService::class)->getConfigByBizCode([
            'useOffline',
            'offlinePayBank',
            'offlinePayCompany',
        ]);
        return $this->success(
            $this->configService->fuzzyConfig($item)
        );
    }

    /**
     * paypal设置保存
     * @return Response
     */
    public function savePayPal(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * paypal设置获取
     * @return Response
     */
    public function payPalSettings(): Response
    {
        $item = app(ConfigService::class)->getConfigByBizCode([
            'usePaypal',
            'paypalCurrency',
            'paypalClientId',
            'paypalSecret',
            'paypalCurrencyList',
        ]);
        $item['paypalCurrencyList'] = !empty($item['paypalCurrencyList']) ? json_decode($item['paypalCurrencyList'], true):[];
        return $this->success(
            $this->configService->fuzzyConfig($item)
        );
    }

    /**
     * 云支付设置保存
     * @return Response
     */
    public function saveYunPay(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 云支付设置获取
     * @return Response
     */
    public function yunPaySettings(): Response
    {
        $item = app(ConfigService::class)->getConfigByBizCode([
            'useYunpay',
            'yunpayUid',
            'yunpaySecretKey',
        ]);
        return $this->success(
            $this->configService->fuzzyConfig($item)
        );
    }

    /**
     * 获取登录协议
     * @return Response
     */
    public function getLoginProtocol(): Response
    {
        $properties = [
            'termsOfServiceShow',        //服务协议展示
            'privacyPolicyShow',        //隐私政策展示
            'afterSalesServiceShow',   //售后服务展示
        ];
        $result = $this->configService->getConfigByBizCode($properties);
        return $this->success($result);
    }

    /**
     * 获取登录协议内容  termsOfService:服务协议；privacyPolicy:隐私政策；afterSalesService:售后服务协议；
     * @return Response
     */
    public function getLoginProtocolContent(): Response
    {
        $filter = $this->request->only([
            'code' => '',
        ], 'get');

        $validCodes = [
            'termsOfService' => [
                'SHOW_KEY' => 'termsOfServiceShow',
                'CONTENT_KEY' => 'termsOfService',
            ],
            'privacyPolicy' => [
                'SHOW_KEY' => 'privacyPolicyShow',
                'CONTENT_KEY' => 'privacyPolicy',
            ],
            'afterSalesService' => [
                'SHOW_KEY' => 'afterSalesServiceShow',
                'CONTENT_KEY' => 'afterSalesService',
            ],
        ];
        // 检查 code 是否为空或不在白名单中
        if (empty($filter['code']) || !isset($validCodes[$filter['code']])) {
            return $this->error(/** LANG */ '参数错误');
        }

        $config = $validCodes[$filter['code']];
        $showKey = $config['SHOW_KEY'];
        $contentKey = $config['CONTENT_KEY'];

        $map = [$showKey, $contentKey];
        $result = $this->configService->getConfigByBizCode($map);

        $data = [
            'content' => array_key_exists($contentKey, $result) ? $result[$contentKey] : '',
            'show' => array_key_exists($showKey, $result) ? $result[$showKey] : 0,
        ];

        return $this->success($data);
    }


    /**
     * 保存登录协议
     * @return Response
     */
    public function saveLoginProtocol(): Response
    {
        $filter = $this->request->only([
            'code' => '',
            'show/d' => 0,
            'content' => '',
        ], 'post');

        // 强制类型转换
        $filter['show'] = (int) $filter['show'];
        $filter['content'] = htmlspecialchars($filter['content'], ENT_QUOTES, 'UTF-8');

        $validCodes = [
            'termsOfService' => [
                'SHOW_KEY' => 'termsOfServiceShow',
                'CONTENT_KEY' => 'termsOfService',
            ],
            'privacyPolicy' => [
                'SHOW_KEY' => 'privacyPolicyShow',
                'CONTENT_KEY' => 'privacyPolicy',
            ],
            'afterSalesService' => [
                'SHOW_KEY' => 'afterSalesServiceShow',
                'CONTENT_KEY' => 'afterSalesService',
            ],
        ];

        if (!isset($validCodes[$filter['code']])) {
            return $this->error(/** LANG */ '参数错误');
        }

        $config = $validCodes[$filter['code']];
        $data[$config['SHOW_KEY']] = $filter['show'];
        $data[$config['CONTENT_KEY']] = $filter['content'];

        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 获取供应商配置
     * @return Response
     */
    public function vendorSettings(): Response
    {

        $properties = [
            'vendorProductNeedCheck',    //是否需要审核商品；0-否，1-是
            'vendorMaxSubAdministrator',  //最大管理员数"
            'vendorSetPriceType',       //供应商设价方式：(1按比例，2-按固定数值加价，3-默认售价)
        //    'vendorSetPriceAutoType',
            'vendorSetPriceAutoValue',  //智能设价（百分比或固定数值）
        ];
        $result = $this->configService->getConfigByBizCode($properties);
        return $this->success($result);
    }

    /**
     * 保存供应商配置
     * @return Response
     */
    public function saveVendor(): Response
    {
        $filter = $this->request->only([
            'vendorProductNeedCheck/d' => 0,
            'vendorMaxSubAdministrator/d' => 0,
            'vendorSetPriceType/d' => 0,
        //    'vendorSetPriceAutoType/d' => 0,
            'vendorSetPriceAutoValue' => 0,
        ], 'post');
        $result = $this->configService->save($filter);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 获取结算与分账配置
     * @return Response
     */
    public function profitSharingSettings(): Response
    {
        $item = app(ConfigService::class)->getConfigByBizCode([
            'billingNode',
            'collectionNode',
            'collectionTimeSetting',
            'collectionMethod',
            'collectionAccountType',
            'splitPaymentMethod',
            'storeGeneralServiceFeeRate',
            'storeWithdrawalFeeRate',
            'storefrontGeneralServiceFeeRate',
            'storefrontWithdrawalFeeRate',
            'supplierGeneralServiceFeeRate',
            'supplierWithdrawalFeeRate'
        ]);
        return $this->success(
            $this->configService->fuzzyConfig($item)
        );
    }
    /**
     * 保存结算与分账配置
     * @return Response
     */
    public function saveProfitSharing(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

    /**
     * 获取提现配置项
     * @return Response
     */
    public function withdrawalSettings(): Response
    {
        $item = app(ConfigService::class)->getConfigByBizCode([
            'withdrawalReceiptMethod',
            'withdrawalEnabled',
            'minAmount',
            'maxAmount',
            'withdrawalFrequencyUnit',
            'withdrawalFrequencyCount',
            'withdrawalReviewMethod',
            'withdrawalDescription',
        ]);
        return $this->success(
            $this->configService->fuzzyConfig($item)
        );
    }

    /**
     * 保存提现配置项
     * @return Response
     */
    public function saveWithdrawal(): Response
    {
        $data = $this->request->all();
        $result = $this->configService->save($data);
        if ($result) {
            return $this->success();
        } else {
            return $this->error(/** LANG */ '设置项更新失败');
        }
    }

}
