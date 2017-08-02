<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Request;
class User extends Admin
{
  //用户列表
  public function lst(){
     $model=db('user');
     $departmentId=input('departmentId')?input('departmentId'):db('department')->where('work_unitId',session('work_unitId'))->min('Id');
     $userInfo=array();
     $userInfo=$model->where('work_unitId',session('work_unitId'))->where('departmentId',$departmentId)->select();
     $this->assign(['userInfo'=>$userInfo,'departmentName'=>db('department')->where('Id',$departmentId)->value('name')]);
     return $this->fetch();
  }
  //科室列表
  public function dlst(){
     $model=db('department');
   
     $departmentInfo=array();
     $departmentInfo=$model->where('work_unitId',session('work_unitId'))->select();
     $this->assign('department',$departmentInfo);
     return $this->fetch();
  }
  //用户添加
  public function add(){
    $model=db('user');
  	 if(request()->isPost()){
        if(input('post.id')){
         $id=db('user')->where('work_unitId',session('work_unitId'))->where('employeeNumber',input('post.employeeNumber'))->value('id');
         if($id){
         if($id!=input('post.id')){
             return $this->error('员工编号不能重复');
         }
         }
         $userid=db('user')->where('phone',input('post.phone'))->value('id');
         if($userid){
          if($userid!=input('post.id')){
             return $this->error('手机号不能重复');
         }
         }
         $arr=['username'=>input('post.username'),
             'phone'=>input('post.phone'),
             'employeeNumber'=>input('post.employeeNumber'),
             'departmentId'=>input('post.departmentId')
             
           ]; 
          $re=$model->where('id',input('post.id'))->update($arr);
          if($re){
            return $this->success('修改成功','lst',1);
          }
          return $this->error('修改失败','lst',1);
       }else{
        if(db('user')->where('work_unitId',session('work_unitId'))->where('employeeNumber',input('post.employeeNumber'))->find()){
                 return $this->error('员工编号不能重复');
              }
        if(db('user')->where('phone',input('post.phone'))->find()){
                 return $this->error('手机号不能重复');
              }
        $arr=['username'=>input('post.username'),
             'phone'=>input('post.phone'),
             'employeeNumber'=>input('post.employeeNumber'),
             'departmentId'=>input('post.departmentId'),
             'work_unitId'=>session('work_unitId'),
             'password'=>md5('123456'),
             'roleId'=>2,
             'registerTime'=>date('Y-m-d H:i:s')
           ]; 
        $re=$model->insert($arr);
        if($re){
          return $this->success('添加成功','lst',1);
        }
        return $this->error('添加失败','add',1);
       }
  	 }
    
     $departmentInfo=db('department')->where('work_unitId',session('work_unitId'))->select();
     if(input('id')){
      $userInfo=$model->find(input('id'));
      $this->assign('user',$userInfo);
      }
     $this->assign('department',$departmentInfo);
     return $this->fetch();
  }
  //科室列表
  public function dadd(){
    $model=db('department');
     if(request()->isPost()){
      
      if(input('post.Id')){
        $departmentId=$model->where('coding',input('post.coding'))->where('work_unitId',session('work_unitId'))->value('Id');
         if($departmentId){
           if($departmentId!=input('post.Id')){
               return $this->error('科室编码重复');
           }
         }
         $arr=['name'=>input('post.name'),
             'directior'=>input('post.directior'),
             'phone'=>input('post.phone'),
             'infomation'=>input('post.infomation'),
             'coding'=>input('post.coding')
             ]; 
          $re=$model->where('Id',input('post.Id'))->update($arr);
          if($re){
            return $this->success('修改成功','dlst',1);
          }
          return $this->error('修改失败','dlst',1);
       }else{
        if($model->where('coding',input('post.coding'))->where('work_unitId',session('work_unitId'))->value('Id')){
         return $this->error('科室编码重复');
      }
        $arr=['name'=>input('post.name'),
             'directior'=>input('post.directior'),
             'phone'=>input('post.phone'),
             'infomation'=>input('post.infomation'),
             'addTime'=>date('Y-m-d H:i:s'),
             'coding'=>input('post.coding'),
             'work_unitId'=>session('work_unitId'),
             ];
        $re=$model->insert($arr);
        if($re){
          return $this->success('添加成功','dlst',1);
        }
        return $this->error('添加失败','dadd',1);
       }
        
     }

     if(input('Id')){
      $departmentInfo=$model->find(input('Id'));
      $this->assign('department',$departmentInfo);
      }
      return $this->fetch();
  }
  public function delDepartment(){
    if(!input('Id')){
      return $this->error('数据异常','dlst',1);
    }
    $re=db('department')->delete(input('Id'));
    if($re){
       return $this->success('删除成功','dlst',1);
    }
    return $this->error('删除失败','dlst',1);
  }

  public function delUser(){
    if(!input('id')){
      return $this->error('数据异常','lst',1);
    }
    $re=db('user')->delete(input('id'));
    if($re){
       return $this->success('删除成功','lst',1);
    }
    return $this->error('删除失败','lst',1);
  }
  //获取科室对应的编码
  public function getcoding(){
    if(!input('departmentId')){
      return json_encode('参数错误');
      exit;
    }
    $department=db('department')->where('Id',input('departmentId'))->find();
    if(!$department['coding']){
      return json_encode('科室编码错误');
      exit;
    }
    return json_encode($department);
    exit;
  }
}