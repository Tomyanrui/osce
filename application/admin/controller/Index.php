<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Request;
class Index extends Admin
{
    public function index()
    {   
    	return $this->fetch();
    }
}
