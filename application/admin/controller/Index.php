<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Request;
class Index extends Admin
{
    public function index()
    {   
    	//首页用户管理下的科室列表
    	
    	return $this->fetch();
    }
    public function changeRole(){
    	if(role()!=1){
    		$this->error('您没有相关权限');
    	}
        if(request()->isPost()){
            $re=db('work_unit')->where('Id',session('work_unitId'))->update([
                'roleName1'=>input('post.roleName1'),
                'roleName2'=>input('post.roleName2'),
                'roleName3'=>input('post.roleName3'),
                'roleName4'=>input('post.roleName4'),
                ]);
            if($re){
                return $this->redirect('Login/loginout');
                exit;
            }
            return $this->error('修改失败');
        }
        $work_unit=db('work_unit')->find(session('work_unitId'));
        $this->assign('work_unit',$work_unit);
    	return $this->fetch();
    }
}
