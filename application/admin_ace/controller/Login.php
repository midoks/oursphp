<?php
// +----------------------------------------------------------------------
// | oursphp [ simple and fast ]
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: midoks <627293072@qq.com>
// +----------------------------------------------------------------------


namespace  app\controller;

use frame\Controller;
use frame\utils\Image;
use \frame\Session;

use common\dao\SysUserDao;

class Login extends Controller {
    
	//登录
    public function index($request, $response) {
        $dao        = new SysUserDao();

        //检查是否已经登录
        $user = cookie('info');
        if ($user){
            $this->redirect('/index');
        }

        //登录操作
        if ($request->isPost()){

            $password = $request->post('password');
            $vars = $request->post('vars');
            $code = cookie('admin_captcha');

            if (strtolower($vars['code']) == strtolower($code)){

                $name = $request->name; 
                $query['username'] = $name;
                $where  = ' username=:username and status=1';
                $user   = $dao->findOne($query, $where);
 
                if( $user && md5($password) === $user['password']) {
                    unset($user['password']);
                    cookie('info', $user, 1*24*60*60,'domain=.yoka.com');
                    cookie('admin_captcha', NULL);
                    $this->redirect("/index");
                }
            }
        }

        return $this->render();
    }

    //登出
    public function out($request, $response) {

        $ret    = cookie('info', NULL);
        //var_dump($ret);
        $this->redirect("/index");
        exit;
    }


    //生成验证码
    public function captcha($request, $response){
    	$im        = new Image();
        $im->captcha();
        $char = $im->getChar();

        //设置cookie
        cookie('admin_captcha',$char, 300);

        $im->output();exit;
    }
	
}