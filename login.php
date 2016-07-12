<?php
    session_start();
    require __DIR__ . '/vendor/autoload.php';
    if(isset($_POST['send'])){
        if($_POST['send']=='send'){
            $username=$_POST['username'];
            $password=$_POST['password'];
            $a=new Mohuishou\Lib\Swugpa();
            $a->login($username,$password);
            $_SESSION['uid']=$a->_uid;
        }
    }

