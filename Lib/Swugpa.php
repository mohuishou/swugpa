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

    /**
     * 教务处cookie
     * @var array
     */
    protected $_jwc_cookie;

    /**
     * 校内门户统一登录cookie
     * @var array
     */
    protected $_college_cookie;

    /**
     * 登录需要的key
     * @var
     */
    protected $_key;

    /**
     * 用户学号
     * @var
     */
    public $_uid;

    /**
     * curl类
     * @var Curl
     */
    protected $_curl;



    public function __construct()
    {
        $this->_curl=new Curl();
    }


    /**
     * @author mohuishou<1@lailin.xyz>
     * @param $username
     * @param $password
     * @return bool
     */
    public function login($username,$password){
        $this->_username=$username;
        $this->_password=$password;
        if(!$this->getKey()){
            return $this->returnData('帐号密码错误',10058);
        }

        $college_cookie=$this->loginCollege($this->_key);
        $data['uid']=$this->_uid;
        $data['college_cookie']=$college_cookie;

        return $this->returnData('登录成功，查询中......',200,$data);

    }

    /**
     * @author mohuishou<1@lailin.xyz>
     * @param $year
     * @param $term
     * @return bool
     */
    public function grade($year,$term){
        if($this->loginJwc()){
            return $this->getGrade($year,$term);
        };
    }

    /**
     * @author mohuishou<1@lailin.xyz>
     * @return bool
     */
    public function getKey()
    {
        $serviceInfo='{"serviceAddress":"https://uaaap.swu.edu.cn/cas/ws/acpInfoManagerWS","serviceType":"soap","serviceSource":"td","paramDataFormat":"xml","httpMethod":"POST","soapInterface":"getUserInfoByUserName","params":{"userName":"'.$this->_username.'","passwd":"'.$this->_password.'","clientId":"yzsfwmh","clientSecret":"1qazz@WSX3edc$RFV","url":"http://i.swu.edu.cn"},"cDataPath":[],"namespace":"","xml_json":""}';
        $this->_curl->get($this->_url_login_progress,array(
            "serviceInfo"=>$serviceInfo
        ));
        if ($this->_curl->error) {
            echo 'Error: ' . $this->_curl->errorCode . ': ' . $this->_curl->errorMessage;
            return false;
        }
        else {
            if(isset($this->_curl->response->data->getUserInfoByUserNameResponse->return->info->attributes->tgt)){
                $key=$this->_curl->response->data->getUserInfoByUserNameResponse->return->info->attributes->tgt;
                $key=base64_decode($key);
                $this->_key=$key;
                $this->_uid=$this->_curl->response->data->getUserInfoByUserNameResponse->return->info->id;
                return true;
            }
            return false;
        }
    }

    /**
     * @author mohuishou<1@lailin.xyz>
     * @param $key
     * @return array
     */
    public function loginCollege($key)
    {
        $this->_curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $this->_curl->setCookieJar(__DIR__.'/../cookie/college-'.$this->_uid.'.cookie');
        $this->_curl->get($this->_url_login_college,array(
           'CTgtId'=> $key
        ));
        if ($this->_curl->error) {
            echo 'Error: ' . $this->_curl->errorCode . ': ' . $this->_curl->errorMessage;
        }
        else {
            $this->_college_cookie=$this->_curl->getResponseCookies();
            return $this->_college_cookie;
        }
    }

    /**
     * @author mohuishou<1@lailin.xyz>
     * @return array
     */
    public function loginJwc()
    {
        $this->_curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $this->_curl->setOpt(CURLOPT_ENCODING , 'gzip');
        $this->_curl->setCookieFile(__DIR__.'/../cookie/college-'.$this->_uid.'.cookie');
        $this->_curl->get($this->_url_login_jwc);
        if ($this->_curl->error) {
            echo 'Error: ' . $this->_curl->errorCode . ': ' . $this->_curl->errorMessage;
        }
        else {
            $this->_jwc_cookie=$this->_curl->getResponseCookies();
            return $this->_jwc_cookie;
        }
    }

    /**
     * 获取成绩
     * @author mohuishou<1@lailin.xyz>
     * @param int $year 学年
     * @param int $term 学期代码
     * @param null|int $uid 用户学号|不填使用$this->_uid
     * @param null|array $jwc_cookie 教务处cookie|不填使用$this->_jwc_cookie
     * @return array
     */
    public function getGrade($year,$term,$uid=null,$jwc_cookie=null)
    {
        $uid || $uid=$this->_uid;
        $jwc_cookie || $jwc_cookie=$this->_jwc_cookie;

        $grade_param=[
            "doType"=>"query",
            "sessionUserKey"=>$uid,
            "xnm"=>$year,
            "xqm"=>$term,
            "queryModel.showCount"=>40,
            "queryModel.currentPage"=>1
        ];

        $this->_curl->setHeader('X-Requested-With', 'XMLHttpRequest');
        foreach ($jwc_cookie as $k => $v){
            $this->_curl->setCookie($k,$v);
        }
        $this->_curl->post($this->_url_grade,$grade_param);
        if ($this->_curl->error) {
            $error_msg= 'Error: ' . $this->_curl->errorCode . ': ' . $this->_curl->errorMessage;
            return $this->returnData($error_msg,20004);
        }
        else {
            if(empty($this->_curl->response->items)){
                return [
                    'status'=>20005,
                    'msg'=>'该学期暂时没有成绩'
                ];
            }

            $re=$this->_curl->response->items;

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

            return $data;
        }
    }

    public function returnData($msg,$status=200,$data=null){
        return ['status'=>$status, 'msg'=>$msg, 'data'=>$data];
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
