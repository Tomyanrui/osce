<?php
namespace app\admin\controller;
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
        $keshi=db('department')->where('work_unitId',session('work_unitId'))->field('Id,name')->select();
    	$this->assign(['keshi'=>$keshi]);
       

	}
}