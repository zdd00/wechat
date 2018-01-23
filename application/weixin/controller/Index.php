<?php

namespace app\weixin\controller;

use think\Controller;
use think\Log;
use wechat\Wxapi;

define("TOKEN", "weixin");

class Index extends Controller
{
    public function index()
    {
        if (!isset($_GET['echostr'])) {
            $this->responseMsg();
        } else {
            $this->valid();
        }


        //return 'http://tj.nineton.cn/Heart/index/all';
    }

    public function valid()
    {
        $echoStr = $_GET['echostr'];
        $signature = $_GET['signature'];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            echo $echoStr;
            exit;
        }

    }

    public function responseMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr)) {
            $this->logger('R '.$postStr);
        } else {

        }
    }

    private function receiveEvent($object)
    {
        $weixin = new Wxapi();
    }

    //日志记录
    private function logger($log_content)
    {
        $max_size = 1000000;
        $log_filename = "log.xml";
        if (file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)) {
            unlink($log_filename);
        }
        file_put_contents($log_filename, date('H:i:s') . " " . $log_content . "\r\n", FILE_APPEND);
    }


}
