<?php
if (!defined("WHMCS"))
	die("This file cannot be accessed directly");
// NeWorld Manager 开始

// 引入文件
require  ROOTDIR . '/modules/addons/NeWorld/library/class/NeWorld.Common.Class.php';

// NeWorld Manager 结束
add_hook('AdminAreaClientSummaryPage', 1, function($vars) {
    $userID = $vars['userid'];
    $verifyinfo = \Illuminate\Database\Capsule\Manager::table('mod_authentication')->where('uid', $userID)->first()->validation;

	if ( $verifyinfo == 'yes' ) {
		$verifyinfo = '<span class="label label-success">已通过实名认证</span>';
	} else {
		$verifyinfo = '<span class="label label-danger">未通过实名认证</span>';
	}

    return $verifyinfo;
});

add_hook('ClientAreaPage', 1, function ($vars){
    $userID = $_SESSION['uid'];

    if (isset($userID)) {

        $verifyinfo = \Illuminate\Database\Capsule\Manager::table('mod_authentication')->where('uid', $userID)->first()->validation;

		if ( $verifyinfo == 'yes' ) {
			$verifyinfo = '<a href="'.$systemurl.'index.php?m=authentication" class="label label-success">已通过实名认证</a>';
		} else {
			$verifyinfo = '<a href="'.$systemurl.'index.php?m=authentication" class="label label-danger" data-toggle="tooltip" data-placement="bottom" title="未实名认证，点击进行认证">未通过实名认证</a>';
		}

    }

    return [
        'verifyinfo' 	=> $verifyinfo,
    ];
});

$coercion = \Illuminate\Database\Capsule\Manager::table('tbladdonmodules')->where('module', 'authentication')->where('setting', 'coercion')->first()->value;

if ( !empty( $coercion ) ) {
	$coercion_pages = \Illuminate\Database\Capsule\Manager::table('tbladdonmodules')->where('module', 'authentication')->where('setting', 'coercion_pages')->first()->value;
	switch ( $coercion_pages ) {
		case '用户中心':
			$add_hooks = 'ClientAreaPage';
			break;
		case '产品详情':
			$add_hooks = 'ClientAreaPageProductDetails';
			break;
		case '购物车':
			$add_hooks = 'ClientAreaPageCart';
			break;
		default:
			$add_hooks = '';
			break;
	}
}
if ( !empty( $add_hooks ) ) {
	add_hook($add_hooks, 1, function ($vars) {
	    $userID = $_SESSION['uid'];
		$verifyinfo = \Illuminate\Database\Capsule\Manager::table('mod_authentication')->where('uid', $userID)->first()->validation;
		$url = explode('m=', $_SERVER['REQUEST_URI']);
		//print_r($url[1]);die();
		if ( $url[1] != 'authentication' ) {
			if ( $verifyinfo != 'yes' ) {
				// 实例化扩展类
			    $ext = new NeWorld\Extended;
			    // 把 $result 放入模板需要输出的变量组中
			    $result = $ext->getSmarty([
					'dir' => __DIR__ . '/templates/',
			        'file' => 'coercion',
			        'vars' => $result,
			    ]);
			    echo $result;
			}
		}
	});
}