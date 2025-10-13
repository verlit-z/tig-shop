<?php

namespace app\service\admin\oauth;

use app\service\common\BaseService;
use EasyWeChat\OfficialAccount\Application;
use exceptions\ApiException;
use Fastknife\Utils\RandomUtils;
use think\Exception;
use think\facade\Cache;
use utils\Config;
use utils\Util;

class WechatOAuthService extends BaseService
{
    protected string|null $platformType = null;

    /**
     *获取平台类型
     * @return string
     */
    public function getPlatformType(): string
    {
        if ($this->platformType === null) {
            return Util::getClientType();
        } else {
            return $this->platformType;
        }
    }

    /**
     * 设置平台类型
     * @param string $platformType
     * @return void
     */
    public function setPlatformType(string $platformType): self
    {
        $this->platformType = $platformType;
        return $this;
    }

    public function webpage_auth(string $code): array
    {
        $user = $this->getApplication()->getOAuth()->userFromCode($code);
        $user->getId();//对应微信的 openid
        $user->getNickname();//对应微信的 nickname
        $user->getName(); //对应微信的 nickname
        $user->getAvatar(); //头像地址
        $user->getRaw(); //原始 API 返回的结果
        $user->getAccessToken(); //access_token
        $user->getRefreshToken(); //refresh_token
        $user->getExpiresIn(); //expires_in，Access Token 过期时间
        $user->getTokenResponse(); //返回 access_token 时的响应值

        return [];
    }

    /**
     * 公众号授权获取用户信息
     * @param string $code
     * @return array
     * @throws Exception
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     */
    public function auth(string $code): array
    {
        try {
            $user = $this->getApplication()->getOAuth()->userFromCode($code)->getRaw();
        } catch (Exception $exception) {
            throw new ApiException(Util::lang($exception->getMessage()));
        }
        return $user;
    }

    /**
     * 获取小程序openid
     * @param string $code
     * @return string
     * @throws ApiException
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getMiniOpenid(string $code): string
    {
        try {
            $openData = app(MiniWechatService::class)->getApplication()->getUtils()->codeToSession($code);
            if (isset($openData['openid'])) return $openData['openid'];
            return '';
        } catch (Exception $exception) {
            throw new ApiException(Util::lang($exception->getMessage()));
        }
    }

    /**
     * 获取网页授权地址
     * @param string $redirect_url
     * @return array
     * @throws ApiException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getOAuthUrl(string $redirect_url = ''): array
    {
        switch ($this->getPlatformType()) {
            case 'wechat':
                $url = $this->getApplication()->getOAuth()->scopes(['snsapi_userinfo'])->redirect($redirect_url);
                return ['url' => $url];
            case 'pc':
                try {
                    $access_token = $this->setPlatformType('wechat')->getApplication()->getAccessToken()->getToken();
                    $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $access_token;
                    $scene_str = RandomUtils::getRandomCode(12);
                    $data = [
                        'expire_seconds' => 300,
                        'action_name' => 'QR_STR_SCENE',
                        'action_info' => [
                            'scene_id' => rand(0, 100000),
                            'scene_str' => $scene_str
                        ]
                    ];
                    $res = $this->getApplication()->getClient()->postJson($url, $data)->toArray();
                    Cache::set($res['ticket'], '', 300);
                    return $res;
                } catch (\Exception $exception) {
                    throw new ApiException(Util::lang($exception->getMessage()));
                }
            default:
                return [];
        }
    }

    /**
     * 发送公众号模板消息
     * @param array $data
     * @return bool
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function sendWechatTemplateMessage(array $data = []): bool
    {
        try {
            $accessToken = $this->setPlatformType('wechat')->getApplication()->getAccessToken()->getToken();
            $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $accessToken;
            $response = $this->getApplication()->getClient()->postJson($url, $data);
            $response->toArray(false);
            return true;
        } catch (\Exception $exception) {
            echo $exception->getMessage();
            return false;
        }
    }

    /**
     * 发送小程序订阅消息
     * @param array $data
     * @return bool
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function sendMiniTemplateMessage(array $data = []): bool
    {
        try {
            $accessToken = $this->setPlatformType('miniProgram')->getApplication()->getAccessToken()->getToken();
            $url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=" . $accessToken;
            $response = $this->getApplication()->getClient()->postJson($url, $data);
            $response->toArray(false);
            return true;
        } catch (\Exception $exception) {
            echo $exception->getMessage();
            return false;
        }
    }

    /**
     * 授权获取用户手机号
     * @param string $code
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getMiniUserMobile(string $code): array
    {
        try {
            $accessToken = $this->setPlatformType('miniProgram')->getApplication()->getAccessToken()->getToken();
            $url = "https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=" . $accessToken;
            $data = ['code' => $code];
            $response = $this->getApplication()->getClient()->postJson($url, $data)->toArray();
            if (isset($response['errcode']) && $response['errcode'] != 0) {
                throw new ApiException(Util::lang($response['errmsg']));
            }

            return ['code' => 1, 'mobile' => $response['phone_info']['purePhoneNumber']];
        } catch (\Exception $exception) {

            return ['code' => 0, 'msg' => Util::lang($exception->getMessage())];
        }
    }

    /**
     * 获取jssdk配置项
     * @param string $url
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getJsSdkConfig(string $url): array
    {
        try {
            $this->setPlatformType('wechat')->getApplication()->getAccessToken()->getToken();
            return $this->getApplication()->getUtils()->buildJsSdkConfig($url);
        } catch (\Exception $exception) {

            return ['code' => 0, 'msg' => $exception->getMessage()];
        }
    }

    /**
     * 获取小程序二维码
     * @param string $path
     * @return string|bool
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getMiniCode(string $path, int $product_id = 0): string|bool
    {
        try {
            $accessToken = $this->setPlatformType('miniProgram')->getApplication()->getAccessToken()->getToken();
            $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $accessToken;
            $data = ["page" => $path, "scene" => "id=" . $product_id];
            $buffer = $this->getApplication()->getClient()->postJson($url, $data)->getContent();
            return "data:image/jpeg;base64," . base64_encode($buffer);
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * 获取基础配置并返回application对象
     * @param string $type
     * @return object|Application
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     */
    public function getApplication(): object
    {
        $app_id = '';
        $secret = '';
        $callback = '';
        switch ($this->getPlatformType()) {
            case 'pc':
                $app_id = Config::get('wechatOpenAppId');
                $secret = Config::get('wechatOpenAppSecret');
                break;
            case 'wechat':
                $app_id = Config::get('wechatAppId');
                $secret = Config::get('wechatAppSecret');
                break;
            case 'miniProgram':
                $app_id = Config::get('wechatMiniProgramAppId');
                $secret = Config::get('wechatMiniProgramSecret');
                break;
            case 'app':
                $app_id = Config::get('wechatAppAppId');
                $secret = Config::get('wechatAppSecret');
                break;
        }
        $config = [
            'app_id' => $app_id,
            'secret' => $secret,
            'http' => [
                'timeout' => 5.0,
                'retry' => true, // 使用默认重试配置
            ],
        ];
        if ($this->getPlatformType() != 'miniProgram') {
            $config['token'] = Config::get('wechat_server_token', 'base_api_wechat');
            $config['aes_key'] = '';
            $config['oauth'] = [
                'scopes' => ['snsapi_userinfo'],
                'callback' => $callback,
            ];
        }

        return new Application($config);

    }


}