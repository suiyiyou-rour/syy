<?php
/**
 * 
 * 微信支付API异常类
 * @author widyhu
 *
 */
class WxPayException extends Exception {
    public function __construct($message)
    {
        echo json_encode(array("code" => 403,"msg" =>$message));
        die;
    }

    public function errorMessage()
	{
		return $this->getMessage();
	}
}
