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
     $userInfo=array();
     $userInfo=$model->where('work_unitId',session('userId'))->select();
     foreach($userInfo as $key=>$val){
      $department=db('department')->where('id',$val['departmentId'])->value('name');
      $userInfo[$key]['department']=$department ? $department : '未知';
     }
     $this->assign('userInfo',$userInfo);
     return $this->fetch();
  }
  //科室列表
  public function dlst(){
     $model=db('department');
     $work_unitId=db('user')->where('id',session('userId'))->value('work_unitId');
     $departmentInfo=array();
     $departmentInfo=$model->where('work_unitId',$work_unitId)->select();
     $this->assign('department',$departmentInfo);
     return $this->fetch();
  }
  //用户添加
  public function add(){
    $model=db('user');
  	 if(request()->isPost()){
        if(input('post.id')){
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
        $arr=['username'=>input('post.username'),
             'phone'=>input('post.phone'),
             'employeeNumber'=>input('post.employeeNumber'),
             'departmentId'=>input('post.departmentId'),
             'work_unitId'=>$model->where('id',session('userId'))->value('work_unitId'),
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
     $work_unitId=$model->where('id',session('userId'))->value('work_unitId');
     $departmentInfo=db('department')->where('work_unitId',$work_unitId)->select();
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
         $arr=['name'=>input('post.name'),
             'directior'=>input('post.directior'),
             'phone'=>input('post.phone'),
             'infomation'=>input('post.infomation'),
           
             
             ]; 
          $re=$model->where('Id',input('post.Id'))->update($arr);
          if($re){
            return $this->success('修改成功','dlst',1);
          }
          return $this->error('修改失败','dlst',1);
       }else{
        $arr=['name'=>input('post.name'),
             'directior'=>input('post.directior'),
             'phone'=>input('post.phone'),
             'infomation'=>input('post.infomation'),
             'addTime'=>date('Y-m-d H:i:s'),
             'work_unitId'=>db('user')->where('id',session('userId'))->value('work_unitId'),
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
}