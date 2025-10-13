<?php

namespace app\service\admin\authority;

use app\service\common\BaseService;
use exceptions\ApiException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use think\facade\Cache;
use think\facade\Request;
use utils\ResponseCode;

/**
 * token访问令牌服务类
 */
class AccessTokenService extends BaseService
{
    protected string $app = 'app';
    protected string $id;
    protected int $accessTokenExpTime = 3600 * 24 * 15; //访问令牌过期时间
    protected int $refreshTokenExpTime = 70; //刷新令牌过期时间
    protected string $key = 'lyecs@2023'; //Key
    protected array|null $data = null; //Key

    public function __construct()
    {
    }

    /**
     * 设置访问类型
     * @param string $app
     * @return $this
     */
    public function setApp(string $app = ''): AccessTokenService
    {
        $this->app = $app;
        return $this;
    }

    /**
     * 设置用户id
     * @param string $id
     * @return $this
     */
    public function setId(int $id = 0): AccessTokenService
    {
        $this->id = $id;
        return $this;
    }

    /**
     * 生成验签
     * @return string
     */
    public function createToken(): string
    {
        $uuid = md5(uniqid(md5(microtime(true)), true));
        $arr = array(
            "iss" => $this->key, //签发者
            "aud" => '', //面象的用户
            "iat" => time(), //签发时间
            "nbf" => time(), //生效时间
            "exp" => time() + $this->accessTokenExpTime, //token 过期时间
            "data" => [
                $this->app . 'Id' => $this->id,
                'uuid' => $uuid,
            ], //JWT数据
        );
        //生成refreshToken
        $token = JWT::encode($arr, $this->key, "HS256");
        //生成AccessToken
        Cache::set($this->app . ':' . ($this->app . 'Id') . ':' . $uuid, $token, $this->accessTokenExpTime);
        return $token;
    }

    /**
     * 退出登陆清除token
     * @return bool
     * @throws ApiException
     */
    public function deleteToken()
    {
        $token = $this->getHeaderToken();
        if (!$token) {
            return false;
        }
        try {
            JWT::$leeway = 10; //当前时间减去60，把时间留点余地
            $decoded = JWT::decode($token, new Key($this->key, 'HS256')); //HS256方式，这里要和签发的时候对应
            $result = (array) $decoded;
            $data = $result['data'];
            // redis检查登录状态
            $redis_token = Cache::get($this->app . ':' . ($this->app . 'Id') . ':' . $data->uuid);

            if (!$redis_token || $token != $redis_token) {
                throw new ApiException('签名错误:token已失效,请重新登录', ResponseCode::NOT_TOKEN);
            }

            //删除缓存
            Cache::set($this->app . ':' . ($this->app . 'Id') . ':' . $data->uuid, '', 60);
            Cache::delete($this->app . ':' . $this->id . ':' . $data->uuid);
            Cache::delete('admin_user::auth::' . $this->id); //删除店铺和供应商登录缓存
            return true;
        }catch (SignatureInvalidException $e) { //签名不正确
            throw new ApiException('签名错误:token无效,请重新登录', ResponseCode::NOT_TOKEN);
        } catch (BeforeValidException $e) { // 签名在某个时间点之后才能用
            throw new ApiException('签名错误:token已失效,请重新登录', ResponseCode::NOT_TOKEN);
        } catch (ExpiredException $e) { // token过期
            throw new ApiException('签名错误:token已失效,请重新登录', ResponseCode::NOT_TOKEN);
        } catch (\Exception $e) { //其他错误
            throw new ApiException('签名错误:token已失效,请重新登录', ResponseCode::NOT_TOKEN);
        }
    }

    /**
     * 验证token
     * @return false
     * @throws ApiException
     */
    public function checkToken(): bool|array
    {
        $token = $this->getHeaderToken();
        if (!$token) {
            return false;
        }
        try {
            JWT::$leeway = 10; //当前时间减去60，把时间留点余地
            $decoded = JWT::decode($token, new Key($this->key, 'HS256')); //HS256方式，这里要和签发的时候对应
            $result = (array) $decoded;
            $data = $result['data'];
            // redis检查登录状态
            $redis_token = Cache::get($this->app . ':' . ($this->app . 'Id') . ':' . $data->uuid);
            if (!$redis_token || $token != $redis_token) {
                throw new ApiException('签名错误:token已失效,请重新登录', ResponseCode::NOT_TOKEN);
            }
            return $result;
        } catch (SignatureInvalidException $e) { //签名不正确
            throw new ApiException('签名错误:token无效,请重新登录', ResponseCode::NOT_TOKEN);
        } catch (BeforeValidException $e) { // 签名在某个时间点之后才能用
            throw new ApiException('签名错误:token已失效,请重新登录', ResponseCode::NOT_TOKEN);
        } catch (ExpiredException $e) { // token过期
            throw new ApiException('签名错误:token已失效,请重新登录', ResponseCode::NOT_TOKEN);
        } catch (\Exception $e) { //其他错误
            throw new ApiException('签名错误:token已失效,请重新登录', ResponseCode::NOT_TOKEN);
        }
    }

    /**
     * 验证token
     * @return false
     * @throws ApiException
     */
    public function checkTokenByToken($token): bool|array
    {
        if (!$token) {
            return false;
        }
        try {
            JWT::$leeway = 10; //当前时间减去60，把时间留点余地
            $decoded = JWT::decode($token, new Key($this->key, 'HS256')); //HS256方式，这里要和签发的时候对应
            $result = (array)$decoded;
            $data = $result['data'];
            // redis检查登录状态
            $redis_token = Cache::get($this->app . ':' . ($this->app . 'Id') . ':' . $data->uuid);
            if (!$redis_token || $token != $redis_token) {
                throw new ApiException('签名错误:token已失效', ResponseCode::NOT_TOKEN);
            }
            return $result;
        } catch (SignatureInvalidException $e) { //签名不正确
            throw new ApiException('签名错误:token无效', ResponseCode::NOT_TOKEN);
        } catch (BeforeValidException $e) { // 签名在某个时间点之后才能用
            throw new ApiException('签名错误:token已失效', ResponseCode::NOT_TOKEN);
        } catch (ExpiredException $e) { // token过期
            throw new ApiException('签名错误:token已失效', ResponseCode::NOT_TOKEN);
        } catch (\Exception $e) { //其他错误
            throw new ApiException('签名错误:' . $e->getMessage(), ResponseCode::NOT_TOKEN);
        }
    }

    /**
     * 验证token
     * @return string
     */
    public function getHeaderToken(): string
    {
        $token = Request::header('authorization');
        $token = str_replace('Bearer null', '', $token ?? '');
        return !empty($token) ? trim(str_replace('Bearer', '', $token)) : '';
    }
}
