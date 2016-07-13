<?php
namespace Mohuishou\Lib;
use Curl\Curl;

class Swugpa{


    /**
     * 地址：查询是否可以登录校内门户
     * @var string
     */
    protected $_url_login_progress="http://i.swu.edu.cn/remote/service/process";

    /**
     * 地址：登录校内门户，并查询是否登录成功
     * @var string
     */
    protected $_url_login_college="https://uaaap.swu.edu.cn/cas/login";

    /**
     * 地址：登录教务处
     * @var string
     */
    protected $_url_login_jwc="https://uaaap.swu.edu.cn/cas/login?service=http%3A%2F%2Fjw.swu.edu.cn%2Fssoserver%2Flogin%3Fywxt%3Djw";

    /**
     * 地址：查询成绩
     * @var string
     */
    protected $_url_grade="http://jw.swu.edu.cn/jwglxt/cjcx/cjcx_cxDgXscj.html";

    /**
     * 校内门户用户名
     * @var string
     */
    protected $_username;

    /**
     * 校内门户密码
     * @var string
     */
    protected $_password;

    protected $_jwc_cookie;

    protected $_key;
    public $_uid;


    /**
     * @author mohuishou<1@lailin.xyz>
     * @return bool
     */
    public function login($username,$password){
        $this->_username=$username;
        $this->_password=$password;
        if(!$this->getKey()){
            $this->returnData('帐号密码错误',10058);
            return false;
        }

        $this->loginCollege($this->_key);

        $this->returnData('登录成功，查询中......');

        return true;

    }

    public function grade($year,$term){

        if($this->loginJwc()){
            $this->getGrade($this->_jwc_cookie,$this->_uid,$year,$term);
        };
    }

    /**
     * @author mohuishou<1@lailin.xyz>
     * @return bool
     */
    public function getKey()
    {
        $curl=new Curl();
        $serviceInfo='{"serviceAddress":"https://uaaap.swu.edu.cn/cas/ws/acpInfoManagerWS","serviceType":"soap","serviceSource":"td","paramDataFormat":"xml","httpMethod":"POST","soapInterface":"getUserInfoByUserName","params":{"userName":"'.$this->_username.'","passwd":"'.$this->_password.'","clientId":"yzsfwmh","clientSecret":"1qazz@WSX3edc$RFV","url":"http://i.swu.edu.cn"},"cDataPath":[],"namespace":"","xml_json":""}';
        $curl->get($this->_url_login_progress,array(
            "serviceInfo"=>$serviceInfo
        ));
        if ($curl->error) {
            echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage;
            return false;
        }
        else {
            if(isset($curl->response->data->getUserInfoByUserNameResponse->return->info->attributes->tgt)){
                $key=$curl->response->data->getUserInfoByUserNameResponse->return->info->attributes->tgt;
                $key=base64_decode($key);
                $this->_key=$key;
                $this->_uid=$curl->response->data->getUserInfoByUserNameResponse->return->info->id;
                return true;
            }
            return false;
        }
    }

    /**
     * @author mohuishou<1@lailin.xyz>
     * @param $key
     */
    public function loginCollege($key)
    {
        $curl=new Curl();
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setCookieJar(__DIR__.'/../cookie/college-'.$this->_uid.'.cookie');
        $curl->get($this->_url_login_college,array(
           'CTgtId'=> $key
        ));
        if ($curl->error) {
            echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage;
        }
        else {
            $curl->close();
        }
    }

    /**
     * @author mohuishou<1@lailin.xyz>
     */
    public function loginJwc()
    {
        $curl=new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->setOpt(CURLOPT_ENCODING , 'gzip');
        $curl->setCookieFile(__DIR__.'/../cookie/college-'.$this->_uid.'.cookie');
        $curl->get($this->_url_login_jwc);
        if ($curl->error) {
            echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage;
        }
        else {
            $this->_jwc_cookie=$curl->getResponseCookies();
            return true;
        }
    }

    /**
     * @author mohuishou<1@lailin.xyz>
     * @param $jwc_cookie
     * @param $uid
     */
    public function getGrade($jwc_cookie,$uid,$year,$term)
    {
        $grade_param=[
            "doType"=>"query",
            "sessionUserKey"=>$uid,
            "xnm"=>$year,
            "xqm"=>$term,
            "queryModel.showCount"=>40,
            "queryModel.currentPage"=>1
        ];

        $curl=new Curl();
        $curl->setHeader('X-Requested-With', 'XMLHttpRequest');
        foreach ($jwc_cookie as $k => $v){
            $curl->setCookie($k,$v);
        }
        $curl->post($this->_url_grade,$grade_param);
        if ($curl->error) {
            echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage;
        }
        else {
            if(empty($curl->response->items)){
                echo json_encode([
                    'status'=>20005,
                    'msg'=>'该学期暂时没有成绩'
                ]);
                return false;
            }

            $re=$curl->response->items;

            $data['name']=$re[0]->xm;
            $data['time']=$re[0]->xnmmc;
            $data['term']=$re[0]->xqmmc;
            $data['nj']=$re[0]->njdm_id;
            $data['sid']=$re[0]->xh;

            $grade_data=[];
            $sum_grade=0;
            $sum_require_grade=0;
            $sum_gpa=0;
            $sum_require_gpa=0;
            $sum_xf=0;
            $sum_require_xf=0;

            foreach ($re as $k => $v){
                $grade_data[$k]['class_name']=$v->kcmc;
                $grade_data[$k]['grade']=$v->cj;
                $grade_val=$this->gradeSwap($v->cj);

                //所有成绩换算为分数
                $grade_data[$k]['grade_val']=$grade_val;

                $grade_data[$k]['gpa']=$v->jd;
                $grade_data[$k]['credit']=$v->xf;
                $sum_xf+=$v->xf;
                //总分和总的绩点
                $sum_grade+=$grade_val*$v->xf;
                $sum_gpa+=$v->jd*$v->xf;

                if(isset($v->kcxzmc)){
                    if(in_array($v->kcxzdm,['01','03','04'])){
                        $grade_data[$k]['type']=1;//该门课程为必修
                        $grade_data[$k]['type_name']='必修';
                        //必修学分和绩点
                        $sum_require_grade+=$grade_val*$v->xf;
                        $sum_require_gpa+=$v->jd*$v->xf;
                        $sum_require_xf+=$v->xf;
                    }else{
                        $grade_data[$k]['type']=2;//该门课程为选修
                        $grade_data[$k]['type_name']='选修';
                    }
//                    $grade_data[$k]['type_name']=$v->kcxzmc;
                }else{
                    //必修学分和绩点
                    $sum_require_grade+=$grade_val*$v->xf;
                    $sum_require_gpa+=$v->jd*$v->xf;
                    $sum_require_xf+=$v->xf;
                    $grade_data[$k]['type']=1;//该门课程为必修
                    $grade_data[$k]['type_name']='必修';
                }
            }
            $data['avg']['all']['grade']=round($sum_grade/$sum_xf,2);
            $data['avg']['all']['gpa']=round($sum_gpa/$sum_xf,2);
            $data['avg']['require']['grade']=round($sum_require_grade/$sum_require_xf,2);
            $data['avg']['require']['gpa']=round($sum_require_gpa/$sum_require_xf,2);
            $data['grade']=$grade_data;
            $data['status']=200;

            echo json_encode($data);
        }
    }

    public function returnData($msg,$status=200,$data=null){
        echo json_encode( ['status'=>$status, 'msg'=>$msg, 'data'=>$data]);
        return false;
    }

    /**
     * 成绩等级分数换算表
     * @author mohuishou<1@lailin.xyz>
     * @param $g
     * @return int
     */
    public function gradeSwap($g){
        $g_swap=0;
        if(is_numeric($g)){
            return $g;
        }
        switch ($g){
            case 'A':
                $g_swap=95;
                break;
            case 'B':
                $g_swap=85;
                break;
            case 'C':
                $g_swap=75;
                break;
            case 'D':
                $g_swap=65;
                break;
            case 'E':
                $g_swap=55;
                break;
            default:
                $g_swap=75;
                break;
        }
        return $g_swap;
    }



}
