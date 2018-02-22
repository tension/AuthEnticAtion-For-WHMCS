<?php

require_once dirname ( __FILE__ ).'/AopSdk.php';

class ZhiMaService {

	//支付宝网关地址
	public $gateway_url = "https://openapi.alipay.com/gateway.do";

	//支付宝公钥
	public $alipay_public_key;

	//商户私钥
	public $private_key;

	//应用id
	public $appid;

	//编码格式
	public $charset = "UTF-8";

	public $token = NULL;
	
	//返回数据格式
	public $format = "json";

	//签名方式
	public $signtype = "RSA2";

	function __construct($config){
		$this->gateway_url = $config['gatewayUrl'];
		$this->appid = $config['app_id'];
		$this->private_key = $config['merchant_private_key'];
		$this->alipay_public_key = $config['alipay_public_key'];
		$this->charset = $config['charset'];
		$this->signtype=$config['sign_type'];

		if(empty($this->appid)||trim($this->appid)==""){
			throw new Exception("appid should not be NULL!");
		}
		if(empty($this->private_key)||trim($this->private_key)==""){
			throw new Exception("private_key should not be NULL!");
		}
		if(empty($this->alipay_public_key)||trim($this->alipay_public_key)==""){
			throw new Exception("alipay_public_key should not be NULL!");
		}
		if(empty($this->charset)||trim($this->charset)==""){
			throw new Exception("charset should not be NULL!");
		}
		if(empty($this->gateway_url)||trim($this->gateway_url)==""){
			throw new Exception("gateway_url should not be NULL!");
		}

	}
	
	function Authentication($builder) {
	
		$request = new ZhimaCustomerCertificationInitializeRequest();
		$request->setBizContent("{" .
"\"transaction_id\":\"".$builder['transaction_id']."\"," .
"\"product_code\":\"w1010100000000002978\"," .
"\"biz_code\":\"FACE\"," .
"\"identity_param\":\"{\\\"identity_type\\\":\\\"CERT_INFO\\\",\\\"cert_type\\\":\\\"IDENTITY_CARD\\\",\\\"cert_name\\\":\\\"".$builder['cert_name']."\\\",\\\"cert_no\\\":\\\"".$builder['cert_no']."\\\"}\"," .
"\"ext_biz_param\":\"{}\"" .
"  }");
		// 首先调用支付api
		$response = $this->aopclientRequestExecute($request, false, 'POST');
		$resultCode = $response->zhima_customer_certification_initialize_response->code;
		if(!empty($resultCode) && $resultCode == 10000) {
			$response = $response->zhima_customer_certification_initialize_response->biz_no;
		} else {
			$response = "失败";
		}
		return $response;
	}
	
	function Certification($bizno, $return_url, $notify_url) {
	
		$requesf = new ZhimaCustomerCertificationCertifyRequest();
		
		$requesf->setNotifyUrl($notify_url);
		$requesf->setReturnUrl($return_url);
		$requesf->setBizContent("{\"biz_no\":\"".$bizno."\"}");
		
		$response = $this->aopclientRequestExecute($requesf, true, 'GET');
		return $response;
	}

	/**
	 * sdkClient
	 * @param $request 接口请求参数对象。
	 * @param $ispage  是否是页面接口，电脑网站支付是页面表单接口。
	 * @return $response 支付宝返回的信息
 	*/
	function aopclientRequestExecute($request, $ispage=false, $pageExecute) {

		$aop = new AopClient ();
		$aop->gatewayUrl = $this->gateway_url;
		$aop->appId = $this->appid;
		$aop->rsaPrivateKey =  $this->private_key;
		$aop->alipayrsaPublicKey = $this->alipay_public_key;
		$aop->apiVersion ="1.0";
		$aop->postCharset = $this->charset;
		$aop->format= $this->format;
		$aop->signType=$this->signtype;
		// 开启页面信息输出
		$aop->debugInfo=true;
		if($ispage) {
			$result = $aop->pageExecute($request, $pageExecute);
			//echo $result;
		}
		else 
		{
			$result = $aop->Execute($request);
		}
        
		//打开后，将报文写入log文件
		//$this->writeLog("response: ".var_export($result,true));
		return $result;
	}
	
	function GetRandStr( $len ) { 
	    $chars = [ 
	        "0", "1", "2", "3", "4", "5", "6", "7", "8", "9" 
	    ]; 
	    $charsLen = count($chars) - 1; 
	    shuffle($chars);   
	    $output = ""; 
	    for ($i=0; $i<$len; $i++) { 
	        $output .= $chars[mt_rand(0, $charsLen)]; 
	    }  
	    return $output;  
	}
	
	/**
	 * 验签方法
	 * @param $arr 验签支付宝返回的信息，使用支付宝公钥。
	 * @return boolean
	 */
	function check($arr){
		$aop = new AopClient();
		$aop->alipayrsaPublicKey = $this->alipay_public_key;
		$result = $aop->rsaCheckV1($arr, $this->alipay_public_key, $this->signtype);

		return $result;
	}
	
	/**
	 * 请确保项目文件有可写权限，不然打印不了日志。
	 */
	function writeLog($text) {
		// $text=iconv("GBK", "UTF-8//IGNORE", $text);
		//$text = characet ( $text );
		file_put_contents ( dirname ( __FILE__ ).DIRECTORY_SEPARATOR."./log.txt", date ( "Y-m-d H:i:s" ) . "  " . $text . "\r\n", FILE_APPEND );
	}
}