<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Db;
// 应用公共文件
/*************************** api开发辅助函数 **********************/

/**
 * @param null $msg  返回正确的提示信息
 * @param flag success CURD 操作成功
 * @param array $data 具体返回信息
 * Function descript: 返回带参数，标志信息，提示信息的json 数组
 *
 */
function returnApiSuccess($msg = null,$data = array()){
  $result = array(
    'flag' => 'Success',
    'msg' => $msg,
    'data' =>$data
  );
  return json_encode($result,JSON_UNESCAPED_UNICODE);
}

/**
 * @param null $msg  返回具体错误的提示信息
 * @param flag success CURD 操作失败
 * Function descript:返回标志信息 ‘Error'，和提示信息的json 数组
 */
function returnApiError($msg = null){
  $result = array(
    'flag' => 'Error',
    'msg' => $msg,
  );
  return  json_encode($result,JSON_UNESCAPED_UNICODE);
}

/**
 * @param null $msg  返回具体错误的提示信息
 * @param flag success CURD 操作失败
 * Function descript:返回标志信息 ‘Error'，和提示信息，当前系统繁忙，请稍后重试；
 */
function returnApiErrorExample(){
  $result = array(
    'flag' => 'Error',
    'msg' => '当前系统繁忙，请稍后重试！',
  );
  return json_encode($result,JSON_UNESCAPED_UNICODE);
}

/**
 * @param null $data
 * @return array|mixed|null
 * Function descript: 过滤post提交的参数；
 *
 */

 function checkDataPost($data = null){
  if(!empty($data)){
    $data = explode(',',$data);
    foreach($data as $k=>$v){
      if((!isset($_POST[$k]))||(empty($_POST[$k]))){
        if($_POST[$k]!==0 && $_POST[$k]!=='0'){
          returnApiError($k.'值为空！');
        }
      }
    }
    unset($data);
    $data = I('post.');
    unset($data['_URL_'],$data['token']);
    return $data;
  }
}

/**
 * @param null $data
 * @return array|mixed|null
 * Function descript: 过滤get提交的参数；
 *
 */
function checkDataGet($data = null){
  if(!empty($data)){
    $data = explode(',',$data);
    foreach($data as $k=>$v){
      if((!isset($_GET[$k]))||(empty($_GET[$k]))){
        if($_GET[$k]!==0 && $_GET[$k]!=='0'){
          returnApiError($k.'值为空！');
        }
      }
    }
    unset($data);
    $data = I('get.');
    unset($data['_URL_'],$data['token']);
    return $data;
  }
}
function is_login(){
    $user = session('userId');
    if (empty($user)) {
        return 0;
    } else {
        return  session('userId');
    }
}
function role(){
   
   $role=Db::table('js_user')->where('id',UID)->find();
   
 
   return $role['roleId'];
}
function search($name){
      if (!mb_check_encoding( $name, 'utf-8')){
                $name = iconv('gbk', 'utf-8',  $name);
             }
          return $name;
}
function sendMessage($code,$mobile){
  include EXTEND_PATH.'Alidayu/TopSdk.php';//这是载入阿里大鱼
 
  $appkey = '24357159';
  $secret = 'b546ea22bea03d18dcf3c09b6fd56335';
  $c = new \TopClient();
  $c->appkey = $appkey;
  $c->secretKey = $secret;
  $c->format = 'json';
  $req = new \AlibabaAliqinFcSmsNumSendRequest;
  $req->setExtend($code);
  $req->setSmsType('normal');
  $req->setSmsFreeSignName('临床测验管理系统'); //发送的签名
  $req->setSmsParam('{"code":"'.$code.'"}');//根据模板进行填写
  $req->setRecNum($mobile);//接收着的手机号码

  $req->setSmsTemplateCode('SMS_71155115');
  $resp = $c->execute($req);
 
  if($resp->result->success){
    return true;
   }else{return false;}
}
function date_replace($date){
    $date3=explode('/',$date);
    $date4=$date3[2].'-'.$date3[1].'-'.$date3[0];
  return $date4;
}
function replace_date($date){
    $date3=explode('-',$date);
    $date4=$date3[2].'/'.$date3[1].'/'.$date3[0];
    return $date4;
}
//查看考站是否已经完成并归档
function isGuidang($stationId){
  $status=Db::table('js_case_station')->where('Id',$stationId)->value('status');
  if($status){
    return false;
    exit;
  }
  return true;
}



