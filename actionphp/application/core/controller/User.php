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

namespace app\core\controller;

use think\Db;
use think\Request;
use app\core\controller\common\Home;

use \Firebase\JWT\JWT; //导入JWT

/**
 * 用户控制器
 *
 * @author jry <ijry@qq.com>
 */
class User extends Home
{
    private $core_user;
    private $core_identity;
    private $core_login;

    public function __construct()
    {
        $this->core_user = new \app\core\model\User();
        $this->core_identity = new \app\core\model\Identity();
        $this->core_login = new \app\core\model\Login();
    }

    /**
    * 是否登陆
    *
    * @return \think\Response
    * @author jry <ijry@qq.com>
    */
    public function isLogin()
    {
        $ret = $this->is_login();
        return json($ret);
    }

    /**
    * 获取用户信息
    *
    * @return \think\Response
    * @author jry <ijry@qq.com>
    */
    public function info()
    {
        $ret = $this->is_login();
        if($ret['code'] != 200){
            return json($ret);
        }
        $user_info = $this->core_user
            ->field('id,nickname,username,email,avatar')
            ->find($ret['data']['uid']);
        return json(['code' => 200, 'msg' => '用户信息', 'data' => ['user_info' => $user_info]]);
    }

    /**
     * 用户登录
     *
     * @param  \think\Request  $request
     * @return \think\Response
     * @author jry <ijry@qq.com>
     */
    public function login(Request $request)
    {
        //获取提交的账号密码/验证码
        $identity_type = input('post.identity_type')?:0;
        $identifier = input('post.identifier');
        $credential = input('post.credential');

        //数据验证

        //登录验证
        switch ($identity_type) {
        case 1: //手机号
            $map = [];
            $map['identity_type'] = $identity_type;
            $map['identifier'] = $identifier;
            $user_identity_info = $this->core_identity
                ->where($map)
                ->find();
            if (!$user_identity_info) {
                return json(['code' => 0, 'msg' => '手机号不存在']);
            }
            if ($user_identity_info['verified'] !== 1) {
                return json(['code' => 0, 'msg' => '手机号未通过验证']);
            }
            $user_info = $this->core_user->where(['id' => $user_identity_info['uid']])->find();
            if (!$user_info) {
                return json(['code' => 0, 'msg' => '用户不存在']);
            }
            if ($user_info['status'] !== 1) {
                return json(['code' => 0, 'msg' => '账号状态异常']);
            }
            break;
        case 2: //邮箱
            $map = [];
            $map['identity_type'] = $identity_type;
            $map['identifier'] = $identifier;
            $user_identity_info = $this->core_identity
                ->where($map)
                ->find();
            if (!$user_identity_info) {
                return json(['code' => 0, 'msg' => '邮箱不存在']);
            }
            if ($user_identity_info['verified'] !== 1) {
                return json(['code' => 0, 'msg' => '邮箱未通过验证']);
            }
            $user_info = $this->core_user
                ->where(['id' => $user_identity_info['uid']])
                ->find();
            if (!$user_info) {
                return json(['code' => 0, 'msg' => '用户不存在']);
            }
            if ($user_info['status'] !== 1) {
                return json(['code' => 0, 'msg' => '账号状态异常']);
            }
            break;
        default: //用户名
            $map = [];
            $map['username'] = $identifier;
            $user_info = $this->core_user
                ->where($map)
                ->find();
            if (!$user_info) {
                return json(['code' => 0, 'msg' => '用户名不存在']);
            }
            if ($user_info['status'] !== 1) {
                return json(['code' => 0, 'msg' => '账号状态异常']);
            }
            if ($user_info['password'] !== user_md5($credential, $user_info['key'])) {
                return json(['code' => 0, 'msg' => '密码错误']);
            }
            break;
        }
    
        //颁发登录凭证token
        $key = \think\helper\Str::random(64); //秘钥
        $login_time = time();
        $expire_time = $login_time + 8640000; //100天有效期
        $token = [
            'iss' => 'initamin.net',//签发者
            'aud' => 'initamin.net',//面向的用户
            'iat' => $login_time,//签发时间
            'nbf' => $login_time,//在什么时候jwt开始生效
            'exp' => $expire_time,//token 过期时间
            'data'=>[
                'uid' => $user_info['id']//可以用户ID，可以自定义
            ]
        ]; //Payload
        $jwt = JWT::encode($token, $key); //此处行进加密算法生成jwt

        //存进数据库
        $data = [];
        $data['uid'] = $user_info['id'];
        $data['key'] = $key;
        $data['token'] = $jwt;
        $data['login_time'] = $login_time;
        $data['expire_time'] = $expire_time;
        $data['client_type'] = input('post.client_type') ? : 0;
        $data['client_name'] = input('post.client_type') ? : '';
        $ret = $this->core_login->insert($data);
        if ($ret) {
            return json(['code' => 200, 'msg' => '登陆成功', 'data' => ['token' => $jwt]]);
        } else {
            return json(['code' => 0, 'msg' => '添加失败', 'data' => []]);
        }
    }

    /**
    * 注销登录
    *
    * @return \think\Response
    * @author jry <ijry@qq.com>
    */
    public function logout()
    {
        $ret = $this->is_login();
        if ($ret['code'] == 200) {
            $ret = $this->core_login
                ->where('token', $ret['data']['data']->token)
                ->delete(true);
            if ($ret) {
                return json(['code' => 200, 'msg' => '注销成功', 'data' => []]);
            } else {
                return json(['code' => 0, 'msg' => '注销失败', 'data' => []]);
            }
        } else {
            return json($ret);
        }
    }
}
