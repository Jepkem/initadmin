<?php
// +----------------------------------------------------------------------
// | tpvue [ 模块化渐进式后台 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018-2019 http://tpvue.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: jry <598821125@qq.com>
// +----------------------------------------------------------------------
namespace app\core\controller\common;

use think\Request;
use app\core\controller\Common;

class Admin extends Common
{
    protected function initialize()
    {
        $ret = $this->is_login();
        if($ret['code'] != 200){
            // echo json_encode($ret);
            // exit();
        }
    }
}