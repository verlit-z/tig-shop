<?php

namespace app;

/**
 * 重写Request一些方法
 */
class Request extends \think\Request

{
    /**
     * 获取指定的参数
     * @access public
     * @param array $name 变量名
     * @param mixed $data 数据或者变量类型
     * @param string|array|null $filter 过滤方法
     * @return array
     */
    public function only(array $name, $data = 'param', string|array|null $filter = '', bool $hasDefault = true): array
    {
        $item = [];
        foreach ($name as $key => $val) {
            if (!$hasDefault) {
                $key = $val;
                $val = null;
            }
            $_key = explode('/', $key);
            $item[$_key[0]] = $this->$data(($key), $val ?? null, $filter);
            if ((is_string($item[$_key[0]]) && $hasDefault) || is_array($item[$_key[0]]) && isSequentialArray($item[$_key[0]])) {
                // 值为string时进行统一trim处理
                if ($item[$_key[0]] === '' && !empty($val)) {
                    // 当字符型请求为空且设置了默认值时，采用默认值（由isset判断改为!empty判断）
                    $item[$_key[0]] = $val;
                }
                if (($_key[0] == 'sort_field' || $_key[0] == 'field') && !empty($item[$_key[0]])) {
                    $item[$_key[0]] = convertCamelCase($item[$_key[0]]);
                }
            }elseif (is_array($item[$_key[0]])) {
                // 如果是数组，递归转换键名为下划线
                //判断数据是个有key的
                $item[$_key[0]] = $this->convertArrayKeysToSnakeCase($item[$_key[0]]);
            }
        }
        return $item;
    }

    /**
     * 将参数值是数组键名转换为下划线
     * @param array $array
     * @return array
     */
    private function convertArrayKeysToSnakeCase(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $snakeKey = convertCamelCase($key);
            if (is_array($value)) {
                $result[$snakeKey] = $this->convertArrayKeysToSnakeCase($value);
            } else {
                $result[$snakeKey] = $value;
            }
        }
        return $result;
    }

    /**
     * 获取GET参数
     * @access public
     * @param string|array|bool $name 变量名
     * @param mixed $default 默认值
     * @param string|array|null $filter 过滤方法
     * @return mixed
     */
    public function get(string|array|bool $name = '', $default = null, string|array|null $filter = '')
    {
        if (is_array($name)) {
            return $this->only($name, $this->get, $filter);
        }

        return $this->input($this->get, $name, $default, $filter);
    }


    /**
     * 获取POST参数
     * @access public
     * @param string|array|bool $name 变量名
     * @param mixed $default 默认值
     * @param string|array|null $filter 过滤方法
     * @return mixed
     */
    public function post(string|array|bool $name = '', $default = null, string|array|null $filter = '')
    {
        if (is_array($name)) {
            return $this->only($name, $this->post, $filter);
        }
        return $this->input($this->post, $name, $default, $filter);
    }

    public function all(string|array $name = '', string|array|null|int $filter = '')
    {
        $data = array_merge($this->param(), $this->file() ?: []);
        if('' != $name){
            if (str_contains($name, '/')) {
                [$name, $type] = explode('/', $name);

            }
            $name = convertUnderline($name);
        }

        if (isset($type) && $type == 'a') {
            $onlyData = $this->only([$name => $filter]);
            $data = isset($onlyData[$name]) ? $onlyData[$name] : $filter;
        } elseif ($name) {
            $data = isset($data[$name]) ? $data[$name] : $filter;
        }

        if (isset($type)) {
            // 强制类型转换
            $this->typeCast($name, $type);
        }
        if ($name == 'field') {
            $data = convertCamelCase($data);
        }
        return $data ;
    }


    public function input(array $data = [], string|bool $name = '', $default = null, string|array|null $filter = '')
    {
        if (false === $name) {
            // 获取原始数据
            return $data;
        }

        $name = (string)$name;
        if ('' != $name) {
            // 解析name
            if (str_contains($name, '/')) {
                [$name, $type] = explode('/', $name);
            }
            $name = convertUnderline($name);
            $data = $this->getData($data, $name);

            if (is_null($data)) {
                return $default;
            }

            if (is_object($data)) {
                return $data;
            }
        }

        $data = $this->filterData($data, $filter, $name, $default);

        if (isset($type) && $data !== $default) {
            // 强制类型转换
            $this->typeCast($data, $type);
        }

        return $data;
    }


}
