<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Request;
class Result extends Admin
{
	//考案列表
	public function lst(){
    $case=array();
    if(role()==1){
		$work_unitId=db('user')->where('id',session('userId'))->value('work_unitId');
		$case=db('text_case')->where('work_unitId',$work_unitId)->where('status',1)->select();
		foreach($case as $key=>$val){
            $case[$key]['stationNumber']=db('case_station')->where('caseId',$val['Id'])->count();
		}
    }
    if(role()==2){
        $caseId=Db::name('case_station')->where('examier',session('userId'))->column('caseId');
        $caseid=array_unique($caseId);
        foreach ($caseid as $key => $value) {
          $caseInfo=Db::name('text_case')->where('status',1)->find($value);
           if(db('text_case')->where('Id',$value)->value('status') && $caseInfo){
             $case[$key]=$caseInfo;
             $case[$key]['stationNumber']=db('case_station')->where('caseId',$value)->count();
             
           }
          
        }
    }

		$this->assign('case',$case);
		return $this->fetch();
	}
	//考站列表
	public function station(){
	   if(!input('caseId')){
         return $this->error('数据异常');
       }
       //共识会开不开
       $togherMeeting=db('text_case')->where('Id',input('caseId'))->value('togherMeeting');

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
       $togher=array();
       if($togherMeeting){//当前考案所有前期和评分人员，参加共识会
         foreach ($station as $key => $value) {
           $togher[]=$value['teach'];//前期作业人员
           $togher[]=$value['examier'];//中期打分人员
           if($value['sper']){
            $togher[]=$value['sper'];//模拟假人
           }
         }
         $togher=array_unique($togher);
         $togherUser=array();
         foreach ($togher as $key => $value) {
           $togherUser[]=db('user')->find($value);
         }
         $this->assign('caseInfo',db('text_case')->find(input('caseId')));
         $this->assign('togherUser',$togherUser);
       }
   
       $this->assign('togherMeeting',$togherMeeting);
       $this->assign('caseId',input('caseId'));
       $this->assign('station',$station);
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
      
      $this->assign('goods',$goods);
      $this->assign('type',$type);
      $this->assign('stationId',input('stationId'));
      $this->assign('caseId',db('case_station')->where('Id',input('stationId'))->value('caseId'));
      return $this->fetch();
    }
    //考俺设计
    public function detail(){
      if(!input('stationId') && !input('detailId')){
        return $this->error('数据异常');
        exit;
      }
       $stationId=db('text_detail')->where('Id',input('detailId'))->value('stationId');
      $stationId1=input('stationId')?input('stationId'):$stationId;
      $stationInfo=db('case_station')->where('Id',$stationId1)->find();
 
      
      if(input('detailId')){
        $this->assign('detail',db('text_detail')->find(input('detailId')));
      }
      $this->assign('caseId',$stationInfo['caseId']);
      return $this->fetch();
    }
    //考站下的考核表
    public function kaohe(){
      if(!input('stationId')){
         return $this->error('数据异常');
      }
      $cateInfo=db('case_assessment')->where('stationId',input('stationId'))->field('Id,name')->select();
      $standardInfo=db('assessment_standard')->where('stationId',input('stationId'))->field('Id,describe,min,max')->select();
      $teach=db('case_station')->where('Id',input('stationId'))->value('teach');
      if($teach==session('userId')){
        $this->assign('bianji',1);
      }

      $this->assign('stationId',input('stationId'));
      $this->assign('cateInfo',$cateInfo);
      $this->assign('standardInfo',$standardInfo);
      return $this->fetch();
    }
    //点击某一考站下的考核结果
    public function score(){
      if(!input('stationId')){
         return $this->error('数据异常');
      }
      $examinee=db('case_station')->where('Id',input('stationId'))->value('examinee');
       $Info=explode(',',$examinee);
        foreach ($Info as $key => $value) {
         $studentsInfo[$key]['id']=$value;
         $info=db('user')->find($value);
         $studentsInfo[$key]['username']=$info['username']?$info['username']:'未知';
         $studentsInfo[$key]['employeeNumber']=$info['employeeNumber']?$info['employeeNumber']:'未知';
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
}
