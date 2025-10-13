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
use app\model\msg\AdminMsg;
use app\service\admin\decorate\DecorateService;
use app\service\admin\image\Image;
use app\service\admin\setting\AreaCodeService;
use app\service\admin\user\UserRankService;
use think\App;
use think\Response;
use utils\Config as UtilsConfig;

/**
 * 首页控制器
 */
class Config extends IndexBaseController
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
     * 首页
     *
     * @return Response
     */
    public function base(): Response
    {
        $data = [
            'theme_id' => UtilsConfig::get('themeId'),
            'theme_style' => UtilsConfig::get('themeStyle'),
            'shop_name' => \utils\Util::lang(UtilsConfig::get('shopName'), '', [], 5),
            'shop_title' => \utils\Util::lang(UtilsConfig::get('shopTitle'), '', [], 5),
            'shop_title_suffix' => \utils\Util::lang(UtilsConfig::get('shopTitleSuffix'), '', [], 5),
            'shop_logo' => UtilsConfig::get('shopLogo'),
            'shop_keywords' => \utils\Util::lang(UtilsConfig::get('shopKeywords'), '', [], 5),
            'shop_desc' => \utils\Util::lang(UtilsConfig::get('shopDesc'), '', [], 5),
            'storage_url' => app(Image::class)->getStorageUrl(),
            'dollar_sign' => UtilsConfig::get('dollarSign') ?? '¥',
            'dollar_sign_cn' => UtilsConfig::get('dollarSignCn') ?? '元',
            'ico_img' => UtilsConfig::get('icoImg') ?? '',
            'auto_redirect' => UtilsConfig::get('autoRedirect') ?? 1,
            'open_wechat_oauth' => UtilsConfig::get('openWechatOauth'),
            'person_apply_enabled' => UtilsConfig::get('personApplyEnabled') ?? '',
            'h5_domain' => UtilsConfig::get('h5Domain') ?? '',
            'pc_domain' => UtilsConfig::get('pcDomain') ?? '',
            'admin_domain' => UtilsConfig::get('adminDomain') ?? '',
            'show_service' => UtilsConfig::get('kefuType') > 0 ? 1 : 0,
            'version_type' => env('VERSION_TYPE', config('app.version_type')),
            'shop_icp_no' => \utils\Util::lang(UtilsConfig::get('shopIcpNo'), '', [], 5),
            'shop_icp_no_url' => UtilsConfig::get('shopIcpNoUrl') ?: 'https://beian.miit.gov.cn',
            'shop_110_no' => UtilsConfig::get('shop110No'),
            'shop_110_link' => UtilsConfig::get('shop110Link') ?: 'https://beian.mps.gov.cn/#/query/webSearch',
            'shop_company' => UtilsConfig::get('shopCompany'),
            'company_address' => UtilsConfig::get('companyAddress'),
            'kefu_phone' => UtilsConfig::get('kefuPhone'),
            'kefu_time' => UtilsConfig::get('kefuTime'),
            'is_enterprise' => UtilsConfig::get('isEnterprise'),
            'de_copyright' => UtilsConfig::get('deCopyright'),
            'powered_by_status' => UtilsConfig::get('poweredByStatus'),
            'powered_by' => UtilsConfig::get('poweredBy'),
            'category_decorate_type' => UtilsConfig::get('productCategoryDecorateType', 1),
            'can_invoice' => UtilsConfig::get('canInvoice'),
            'invoice_added' => UtilsConfig::get('invoiceAdded'),
            'default_shop_name' => \utils\Util::lang(UtilsConfig::get('defaultShopName')),
            'is_open_mobile_area_code' => UtilsConfig::get('isOpenMobileAreaCode'),
            'show_selled_count' => UtilsConfig::get('showSelledCount'),
            'show_marketprice' => UtilsConfig::get('showMarketprice'),
            'use_surplus' => UtilsConfig::get('useSurplus', ""),
            'use_points' => UtilsConfig::get('usePoints', ""),
            'use_coupon' => UtilsConfig::get('useCoupon', ""),
            'close_order' => UtilsConfig::get('closeOrder', 0),
            'shop_reg_closed' => UtilsConfig::get('shopRegClosed', 0),
            'company_data_type' => UtilsConfig::get('type', 2),
            'company_data_tips' => UtilsConfig::get('tips', ''),
            'is_identity' => UtilsConfig::get('isIdentity', 0),
            'is_enquiry' => UtilsConfig::get('isEnquiry', 0),
            'openWechatRegister' => UtilsConfig::get('openWechatRegister', 0),
            'wechatRegisterBindPhone' => UtilsConfig::get('wechatRegisterBindPhone', 0),
            'googleLoginOn' => UtilsConfig::get('googleLoginOn'),
            'facebookLoginOn' => UtilsConfig::get('facebookLoginOn'),
            'defaultTechSupport' => UtilsConfig::get('defaultTechSupport', ''),
            'poweredByLogo' => UtilsConfig::get('poweredByLogo', ''),
            'openEmailRegister' => UtilsConfig::get('openEmailRegister', 0),
            'integralName' => UtilsConfig::get('integralName'),
            'lightShopLogo' => UtilsConfig::get('lightShopLogo', ''),
        ];
        $data['grow_up_setting'] = app(UserRankService::class)->getGrowConfig();
        $data['shop_company'] = $data['de_copyright'] ? $data['shop_company'] : config('app.default_company');
        $preview_id = $this->request->all('preview_id/d', 0);
        $data['decorate_page_config'] = app(DecorateService::class)->getPcIndexDecoratePageConfig($preview_id);
        $data['defaultHeaderStyle'] = app(DecorateService::class)->getDefaultDecoratePageHeaderStyle();
        return $this->success($data);
    }

    /**
     * 首页
     *
     * @return Response
     */
    public function themeSettings(): Response
    {
        return $this->success(
            [
                'theme_style' => UtilsConfig::get('themeStyle'),
            ]
        );
    }

    /**
     * 售后服务配置
     * @return Response
     */
    public function afterSalesService(): Response
    {
        return $this->success(UtilsConfig::getConfig('afteSalesService'));
    }


    /**
     * @return Response
     */
    public function mobileAreaCode(): Response
    {
        $list = app(AreaCodeService::class)->getFilterList([
            'is_available' => 1,
            'sort_field' => 'is_default',
            'sort_order' => 'desc',
            'size' => -1
        ]);
        return $this->success($list);
    }

}
