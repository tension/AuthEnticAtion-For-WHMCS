<!-- Page JS Plugins CSS -->
<link rel="stylesheet" href="{$assets}assets/datatables/jquery.dataTables.min.css">
<!-- Page JS Plugins -->
<script src="{$assets}assets/datatables/jquery.dataTables.min.js"></script>
<!-- Page JS Code -->
<script src="{$assets}assets/js/base_tables_datatables.js"></script>

<div class="block block-rounded block-bordered">
    <div class="block-content" style="padding:0">
        <table id="license-list" class="table table-hover js-dataTable-full small" style="margin-bottom:0">
	        <colgroup>
	        	<col width="10%">
	        	<col width="15%">
	        	<col width="10%">
	        	<col width="15%">
	        	<col width="25%">
	        	<col width="10%">
	        	<col width="10%">
	        	<col width="5%">
	        </colgroup>
            <thead>
	            <tr>
	                <th>ID</th>
	                <th>用户名</th>
	                <th>姓名</th>
	                <th>身份证号</th>
	                <th>认证号</th>
	                <th>认证时间</th>
	                <th class="text-center">状态</th>
	                <th class="text-right">操作</th>
	            </tr>
            </thead>
            <tbody>
            {if $auth}
                {foreach $auth as $value}
                    <tr id="auth_{$value['uid']}">
                        <td>#{$value['uid']}</td>
                        <td><a href="clientssummary.php?userid={$value['uid']}" data-toggle="tooltip" data-placement="bottom" title="{$value['cert_name']} - {$value['cert_no']}">{$value['username']}</a></td>
                        <td>{$value['cert_name']}</td>
                        <td>{$value['cert_no']}</td>
                        <td class="hidden-xs">{$value['biz_no']}</td>
                        <td class="hidden-xs">{$value['date']|date_format:"%Y-%m-%d"}</td>
                        <td class="text-center">
	                        {if $value['validation'] == 'yes'}
	                        	<a onClick="javascript:Change({$value['uid']|trim});" class="change btn btn-success btn-xs">已实名</a>
	                        {else}
	                        	<a onClick="javascript:Change({$value['uid']|trim});" class="change btn btn-danger btn-xs">未实名</a>
	                        {/if}
                        </td>
                        <td>
                            <a class="btn btn-danger btn-xs" onClick="javascript:Delete({$value['uid']|trim});">
                                <span class="glyphicon glyphicon-floppy-remove"></span> 删除
                            </a>
                        </td>
                    </tr>
                {/foreach}
            {else}
                <tr id="message">
                    <td colspan="6" class="text-center">
                        当前还没有添加任何内容
                    </td>
                </tr>
            {/if}
            </tbody>
        </table>
    </div>
</div>
<script type="text/javascript">
function Delete( ID ) {
	$.ajax({
		method: "POST",
		url: "{$module}&action=del",
		data: { id: ID },
		dataType: 'json',
		cache: false,
		beforeSend:function() {
			completeFlag = false;
		},
		complete:function() {
			completeFlag = true;
		},
		success: function(data) {
			if(data.status=='success') {
				$.growl.notice({
					title: "成功",
					message: "已成功删除!"
				});
			} else if (data.status=='error') {
				$.growl.warning({
		    		title: "错误",
					message: data.msg
		    	});
			};
			$('#auth_'+ID).hide(1000, function(){
				$('#auth_'+ID).animate({
                    "opacity":"0"
                },800);
			});
		},
		error:function() {
			$.growl.warning({
	    		title: "错误",
				message: "服务器忙，请稍后重试"
	    	});
		}
	});
}
function Change( ID ) {
	swal({ 
		title: "变更状态", 
		text: "此操作将进行实名认证状态变更！", 
		type: "warning",
		showCancelButton: true, 
		confirmButtonText: "确定", 
		cancelButtonText: "取消",
		closeOnConfirm: false, 
		closeOnCancel: false	
	},
	function(isConfirm){ 
		if (isConfirm) { 
			$.ajax({
				method: "POST",
				url: "{$module}&action=change",
				data: { id: ID },
				dataType: 'json',
				cache: false,
				beforeSend:function() {
					completeFlag = false;
				},
				complete:function() {
					completeFlag = true;
				},
				success: function(data) {
					if(data.status=='success') {
						$.growl.notice({
							title: "成功",
							message: "已成功变更状态!"
						});
					} else if (data.status=='error') {
						$.growl.warning({
				    		title: "错误",
							message: data.msg
				    	});
					};
					$('#auth_'+ID+' .change').toggleClass(data.msg);
				},
				error:function() {
					$.growl.warning({
			    		title: "错误",
						message: "服务器忙，请稍后重试"
			    	});
				}
			});
			swal("变成成功", "所选状态已经变更！","success"); 
		} else { 
			swal("取消", "状态未更改！","error"); 
		} 
	});
}
</script>