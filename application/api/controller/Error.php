<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
class Error extends controller
{
    public function index(){
    	echo  returnApiError('接口错误');
        exit;
    }
}