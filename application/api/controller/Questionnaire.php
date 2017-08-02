<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
class Questionnaire extends controller
{
   //问卷调查列表
	public function caseList($aJson){
		if(!yanzheng($aJson['token'])){
             echo  returnApiError('token验证失败');
             exit;
        }
        if(!isset($aJson['userId'])||!$aJson['userId'] || !is_numeric($aJson['userId'])){
          echo  returnApiError('用户Id错误');
          exit;
        }
        $userId=Db::name('user')->where('id',$aJson['userId'])->value('id');
        if(!$userId){
          echo  returnApiError('用户Id不存在');
          exit;
        }
        $work_unitId=db('user')->where('id',$aJson['userId'])->value('work_unitId');
        
        $caseInfo=db('text_case')->where('work_unitId',$work_unitId)->where('status',1)->where("wenjuan != ''")->field('Id,name,number,wenjuan')->select();
       
        foreach ($caseInfo as $key => $value) {//删选出做好调查问卷的考案
        	if(!db('question')->where('caseId',$value['Id'])->find()){
        		unset($caseInfo[$key]);
        	}
        }
        $caseInfo=array_values($caseInfo);
         
        foreach ($caseInfo as $key => $value) {//根据考案得出考生列表
        	$examinee=db('case_station')->where('caseId',$value['Id'])->column('examinee');
            $exam='';
        	foreach ($examinee as $k => $val) {

        		$exam.=$val.',';
        	}
        	$caseInfo[$key]['examinee']=$exam;
        }
        foreach ($caseInfo as $key => $value) {//排除考按考生列表没有此考生的靠岸
        	if(!in_array($aJson['userId'],explode(',',$value['examinee']))){
        		unset($caseInfo[$key]);
        	}
        }
        $caseInfo=array_values($caseInfo);
        foreach ($caseInfo as $key => $value) {
        	$done='';
        	if(db('answer')->where('caseId',$value['Id'])->where('studentId',$aJson['userId'])->value('Id')){
               $done=1;
        	}
        	$caseInfo[$key]['done']=$done ? $done : 0;
        }
        if(empty($caseInfo)){
          echo  returnApiError('暂无相关考案信息');
          exit;
        }
      
        echo returnApiSuccess(null,$caseInfo);
        exit;

	}
	//点击靠岸获取调查问卷
	public function getQuestion($aJson){
		if(!yanzheng($aJson['token'])){
             echo  returnApiError('token验证失败');
             exit;
        }
        if(!isset($aJson['caseId'])||!$aJson['caseId'] || !is_numeric($aJson['caseId'])){
            echo  returnApiError('考案Id错误');
            exit;
        }
        $caseInfo=Db::name('text_case')->find($aJson['caseId']);
        if(!$caseInfo['Id']){
           echo  returnApiError('没有此考案Id');
           exit;
        }
        
        $caseInfo['info']=db('question')->where('caseId',$caseInfo['Id'])->field('Id as questionId,title,type')->select();
        
        foreach ($caseInfo['info'] as $key => $value) {
        	
        	if($value['type']!=3){
        		$caseInfo['info'][$key]['option']=db('select')->where('quId',$value['questionId'])->field('Id as optionId,optionName')->select();
        	}
        }
        
        echo returnApiSuccess(null,$question);
        exit;



	}
    //问卷调查提交
    public function answerSubmit($aJson){
        if(!yanzheng($aJson['token'])){
             echo  returnApiError('token验证失败');
             exit;
        } 
        if(!isset($aJson['caseId'])||!$aJson['caseId'] || !is_numeric($aJson['caseId'])){
            echo  returnApiError('考案Id错误');
            exit;
        }
        $caseInfo=Db::name('text_case')->find($aJson['caseId']);
        if(!$caseInfo['Id']){
           echo  returnApiError('没有此考案Id');
           exit;
        }
        if(!$caseInfo['wenjuan']){
           echo  returnApiError('此考案没做问卷调查');
           exit;
        }
       
        if(!isset($aJson['studentId'])||!$aJson['studentId'] || !is_numeric($aJson['studentId'])){
          echo  returnApiError('用户Id错误');
          exit;
        }
        $userId=Db::name('user')->where('id',$aJson['studentId'])->value('id');
        if(!$userId){
          echo  returnApiError('用户Id不存在');
          exit;
        }
        if(db('answer')->where('studentId',$aJson['studentId'])->where('caseId',$aJson['caseId'])->value('Id')){
            echo  returnApiError('您已做过该调查问卷了');
            exit;
        }
        foreach ($aJson['answer'] as $key => $value) {
           if(!$value['questionId'] || !$value['type'] || !$value['answer']){
              echo  returnApiError('试题Id,试题类型，或答案错误');
              exit;
           }
           $aJson['answer'][$key]['studentId']=$aJson['studentId'];
           $aJson['answer'][$key]['caseId']=$aJson['caseId'];
          
           $aJson['answer'][$key]['addTime']=date('Y-m-d H:i:s');

        }
        $re=db('answer')->insertAll($aJson['answer']);
        if($re){
             echo returnApiSuccess('添加成功',null);
             exit;
        }
          echo  returnApiError('未知错误');
          exit;

    }
}