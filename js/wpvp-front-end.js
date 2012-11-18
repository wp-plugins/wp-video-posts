jQuery(document).ready(function(){
	var fileSize;
	jQuery('form[name=wpvp-update-video]').submit(function(){
                if((!jQuery('#wpvp-update-video input').val())||(!jQuery('textarea[name=wpvp_desc]').val())){
                        if(!jQuery('input[name=wpvp_title]').val()){
                        	jQuery('.wpvp_title_error').html('Title is missing');
                        } else{
                                jQuery('.wpvp_title_error').html('');
                        }
                        if(!jQuery('textarea[name=wpvp_desc]').val()){
                               	jQuery('.wpvp_desc_error').html('Description is missing');
                        } else{
                        	jQuery('.wpvp_desc_error').html('');
                        }
                	return false;
               	} else{
                        jQuery('.wpvp_title_error').html();
                        jQuery('.wpvp_desc_error').html();
                	return true;
        	}
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
