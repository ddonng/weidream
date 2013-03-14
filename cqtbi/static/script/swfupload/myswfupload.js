var swfu1;
var swfu2;

window.onload = function() {
	var settings1 = {
		flash_url : "static/swf/swfupload.swf",
		upload_url: "http://static.cqtbi.esch.cn:8080/upload.php",
		post_params: {"type" :"1" , "sys" : "qlty"},
		file_size_limit : "100 MB",
		file_types : "*.*",
		file_types_description : "All Files",
		file_upload_limit : 1,
		file_queue_limit : 1,
		custom_settings : {
			progressTarget : "fsUploadProgress1",
			cancelButtonId : "btnCancel1"
		},
		debug: true,

		// Button settings
		button_image_url: "static/image/btn-object-upload-160x44.png",
		button_width: "160",
		button_height: "44",
		button_placeholder_id: "spanButtonPlaceHolder1",
		button_text: '<span class="upload_button_font">上传文件</span>',
		button_text_style: ".upload_button_font{text-align:center;color:#ffffff;}",
		//button_text_left_padding: 12,
		button_text_top_padding: 10,
		
		// The event handler functions are defined in handlers.js
		file_queued_handler : fileQueued,
		file_queue_error_handler : fileQueueError,
		file_dialog_complete_handler : fileDialogComplete,
		upload_start_handler : uploadStart,
		upload_progress_handler : uploadProgress,
		upload_error_handler : uploadError,
		upload_success_handler : uploadSuccess,
		upload_complete_handler : uploadComplete,
		queue_complete_handler : queueComplete	// Queue plugin event
	};

	var settings2 = {
		flash_url : "static/swf/swfupload.swf",
		upload_url: "http://static.cqtbi.esch.cn:8080/upload.php",
		post_params: {"type" : "2" , "sys" : "qlty"},
		file_size_limit : "100 MB",
		file_types : "*.*",
		file_types_description : "All Files",
		file_upload_limit : 100,
		file_queue_limit : 0,
		custom_settings : {
			progressTarget : "fsUploadProgress2",
			cancelButtonId : "btnCancel2"
		},
		debug: false,

		// Button settings
		button_image_url: "static/image/XPButtonUploadText_61x22.png",
		button_width: "61",
		button_height: "22",
		button_placeholder_id: "spanButtonPlaceHolder2",
		//button_text: '<span class="theFont">Hello</span>',
		//button_text_style: "btn btn-large",
		//button_text_left_padding: 12,
		//button_text_top_padding: 3,
		
		// The event handler functions are defined in handlers.js
		file_queued_handler : fileQueued,
		file_queue_error_handler : fileQueueError,
		file_dialog_complete_handler : fileDialogComplete,
		upload_start_handler : uploadStart,
		upload_progress_handler : uploadProgress,
		upload_error_handler : uploadError,
		upload_success_handler : uploadSuccess,
		upload_complete_handler : uploadComplete,
		queue_complete_handler : queueComplete	// Queue plugin event
	};

	swfu1 = new SWFUpload(settings1);
	swfu2 = new SWFUpload(settings2);
 };