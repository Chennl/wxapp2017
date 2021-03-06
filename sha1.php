<?php
/**
 * SHA1 class
 *
 * 计算 公众平台的消息签名接口.
 */
class SHA1 {
	/**
	 * 用SHA1算法生成安全签名
	 * @param string $token 票据
	 * @param string $timestamp 时间戳
	 * @param string $once 随机字符串
	 * @param string $encrypt_msg 密文消息
	 */
	public function getSHA1($token,$timestamp,$once,$encrypt_msg){
		try{
			$array = array($encrypt_msg, $token, $timestamp, $nonce);
			sort($array, SORT_STRING);
			$str = implode($array);
			return array(ErrorCode::$OK, sha1($str));
		}catch(Exception $e){
			return array(ErrorCode::$ComputeSignatureError,null);
		}
	}
}
?>