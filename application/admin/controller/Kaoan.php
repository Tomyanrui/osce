<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Request;
class Kaoan extends Admin
{
   //添加考案申请
	public function add(){
		$model=db('text_case');
		$work_unitId=db('user')->where('id',session('userId'))->value('work_unitId');
        if(request()->isPost()){
        	if(role()!=1){
			      return $this->error('您没有权限');
       		  exit;
		       }   
            $textTime=strtotime(input('post.textTime'));
       	    $firstdoneTime=strtotime(input('post.firstdoneTime'));
       	    $now=time();
            
       	    if(($textTime<$now) || ($firstdoneTime<$now)){
       		  return $this->error('测验日期或预订测验日期不能在今天之前');
       		  exit;
       	    }
       	    if($textTime<$firstdoneTime){
       		  return $this->error('测验日期不能在预订测验日期之前');
       		  exit;
       	    }
        	if(input('post.Id')){
               $uid=$model->where('Id',input('post.Id'))->value('uId');
               if($uid != session('userId')){
                   return $this->error('非本人不得修改');
                   exit;
               }
               $arr=['name'=>input('post.name'),
                     'number'=>input('post.number'),
                     'textTime'=>input('post.textTime'),
                     'firstdoneTime'=>input('post.firstdoneTime'),
                     'togherMeeting'=>input('post.togherMeeting')
                     ]; 
               $re=$model->where('Id',input('post.Id'))->update($arr);
               if($re){
                  return $this->success('修改成功','lst',1);
               }
               return $this->error('修改失败');
             }else{
       	       $arr=['category'=>input('post.category'),
                     'name'=>input('post.name'),
                     'number'=>input('post.number'),
                     'uId'=>session('userId'),
                     'work_unitId'=>$work_unitId,
                     'textTime'=>input('post.textTime'),
                     'togherMeeting'=>input('post.togherMeeting'),
                     'firstdoneTime'=>input('post.firstdoneTime'),
                     'addTime'=>date('Y-m-d H:i:s')
               ]; 
               $re=$model->insert($arr);
               if($re){
                  return $this->success('添加成功','lst',1);
               }
               return $this->error('添加失败');
            }
        }
        
		$cate=db('work_unit')->where('Id',$work_unitId)->value('category');
    if(input('id')){
      $caseInfo=db('text_case')->where('Id',input('id'))->find();
     
   

      $this->assign('case',$caseInfo);
    }
		$this->assign('cate',$cate);
    return $this->fetch();
	}
	//考案列表
	public function lst(){
    $case=array();
    if(role()==1){
		$work_unitId=db('user')->where('id',session('userId'))->value('work_unitId');

		$case=db('text_case')->where('work_unitId',$work_unitId)->where('status',0)->select();
    //return db('text_case')->getlastSql();
		foreach($case as $key=>$val){
            $case[$key]['stationNumber']=db('case_station')->where('caseId',$val['Id'])->count();
		}
    }
    if(role()==2){
        $caseId=Db::name('case_station')->where('teach',session('userId'))->whereor('examier',session('userId'))->column('caseId');
        $caseid=array_unique($caseId);
        foreach ($caseid as $key => $value) {
          $caseInfo=Db::name('text_case')->where('status',0)->find($value);
           if(!db('text_case')->where('Id',$value)->value('status') && $caseInfo){
             $case[$key]=$caseInfo;
             $case[$key]['stationNumber']=db('case_station')->where('caseId',$value)->count();
             
           }
          
        }
    }
   
		$this->assign('case',$case);
		return $this->fetch();
	}
	//删除考案
	public function delCase(){
		if(!input('caseId')){
         return $this->error('数据异常');
       }
    $stationInfo=db('case_station')->where('caseId',input('caseId'))->find();
    if(!empty($stationInfo)){
      return $this->error('该考案已添加考站，不得删除');
      
    }
    $re=db('text_case')->delete(input('caseId'));
    if($re){
      return $this->success('删除成功','lst',1);
    }
	}
	//考站列表
	public function station(){
	   if(!input('caseId')){
         return $this->error('数据异常');
       }
       //共识会开不开
       $togherMeeting=db('text_case')->where('Id',input('caseId'))->value('togherMeeting');

       $model=db('case_station');
       $station=array();
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
	//添加考站
	public function addStation(){

       if(!input('caseId') && !input('Id')){
         return $this->error('数据异常');
       }
       $model=db('case_station');
       if(request()->isPost()){
         $isSp=input('post.sper')? 1:0;
          if(input('post.Id')){
             if(!isGuidang(input('post.Id'))){
              return $this->error('已归档，不得改动');
             }
             $caseId=$model->where('Id',input('post.Id'))->value('caseId');
             /*后期作业人员开始*/
             if(role()==2){
                $teach=$model->where('Id',input('post.Id'))->value('teach');
                if($teach!=session('userId')){
                  return $this->error('您不是该考站的前期作业人员不得提交');
                }
                $rule='';
                foreach($_POST['examinee'] as $key => $value){
                   $rule.=$value.',';
                }
           
                $rules=trim($rule,',');
               
                $arr=['examinee'=>$rules];
              $re=$model->where('Id',input('post.Id'))->update($arr);
               if($re){
                 return $this->redirect('station', ['caseId' =>$caseId]);
               }
              return $this->error('修改失败');
             }
            /*后期作业人员结束*/
           
             $arr=['name'=>input('post.name'),
                'text_core'=>input('post.text_core'),
                'teach'=>input('post.teach'),
                'location'=>input('post.location'),
                'examier'=>input('post.examier'),
                'isSp'=>$isSp,
                'sper'=>input('post.sper')
                ];
             $re=$model->where('Id',input('post.Id'))->update($arr);
            if($re){
                 return $this->redirect('station', ['caseId' =>$caseId]);
               }
              return $this->error('修改失败');
          }else{
          $arr=['name'=>input('post.name'),
                'text_core'=>input('post.text_core'),
                'teach'=>input('post.teach'),
                'location'=>input('post.location'),
                'addTime'=>date('Y-m-d H:i:s'),
                'caseId'=>input('caseId'),
                'examier'=>input('post.examier'),
                'isSp'=>$isSp,
                'sper'=>input('post.sper')
                ];
          $re=$model->insert($arr);
          if($re){
                 
                 return $this->redirect('station', ['caseId' =>input('caseId')]);
               }
          return $this->error('添加失败');
          }
       }
    
       $this->assign('user',db('user')->where('roleId',2)->select());
       $this->assign('shixi',db('user')->where('roleId',3)->select());
       if(input('Id')){
         $stationInfo=$model->find(input('Id'));
         $this->assign('station',$stationInfo);
       }
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
    //删除考站考核表评分标准
    public function delscore(){
      $stationId=db('assessment_standard')->where('Id',input('Id'))->value('stationId');
      $stationInfo=db('case_station')->where('Id',$stationId)->find();
      if($stationInfo['teach']!=session('userId')){
        return  json_encode(3);
        exit;
      }
      if($stationInfo['status']==1){//根据考站状态判断是否要进行修改
        return  json_encode(5);
        exit;
      }
      $re=db('assessment_standard')->delete(input('Id'));
      if($re){
        return  json_encode(1);
        exit;
      }
       return  json_encode(7);
        exit;

    }
    //删除考站考核表评分项
    public function  delcate(){
      $stationId=db('case_assessment')->where('Id',input('Id'))->value('stationId');
      $stationInfo=db('case_station')->where('Id',$stationId)->find();
      if($stationInfo['teach']!=session('userId')){
        return  json_encode(3);
        exit;
      }
      if($stationInfo['status']==1){//根据考站状态判断是否要进行修改
        return  json_encode(5);
        exit;
      }
       $re=db('case_assessment')->delete(input('Id'));
      if($re){
        return  json_encode(1);
        exit;
      }
       return  json_encode(7);
        exit;
    }
    //添加评分标准信息
    public function addgradescore(){
      $model=db('assessment_standard');
      if(request()->isPost()){
        if(!isGuidang(input('post.stationId'))){
              return $this->error('已归档，不得改动');
             }
        if(input('standardId')){

           $arr=['describe'=>input('post.describe'),
              'min'=>input('post.min'),
              'max'=>input('post.max')
               ];
             $re=$model->where('Id',input('standardId'))->update($arr);
          if($re){
          return $this->redirect('kaohe', ['stationId' =>input('post.stationId')]);
          }
        }else{
          $arr=['describe'=>input('post.describe'),
              'min'=>input('post.min'),
              'max'=>input('post.max'),
              'stationId'=>input('post.stationId'),
              'caseId'=>db('case_station')->where('Id',input('post.stationId'))->value('caseId'),
              'addTime'=>date('Y-m-d H:i:s')
              ];
          $re=$model->insert($arr);
          if($re){
          return $this->redirect('kaohe', ['stationId' =>input('post.stationId')]);
          }
        }
      }

    }
    //编辑评分标准信息
    public function editgradescore(){
      $model=db('assessment_standard');
      if(!input('standardId')){
        return $this->error('数据异常');
      }
      $standardInfo=$model->where('Id',input('standardId'))->find();
      return  json_encode($standardInfo,JSON_UNESCAPED_UNICODE);
        exit;
    }
    public function addgradecate(){
      $model=db('case_assessment');
      if(request()->isPost()){
        if(!isGuidang(input('post.stationId'))){
              return $this->error('已归档，不得改动');
             }
        if(input('assessmentId')){

          $arr=[
               //'describe'=>input('post.describe'),
                 'name'=>input('post.name')
                ];
          $re=$model->where('Id',input('assessmentId'))->update($arr);
          if($re){
          return $this->redirect('kaohe', ['stationId' =>$model->where('Id',input('assessmentId'))->value('stationId')]);
          }
        }else{
          $arr=[//'describe'=>input('post.describe'),
              'name'=>input('post.name'),
              
              'stationId'=>input('post.stationId'),
              'caseId'=>db('case_station')->where('Id',input('post.stationId'))->value('caseId'),
              'addTime'=>date('Y-m-d H:i:s')
              ];
          $re=$model->insert($arr);
          if($re){
          return $this->redirect('kaohe', ['stationId' =>input('post.stationId')]);
          }
        }
        


      }

    }
    //编辑评分类别信息
    public function editgradecate(){
      $model=db('case_assessment');
      if(!input('assessmentId')){
        return $this->error('数据异常');
      }
      $assessmentInfo=$model->where('Id',input('assessmentId'))->find();
      return  json_encode($assessmentInfo,JSON_UNESCAPED_UNICODE);
      exit;
    }
    //考官添加考生
    public function addStudent(){
      if(!input('stationId')){
        return  json_encode(7);
        exit;
      }
      $stationInfo=db('case_station')->find(input('stationId'));
      if($stationInfo['examier']!=session('userId')){
        return  json_encode(6);
        exit;
      }
      if($stationInfo['status']){
       return  json_encode(5);
        exit;
      }
      if(db('user')->where('phone',input('phone'))->find()){
        return  json_encode(3);
        exit;
      }
        $arr=['username'=>input('name'),
              'phone'=>input('phone'),
              'employeeNumber'=>input('employeeNumber'),
              'roleId'=>3,
              'work_unitId'=>db('user')->where('Id',session('userId'))->value('work_unitId'),
              'password'=>md5('123456'),
              'registerTime'=>date('Y-m-d H:i:s')
        ];
        $re=db('user')->insert($arr);
        if($re){
          return  json_encode(2);
          exit;
        }
      

    }
    //共识会安排
    public function anpai(){
      if(!input('caseId')){
        return $this->error('数据异常');
        exit;
      }
      $rule='';
      foreach($_POST['meetingPerson'] as $key => $value){
              $rule.=$value.',';
            }
     $meetingPerson=trim($rule,',');
     if(request()->isPost()){
      $arr=['location'=>input('post.location'),
            'meetingDate'=>input('post.meetingDate'),
            'meetingPerson'=>$meetingPerson
           ];
      $re=db('text_case')->where('Id',input('caseId'))->update($arr);
      if($re){
        return $this->redirect('station',['caseId'=>input('caseId')]);
      }
     }

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
    //考案物品添加
    public function goodsAdd(){
      if(!input('stationId') && !input('goodsId')){
        return $this->error('数据异常');
        exit;
      }
      $model=db('goods');
      if(request()->isPost()){
        if(!isGuidang(input('stationId'))){
              return $this->error('已归档，不得改动');
             }
        if(input('goodsId')){
           $arr=['name'=>input('post.name'),
              'number'=>input('post.number'),
              
              'describe'=>input('post.describe')
             
             ];
        $re=$model->where('Id',input('goodsId'))->update($arr); 
        }else{
          $arr=['name'=>input('post.name'),
              'number'=>input('post.number'),
              'type'=>input('post.type'),
              'describe'=>input('post.describe'),
              'stationId'=>input('post.stationId'),
              'caseId'=>db('case_station')->where('Id',input('post.stationId'))->value('caseId'),
              'addTime'=>date('Y-m-d H:i:s')
             ];
        $re=$model->insert($arr);
        }
        
        if($re){
          return $this->redirect('goods',['stationId'=>input('stationId'),'type'=>input('post.type')]);
        }

      }
    }
    //编辑靠站物品
    public function  editgoods(){
      $model=db('goods');
      if(!input('goodsId')){
        return $this->error('数据异常');
      }
      $goods=$model->where('Id',input('goodsId'))->find();
      return  json_encode($goods,JSON_UNESCAPED_UNICODE);
      exit;
    }
    //删除物品
    public function delgoods(){
      if(role()!=1){
        return  json_encode(4);
        exit;
      }
      if(!input('goodsId')){
        return  json_encode(7);
        exit;
      }
      $stationId=db('goods')->where('Id',input('goodsId'))->value('stationId');

      if(db('case_station')->where('Id',$stationId)->value('status')){
         return  json_encode(5);
        exit;
      }
      $re=db('goods')->delete(input('goodsId'));
      if($re){
        return  json_encode(1);
        exit;
      }
      return  json_encode(9);
        exit;
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
 
      if(request()->isPost()){
         if($stationInfo['status']){
          return $this->error('已归档，不得改动');
           exit;
         }
        
         if($stationInfo['teach']!=session('userId')){
           return $this->error('您不是该考站的前期工作人员，不能编辑');
           exit;
         }
         $model=db('text_detail');
         if(input('detailId')){
            $arr=['name'=>input('post.name'),
                 'sex'=>input('post.sex'),
                'age'=>input('post.age'),
                'job'=>input('post.job'),
                'school'=>input('post.school'),
                'merry'=>input('post.merry'),
                'context'=>input('post.context'),
                'person'=>input('post.person'),
                'now_medicines'=>input('post.now_medicines'),
                'before'=>input('post.before'),
                'menses'=>input('post.menses'),
                'family'=>input('post.family'),
                'describe'=>input('post.describe')
                ];
                $re=$model->where('Id',input('detailId'))->update($arr);
                if($re){
                   return $this->redirect('station', ['caseId' =>$stationInfo['caseId']]);
                }
                return $this->error('修改失败');
         }else{

          $arr=['name'=>input('post.name'),
                 'stationId'=>input('stationId'),
                 'caseId'=>$stationInfo['caseId'],
                'sex'=>input('post.sex'),
                'age'=>input('post.age'),
                'job'=>input('post.job'),
                'school'=>input('post.school'),
                'merry'=>input('post.merry'),
                'context'=>input('post.context'),
                'person'=>input('post.person'),
                'now_medicines'=>input('post.now_medicines'),
                'before'=>input('post.before'),
                'menses'=>input('post.menses'),
                'family'=>input('post.family'),
                'addTime'=>date('Y-m-d H:i:s'),
                'describe'=>input('post.describe')
                ];
          $re=$model->insert($arr);
          if($re){
            return $this->redirect('station', ['caseId' =>$stationInfo['caseId']]);
          }
          return $this->error('添加失败');
         }
      }
      if(input('detailId')){
        $this->assign('detail',db('text_detail')->find(input('detailId')));
      }
      $this->assign('caseId',$stationInfo['caseId']);
      return $this->fetch();
    }
	  //考站检查完成后的归档
    public function stationGuidang(){
      if(!input('stationId')){
        return $this->error('参数错误');
      }
      //查看当前考站下的考核表信息
      $cateInfo=db('case_assessment')->where('stationId',input('stationId'))->find();
      $standardInfo=db('assessment_standard')->where('stationId',input('stationId'))->find();
      if(empty($cateInfo)||empty($standardInfo)){
          return $this->error('还没有完成考核表设计');
      }
      $detail=db('text_detail')->where('stationId',input('stationId'))->value('Id');
      if(!$detail){
        return $this->error('还没有完成教案设计');
      }
      $examinee=db('case_station')->where('Id',input('stationId'))->value('examinee');
      if(!$examinee){
        return $this->error('还没有选择实习生');
      }
      $goods_kaozhan=db('goods')->where('stationId',input('stationId'))->where('type',1)->find();
      $goods_zidai=db('goods')->where('stationId',input('stationId'))->where('type',2)->find();
      if(empty($goods_kaozhan) || empty($goods_zidai)){
        return $this->error('还没有完成物品准备');
      }
      $re=db('case_station')->where('Id',input('stationId'))->update(['status'=>1]);
      if($re){
        return $this->redirect('station',['caseId'=>db('case_station')->where('Id',input('stationId'))->value('caseId')]);
      }

    }
    //删除考站
    public function delStation(){
      if(!input('stationId')){
        return $this->error('数据异常','lst',1);
        exit;
      }
      $caseid=db('case_station')->where('Id',input('stationId'))->value('caseId');
      db('goods')->where('stationId',input('stationId'))->delete();//物品
      db('case_assessment')->where('stationId',input('stationId'))->delete();//考核评分项
      db('assessment_standard')->where('stationId',input('stationId'))->delete();//考核评分标准
      db('text_detail')->where('stationId',input('stationId'))->delete();//教案设计
      $re=db('case_station')->delete(input('stationId'));
      if($re){
        return $this->redirect('station',['caseId'=>$caseid]);
      }
         
    }
    //考案归档
    public function caseGuidang(){
      if(!input('caseId')){
         return $this->error('数据异常');
       }
       $stationInfo=db('case_station')->where('caseId',input('caseId'))->select();
       if(empty($stationInfo)){
        return $this->error('还未添加考站信息，不得归档');
       }
       foreach ($stationInfo as $key => $value) {
          if(!$value['status']){
            return $this->error('考站信息还未完善，不得归档');
            exit;
          }
       }
       $re=db('text_case')->where('Id',input('caseId'))->update(['status'=>1]);
       if($re){
        return $this->success('归档完成');
       }
    }
    //判断考核表评分项分数区间是否重叠
    public function checkRight(){

      if(!input('stationId') || !is_numeric(input('min')) || !is_numeric(input('max'))){
        return  json_encode(1);//参数错误
        exit;
      }

      $standardInfo=db('assessment_standard')->where('stationId',input('stationId'))->where('whole',0)->select();
      if(empty($standardInfo)){
        return  json_encode(2);//可以添加，成功
        exit;
      }
      foreach ($standardInfo as $key => $value){
        if(input('min')>=$value['min'] && input('min')<=$value['max'] && input('standardId')!=$value['Id']){
          return  json_encode(3);//最小值在不能再已有分数区间内
          exit;
        }
        if(input('max')>=$value['min'] && input('max')<=$value['max'] && input('standardId')!=$value['Id']){
          return  json_encode(4);//最大值在不能再已有分数区间内
          exit;
        }

      }
      return  json_encode(2);//可以添加，成功
      exit;

    }
}