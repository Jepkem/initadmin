<?php
/**
 * +----------------------------------------------------------------------
 * | InitAdmin/actionphp [ InitAdmin渐进式模块化通用后台 ]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2018-2019 http://initadmin.net All rights reserved.
 * +----------------------------------------------------------------------
 * | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
 * +----------------------------------------------------------------------
 * | Author: jry <ijry@qq.com>
 * +----------------------------------------------------------------------
*/

namespace app\core\behavior;

use think\Db;
use think\facade\Route;
use think\facade\Request;

/**
 * 路由行为扩展
 *
 * @author jry <ijry@qq.com>
 */
class Router
{
    // 行为逻辑
    public function run($params)
    {
        //内置路由
        Route::rule('/', 'core/common.Index/index'); // 首页访问路由

        //计算后台API路由
        $data_list = Db::name('core_menu')
            ->removeOption('where')
            ->where('menu_type', 'in' , '1,2,3')
            ->select();
        foreach ($data_list as $key => $val) {
            $path = explode('/', $val['path']);
            Route::rule(
                'api/' . $val['api_prefix'] . '/admin' . $val['path'] . $val['api_suffix'],
                $path[1] . '/admin.' . $path[2] . '/' . $path[3],
                $val['api_method']
            );
        }

        //计算前台API路由
        $data_list = Db::name('core_menu')
            ->removeOption('where')
            ->where('menu_type', '=' , '5')
            ->select();
        foreach ($data_list as $key => $val) {
            $path = explode('/', $val['path']);
            Route::rule(
                'api/' . $val['api_prefix'] . $val['path'] . $val['api_suffix'],
                $path[1] . '/' . $path[2] . '/' . $path[3],
                $val['api_method']
            );
        }

        //dump(Route::getNames());exit;
    }
}