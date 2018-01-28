<?php

namespace app\weixin\controller;

use think\Controller;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
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
            $this->logger('R ' . $postStr);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);
            if (($postObj->MsgType == "event") && ($postObj->Event == "subscribe" || $postObj->Event == "unsubscribe" || $postObj->Event == "TEMPLATESENDJOBFINISH")) {
                //过滤关注和取消关注事件
            } else {
                Db::name('user')->where('openid', $postObj->FormUserName)->setField('heartbeat', time());
            }

            switch ($RX_TYPE) {
                case "event":
                    $result = $this->receiveEvent($postObj);
                    break;
                case "text":
                    $result = $this->receiveText($postObj);
                    break;
                default:
                    $result = '未知类型' . $RX_TYPE;
            }
            $this->logger("T " . $result);
            echo $result;
        } else {
            echo "";
            exit();
        }
    }

    private function receiveEvent($object)
    {
        $weixin = new Wxapi();
        $openid = strval($object->FormUserName);
        $content = '';
        switch ($object->Event) {
            case "subscribe":

        }
    }


    //接收文本消息
    private function receiveText($object)
    {
        $keyword = trim($object->Content);
        $openid = strval($object->FromUserName);
        $content = "";

        if (strstr($keyword, "利红") || strstr($keyword, "崔利红")) {
            $content = "老婆我爱你!";
        } else if (strstr($keyword, "天气")) {
            try {
                $content = $this->getWeather($keyword);
            } catch (DataNotFoundException $e) {
            } catch (ModelNotFoundException $e) {
            } catch (DbException $e) {
            }
        } else {
            $content = '你发送了消息' . $keyword;
        }

        if (is_array($content)) {
            $result = $this->transmitNews($object, $content);
        } else {
            $result = $this->transmitText($object, $content);
        }
        return $result;
    }

    //回复文本消息
    private function transmitText($object, $content)
    {
        if (!isset($content) || empty($content)) {
            return "";
        }
        $xmlTpl = "<xml>
                 <ToUserName><![CDATA[%s]]></ToUserName>
                 <FromUserName><![CDATA[%s]]></FromUserName>
                 <CreateTime>%s</CreateTime>
                 <MsgType><![CDATA[text]]></MsgType>
                 <Content><![CDATA[%s]]></Content>
                 </xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }

    //回复图文消息
    private function transmitNews($object, $newsArray)
    {
        if (!is_array($newsArray)) {
            return "";
        }
        $itemTpl = " <item>
                     <Title><![CDATA[%s]]></Title>
                     <Description><![CDATA[%s]]></Description>
                     <PicUrl><![CDATA[%s]]></PicUrl>
                     <Url><![CDATA[%s]]></Url>
                     </item>";
        $item_str = "";
        foreach ($newsArray as $item) {
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        $xmlTpl = "<xml>
                     <ToUserName><![CDATA[%s]]></ToUserName>
                     <FromUserName><![CDATA[%s]]></FromUserName>
                     <CreateTime>%s</CreateTime>
                     <MsgType><![CDATA[news]]></MsgType>
                     <ArticleCount>%s</ArticleCount>
                     <Articles>
                     $item_str</Articles>
                     </xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
        Log::write($result);
        return $result;
    }


    /**
     * @param $keyword 文本
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getWeather($keyword)
    {
        $cityList = Db::name('weather')->field('townName,townId')->select();
        $townId = '';
        foreach ($cityList as $city) {
            if (strstr($keyword, $city['townName'])) {
                $townId = $city['townId'];
            }
        }
        if ($townId == '') {
            return '未知地点';
        } else {
            $res = $this->http_request('http://tj.nineton.cn/Heart/index/all?city=' . $townId);
            $result = json_decode($res, true);
            if (isset($result['status'])) {
                $weather = $result['weather'][0];
                $weatherArray[] = array("Title" => $weather['city_name'] . "天气预报", "Description" => "", "PicUrl" => "", "Url" => "");
                for ($i = 0; $i < 6; $i++) {
                    $weatherArray[] = array("Title" =>
                        $weather["future"][$i]["day"] . "\n" .
                        $weather["future"][$i]["text"] . " " .
                        $weather["future"][$i]["wind"] . " " .
                        $weather["future"][$i]["low"] . "~" .
                        $weather["future"][$i]["high"],
                        "Description" => "",
                        "PicUrl" => "",
                        "Url" => "");
                }
                return $weatherArray;
            } else {
                return '未查到天气!';
            }
        }

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
        file_put_contents($log_filename, date('H:i:s') . " " . $log_content . "\r\n", FILE_APPEND);
//        file_put_contents($log_filename, date('H:i:s') . " " . $log_content . "\r\n");
    }


}
