<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Request;
class Login extends controller
{
    public function login(){
        if(request()->isPost()){
        	$userInfo=Db::name('user')->where('username',input('post.username'))->whereor('phone',input('post.username'))->find();
        	if(!$userInfo){
        		return $this->error('没有此用户名或手机号','login');
            }
            if($userInfo['password']!=md5(input('post.password'))){
            	return $this->error('密码错误','login');
            }
            if($userInfo['roleId']==3){
            	return $this->error('没有权限','login');
            }
            Db::name('user')->where('id',$userInfo['id'])->update(['lastLoginTime'=>date('Y-m-d H:i:s')]);
            session('userId',$userInfo['id']);
			session('username',$userInfo['username']);
			session('hospital',Db::name('work_unit')->where('Id',$userInfo['work_unitId'])->value('name'));
            return $this->redirect('Index/index');
        }
    	return $this->fetch('login');
    }
    public function loginout(){
        
        Db::name('user')->where('id',session('userId'))->update(['loginOutTime'=>date('Y-m-d H:i:s')]);
    	session(null);
    	return $this->redirect('login');
    }
    public function changePassword(){
        define('UID',is_login());
        if(request()->isPost()){
            $userId=Db::name('user')->where('phone',input('post.phone'))->value('id');
            if(!$userId){
            return $this->error('没有此手机号','changePassword');
            exit;
            }
            $info=Db::name('notify')->where('phone',input('post.phone'))->find();
            if(!$info['notify']){
              return $this->error('请重新获取验证码','changePassword');
               exit;
            }
            if($info['notify']!=input('post.notify') || time()>$info['loseTime'] || $info['lose']==1){
             
             return $this->error('验证码已失效，请重新获取','changePassword');
             exit;
            }
             Db::name('notify')->where('phone',input('post.phone'))->update(['lose'=>1]);
            $re=Db::name('user')->where('id',$userId)->update(['password'=>md5(input('post.password'))]);

           if($re){
            return $this->success('修改成功','loginout');
            exit;
            }
           return $this->error('修改失败','changePassword');
           exit;
        }
        return $this->fetch();
    }
     //修改密码
        public function sendMessage(){
         
         $search ='/^1(3|4|5|7|8)\d{9}$/';
         $phone=db('user')->where('id',session('userId'))->value('phone');

         if( !preg_match($search,input('mobile'))){
          echo  json_encode(2);
          exit;
         }
         $phone=db('user')->where('id',session('userId'))->value('phone');
         if(input('mobile')!=$phone){
          echo  json_encode(1);
          exit;
         }
         $info=Db::name('notify')->where('phone',input('mobile'))->find();
         $now=time();
         if($now<$info['againTime']){
           
            echo  json_encode(3);
            exit;
         }
         if($now>$info['againTime'] && $now<$info['loseTime'] && !$info['lose']){
            
            echo  json_encode(4);
            exit;
         }
         $code=rand(100000,999999);
         $re=sendMessage($code,input('mobile'));
         if($re){
          if($info){
            
            $arr=['notify'=>$code,'addTime'=>$now,'againTime'=>$now+60,'loseTime'=>$now+600,'lose'=>0];
            Db::name('notify')->where('phone',input('mobile'))->update($arr);
          }else{
           
            $arr=['notify'=>$code,'addTime'=>$now,'againTime'=>$now+60,'loseTime'=>$now+600,'phone'=>input('mobile')];
            Db::name('notify')->insert($arr);
          }
           echo  json_encode(5);
           exit;
        }
        echo  json_encode(6);
        exit;
    }
}