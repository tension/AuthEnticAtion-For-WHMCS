<div class="u-p-medium">
	<div class="row">
		<div class="col-md-8">
			<div class="form-group col-sm-12">
            	<label for="cert_name" class="control-label">姓名</label>
                <input type="text" name="cert_name" id="cert_name" class="form-control" value="{$username}" {if $validation == 'yes'}disabled{/if} />
            </div>
            <div class="form-group col-sm-12">
                <label for="cert_no" class="control-label">身份证号</label>
                <input type="text" name="cert_no" id="cert_no" class="form-control" value="{$userno}" {if $validation == 'yes'}disabled{/if} />
            </div>
            
            <div class="form-group col-sm-12 validationdate {if $validation != 'yes'}hide{/if}">
                <label for="date" class="control-label">认证时间</label>
                <input type="text" id="validationdate" class="form-control" value="{$date}" disabled/>
            </div>
		    <div class="form-group col-sm-12 hide">
			    <div class="checkbox">
		        	<label><input type="checkbox" id="checkbox" value="check" {if $validation == 'yes'}disabled{/if}/> 阅读并同意下列条款</label>
			    </div>
		    </div>
		    <div class="form-group col-sm-12">
			    <button type="submit" id="authsubmit" class="btn btn-success" {if $validation == 'yes'}disabled{/if}>
			        <span>
			        	<span>{if $validation == 'yes'}已成功进行实名认证{else}提交认证{/if}</span>
					</span>
					<span class="button-loader hidden">
						<i class="spinner"></i>
					</span>
                </button>
		    </div>
		</div>
		<div class="col-md-4">
			<div class="col-sm-12">
				<div class="qrcodeTxt">请使用支付宝客户端扫描认证</div>
				<div class="qrcode">
					<span>
						{if $validation == 'yes'}
						<i class="zmdi zmdi-check"></i>
						{else}
						<i class="zmdi zmdi-face"></i>
						{/if}
					</span>
					<div id="qrcode" style="display: none"></div>
					<div class="loading" style="display: none">
						<div class="main">
							<div class="icon-holder">
								<i class="spinner2"></i>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	    <div class="col-sm-12" style="margin-top: 20px;">
		    <div class="alert alert-danger">
		        <p><strong>为更好的享受 {$companyname} 提供的服务，本人知晓并同意芝麻信用有权基于提供征信服务的需要向合法保存有本人信息的机构采集本人信息（包括由 {$companyname} 有权将本人在使用其服务过程中提交或产生的信息提供给芝麻信用），用于验证本人信息的真实性及提供征信服务使用。本人授权芝麻信用，可根据 {$companyname} 的查询指令，向其提供相关信息的真实性判断结果及本人的信用信息，用以交易决策使用。</strong></p>
		    </div>
	    </div>
	</div>
</div>
<link href="{$WEB_ROOT}/modules/addons/authentication/templates/assets/css/css.css?v41" rel="stylesheet">
<script src="{$WEB_ROOT}/modules/addons/authentication/templates/assets/js/qrcode.min.js"></script>
{literal}
<script>
	window.jQuery || document.write("<script src=\"//cdnjs.neworld.org/ajax/libs/jquery/3.1.0/jquery.min.js\"><\/script>");
	(function() {
		$('#authsubmit').click(function(){
		    $('#qrcode').html();
			var cert_name = $('#cert_name').val();
			var cert_no = $('#cert_no').val();
			formValidate();
			$.ajax({ //调用jQuery的ajax方法
	            type: "POST", //设置ajax方法提交数据的形式
		        timeout : 150000, //超时时间设置，单位毫秒
	            url: "", //把数据提交
	            data: "cert_name=" + cert_name + "&cert_no=" + cert_no + "&action=auth",
	            dataType: 'json',
	            beforeSend:function(XMLHttpRequest){
		            $('#authsubmit span').first().css('visibility', 'hidden');
		            $('.button-loader').removeClass('hidden');
	                $('.loading').show();
	            },
	            success: function(data) { //提交成功后的回调。
	                if (data.status == 'success') {
		                $('#qrcode img').remove();
	                    var qrcode = data.qrcode;
	                    $('#qrcode').show();
					    var qrcode = new QRCode("qrcode", {
					        text: qrcode,
					        width: 198,
					        height: 198,
					        colorDark : "#000",
					        colorLight : "#FFF",
					        correctLevel : QRCode.CorrectLevel.L
					    });
	                } else {
		                $('#qrcode').hide();
		                $('.qrcode span').css('opacity', '1').html(data.info);
	                }
	                $('.loading').hide();
		            $('.button-loader').addClass('hidden');
					$('#authsubmit').attr("disabled",true);
		            $('#authsubmit span').first().css('visibility', 'visible').text('请使用支付宝客户端扫描认证');
	            }
	        });
		});
	})();
	{/literal}
	{if $validation != 'yes'}
	{literal}
	//设置每隔1000毫秒执行一次 load() 方法
	setInterval(function(){ load() }, 10000);
	function load() {
		$.ajax({
			type: "POST",
	        url: "", //把数据提交
			data: { action: "check" },
			dataType:"json",
			success: function (data) {
				// 判断是否成功
				if (data.status == "success") {
		            $("#cert_name").attr("disabled","disabled");
		            $("#cert_no").attr("disabled","disabled");
		            $('#qrcode img').remove();
					$('.validationdate').removeClass('hide');
					$('#validationdate').val(data.date);
		            $('.qrcode span').html('<i class="zmdi zmdi-check"></i>');
		            $('#authsubmit span').first().text('已成功进行实名认证');
				}
			}
		});
	}
	{/literal}
	{/if}
	{literal}
	// 验证中文名称
	function isChinaName(name) {
	    var pattern = /^[\u4E00-\u9FA5]{1,6}$/;
	    return pattern.test(name);
	}
	
	// 验证身份证 
	function isCardNo(card) {  
	   var pattern = /(^\d{ 15 }$)|(^\d{18}$)|(^\d{ 17 }(\d|X|x)$)/;  
	   return pattern.test(card); 
	}
	// 验证函数
	function formValidate() {
	    var str = '';
	
	    // 判断名称
	    if($.trim($('#cert_name').val()).length == 0) {
	        str += '名称没有输入\n';
	        $('#cert_name').focus();
	    } else {
	        if(isChinaName($.trim($('#cert_name').val())) == false) {
	            str += '名称不合法\n';
	            $('#cert_name').focus();
	        }
	    }
	
	    // 验证身份证
	    if($.trim($('#cert_no').val()).length == 0) {  
	        str += '身份证号码没有输入\n';
	        $('#cert_no').focus();
	    } else {
	        if(isCardNo($.trim($('#cert_no').val())) == false) {
	            str += '身份证号不正确；\n';
	            $('#cert_no').focus();
	        }
	    }
	
	    // 如果没有错误则提交
	    if(str != '') {
	        alert(str);
	        return false;
	    } else {
	        $('.auth-form').submit();
	    }
	}
</script>
{/literal}