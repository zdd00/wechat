<?php
/**
 * Created by PhpStorm.
 * User: ZDD
 * Date: 2018/1/23
 * Time: 22:09
 */

namespace wechat;


use think\Log;

class Wxapi
{
    var $appid = 'wx318bc4442720c2e5';
    var $appsecret = '306ab5bf5bb62036b7a76bcc3bb0cf80';

    public function __construct($appid = NULL, $appsecret = NULL)
    {
        if ($appid && $appsecret) {
            $this->appid = $appid;
            $this->appsecret = $appsecret;
        }

        $res = file_get_contents('access_token.json');
        $result = json_decode($res, true);
        $this->expires_time = $result['expires_time'];
        $this->access_token = $result['access_token'];
        if (time() > ($this->expires_time + 7000)) {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $this->appid . "&secret=" . $this->appsecret;
            $res = $this->http_request($url);
            $result = json_decode($res, true);
            $this->expires_time = time();
            $this->access_token = $result['access_token'];
            file_put_contents('access_token.json', '{"access_token": "' . $this->access_token . '", "expires_time": ' . $this->expires_time . '}');
        }

    }

    public function get_user_info($openid)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=" . $this->access_token . "&openid=" . $openid . "&lang=zh_CN";
        $res = $this->http_request($url);
        return json_decode($res, true);
    }

    //HTTP请求（支持HTTP/HTTPS，支持GET/POST）
    protected function http_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    //日志记录
    private function logger($log_content)
    {
        $max_size = 1000000;
        $log_filename = "log.xml";
        if (file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)) {
            unlink($log_filename);
        }
//        file_put_contents($log_filename, date('H:i:s') . " " . $log_content . "\r\n", FILE_APPEND);
        file_put_contents($log_filename, date('H:i:s') . " " . $log_content . "\r\n");
    }
}