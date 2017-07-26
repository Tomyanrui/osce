<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
class Test{
  public function test(){
  include EXTEND_PATH.'Alidayu/TopSdk.php';//这是载入阿里大鱼
  $code=rand(100000,999999);
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
  $req->setRecNum('15034316769');//接收着的手机号码

  $req->setSmsTemplateCode('SMS_71155115');
  $resp = $c->execute($req);
 
  if($resp->result->success){
    echo 'chenggong';
   }else{echo '失败';}
  }
}