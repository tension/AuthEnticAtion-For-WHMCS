<?php
if (!defined('WHMCS')) {
	die('This file cannot be accessed directly');
}
use WHMCS\Database\Capsule;
// NeWorld Manager 开始

// 引入文件
require  ROOTDIR . '/modules/addons/NeWorld/library/class/NeWorld.Common.Class.php';

// NeWorld Manager 结束

// 判断函数是否不存在
if (!function_exists('authentication_config')) {
    // 设置项目
    function authentication_config() {
	    
	    $appid = \WHMCS\Database\Capsule::table('tblpaymentgateways')->where('gateway','f2falipay')->where('setting', 'app_id')->first()->value;
		$merchant_private_key 	= \WHMCS\Database\Capsule::table('tblpaymentgateways')->where('gateway','f2falipay')->where('setting', 'merchant_private_key')->first()->value;
		$SystemURL 	= \WHMCS\Config\Setting::getValue('SystemURL');
		
		if ( $merchant_private_key ) {
			$private = '<script src="'.$systemURL.'/modules/addons/authentication/templates/assets/js/setPrivateKey.js?v11"></script><link href="'.$systemURL.'/modules/addons/authentication/templates/assets/css/css.css?v10" rel="stylesheet" type="text/css"><a class="btn btn-success btn-xs" onClick="javascript:setPrivateKey(\''.$systemURL.'\', \'SHA1\');">生成 SHA1 私钥</a><a class="btn btn-default btn-xs" onClick="javascript:getalipayKey(\''.$systemURL.'\', \''.$appid.'\', \''.$merchant_private_key.'\');">获取支付宝模块参数</a> 需要服务器安装 <code>openSSL</code> 模块';
		} else {
			$private = '<script src="'.$systemURL.'/modules/addons/authentication/templates/assets/js/setPrivateKey.js?v11"></script><link href="'.$systemURL.'/modules/addons/authentication/templates/assets/css/css.css?v10" rel="stylesheet" type="text/css"><a class="btn btn-success btn-xs" onClick="javascript:setPrivateKey(\''.$systemURL.'\', \'SHA1\');">生成 SHA1 私钥</a> 需要服务器安装 <code>openSSL</code> 模块';
		}

        // 返回结果
        $configarray = [
            'name' 			=> 'AuthenticAtion',
            'description' 	=> '这是基于 支付宝开放平台 芝麻认证 的一个实名认证模块',
            'version' 		=> '1.3',    // 读取配置文件中的版本
            'author' 		=> '<a target="_blank" href="https://neworld.org/">NeWorld</a>',
            'fields' 		=> []
        ];
		
		$configarray['fields']['app_id'] = [
			"FriendlyName" => "应用ID",
			"Type" => "text",
			"Size" => "25",
		];
		
		$configarray['fields']['merchant_private_key'] = [
			"FriendlyName" => "商户私钥 (SHA1私钥)",
            'Type' => 'textarea',
            'Rows' => '9',
            'Description' => $private,
		];
		
		$configarray['fields']['coercion'] = [
			'FriendlyName' => '强制认证',
			'Type' => 'yesno',
            'Description' 	=> '是否开启强制实名认证。',
		];
		
		$coercion = \Illuminate\Database\Capsule\Manager::table('tbladdonmodules')->where('module', 'authentication')->where('setting', 'coercion')->first()->value;
		if (!empty($coercion)) {
			
			$configarray['fields']['coercion_pages'] = [
				'FriendlyName' => '选择页面',
				'Type' => 'dropdown',
	            "Options" => "用户中心,购物车,产品详情",
	            "Default" => "用户中心",
	            'Description' 	=> '需要开启强制实名的页面。',
			];
		}
        
        return $configarray;
    }
}

// 判断函数是否不存在
if (!function_exists('authentication_activate')) {
    // 插件激活
	function authentication_activate() {
			try {
				if (!Capsule::schema()->hasTable('mod_authentication')) {
					Capsule::schema()->create('mod_authentication', function ($table) {
						$table->increments('uid');
						$table->text('biz_no');
						$table->dateTime('date')->default('0000-00-00 00:00:00');
						$table->text('cert_name');
						$table->text('cert_no');
						$table->text('validation');
					});
				}
			} catch (Exception $e) {
				return [
					'status' => 'error',
					'description' => '不能创建表 mod_authentication: ' . $e->getMessage()
				];
			}
			return [
				'status' => 'success',
				'description' => '模块激活成功. 点击 配置 对模块进行设置。'
			];
	}
}

// 判断函数是否不存在
if (!function_exists('authentication_deactivate')) {
    // 插件卸载
	function authentication_deactivate() {
		try {
			Capsule::schema()->dropIfExists('mod_authentication');
			return [
				'status' => 'success',
				'description' => '模块卸载成功'
			];
		} catch (Exception $e) {
			return [
				'status' => 'error',
				'description' => 'Unable to drop tables: ' . $e->getMessage()
			];
		}
	}
}

// 判断函数是否不存在
if (!function_exists('authentication_clientarea')) {
	function authentication_clientarea($vars) {
			
		$uid = $_SESSION['uid'];
		$userinfo = \Illuminate\Database\Capsule\Manager::table('mod_authentication')->where('uid', $uid)->first();
		
		if ( $userinfo->validation == 'yes' ) {
            $username = substr_replace($userinfo->cert_name, '***', 0, 3);
            $userno = substr_replace($userinfo->cert_no, '********', 6, 8);
		} else {
            $username = '';
            $userno = '';
		}
		
		if ( !empty($_REQUEST['action']) ) {
			
			require_once __DIR__.'/config.php';
			require_once __DIR__.'/ZhiMaService.php';
			$aop = new ZhiMaService($config);
			
			$cert_name 			= $_REQUEST['cert_name'];
			$cert_no 			= $_REQUEST['cert_no'];
			$transaction_id 	= 'Auth' . date('YmdHis') . $aop->GetRandStr(14);
			
			$RequestBuilder = [
				'transaction_id' => $transaction_id,
				'cert_name' => $cert_name,
				'cert_no' => $cert_no,
			];
			// 初始化认证
			$bizno = $aop->Authentication($RequestBuilder);
			
			if ( $_REQUEST['action'] == 'check' ) {
				
				// 判断提交数据
				if ( $userinfo->validation == 'yes') {
					
					$results = [
						'status' => 'success',
						'date' => date('Y-m-d H:i:s'),
					];
					
				} else {
						
					$results = [
						'status' => 'error',
						'info' => '尚未认证成功',
						
					];
				}
				
			} else {
			
				// 判断提交数据
				if ( !empty( $userinfo ) ) {
					//判断是否认证
					if ( $userinfo->validation != 'yes' ) {
						
						\Illuminate\Database\Capsule\Manager::table('mod_authentication')->where('uid', $uid)->update([
							'biz_no' 	=> $bizno,
						    'date' 		=> date('Y-m-d H:i:s'), 
							'cert_name' => $cert_name,
							'cert_no' 	=> $cert_no,
						]);
				
						// 提交认证
						$qrcode = $aop->Certification($bizno, $config['return_url'], $config['notify_url']);
						$results = [
							'status' => 'success',
							'qrcode' => $qrcode,
						];
						
					} else {
						
						$results = [
							'status' => 'error',
							'info' => '已经实名认证',
							
						];
						
					}
				} else {
					
					\Illuminate\Database\Capsule\Manager::table('mod_authentication')->insert([
						'uid' 		=> $uid,
						'biz_no' 	=> $bizno,
						'date' 		=> date('Y-m-d H:i:s'), 
						'cert_name' => $cert_name,
						'cert_no' 	=> $cert_no,
					]);
				
					// 提交认证
					$qrcode = $aop->Certification($bizno, $config['return_url'], $config['notify_url']);
					$results = [
						'status' => 'success',
						'qrcode' => $qrcode,
					];
					
				}
			}
					
			die(json_encode($results));
		}
			
		return [
	        'pagetitle'    			=> '实名认证',
	        'breadcrumb'   			=> ['index.php?m=authentication' => '实名认证'],
	        'templatefile' 			=> 'templates/clientarea',
	        'requirelogin'			=> true,
	        'vars'         			=> [
	            'username' 			=> $username,
	            'userno' 			=> $userno,
	            'date' 				=> $userinfo->date,
	            'validation' 		=> $userinfo->validation,
	        ]
	    ];
		
		
	}
}

// 判断函数是否不存在
if (!function_exists('authentication_output')) {
    // 插件输出
    function authentication_output($vars) {
	    $modulelink = $vars['modulelink'];

        try {
            // 实例化扩展类
            $ext = new NeWorld\Extended;

            try {
                // 实例化数据库类
                $db = new NeWorld\Database;

                // 读取数据库中已激活的产品
                $getData = $db->runSQL([
                    'action' => [
                        'list' => [
                            'sql' => 'SELECT * FROM mod_authentication',
                            'all' => true,
                        ],
                    ],
                    'trans' => false,
                ]);
                
				$result['action'] = $_REQUEST['action'];
				
                switch ( $result['action'] ) {
					case 'del':
			            $id = (int) $_REQUEST['id'];
						// 判断是否有 POST
				        if (!empty($_POST)) {
				            try {
					            \Illuminate\Database\Capsule\Manager::table('mod_authentication')->where('uid', $id)->delete();
								$value = [
									'status' => 'success',
								];
				            }
				            catch (Exception $e) {
				                $value = [
									'status' => 'error',
									'msg'	 => $e->getMessage(),
								];
				            }
				            die(json_encode($value));
				        }
						break;
					case 'change':
			            $id = (int) $_REQUEST['id'];
						// 判断是否有 POST
				        if (!empty($_POST)) {
				            try {
					            $info = \Illuminate\Database\Capsule\Manager::table('mod_authentication')->where('uid', $id)->first()->validation;
					            
					            if ( empty( $info ) ) {
						            \Illuminate\Database\Capsule\Manager::table('mod_authentication')->where('uid', $id)->update([
						            	'validation' => 'yes', 
						            ]);
					            } else {
						            \Illuminate\Database\Capsule\Manager::table('mod_authentication')->where('uid', $id)->update([
						            	'validation' => '', 
						            ]);
					            }
								$value = [
									'status' => 'success',
									'msg' => 'btn-danger btn-success',
								];
				            }
				            catch (Exception $e) {
				                $value = [
									'status' => 'error',
									'msg'	 => $e->getMessage(),
								];
				            }
				            die(json_encode($value));
				        }
						break;
					default:
				
		                // 返回给模板
		                $result['auth'] = $getData['list']['result'];
		
		                // 遍历产品数组
		                foreach ($result['auth'] as $key => $value) {
		                    try {
								$getData = $db->runSQL([
				                    'action' => [
				                        'user' => [
				                            'sql' => 'SELECT * FROM tblclients WHERE id = ?',
											'pre' => [$result['auth'][$key]['uid']],
				                        ],
				                    ],
				                    'trans' => false,
				                ]);
				                
		                        $result['auth'][$key]['username'] = $getData['user']['result']['firstname'] . ' ' . $getData['user']['result']['lastname'];
		
		                    }
		                    catch (Exception $e) {
		                        // 销毁要返回的数组
		                        unset($result['auth'][$key]);
		
		                        // 返回提示
		                        $result['notice'] .= $ext->getSmarty([
		                            'file' => 'tips/danger',
		                            'vars' => [
		                                'message' => '无法获取标题 [ '.$value['title'].' ] 在数据库中的信息，错误信息: '.$e->getMessage(),
		                            ],
		                        ]);
		                    }
		                }
						$result['PageName'] = 'index';
						break;
				}
				
				$result['assets'] = $ext->getSystemURL().'modules/addons/authentication/templates/';
				$result['version'] = $vars['version'];
				$result['module'] = $vars['modulelink'];

                // 把 $result 放入模板需要输出的变量组中
                $result = $ext->getSmarty([
					'dir' => __DIR__ . '/templates/',
                    'file' => 'home',
                    'vars' => $result,
                ]);
            }
            catch (Exception $e) {
                // 输出错误信息
                $result = $ext->getSmarty([
                    'file' => 'tips/danger',
                    'vars' => [
                        'message' => $e->getMessage(),
                    ],
                ]);
            }
            finally {
                echo $result;
            }
        }
        catch (Exception $e) {
            // 如果报错则终止并输出错误
            die($e->getMessage());
        }
    }
}