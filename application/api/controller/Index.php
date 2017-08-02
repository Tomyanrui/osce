<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
use think\Request;
class Index extends controller
{
   public function index(){
        //接收POST数据
        $sData	= file_get_contents('php://input', 'r');
        
        $sData	= str_replace('"[','[',$sData);
        $sData	= str_replace(']"',']',$sData);
        $aJson	= json_decode($sData, true);
        $fp = fopen("revice.txt","a+");
        fwrite($fp,date('Y-m-d H:i:s')."\r\n");
        fwrite($fp,print_r($sData,true)."\r\n");
        fwrite($fp,print_r($aJson,true)."\r\n");
        fwrite($fp,'---------------------------------'."\r\n");
        fclose($fp);
       

        if(empty($aJson)){
        
            $test = 1;
            //登录接口
          //  $aJson = array('Pack'=>'Login' , 'Interface'=>'login','Username'=>13552453024,'Password'=>'123456');
            $aJson = array('Pack'=>'Questionnaire' , 'Interface'=>'caseList','userId'=>4,'token'=>'4c7cecd2040e3ec2a0eeb007231217ba');
          //  $aJson = array('Pack'=>'Questionnaire' , 'Interface'=>'getQuestion','caseId'=>2,'token'=>'4c7cecd2040e3ec2a0eeb007231217ba');
          // $aJson = array('Pack'=>'Questionnaire' , 'Interface'=>'answerSubmit','token'=>'4c7cecd2040e3ec2a0eeb007231217ba',
          //                'caseId'=>2,'studentId'=>4,
          //                'answer'=>array(
          //  array('questionId'=>1,'type'=>1,'answer'=>2),
          //  array('questionId'=>2,'type'=>2,'answer'=>'5,6'),
          //  array('questionId'=>3,'type'=>3,'answer'=>'文本答案'),
          //  array('questionId'=>4,'type'=>4,'answer'=>'10,8,9')
          //   )); 
           // $aJson = array('Pack'=>'Kaoan' , 'Interface'=>'index','caseId'=>1);
           // $aJson = array('Pack'=>'Kaoan' , 'Interface'=>'detail','stationId'=>3,'userId'=>2);
            // $aJson = array('Pack'=>'Kaoan' , 'Interface'=>'doneInfo','stationId'=>2,'userId'=>2);
           //  $aJson = array('Pack'=>'Kaoan' , 'Interface'=>'grade','stationId'=>15,'userId'=>11,'studentId'=>15);
           //  $aJson = array('Pack'=>'Kaoan' , 'Interface'=>'gradeSubmit','stationId'=>2,'userId'=>2,'studentId'=>7,'scoreInfo'=>array(array('case_assessmentId'=>4,'result'=>16),array('case_assessmentId'=>3,'result'=>20)));
          //  $aJson = array('Pack'=>'Kaoan' , 'Interface'=>'allSubmit','stationId'=>2,'userId'=>2,'studentId'=>7,'result'=>30);
           //  $aJson = array('Pack'=>'Kaoan' , 'Interface'=>'doneResult','stationId'=>1,'studentId'=>4);
            // $aJson = array('Pack'=>'Login' , 'Interface'=>'sendMessage','phone'=>'15535730438');
            // $aJson = array('Pack'=>'Login' , 'Interface'=>'changePassword','phone'=>15949208653,'password'=>'12345678','code'=>'880908');
          //  echo json_encode($aJson,JSON_UNESCAPED_UNICODE);exit;
        }

        $Control = $aJson['Pack'];


        $Class   = $aJson['Interface'];

        if(!$Control or !$Class){ return false;}
        $aResult = controller($Control);
        if(!$aResult){
          echo  returnApiError('接口异常错误（控制器不存在）');
          exit;
        }
        $sResult = $aResult->$Class($aJson);
        
        
        


    }
}
