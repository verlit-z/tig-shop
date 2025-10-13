<?php

declare (strict_types=1);

namespace app\adminapi\middleware;

use app\service\admin\authority\AuthorityService;


class CheckAuthor
{
    public function handle($request, \Closure $next)
    {
        $authority_sn = $request->only([
            'authorityCheckAppendName' => '',
            'authorityCheckSubPermissionName' => ''
        ]);
        $authority_sn = array_filter(array_values($authority_sn));
        if ($authority_sn) {
            app(AuthorityService::class)->checkAuthor($authority_sn, (int)request()->shopId, request()->authList ?? []);
        }
        return $next($request);
    }
}