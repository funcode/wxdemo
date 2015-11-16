<?php
/**
  * wechat php test
  */

//define your token
define("TOKEN", "beijing2014");
//ini_set("display_errors","stderr");
//xdebug_start_trace("../trace.log");

$wechatObj = new wechatCallbackapiTest();
if   ( $_GET["echostr"] )
    $wechatObj->valid();

else
    $wechatObj->responseMsg();

//xdebug_stop_trace();

class wechatCallbackapiTest
{

    public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
        	echo $echoStr;
        	exit;
        }
    }

    public function responseMsg()
    {
	//get post data, May be due to the different environments
	$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $app_json=json_decode($_ENV["VCAP_APPLICATION"]);
        $instance = $app_json -> {"instance_index"};

        $service_json = json_decode($_ENV["VCAP_SERVICES"]);
        $service = $service_json -> {"p-mysql"}[0];
        #$service = $service_json -> {"user-provided"}[0];
        $service_name = $service -> {"name"};
        $credentials = $service -> {"credentials"};
        $host = $credentials -> {"hostname"};
        $username = $credentials -> {"username"};
        $password = $credentials -> {"password"};
        $dbname = $credentials -> {"name"};
        $port = $credentials -> {"port"};
        $table = "demodb";

        $mysqli = new mysqli($host, $username, $password, $dbname);
        if ($mysqli->connect_errno) {
                exit( "connection failed: ".$mysqli->connect_error."\n");
        }


        $query='CREATE TABLE IF NOT EXISTS '.$table.' (msg VARCHAR(256)) '.' default charset utf8';
        if(!$mysqli->query($query)){
                exit("can not create table! \n");
        }


      	//extract post data
	if (!empty($postStr)){
                /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
                   the best way is to check the validity of xml by yourself */
                libxml_disable_entity_loader(true);
              	$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $fromUsername = $postObj->FromUserName;
                $toUsername = $postObj->ToUserName;
                $keyword = trim($postObj->Content);
                $time = time();
                $textTpl = "<xml>
				<ToUserName><![CDATA[%s]]></ToUserName>
				<FromUserName><![CDATA[%s]]></FromUserName>
				<CreateTime>%s</CreateTime>
				<MsgType><![CDATA[%s]]></MsgType>
				<Content><![CDATA[%s]]></Content>
				<FuncFlag>0</FuncFlag>
		        </xml>";
		if(!empty( $keyword ))
                {
        		$msgType = "text";

                	//$contentStr = "实例     ".$instance."    收到: ".$keyword."\n存入 ".$service_name;
                	//$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                	//echo $resultStr;

			if(strcasecmp($keyword , 'reset') == 0){
				$query = 'DELETE FROM '.$table;
				$contentStr = "实例     ".$instance."    收到: ".$keyword."\n清空 ".$service_name;
			}else{
				$query = 'INSERT INTO '.$table.' (msg) VALUES ("'.$keyword.'")';
				$contentStr = "实例     ".$instance."    收到: ".$keyword."\n存入 ".$service_name;
			}	
                        $n = $mysqli->query($query);
                        if(!$n){
                           error_log( 'Error : ('. $mysqli->errno .') '. $mysqli->error. $query);
                           exit("can not insert to table!");
                        }

			$query = "SELECT COUNT(*) FROM ".$table;
			$result = $mysqli->query($query);
			$row = $result->fetch_array(MYSQLI_NUM);
			$contentStr = $contentStr."\n共: ".strval($row[0])."条消息";
                	$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                	echo $resultStr;
			$result->free();

                }else{
                	echo "Input something...";
                }

        }else {
        	echo "";
        	//exit;
        }
        $mysqli->close();
    }

	private function checkSignature()
	{

        // you must define TOKEN by yourself
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }

        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );

		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}

?>
