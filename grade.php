<?php
session_start();
/**
 * Created by mohuishou<1@lailin.xyz>.
 * User: mohuishou<1@lailin.xyz>
 * Date: 2016/7/12 0012
 * Time: 2:32
 */
require __DIR__ . '/vendor/autoload.php';
if(!isset($_SESSION['uid'])){
    echo json_encode([
        'status'=>20001,
        'msg'=>'尚未登录',
    ]);
    exit();
}

if(isset($_POST['year'])&&$_POST['term']){
    $grade=new \Mohuishou\Lib\Swugpa();
    $grade->_uid=$_SESSION['uid'];
    $grade->grade($_POST['year'],$_POST['term']);
}else{
    echo json_encode([
        'status'=>20002,
        'msg'=>'参数错误'
    ]);
    return false;
}



