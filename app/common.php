<?php
// 应用公共文件
if (!function_exists('swoole_cpu_num')) {
    function swoole_cpu_num()
    {
        return 4;
    }
}
if (!function_exists('param_validate'))
{
    /**
     * @desc 验证器助手函数
     * @param array $data 数据
     * @param string|array $validate 验证器类名或者验证规则数组
     * @param array $message 错误提示信息
     * @param array $scene 场景
     * @param bool $batch 是否批量验证
     * @param bool $failException 是否抛出异常
     * @return bool
     */
    function param_validate(array $data, $validate = '', string $scene = '',array $message = [], bool $batch = false, bool $failException = true)
    {
        if(!empty($scene))
        {
            $validate = $validate.'.'.$scene;
        }
        if (is_array($validate)) {
            $v = new think\validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                [$validate, $scene] = explode('.', $validate);
            }
            $v = new $validate();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }
        if(!$v->check($data))
        {
            throw new \exceptions\ApiException($v->getError());
        }
    }
}

use function Swoole\Coroutine\batch;

if (!function_exists('tig_batch_task')) {

    /**
     * 批量异步步执行代码
     * @return array
     */
    function tig_batch_task(array $tasks)
    {
        if (extension_loaded('swoole') && config('app.IS_PRO') && php_sapi_name() == 'cli') {
            return batch($tasks);
        } else {
            $data = [];
            foreach ($tasks as $key => $task) {
                $data[$key] = $task();
            }
            return $data;
        }
    }
}

//生成随机数
    if(!function_exists('random_num'))
    {
        function random_num(int $num , array $search = [])
        {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            while (true) {
                $randomString = '';
                for ($i = 0; $i < $num; $i++) {
                    $randomString .= $characters[mt_rand(0, $charactersLength - 1)];
                }
                if(empty($search) || !in_array($randomString, $search)) {
                    break;
                }
           }
           return $randomString;
        }
    }

//判断是不是json
if(!function_exists('is_json')){
    function is_json($string) {
        // 尝试将字符串解码为 JSON
        $data = json_decode($string, true);

        // 如果解码失败，或者解码后的数据类型不是数组或对象，则不是有效的 JSON
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return true;
    }
}

/**
 * 将下划线命名转换为驼峰式命名
 *
 * @param $str
 * @param bool $ucfirst
 *
 * @return string|string[]
 */
function convertUnderline($str, $ucfirst = true)
{
    if (version_compare(config('app.version'), '2.2.7', '<')) {
        return $str;
    }
    $str = ucwords(str_replace('_', ' ', $str));
    $str = str_replace(' ', '', lcfirst($str));
    return $ucfirst ? $str : $str;
}

/*
* 将下划线命名数组转换为驼峰式命名数组
* @pram $data 原数组
* @pram $ucfirst 首字母大小写，false 小写，TRUE 大写
*
* @return string|string[]
*/
function camelCase($data, $ucfirst = false)
{
    if (version_compare(config('app.version'), '2.2.7', '<')) {
        return $data;
    }
    if ($data instanceof \think\Collection || $data instanceof \think\Model) {
        $data = $data->toArray();
    }
    if (!is_array($data)) {
        return $data;
    }
    $result = [];
    foreach ($data as $key => $value) {
        $key1 = convertUnderline($key, $ucfirst);
        $value1 = camelCase($value);
        $result[$key1] = $value1;
    }
    return $result;
}


/*
* 将小驼峰代码转成下划线
*/
function convertCamelCase($str)
{
    if (version_compare(config('app.version'), '2.2.7', '<')) {
        return $str;
    }
    $str = preg_replace_callback('/([A-Z])/', function ($matches) {
        return '_' . strtolower($matches[1]);
    }, $str);
    return $str;
}

function isSequentialArray($arr)
{
    // 检查是否为一维数组
    foreach ($arr as $value) {
        if (is_array($value)) {
            return false;
        }
    }

    // 检查键是否为自增整数
    $keys = array_keys($arr);
    for ($i = 0; $i < count($keys); $i++) {
        if ($keys[$i] !== $i) {
            return false;
        }
    }

    return true;
}

/**
 * 检测密码是否过于简单
 * @param string $password
 * @return bool
 */
function isPasswordTooSimple(string $password): bool
{

    // 1. 长度检查（至少8位）
    if (strlen($password) < 8) {
        return true;
    }
    if (in_array($password, [
        '123456789',
        'qwerty',
        'password',
        '12345678',
        '1234567890',
        'admin123',
    ])) {
        return true;
    }
    // 3. 字符类型复杂度检查（需包含至少3种类型）
    $typesCount = 0;
    if (preg_match('/[a-z]/', $password)) {
        $typesCount++;
    } // 小写字母
    if (preg_match('/[A-Z]/', $password)) {
        $typesCount++;
    } // 大写字母
    if (preg_match('/[0-9]/', $password)) {
        $typesCount++;
    } // 数字
    if (preg_match('/[\W_]/', $password)) {
        $typesCount++;
    } // 特殊字符

    if ($typesCount < 2) {
        return true;
    }
    return false;
}
