<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Request;
use think\debug;
class Result extends Admin
{
	//考案列表
	public function lst(){
   
    $case=array();
    $category=input('category')?input('category'):1;
    $case=db('text_case')->where('work_unitId',session('work_unitId'))->where('status',1)->where('category',$category)->select();
    foreach($case as $key=>$val){
        $case[$key]['stationNumber']=db('case_station')->where('caseId',$val['Id'])->count(); 
         if(role()==2){
           $stationInfo=db('case_station')->where('caseId',$val['Id'])->where('examier',session('userId'))->find();
           if(empty($stationInfo)){
              unset($case[$key]);
           } 
         }    
      }
   $this->assign(['case'=>$case,'category'=>$category]);
	 return $this->fetch();
	}
	//考站列表
	public function station(){
	   if(!input('caseId')){
         return $this->error('数据异常');
       }
       $model=db('case_station');
       $station=$model->where('caseId',input('caseId'))->select();
       foreach ($station as $key => $value) {
       	 $station[$key]['teachName']=db('user')->where('id',$value['teach'])->value('username');
       	 $station[$key]['examierName']=db('user')->where('id',$value['examier'])->value('username');
         $cateInfo=db('case_assessment')->where('stationId',$value['Id'])->find();
         $standardInfo=db('assessment_standard')->where('stationId',$value['Id'])->find();
         $detail=db('text_detail')->where('stationId',$value['Id'])->value('Id');
         $station[$key]['sheji']=$detail ? $detail : 0;


        if(empty($cateInfo)||empty($standardInfo)){
          $station[$key]['kaohe']=0;
         }else{
          $station[$key]['kaohe']=1;
         }
       	 if($value['isSp']){
       	 	$station[$key]['spName']=db('user')->where('id',$value['sper'])->value('username');
       	 }
       }
    
        $this->assign(['caseInfo'=>db('text_case')->find(input('caseId')),
                     
                     'station'=>$station
                     ]);
       return $this->fetch();
	}
	//考站物品准备
    public function goods(){
      if(!input('stationId')){
        return $this->error('数据异常');
        exit;
      }
      $type=input('type')?input('type'):1;
      $model=db('goods');
      $goods=$model->where('stationId',input('stationId'))->where('type',$type)->select();
      $caseId=db('case_station')->where('Id',input('stationId'))->value('caseId');
      $caseName=db('text_case')->where('Id',$caseId)->value('name');
       $this->assign(['goods'=>$goods,'type'=>$type,
                      'stationId'=>input('stationId'),
                      'caseId'=>$caseId,
                      'caseName'=>$caseName
                      ]);
      return $this->fetch();
    }
    //考俺设计
    public function detail(){
      if(!input('detailId')){
        return $this->error('数据异常');
        exit;
      }
      $stationId=db('text_detail')->where('Id',input('detailId'))->value('stationId');
      $stationInfo=db('case_station')->where('Id',$stationId)->find();
      $this->assign('detail',db('text_detail')->find(input('detailId')));
      $this->assign('caseId',$stationInfo['caseId']);
      $this->assign('caseName',db('text_case')->where('Id',$stationInfo['caseId'])->value('name'));
      return $this->fetch();
    }
    //考站下的考核表
    public function kaohe(){
      if(!input('stationId')){
         return $this->error('数据异常');
      }
      $cateInfo=db('case_assessment')->where('stationId',input('stationId'))->field('Id,name')->select();
      $standardInfo=db('assessment_standard')->where('stationId',input('stationId'))->field('Id,describe,min,max')->select();
      $station=db('case_station')->where('Id',input('stationId'))->find();
      $this->assign([
                     'cateInfo'=>$cateInfo,
                     'standardInfo'=>$standardInfo,
                     'caseId'=>$station['caseId'],
                     'caseName'=>db('text_case')->where('Id',$station['caseId'])->value('name')
                     ]);
      return $this->fetch();
    }
    //点击某一考站下的考核结果
    public function score(){
      if(!input('stationId')){
         return $this->error('数据异常');
      }
      //debug('begin');
      $examinee=db('case_station')->where('Id',input('stationId'))->value('examinee');
       $Info=explode(',',$examinee);
        foreach ($Info as $key => $value) {
         $studentsInfo[$key]['id']=$value;
         $info=db('user')->find($value);
         $studentsInfo[$key]['username']=$info['username'];
         $studentsInfo[$key]['employeeNumber']=$info['employeeNumber'];
      }

      $cateInfo=db('case_assessment')->where('stationId',input('stationId'))->field('Id,name')->select();
      foreach ($cateInfo as $key => $value) {
          foreach ($studentsInfo as $k => $v) {
             $resultInfo[$v['id']]['studentId']=$v['id'];
             $resultInfo[$v['id']]['username']=$v['username'];
             $resultInfo[$v['id']]['employeeNumber']=$v['employeeNumber'];
             $result=db('text_result')->where('stationId',input('stationId'))->where('studentId',$v['id'])->where('case_assessmentId',$value['Id'])->field('Id,result')->find();
              
             $resultInfo[$v['id']]['result'][]=$result;

          }
      }
      //debug('end');
      //echo debug('begin','end').'s';exit;
      $stationInfo=db('case_station')->find(input('stationId'));
      $standardInfo=db('assessment_standard')->where('stationId',input('stationId'))->where('whole',0)->select();
      
      $caseInfo=db('text_case')->find(db('case_station')->where('Id',input('stationId'))->value('caseId'));
      
      
      $this->assign(['cateInfo'=>$cateInfo,'resultInfo'=>$resultInfo,'stationInfo'=>$stationInfo,'caseInfo'=>$caseInfo,
                      'standardInfo'=>$standardInfo
        ]);
      return $this->fetch();
    }
    //修改某一考生的评分结果
    public function  changeResult(){
      if(!input('resultId') && !input('result') && !input('stationId')){
         return $this->error('数据异常');
      }
     
      $standardInfo=db('assessment_standard')->where('stationId',input('stationId'))->where('whole',0)->select();
      foreach ($standardInfo as $key => $value) {
        $number[]=$value['min'];
        $number[]=$value['max'];
      }
      if(input('result')<min($number) || input('result')>max($number)){
        return $this->error('分数不在分数区间内');
      }
      $re=db('text_result')->where('Id',input('resultId'))->update(['result'=>input('result')]);
      if($re){
        return $this->redirect('score',['stationId'=>input('stationId')]);
        exit;
      }
      return $this->error('异常错误');
    }
    //添加问卷名称
    public function getWenjuan(){
      if(!input('caseId')){
        return json_encode('异常错误');
        exit;
      }
      $caseInfo=db('text_case')->find(input('caseId'));
      return json_encode($caseInfo);
      exit;
    }
}
