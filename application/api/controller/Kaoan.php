<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
class Kaoan extends controller
{
    public function _empty($aJson){
      echo  returnApiError('接口数据错误（方法名不存在）');
      exit;
    }
    //首页根据用户Id为考官的考案信息
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
        $caseId=Db::name('case_station')->where('examier',$aJson['userId'])->where('state',0)->where('status',1)->column('caseId');
        $caseid=array_unique($caseId);
        $caseInfo=array();
        foreach ($caseid as $key => $value) {
           $case=Db::name('text_case')->where('status',1)->find($value);
           if($case){
            $caseInfo[$key]=$case;
            $caseInfo[$key]['username']=db('user')->where('id',$aJson['userId'])->value('username');
           }
           
        }
        $caseInfo=array_values($caseInfo);
        if(empty($caseInfo)){
          echo  returnApiError('暂无相关考案信息');
          exit;
        }
      
        echo returnApiSuccess(null,$caseInfo);
        exit;
    }
    //根据考案Id获取考站
    public function index($aJson){
      if(!yanzheng($aJson['token'])){
             echo  returnApiError('token验证失败');
             exit;
      }
      if(!isset($aJson['caseId'])||!$aJson['caseId'] || !is_numeric($aJson['caseId'])){
      echo  returnApiError('考案Id错误');
      exit;
      }
      $caseId=Db::name('text_case')->where('Id',$aJson['caseId'])->value('Id');
      if(!$caseId){
        echo  returnApiError('没有此考案Id');
        exit;
      }
      $stationInfo=Db::name('case_station')->where('caseId',$aJson['caseId'])->where('examier',$aJson['userId'])->where('state',0)->select();
      if(empty($stationInfo)){
        echo  returnApiError('暂无相关考站信息');
        exit;
      }
     
      echo returnApiSuccess(null,$stationInfo);
      exit;
    }
    //点击考站返回相关介绍和考生姓名
    public function detail($aJson){
      if(!yanzheng($aJson['token'])){
             echo  returnApiError('token验证失败');
             exit;
        }
      if(!isset($aJson['stationId'])||!$aJson['stationId'] || !is_numeric($aJson['stationId'])){
      echo  returnApiError('考站Id错误');
      exit;
      }

      if(!isset($aJson['userId'])||!$aJson['userId'] || !is_numeric($aJson['userId'])){
      echo  returnApiError('用户Id错误');
      exit;
      }
      $stationInfo=Db::name('case_station')->where('Id',$aJson['stationId'])->find();
      if(!$stationInfo['Id']){
        echo  returnApiError('没有此考站Id');
        exit;
      }
      // if($stationInfo['examier']!=$aJson['userId']){
      //   echo  returnApiError('您不是该考站的考官');
      //   exit;
      // }
      $state=Db::name('case_station')->where('Id='.$aJson['stationId'])->value('state');//查看是否已经完成全部评分
      if($state){
        echo  returnApiError('此考站已经完成全部评分了');
        exit;
      }
      $caseInfo=Db::name('text_case')->where('Id',$stationInfo['caseId'])->find();
      $stationInfo['text_case_name']=$caseInfo['name'];//考案名称
      $stationInfo['text_case_number']=$caseInfo['number'];//考案编号
      $stationInfo['textTime']=$caseInfo['textTime'];//测验日期
      $work_unitId=Db::name('user')->where('id',$aJson['userId'])->value('work_unitId');

      $stationInfo['work_unit']=Db::name('work_unit')->where('Id',$work_unitId)->value('name');
      $Info=explode(',',$stationInfo['examinee']);
       foreach ($Info as $key => $value) {
         $userInfo[$key]['id']=$value;
         $info=Db::name('user')->find($value);
         $userInfo[$key]['username']=$info['username']?$info['username']:'未知';
         $userInfo[$key]['phone']=$info['phone']?$info['phone']:'未知';
         $userInfo[$key]['employeeNumber']=$info['employeeNumber']?$info['employeeNumber']:'未知';
       }
       $resultInfo=Db::name('score_result')->where('stationId='.$aJson['stationId'])->select();
       if(!empty($resultInfo)){
          foreach ($resultInfo as $key => $value) {
              foreach ($userInfo as $k => $v) {
                 if($v['id']==$value['studentId']){
                      unset($userInfo[$k]);
                 }
              }
          }
       }
       $userInfo=array_values($userInfo);
       $info=array(
          'stationInfo'=>$stationInfo,
          'userInfo'=>$userInfo,
        );
       echo returnApiSuccess(null,$info);exit;


    }
    //点击考生获取考核表
    public function grade($aJson){
      if(!yanzheng($aJson['token'])){
             echo  returnApiError('token验证失败');
             exit;
        }
      if(!isset($aJson['stationId'])||!$aJson['stationId'] || !is_numeric($aJson['stationId'])){
        
        echo  returnApiError('输入考站Id有误');
        exit;
      }
      if(!isset($aJson['studentId'])||!$aJson['studentId'] || !is_numeric($aJson['userId'])){
       
        echo  returnApiError('用户Id有误');
        exit;
      }
      if(!isset($aJson['studentId'])||!$aJson['studentId'] || !is_numeric($aJson['studentId'])){
        
        echo  returnApiError('被评分考生Id有误');
        exit;
      }
      $stationInfo=Db::name('case_station')->where('Id='.$aJson['stationId'])->find();
      $userId=Db::name('user')->where('id='.$aJson['userId'])->value('id');
      $studentId=Db::name('user')->where('id='.$aJson['studentId'])->value('id');
      if(!$stationInfo['Id'] || !$userId || !$studentId){
        
        echo  returnApiError('没有此考站Id，审核用户id或实习生Id');
        exit;
      }
     
      $gradeInfo=Db::name('score_result')
                ->where('stationId',$aJson['stationId'])
                ->where('studentId',$aJson['studentId'])
                ->where('userId',$aJson['userId'])
                ->find();
      if($gradeInfo){
        echo  returnApiError('您已经为该考生打过分了');
        exit;
      }
       //返回考核表信息
      $cateInfo=Db::name('case_assessment')->where('stationId',$aJson['stationId'])->field('Id,describe,name')->select();
      $pointsInfo=Db::name('assessment_standard')->where('stationId',$aJson['stationId'])->where('whole','NEQ',1)->field('Id,describe,min,max')->select();
      if(empty($cateInfo) || empty($pointsInfo)){
        echo  returnApiError('暂无考核表信息');
        exit;
      }
     
      $info=array('cate'=>$cateInfo,'points'=>$pointsInfo,'stationInfo'=>$stationInfo);
      
      echo returnApiSuccess(null,$info);

       
      
    }
    //考生评分提交
    public function gradeSubmit($aJson){
      if(!yanzheng($aJson['token'])){
             echo  returnApiError('token验证失败');
             exit;
        }
      if(!isset($aJson['stationId'])|| !$aJson['stationId'] || !is_numeric($aJson['stationId'])){
        echo  returnApiError('输入考站Id有误');
        exit;
      }
      $stationInfo=Db::name('case_station')->where('Id='.$aJson['stationId'])->find();
      if(!$stationInfo['Id']){
        echo  returnApiError('此考站不存在');
        exit;
      }
      if(!isset($aJson['studentId'])|| !$aJson['studentId'] || !is_numeric($aJson['studentId'])){
        echo  returnApiError('考生Id有误');
        exit;
      }
      if(!isset($aJson['userId'])|| !$aJson['userId'] || !is_numeric($aJson['userId'])){
        echo  returnApiError('用户Id有误');
        exit;
      }
      if($stationInfo['examier']!=$aJson['userId']){
        echo  returnApiError('您不是该考站的考官');
        exit;
      }
      $studentId=Db::name('user')->where('id',$aJson['studentId'])->value('id');
      if(!$studentId){
        echo  returnApiError('考生Id不存在');
        exit;
      }
      $userId=Db::name('user')->where('id',$aJson['userId'])->value('id');
      if(!$userId){
        echo  returnApiError('用户Id不存在');
        exit;
      }
      $gradeInfo=Db::name('text_result')
                ->where('stationId',$aJson['stationId'])
                ->where('studentId',$aJson['studentId'])
                ->where('userId',$aJson['userId'])
                ->find();
      if($gradeInfo){
         //$all=Db::name('assessment_standard')->where('stationId',$aJson['stationId'])->where('whole',1)->field('Id,describe,score')->select();
       echo returnApiSuccess('考核评分已提交，请进行整体评分',null);
       exit;
      }
      $allInfo=Db::name('score_result')
                ->where('stationId',$aJson['stationId'])
                ->where('studentId',$aJson['studentId'])
                ->where('userId',$aJson['userId'])
                ->find();
      if($allInfo){
        echo  returnApiError('已完成全部评分');
        exit;
      }
      foreach ($aJson['scoreInfo'] as $key => $value) {
        if(!isset($value['result'])||!isset($value['case_assessmentId'])){
           echo  returnApiError('参数有误');
          exit;
        }
        if(!is_numeric($value['case_assessmentId']) || !is_numeric($value['result'])){
           echo  returnApiError('评分类别和结果应为数字');
          exit;
        }
      }
      $caseId=Db::name('case_station')->where('Id',$aJson['stationId'])->value('caseId');
      foreach ($aJson['scoreInfo'] as $key => $value) {
         $scoreInfo[$key]=[
                         'studentId'=>$aJson['studentId'],
                         'caseId'=>$caseId,
                         'userId'=>$aJson['userId'],
                         'case_assessmentId'=>$value['case_assessmentId'],
                         'stationId'=>$aJson['stationId'],
                         'result'=>$value['result'],
                         'addTime'=>date('Y-m-d H:i:s'),
                         ];
     }
     $re=Db::name('text_result')->insertAll($scoreInfo);
     if($re){
       
       //$all=Db::name('assessment_standard')->where('stationId',$aJson['stationId'])->where('whole',1)->field('Id,describe,score')->select();
       echo returnApiSuccess('分数提交成功',null);
       exit;
     }
     echo returnApiError('分数提交失败');
       exit;
    }
    //整体表现评分提交
    public function allSubmit($aJson){
      if(!yanzheng($aJson['token'])){
             echo  returnApiError('token验证失败');
             exit;
        }
      if(!isset($aJson['stationId'])|| !$aJson['stationId'] || !is_numeric($aJson['stationId'])){
        echo  returnApiError('输入考站Id有误');
        exit;
      }
      $stationId=Db::name('case_station')->where('Id='.$aJson['stationId'])->value('Id');
      if(!$stationId){
        echo  returnApiError('此考站不存在');
        exit;
      }
      if(!isset($aJson['studentId'])|| !$aJson['studentId'] || !is_numeric($aJson['studentId'])){
        echo  returnApiError('考生Id有误');
        exit;
      }
      if(!isset($aJson['userId'])|| !$aJson['userId'] || !is_numeric($aJson['userId'])){
        echo  returnApiError('用户Id有误');
        exit;
      }
      $studentId=Db::name('user')->where('id',$aJson['studentId'])->value('id');
      if(!$studentId){
        echo  returnApiError('考生Id不存在');
        exit;
      }
      $scoreInfo=Db::name('score_result')->where('stationId',$aJson['stationId'])->where('studentId',$aJson['studentId'])->find();
      if($scoreInfo){
         echo returnApiError('已经为该考生提交过整体表现了');
         exit;
      }
      $userId=Db::name('user')->where('id',$aJson['userId'])->value('id');
      if(!$userId){
        echo  returnApiError('用户Id不存在');
        exit;
      }
      if(!isset($aJson['result'])|| !$aJson['result'] || !is_numeric($aJson['result'])){
        echo  returnApiError('整体表现评分有误');
        exit;
      }

      $caseId=Db::name('case_station')->where('Id',$aJson['stationId'])->value('caseId');
      $info=['stationId'=>$aJson['stationId'],
             'userId'=>$aJson['userId'],
             'studentId'=>$aJson['studentId'],
             'caseId'=>$caseId,
             'result'=>$aJson['result'],
             'addTime'=>date('Y-m-d H:i:s')
             ];
      
      $re=Db::name('score_result')->insert($info);
      if($re){
        $result=$this->updateState($aJson['stationId']);
       if(!$result){
          Db::name('case_station')->where('Id',$aJson['stationId'])->setField('state',1);
       }
        echo returnApiSuccess('整体表现分数提交成功',null);
       exit;
      }
      echo returnApiError('整体表现分数提交失败');
      exit;
    }

    //更新考站考生评分状态
    public function updateState($stationId){
     $stationInfo=Db::name('case_station')->find($stationId);
     $Info=explode(',',$stationInfo['examinee']);

     foreach ($Info as $key => $value) {
       $userInfo[$key]['id']=$value;
      
     }
     foreach ($userInfo as $key => $value) {
       $user[]=$value['id'];
     }

     $resultInfo=Db::name('score_result')->where('stationId',$stationId)->column('studentId');
     
     $done=array_unique($resultInfo);

     $re=array_diff($user,$done);

     if($re){//还有考生没考完
          return  true;
          exit;
     }
     return  false;//考完了
     exit;
    }
    //点击考站返回已完成打分的结果
    public function doneInfo($aJson){
      if(!yanzheng($aJson['token'])){
             echo  returnApiError('token验证失败');
             exit;
        }
      if(!isset($aJson['stationId'])||!$aJson['stationId'] || !is_numeric($aJson['stationId'])){
      echo  returnApiError('考站Id错误');
      exit;
      }

      if(!isset($aJson['userId'])||!$aJson['userId'] || !is_numeric($aJson['userId'])){
      echo  returnApiError('用户Id错误');
      exit;
      }
      $stationInfo=Db::name('case_station')->where('Id',$aJson['stationId'])->find();
      if(!$stationInfo['Id']){
        echo  returnApiError('没有此考站Id');
        exit;
      }
      if($stationInfo['examier']!=$aJson['userId']){
        echo  returnApiError('您不是该考站的考官');
        exit;
      }
      $state=Db::name('case_station')->where('Id='.$aJson['stationId'])->value('state');//查看是否已经完成全部评分
      if(!$state){
        echo  returnApiError('对所有考生评分还未完成');
        exit;
      }
      $caseInfo=Db::name('text_case')->where('Id',$stationInfo['caseId'])->find();
      $stationInfo['text_case_name']=$caseInfo['name'];//考案名称
      $stationInfo['text_case_number']=$caseInfo['number'];//考案编号
      $Info=explode(',',$stationInfo['examinee']);
       foreach ($Info as $key => $value) {
         $userInfo[$key]['id']=$value;
         $info=Db::name('user')->find($value);
         $userInfo[$key]['username']=$info['username']?$info['username']:'未知';
         $userInfo[$key]['phone']=$info['phone']?$info['phone']:'未知';
         $userInfo[$key]['employeeNumber']=$info['employeeNumber']?$info['employeeNumber']:'未知';
         $userInfo[$key]['total_score']=Db::name('text_result')->where('studentId',$value)->where('stationId',$aJson['stationId'])->sum('result');
       }
       $info=array(
          'stationInfo'=>$stationInfo,
          'userInfo'=>$userInfo,
        );
       echo returnApiSuccess(null,$info);exit;

    }
    //点击已完成的考站详情下的某一考生评分结果
    public function doneResult($aJson){
      if(!yanzheng($aJson['token'])){
             echo  returnApiError('token验证失败');
             exit;
        }
      if(!isset($aJson['stationId'])||!$aJson['stationId'] || !is_numeric($aJson['stationId'])){
      echo  returnApiError('考站Id错误');
      exit;
      }
      $stationId=Db::name('case_station')->where('Id='.$aJson['stationId'])->value('Id');
      if(!$stationId){
        echo  returnApiError('此考站不存在');
        exit;
      }
      if(!isset($aJson['studentId'])|| !$aJson['studentId'] || !is_numeric($aJson['studentId'])){
        echo  returnApiError('考生Id有误');
        exit;
      }
      $studentId=Db::name('user')->where('id',$aJson['studentId'])->value('id');
      if(!$studentId){
        echo  returnApiError('考生Id不存在');
        exit;
      }
       //返回考核表信息
      $cateInfo=Db::name('case_assessment')->where('stationId',$aJson['stationId'])->field('Id,describe,name')->select();
      $pointsInfo=Db::name('assessment_standard')->where('stationId',$aJson['stationId'])->where('whole','NEQ',1)->field('Id,describe,score')->select();
      $resultInfo=Db::name('text_result')->where('stationId',$aJson['stationId'])->where('studentId',$aJson['studentId'])->select();
  //  $allInfo=Db::name('assessment_standard')->where('stationId',$aJson['stationId'])->where('whole',1)->field('Id,describe,score')->select();
      $allresult=Db::name('score_result')->where('stationId',$aJson['stationId'])->where('studentId',$aJson['studentId'])->value('result');
      
      $info=array('cate'=>$cateInfo,'points'=>$pointsInfo,'resultInfo'=>$resultInfo,'allresult'=>$allresult);
       
       echo returnApiSuccess(null,$info);exit;
      

    }


}
