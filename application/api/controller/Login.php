<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
class Login extends controller
{
    public function login($aJson)
    {
        
        if(!isset($aJson['Username'])|| !$aJson['Username'] ){
        echo  returnApiError('用户名错误');
        exit;
        }
        if(!isset($aJson['Password'])|| !($aJson['Password'])){
        echo  returnApiError('密码错误');
        exit;
        }
        $model=Db::name('user');
        $userInfo1=$model->where('username',$aJson['Username'])->find();
        $userInfo2=$model->where('phone',$aJson['Username'])->find();
        if(!($userInfo1) && !($userInfo2)) {
        echo  returnApiError('没有此用户名和手机号');
        exit;

        }
        $userInfo=$userInfo1?$userInfo1:$userInfo2;

        if(md5($aJson['Password'])!=$userInfo['password']){
        echo returnApiError('密码输入有误');
           exit;
        }
        if($userInfo['roleId']!=2){
        echo returnApiError('没有权限');
        exit;
        }

         $token=md5($userInfo['phone'].time());
        $model->where('id',$userInfo['id'])->update(['lastLoginTime'=>date('Y-m-d H:i:s'),'token'=>$token]);
       
        $userInfo['token']=$token;
       
       echo returnApiSuccess(null,$userInfo);
       exit;
       
    }
    //修改密码
        public function sendMessage($aJson){
         $search ='/^1(3|4|5|7|8)\d{9}$/';
         if(!isset($aJson['phone'])||!$aJson['phone'] || !is_numeric($aJson['phone']) || !preg_match($search,$aJson['phone'])){
          echo  returnApiError('手机号错误');
          exit;
         }
         $info=Db::name('notify')->where('phone',$aJson['phone'])->find();
         $now=time();
         if($now<$info['againTime']){
            echo  returnApiError('一分钟之内不得重复发送验证码');
            exit;
         }
         if($now>$info['againTime'] && $now<$info['loseTime'] && !$info['lose']){
            echo  returnApiError('您之前的验证码仍在有效期内，请查收');
            exit;
         }
         $code=rand(100000,999999);
         $re=sendMessage($code,$aJson['phone']);
         if($re){
          if($info){
            
            $arr=['notify'=>$code,'addTime'=>$now,'againTime'=>$now+60,'loseTime'=>$now+600,'lose'=>0];
            Db::name('notify')->where('phone',$aJson['phone'])->update($arr);
          }else{
           
            $arr=['notify'=>$code,'addTime'=>$now,'againTime'=>$now+60,'loseTime'=>$now+600,'phone'=>$aJson['phone']];
            Db::name('notify')->insert($arr);
          }
           echo  returnApiSuccess('短信发送成功',null);
           exit;
        }
        echo  returnApiError('短信发送失败');
        exit;
    }
    //根据验证码修改密码
    public function changePassword($aJson){
        if(!isset($aJson['password'])|| !($aJson['password'])){
           echo  returnApiError('密码错误');
           exit;
        }
        $search ='/^1(3|4|5|7|8)\d{9}$/';
        if(!isset($aJson['phone'])||!$aJson['phone'] || !preg_match($search,$aJson['phone'])){
          echo  returnApiError('手机号错误');
          exit;
        }
        if(!isset($aJson['code'])||!$aJson['code'] || !(is_numeric($aJson['code'])) ){
           echo  returnApiError('验证码格式有误');
           exit;
        }
        if(strlen($aJson['code'])!=6){
           echo  returnApiError('验证码长度必须为6位数字');
           exit;
        }
        $userId=Db::name('user')->where('phone',$aJson['phone'])->value('id');
        if(!$userId){
            echo  returnApiError('该手机号还不是该系统用户，请联系学务处');
            exit;
        }
        $info=Db::name('notify')->where('phone',$aJson['phone'])->find();
        if(!$info['notify']){
           echo  returnApiError('请重新获取验证码');
           exit;
        }
       
        if($info['notify']!=$aJson['code'] || time()>$info['loseTime'] || $info['lose']==1){
           echo  returnApiError('验证码已失效，请重新获取');
          exit;
        }
        Db::name('notify')->where('phone',$aJson['phone'])->update(['lose'=>1]);
        $re=Db::name('user')->where('id',$userId)->update(['password'=>md5($aJson['password'])]);

        if($re){
            echo returnApiSuccess('修改成功',null);
            exit;
        }
        echo  returnApiError('异常错误，修改失败');
        exit;

    }


}
