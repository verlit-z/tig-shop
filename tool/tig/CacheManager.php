<?php

namespace tig;

use think\facade\Cache;

class CacheManager
{
    public function clearCacheByTag($tag = 'all')
    {
        if ($tag == 'all') {
            //Cache::clear();

            //KEYS * 命令会阻塞 Redis 服务器，不要在生产环境使用，特别是当键数量很大时
            $redis = Cache::store('redis')->handler(); // 获取 Redis 实例
            $allKeys = $redis->keys('*');
            foreach ($allKeys as $key) {
                if (!preg_match('/^(admin:adminId:|app:appId:)/', $key)) {
                    Cache::delete($key);
                }
            }

        } else {
            Cache::tag($tag)->clear();
        }
    }
}
