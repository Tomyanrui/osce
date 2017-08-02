<?php
namespace app\back\controller;
use think\Controller;
use think\Db;
use think\Request;
class Admin extends controller
{
	public function _initialize(){
		define('UID',is_login());
        if( !UID ){// 还没登录 跳转到登录页面
            $this->redirect('Login/login');
            exit;
        }
       

	}
}