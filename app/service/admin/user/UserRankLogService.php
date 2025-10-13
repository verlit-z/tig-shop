<?php

namespace app\service\admin\user;

use app\model\user\UserRankLog;
use app\service\common\BaseService;

class UserRankLogService extends BaseService
{
    protected function filterQuery(array $filter): object
    {
        $query = UserRankLog::query();
        if (isset($filter['username']) && !empty($filter['username'])) {
            $username = $filter['username'];
            $query->hasWhere('user', function ($query) use ($username) {
                $query->where('username', 'like', '%' . $username . '%');
            });
        }
        return $query;
    }
}