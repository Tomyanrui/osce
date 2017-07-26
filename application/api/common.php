<?php
use think\Db;
function yanzheng($token){
    if(db('user')->where('token',$token)->find()){
    	return true;
    	exit;
    }
    return false;
}