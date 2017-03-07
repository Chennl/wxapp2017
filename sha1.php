<?php
/**
 * SHA1 class
 *
 * ���� ����ƽ̨����Ϣǩ���ӿ�.
 */
class SHA1 {
	/**
	 * ��SHA1�㷨���ɰ�ȫǩ��
	 * @param string $token Ʊ��
	 * @param string $timestamp ʱ���
	 * @param string $once ����ַ���
	 * @param string $encrypt_msg ������Ϣ
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