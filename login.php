<?php
    session_start();
    require __DIR__ . '/vendor/autoload.php';
    if(isset($_POST['send'])){
        if($_POST['send']=='send'){
            $username=$_POST['username'];
            $password=$_POST['password'];
            $a=new Mohuishou\Lib\Swugpa();
            $re=$a->login($username,$password);
            if($re['status']==200){
                $_SESSION['uid']=$re['data']['uid'];
                $_SESSION['college_cookie']=$re['data']['college_cookie'];
            }
            echo json_encode($re);
        }
    }

