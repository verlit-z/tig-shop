<?php

namespace app\service\admin\decorate;

use app\model\decorate\Decorate;
use app\model\decorate\DecorateShare;
use app\service\admin\image\Image;
use app\service\common\BaseService;
use exceptions\ApiException;
use think\facade\Cache;
use tig\Http;
use utils\Util;


class DecorateShareService extends BaseService
{
    const SN_NUM = 6;
    const TOKEN_NUM = 5;
    const EXPIRY_TIME = 7 * 86400; //toke过期时间

    public function __construct(DecorateShare $model)
    {
        $this->model = $model;
    }

    /**
     * @param int $decorate_id
     * @return array
     */
    public function share(int $decorate_id): array
    {
        $time = time() + self::EXPIRY_TIME;
        $snList = $this->getSnList();
        $shareSn = random_num(self::SN_NUM, $snList);
        $tokenList = $this->getTokenList();
        $shareToken = random_num(self::TOKEN_NUM, $tokenList);
        //将token存入redies，并设置过期时间
        Cache::set($shareToken, $shareSn);
        Cache::expire($shareToken, self::EXPIRY_TIME);
        //将数据入库
        $data = [
            'share_sn' => $shareSn,
            'share_token' => $shareToken,
            'decorate_id' => $decorate_id,
            'valid_time' => $time
        ];
        $this->model->save($data);
        return [
            'sn' => $shareSn,
            'token' => $shareToken,
            'api_url' => '/api/home/share/import?sn=' . $shareSn . '&token=' . $shareToken,
        ];
    }

    /**
     * @param $filter
     * @return bool
     * @throws ApiException
     */
    public function import($filter)
    {
        //分析url
        $urlInfo = $this->analyzeUrl(urldecode($filter['url']));
        $params = $urlInfo['query_params'];
        if (!isset($params['sn']) || empty($params['sn'])) {
            throw new ApiException('链接中参数缺少sn字段!');
        }
        if (!isset($params['token']) || empty($params['token'])) {
            throw new ApiException('链接中参数缺少token字段!');
        }
        $res = Http::get($urlInfo['base_url'], $urlInfo['query_params']);
        if(!is_json($res)) {
            throw new ApiException('返回结果有误！');
        }
        $res_arr = json_decode($res,true);
        if (!isset($res_arr['data'])) {
            throw new ApiException('未获取到有用的模板信息，请重新导入分享模板链接！');
        }
        $decorate = $res_arr['data'];
        //构建插入模板数据
        $insert['decorate_title'] = $decorate['decorateTitle'];
        $insert['data'] = $decorate['data'];
        $insert['draft_data'] = $decorate['draftData'];
        $insert['decorate_type'] = $decorate['decorateType'];
        $insert['shop_id'] = $filter['shop_id'];
        $insert['update_time'] = time();
        $model = new Decorate();
        return $model->save($insert);
    }


    public function getInfoBySn(string $sn, string $token)
    {
        if (empty($sn) || empty($token)) {
            throw new ApiException(Util::lang('参数缺失！'));
        }
        //校验token
        $sn = Cache::get($token);
        if (empty($sn)) {
            //如果没获取到就去mysql表里获取
            $sn = $this->model->where('share_token', $token)->value('share_sn');
            if(empty($sn)) {
                throw new ApiException(Util::lang('分享链接不存在或已失效!'));
            }
        }
        $decorateId = $this->model->where('share_sn', $sn)->value('decorate_id');
        if (empty($decorateId)) {
            throw new ApiException(Util::lang('未查询到分享装修信息!'));
        }

        $decorate = new Decorate();
        $decorateInfo = $decorate->find($decorateId);
        if(empty($decorateInfo)) {
            throw new ApiException(Util::lang('未查询到分享装修信息!'));
        }

        return $this->updateData( $decorateInfo->toArray());

    }

    //查询分享码链接列表
    public function getSnList()
    {
        return $this->model->field('share_sn')->select()->toArray();
    }

    //查询token
    public function getTokenList()
    {
        $data = [];
        $list = $this->model->field('share_token, valid_time')->select()->toArray();
        if (!empty($list)) {
            foreach ($list as $item) {
                if ($item['valid_time'] < time()) continue;
                $data[] = $item['share_token'];
            }
        }
        unset($list);
        return $data;
    }

    public function analyzeUrl($url)
    {
        // 检查是否是有效的URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ApiException(Util::lang('无效的链接!'));
        }
        // 解析URL
        $parsed_url = parse_url($url);
        $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        // 获取基础URL（协议 + 主机名 + 路径）
        $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $port . $parsed_url['path'];
        // 获取查询参数部分
        $query_params = isset($parsed_url['query']) ? $parsed_url['query'] : '';
        // 将查询参数解析为关联数组
        parse_str($query_params, $params_array);
        return [
            'base_url' => $base_url,
            'query_params' => $params_array
        ];
    }

    //解决模板域名拼接的问题

    public function updateData(array $decorate)
    {
        $decorate['data'] = $this->handle($decorate['data']);
        if(!empty($decorate['draft_data'])) {
            $decorate['draft_data'] = $this->handle($decorate['draft_data']);
        } else {
            $decorate['draft_data'] =$decorate['data'];
        }

        return $decorate;
    }

    /**
     * 处理数据
     * @param $data
     * @return mixed
     */
    public function handle($data)
    {
        if(empty($data)){
            return '';
        }
        foreach ($data['moduleList'] as &$value) {
            if(isset($value['module']['customPic']['picUrl'])){
                if(isset($value['module']['customPic']['picUrl'])){
                    $value['module']['customPic']['picUrl'] = $this->isUrl($value['module']['customPic']['picUrl']);
                }
                if(isset($value['module']['customPic']['picThumb'])) {
                    $value['module']['customPic']['picThumb'] = $this->isUrl($value['module']['customPic']['picThumb']);
                }
            }

            if(isset($value['module']['icoPic']['picUrl'])){
                if(isset($value['module']['icoPic']['picUrl'])) {
                    $value['module']['icoPic']['picUrl'] = $this->isUrl($value['module']['icoPic']['picUrl']);
                }
                if(isset($value['module']['icoPic']['picThumb'])){
                    $value['module']['icoPic']['picThumb'] = $this->isUrl($value['module']['icoPic']['picThumb']);
                }
            }

            if(isset($value['module']['bannerContent']['picList'])) {
                if(isset($value['module']['bannerContent']['picUrl'])){
                    $value['module']['bannerContent']['picUrl'] = $this->isUrl($value['module']['bannerContent']['picUrl']);
                    $value['module']['bannerContent']['picThumb'] = $this->isUrl($value['module']['bannerContent']['picThumb']);
                } else {
                    foreach ($value['module']['bannerContent']['picList'] as $k=> $v) {
                        if(isset($value['module']['bannerContent']['picList'][$k]['picUrl'])) {
                            $value['module']['bannerContent']['picList'][$k]['picUrl'] = $this->isUrl($v['picUrl']);
                        }
                        if(isset($value['module']['bannerContent']['picList'][$k]['picThumb'])){
                            $value['module']['bannerContent']['picList'][$k]['picThumb'] = $this->isUrl($v['picThumb']);
                        }
                    }
                }
            }

            if(isset($value['module']['categoryList'])) {
                foreach ($value['module']['categoryList'] as $k=> $v) {
                    if(isset($value['module']['categoryList'][$k]['picUrl'])) {
                        $value['module']['categoryList'][$k]['picUrl'] = $this->isUrl($v['picUrl']);
                    }
                    if(isset($value['module']['categoryList'][$k]['picThumb'])) {
                        $value['module']['categoryList'][$k]['picThumb'] = $this->isUrl($v['picThumb']);
                    }
                }
            }

            if(isset($value['module']['title']['titleBackgroundPic']['picUrl'])) {
                if(isset($value['module']['title']['titleBackgroundPic']['picUrl'])) {
                    $value['module']['title']['titleBackgroundPic']['picUrl'] = $this->isUrl(
                        $value['module']['title']['titleBackgroundPic']['picUrl']
                    );
                }
                if(isset($value['module']['title']['titleBackgroundPic']['picThumb'])) {
                    $value['module']['title']['titleBackgroundPic']['picThumb'] = $this->isUrl(
                        $value['module']['title']['titleBackgroundPic']['picThumb']
                    );
                }
            }

            if (isset($value['module']['picList'])) {
                if (isset($value['module']['picList']['picUrl'])) {
                    $value['module']['picList']['picUrl'] = $this->isUrl($value['module']['picList']['picUrl']);
                    $value['module']['picList']['picThumb'] = $this->isUrl($value['module']['picList']['picThumb']);
                } else {
                    foreach ($value['module']['picList'] as $k => $v) {
                        if(isset($value['module']['picList'][$k]['picUrl'])){
                            $value['module']['picList'][$k]['picUrl'] = $this->isUrl($v['picUrl']);
                        }
                        if(isset($value['module']['picList'][$k]['picThumb'])){
                            $value['module']['picList'][$k]['picThumb'] = $this->isUrl($v['picThumb']);
                        }
                    }
                }
            }

            if (isset($value['module']['picList2'])) {
                foreach ($value['module']['picList2'] as $k => $v) {
                    if(isset($value['module']['picList2'][$k]['picUrl'])){
                        $value['module']['picList2'][$k]['picUrl'] = $this->isUrl($v['picUrl']);
                    }
                    if(isset($value['module']['picList2'][$k]['picThumb'])){
                        $value['module']['picList2'][$k]['picThumb'] = $this->isUrl($v['picThumb']);
                    }
                }
            }
            if (isset($value['module']['picList3'])) {
                foreach ($value['module']['picList3'] as $k => $v) {
                    if(isset($value['module']['picList3'][$k]['picUrl'])) {
                        $value['module']['picList3'][$k]['picUrl'] = $this->isUrl($v['picUrl']);
                    }
                    if(isset($value['module']['picList3'][$k]['picThumb'])) {
                        $value['module']['picList3'][$k]['picThumb'] = $this->isUrl($v['picThumb']);
                    }
                }
            }
            if (isset($value['module']['navBackgroundPic'])) {
                $value['module']['navBackgroundPic']['picUrl'] = $this->isUrl($value['module']['navBackgroundPic']['picUrl']);
                $value['module']['navBackgroundPic']['picThumb'] = $this->isUrl($value['module']['navBackgroundPic']['picThumb']);
            }
            if (isset($value['module']['logoPic'])) {
                $value['module']['logoPic']['picUrl'] = $this->isUrl($value['module']['logoPic']['picUrl']);
                $value['module']['logoPic']['picThumb'] = $this->isUrl($value['module']['logoPic']['picThumb']);
            }
        }
        return $data;
    }

    //判断是链接是否已经有了域名
    private function isUrl($url){
        //获取图片配置域名
        if(empty($url)) return '';
        $domain = app(Image::class)->getStorageUrl();
        $pattern = '/^(http|https):\/\/[^\s$.?#].[^\s]*$/i';
        if(preg_match($pattern, $url)){
            return $url;
        }else{
            return $domain . $url;
        }
    }
}