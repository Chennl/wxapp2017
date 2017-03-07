<?php
/**
 * SHA1 class
 *
 * 计算 公众平台的消息签名接口.
 */


class wechatCallbackapiHandler
{
	//验证签名
	public function valid()
	{
		$echoStr = $_GET["echostr"];
		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);
		if($tmpStr == $signature){
			echo $echoStr;
			exit;
		}
	}

	//鍝嶅簲娑堟伅
	public function responseMsg()
	{
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
		if (!empty($postStr)){
			$this->logger("R \r\n".$postStr);
			$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
			$RX_TYPE = trim($postObj->MsgType);

			if (($postObj->MsgType == "event") && ($postObj->Event == "subscribe" || $postObj->Event == "unsubscribe")){
				////过滤关注和取消关注事件
			}else{

			}

			//消息类型
			switch ($RX_TYPE)
			{
				case "event":
					$result = $this->receiveEvent($postObj);
					break;
				case "text":
					if (strstr($postObj->Content, "第三方")){
						//http://www.hzgbjy.com/xapp2017/index.php
						$result = $this->relayPart3("http://www.hzgbjy.com/wxapp2017/part3.php".'?'.$_SERVER['QUERY_STRING'], $postStr);
					}else{
						$result = $this->receiveText($postObj);
					}
					break;
				case "image":
					$result = $this->receiveImage($postObj);
					break;
				case "location":
					$result = $this->receiveLocation($postObj);
					break;
				case "voice":
					$result = $this->receiveVoice($postObj);
					break;
				case "video":
					$result = $this->receiveVideo($postObj);
					break;
				case "link":
					$result = $this->receiveLink($postObj);
					break;
				default:
					$result = "unknown msg type: ".$RX_TYPE;
					break;
			}
			$this->logger("T \r\n".$result);
			echo $result;
		}else {
			echo "";
			exit;
		}
	}

	//鎺ユ敹浜嬩欢娑堟伅
	private function receiveEvent($object)
	{
		$content = "";
		switch ($object->Event)
		{
			case "subscribe":
				$content = "娆㈣繋鍏虫敞锛� 闆噣鑳＄敯鐗ч┈杩�,鏄庢湀缇岀瑳鎴嶆ゼ闂�. ";
				$content .= (!empty($object->EventKey))?("\n鏉ヨ嚜浜岀淮鐮佸満鏅� ".str_replace("qrscene_","",$object->EventKey)):"";
				break;
			case "unsubscribe":
				$content = "鍙栨秷鍏虫敞";
				break;
			case "CLICK":
				switch ($object->EventKey)
				{
					case "COMPANY":
						$content = array();
						$content[] = array("Title"=>"鏄庢湀缇岀瑳宸ヤ綔瀹�", "Description"=>"", "PicUrl"=>"http://discuz.comli.com/weixin/weather/icon/cartoon.jpg", "Url" =>"http://m.cnblogs.com/?u=txw1958");
						break;
					default:
						$content = "鐐瑰嚮鑿滃崟锛�".$object->EventKey;
						break;
				}
				break;
			case "VIEW":
				$content = "璺宠浆閾炬帴 ".$object->EventKey;
				break;
			case "SCAN":
				$content = "鎵弿鍦烘櫙 ".$object->EventKey;
				break;
			case "LOCATION":
				$content = "上传位置：纬度 ".$object->Latitude.";经度 ".$object->Longitude;
				break;
			case "scancode_waitmsg":
				if ($object->ScanCodeInfo->ScanType == "qrcode"){
					$content = "鎵爜甯︽彁绀猴細绫诲瀷 浜岀淮鐮� 缁撴灉锛�".$object->ScanCodeInfo->ScanResult;
				}else if ($object->ScanCodeInfo->ScanType == "barcode"){
					$codeinfo = explode(",",strval($object->ScanCodeInfo->ScanResult));
					$codeValue = $codeinfo[1];
					$content = "鎵爜甯︽彁绀猴細绫诲瀷 鏉″舰鐮� 缁撴灉锛�".$codeValue;
				}else{
					$content = "鎵爜甯︽彁绀猴細绫诲瀷 ".$object->ScanCodeInfo->ScanType." 缁撴灉锛�".$object->ScanCodeInfo->ScanResult;
				}
				break;
			case "scancode_push":
				$content = "鎵爜鎺ㄤ簨浠�";
				break;
			case "pic_sysphoto":
				$content = "绯荤粺鎷嶇収";
				break;
			case "pic_weixin":
				$content = "鐩稿唽鍙戝浘锛氭暟閲� ".$object->SendPicsInfo->Count;
				break;
			case "pic_photo_or_album":
				$content = "鎷嶇収鎴栬�呯浉鍐岋細鏁伴噺 ".$object->SendPicsInfo->Count;
				break;
			case "location_select":
				$content = "鍙戦�佷綅缃細鏍囩 ".$object->SendLocationInfo->Label;
				break;
			default:
				$content = "receive a new event: ".$object->Event;
				break;
		}

		if(is_array($content)){
			if (isset($content[0]['PicUrl'])){
				$result = $this->transmitNews($object, $content);
			}else if (isset($content['MusicUrl'])){
				$result = $this->transmitMusic($object, $content);
			}
		}else{
			$result = $this->transmitText($object, $content);
		}
		return $result;
	}

	////接收文本消息
	private function receiveText($object)
	{
		$keyword = trim($object->Content);
		//服务平台
		if (strstr($keyword, "请问在吗") || strstr($keyword, "在线客服")|| strstr($keyword, "你好")){
			$result = $this->transmitService($object);
			return $result;
		}
		//文本
		if (strstr($keyword, "文本")){
			$content = "你输入的是纯文本";
		}else if (strstr($keyword, "表情")){
            $content = "中国：".$this->bytes_to_emoji(0x1F1E8).$this->bytes_to_emoji(0x1F1F3)."\n仙人掌：".$this->bytes_to_emoji(0x1F335);
        }else if (strstr($keyword, "单图文")){
            $content = array();
            $content[] = array("Title"=>"单图文标题",  "Description"=>"单图文内容", "PicUrl"=>"http://file.service.qq.com/user-files/uploads/201512/0fe775e3c637b707f1a3fc2131bb3d4d.png", "Url" =>"https://mp.weixin.qq.com/");
        }else if (strstr($keyword, "图文") || strstr($keyword, "多图文")){
            $content = array();
            $content[] = array("Title"=>"多图文1标题", "Description"=>"", "PicUrl"=>"http://discuz.comli.com/weixin/weather/icon/cartoon.jpg", "Url" =>"http://m.cnblogs.com/?u=txw1958");
            $content[] = array("Title"=>"多图文2标题", "Description"=>"", "PicUrl"=>"http://d.hiphotos.bdimg.com/wisegame/pic/item/f3529822720e0cf3ac9f1ada0846f21fbe09aaa3.jpg", "Url" =>"http://m.cnblogs.com/?u=txw1958");
            $content[] = array("Title"=>"多图文3标题", "Description"=>"", "PicUrl"=>"http://g.hiphotos.bdimg.com/wisegame/pic/item/18cb0a46f21fbe090d338acc6a600c338644adfd.jpg", "Url" =>"http://m.cnblogs.com/?u=txw1958");
        }else if (strstr($keyword, "音乐")){
            $content = array();
            $content = array("Title"=>"最炫民族风", "Description"=>"歌手：凤凰传奇", "MusicUrl"=>"http://121.199.4.61/music/zxmzf.mp3", "HQMusicUrl"=>"http://121.199.4.61/music/zxmzf.mp3"); 
        }else{
			$content = date("Y-m-d H:i:s",time())."\nOpenID".$object->FromUserName."\n羌笛明月伴你行";
		}

		if(is_array($content)){
			if (isset($content[0])){
				$result = $this->transmitNews($object, $content);
			}else if (isset($content['MusicUrl'])){
				$result = $this->transmitMusic($object, $content);
			}
		}else{
			$result = $this->transmitText($object, $content);
		}
		return $result;
	}

	//图片消息
	private function receiveImage($object)
	{
		$content = array("MediaId"=>$object->MediaId);
		$result = $this->transmitImage($object, $content);
		return $result;
	}

	//地址消息
	private function receiveLocation($object)
	{
		$content = "浣犲彂閫佺殑鏄綅缃紝缁忓害涓猴細".$object->Location_Y."锛涚含搴︿负锛�".$object->Location_X."锛涚缉鏀剧骇鍒负锛�".$object->Scale."锛涗綅缃负锛�".$object->Label;
		$result = $this->transmitText($object, $content);
		return $result;
	}

	//收到语音消息
	private function receiveVoice($object)
	{
		if (isset($object->Recognition) && !empty($object->Recognition)){
			$content = "你刚才说的是：".$object->Recognition;
			$result = $this->transmitText($object, $content);
		}else{
			$content = array("MediaId"=>$object->MediaId);
			$result = $this->transmitVoice($object, $content);
		}
		return $result;
	}

	////接收视频消息
	private function receiveVideo($object)
	{
		$content = array("MediaId"=>$object->MediaId, "ThumbMediaId"=>$object->ThumbMediaId, "Title"=>"", "Description"=>"");
		$result = $this->transmitVideo($object, $content);
		return $result;
	}

	//鎺ユ敹閾炬帴娑堟伅
	private function receiveLink($object)
	{
		$content = "浣犲彂閫佺殑鏄摼鎺ワ紝鏍囬涓猴細".$object->Title."锛涘唴瀹逛负锛�".$object->Description."锛涢摼鎺ュ湴鍧�涓猴細".$object->Url;
		$result = $this->transmitText($object, $content);
		return $result;
	}

	//回复文本
	private function transmitText($object, $content)
	{
		if (!isset($content) || empty($content)){
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

	//鍥炲鍥炬枃娑堟伅
	private function transmitNews($object, $newsArray)
	{
		if(!is_array($newsArray)){
			return "";
		}
		$itemTpl = "        <item>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
        </item>
";
		$item_str = "";
		foreach ($newsArray as $item){
			$item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
		}
		$xmlTpl = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[news]]></MsgType>
		<ArticleCount>%s</ArticleCount>
		<Articles>
		$item_str    </Articles>
		</xml>";

		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
		return $result;
	}

	//鍥炲闊充箰娑堟伅
	private function transmitMusic($object, $musicArray)
	{
		if(!is_array($musicArray)){
			return "";
		}
		$itemTpl = "<Music>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <MusicUrl><![CDATA[%s]]></MusicUrl>
        <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
    </Music>";

		$item_str = sprintf($itemTpl, $musicArray['Title'], $musicArray['Description'], $musicArray['MusicUrl'], $musicArray['HQMusicUrl']);

		$xmlTpl = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[music]]></MsgType>
		$item_str
		</xml>";

		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
		return $result;
	}

	//鍥炲鍥剧墖娑堟伅
	private function transmitImage($object, $imageArray)
	{
		$itemTpl = "<Image>
        <MediaId><![CDATA[%s]]></MediaId>
    </Image>";

		$item_str = sprintf($itemTpl, $imageArray['MediaId']);

		$xmlTpl = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[image]]></MsgType>
		$item_str
		</xml>";

		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
		return $result;
	}

	//回复语音
	private function transmitVoice($object, $voiceArray)
	{
		$itemTpl = "<Voice>
        <MediaId><![CDATA[%s]]></MediaId>
    </Voice>";

		$item_str = sprintf($itemTpl, $voiceArray['MediaId']);
		$xmlTpl = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[voice]]></MsgType>
		$item_str
		</xml>";

		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
		return $result;
	}

	//回复视频
	private function transmitVideo($object, $videoArray)
	{
		$itemTpl = "<Video>
        <MediaId><![CDATA[%s]]></MediaId>
        <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
    </Video>";
		$item_str = sprintf($itemTpl, $videoArray['MediaId'], $videoArray['ThumbMediaId'], $videoArray['Title'], $videoArray['Description']);
		$xmlTpl = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[video]]></MsgType>
		$item_str
		</xml>";

		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
		return $result;
	}

	//回复多客服消息
	private function transmitService($object)
	{
		$xmlTpl = "<xml>
        		<ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName>
        		<CreateTime>%s</CreateTime><MsgType><![CDATA[你好，我有什么可以帮助你？]]></MsgType>
        		</xml>";
		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, date("Y-m-d h-i-sa",time()));
		return $result;
	}

	//第三方接入平台
	private function relayPart3($url, $rawData)
	{
		$headers = array("Content-Type: text/xml; charset=utf-8");
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_HEADER,false);
		/*  curl_setopt($ch, CURLOPT_REURNTRANSFER, 1);
		 curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		 curl_setopt($ch, CURLOPT_POST, 1);
		 curl_setopt($ch, CURLOPT_POSTFIELDS, $rawData);*/
		$output = curl_exec($ch);
		curl_close($ch);
		print($output);
	}

	//字节转Emoji表情
	function bytes_to_emoji($cp)
	{
		if ($cp > 0x10000){       # 4 bytes
			return chr(0xF0 | (($cp & 0x1C0000) >> 18)).chr(0x80 | (($cp & 0x3F000) >> 12)).chr(0x80 | (($cp & 0xFC0) >> 6)).chr(0x80 | ($cp & 0x3F));
		}else if ($cp > 0x800){   # 3 bytes
			return chr(0xE0 | (($cp & 0xF000) >> 12)).chr(0x80 | (($cp & 0xFC0) >> 6)).chr(0x80 | ($cp & 0x3F));
		}else if ($cp > 0x80){    # 2 bytes
			return chr(0xC0 | (($cp & 0x7C0) >> 6)).chr(0x80 | ($cp & 0x3F));
		}else{                    # 1 byte
			return chr($cp);
		}
	}

	//鏃ュ織璁板綍
	private function logger($log_content)
	{
		/* if(isset($_SERVER['HTTP_APPNAME'])){   //SAE
			sae_set_display_errors(false);
			sae_debug($log_content);
			sae_set_display_errors(true);
		}else if($_SERVER['REMOTE_ADDR'] != "127.0.0.1"){ //LOCAL
			$max_size = 1000000;
			$log_filename = "log.xml";
			if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)){unlink($log_filename);}
			file_put_contents($log_filename, date('Y-m-d H:i:s')." ".$log_content."\r\n", FILE_APPEND);
		} */
	}
}

?>