upload_size = wpvp_vars.upload_size;
file_upload_limit = wpvp_vars.file_upload_limit;
wpvp_ajax = wpvp_vars.wpvp_ajax;
jQuery(document).ready(function(){
	var fileSize;
	jQuery('.wpvp-submit').click(function(e){
		e.preventDefault();
		var errors = false;
		var form = jQuery('form.wpvp-processing-form');
		var action = form.attr('id');
		var formD = form.serialize();
		form.find('.wpvp_require').each(function(){
			jQuery(this).addClass('cjecled');
			if(!jQuery(this).val()){
				errors = true;
				jQuery(this).next('.wpvp_error').html('This field is required.');
			} else {
				jQuery(this).next('.wpvp_error').html('');
			}
		}).promise().done(function(){
			if(errors){
				return false;
			} else {
				if(window.fileSize>file_upload_limit){
					errors = true;
					jQuery('.wpvp_file_error').html('Video size exceeds allowed '+upload_size);
					return false;
				} else{
					jQuery('.wpvp_file_error').html('');
				}
				if(!errors){
					console.log(action);
					if(action=='wpvp-update-video'){
						var data = {
							action: 'wpvp_process_update',
							'cookie': encodeURIComponent(document.cookie),
							formData: formD
						};
						jQuery.post(wpvp_ajax,data,function(response){
							var obj = JSON.parse(response);
							var status = '';
							if(obj.hasOwnProperty('status'))
								status = obj.status;
							var msg = [];
							if(obj.hasOwnProperty('msg'))
								msg = obj.msg;
							if(msg instanceof Array){
								var msgBlock = jQuery('.wpvp_msg');
								msgBlock.html('');
								for(var i=0; i < msg.length; i++){
									msgBlock.append(msg[i]);
								}
							}
						});
					} else {
						wpvp_progressBar();
						form.submit();
					}
				}
			}
		});
	});
	jQuery('input[name=async-upload]').bind('change', function() {
		window.fileSize = this.files[0].size;
	});
});

// TODO: js check if not implemented
function wpvp_openFile(file) {
	var extension = file.substr( (file.lastIndexOf('.') +1) );
	switch(extension) {
	case 'jpg':
		case 'png':
		case 'gif':
			alert('was jpg png gif');
				break;
		case 'zip':
		case 'rar':
			alert('zip,rar');
				break;
		case 'pdf':
				alert('pdf');
				break;
		default:
			alert('else');
	}
};
        
function wpvp_progressBar() {
	jQuery('.wpvp_upload_progress').css('display','block');
	return true;
};	
