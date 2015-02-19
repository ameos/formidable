Formidable.Classes.PlUpload = Formidable.inherit({
	aHandlers: {},
	oUploader: null,
	__constructor: function(oConfig) {
		this.aHandlers = {};
		this.oUploader = null;

		this.base(oConfig);
		this.init();
	},
	init: function() {
		// attaching mandatory system actions
		this.addHandler("onfileuploaded", this.onFileUploaded_systemAction.bind(this));

		if(this.config.defaultbehaviour) {
			// attaching default events
			this.addHandler("onfilesadded", this.onFilesAdded_defaultAction.bind(this));
			this.addHandler("onuploadprogress", this.onUploadProgress_defaultAction.bind(this));
			this.addHandler("onerror", this.onError_defaultAction.bind(this));
			this.addHandler("onfileuploaded", this.onFileUploaded_defaultAction.bind(this));
		}

		// NOTE: the following line is executed in the post-init event
		//	to allow Formidable to re-init the rdt after repaint
		// this.plUploadObjectInit();
	},
	plUploadObjectUninit: function() {
		this.detachEvents();
		this.oUploader.destroy();
	},
	plUploadObjectInit: function() {
		var oUploaderConfig = {
			runtimes: this.config.runtimes,
			browse_button: this.config.browse_button,
			container: this.config.container,
			max_file_size: this.config.max_file_size,
			url: this.config.upload_script,
			flash_swf_url: this.config.flash_swf_url,
			silverlight_xap_url: this.config.silverlight_xap_url,
			multi_selection: this.maySelectMultiple()
		};


		if(this.config.filetype) {
			oUploaderConfig.filters = [
				{title : "Fichiers", extensions : this.config.filetype}
			];
		}

		this.oUploader = new plupload.Uploader(oUploaderConfig);
		this.oUploader.init();

		// attaching custom events
		this.attachEvents();
	},
	attachEvents: function() {
		Formidable.attachEvent(Formidable.getElementById(this.config.upload_button), 'click', this.onUploadBtnClick.bind(this));
		
		Formidable.attachEvent(this.oUploader, 'BeforeUpload', this.onBeforeUpload_handler.bind(this));		// BeforeUpload(uploader:Uploader, file:File); Fires when just before a file is uploaded
		Formidable.attachEvent(this.oUploader, 'ChunkUploaded', this.onChunkUploaded_handler.bind(this));	// ChunkUploaded(uploader:Uploader, file:File, response:Object); Fires when file chunk is uploaded
		Formidable.attachEvent(this.oUploader, 'Destroy', this.onDestroy_handler.bind(this));			// Destroy(uploader:Uploader); Fires when destroy method is called
		Formidable.attachEvent(this.oUploader, 'Error', this.onError_handler.bind(this));				// Error(uploader:Uploader, error:Object); Fires when a error occurs
		Formidable.attachEvent(this.oUploader, 'FilesAdded', this.onFilesAdded_handler.bind(this));		// FilesAdded(uploader:Uploader, files:Array); Fires while when the user selects files to upload
		Formidable.attachEvent(this.oUploader, 'FilesRemoved', this.onFilesRemoved_handler.bind(this));		// FilesRemoved(uploader:Uploader, files:Array); Fires while a file was removed from queue
		Formidable.attachEvent(this.oUploader, 'FileUploaded', this.onFileUploaded_handler.bind(this));		// FileUploaded(uploader:Uploader, file:File, response:Object); Fires when a file is successfully uploaded
		Formidable.attachEvent(this.oUploader, 'Init', this.onInit_handler.bind(this));				// Init(uploader:Uploader); Fires when the current RunTime has been initialized
		Formidable.attachEvent(this.oUploader, 'PostInit', this.onPostInit_handler.bind(this));			// PostInit(uploader:Uploader); Fires after the init event incase you need to perform actions there
		Formidable.attachEvent(this.oUploader, 'QueueChanged', this.onQueueChanged_handler.bind(this));		// QueueChanged(uploader:Uploader); Fires when the file queue is changed
		Formidable.attachEvent(this.oUploader, 'Refresh', this.onRefresh_handler.bind(this));			// Refresh(uploader:Uploader); Fires when the silverlight/flash or other shim needs to move.
		Formidable.attachEvent(this.oUploader, 'StateChanged', this.onStateChanged_handler.bind(this));		// StateChanged(uploader:Uploader); Fires when the overall state is being changed for the upload queue
		Formidable.attachEvent(this.oUploader, 'UploadComplete', this.onUploadComplete_handler.bind(this));	// UploadComplete(uploader:Uploader, files:Array); Fires when all files in a queue are uploaded
		Formidable.attachEvent(this.oUploader, 'UploadFile', this.onUploadFile_handler.bind(this));		// UploadFile(uploader:Uploader, file:File); Fires when a file is to be uploaded by the runtime
		Formidable.attachEvent(this.oUploader, 'UploadProgress', this.onUploadProgress_handler.bind(this));	// UploadProgress(uploader:Uploader, file:File); Fires while a file is being uploaded
	},
	detachEvents: function() {
		Formidable.unattachEvent(Formidable.getElementById(this.config.upload_button), 'click', this.onUploadBtnClick.bind(this));
		Formidable.unattachEvent(this.oUploader, 'BeforeUpload', this.onBeforeUpload_handler.bind(this));		// BeforeUpload(uploader:Uploader, file:File); Fires when just before a file is uploaded
		Formidable.unattachEvent(this.oUploader, 'ChunkUploaded', this.onChunkUploaded_handler.bind(this));	// ChunkUploaded(uploader:Uploader, file:File, response:Object); Fires when file chunk is uploaded
		Formidable.unattachEvent(this.oUploader, 'Destroy', this.onDestroy_handler.bind(this));			// Destroy(uploader:Uploader); Fires when destroy method is called
		Formidable.unattachEvent(this.oUploader, 'Error', this.onError_handler.bind(this));				// Error(uploader:Uploader, error:Object); Fires when a error occurs
		Formidable.unattachEvent(this.oUploader, 'FilesAdded', this.onFilesAdded_handler.bind(this));		// FilesAdded(uploader:Uploader, files:Array); Fires while when the user selects files to upload
		Formidable.unattachEvent(this.oUploader, 'FilesRemoved', this.onFilesRemoved_handler.bind(this));		// FilesRemoved(uploader:Uploader, files:Array); Fires while a file was removed from queue
		Formidable.unattachEvent(this.oUploader, 'FileUploaded', this.onFileUploaded_handler.bind(this));		// FileUploaded(uploader:Uploader, file:File, response:Object); Fires when a file is successfully uploaded
		Formidable.unattachEvent(this.oUploader, 'Init', this.onInit_handler.bind(this));				// Init(uploader:Uploader); Fires when the current RunTime has been initialized
		Formidable.unattachEvent(this.oUploader, 'PostInit', this.onPostInit_handler.bind(this));			// PostInit(uploader:Uploader); Fires after the init event incase you need to perform actions there
		Formidable.unattachEvent(this.oUploader, 'QueueChanged', this.onQueueChanged_handler.bind(this));		// QueueChanged(uploader:Uploader); Fires when the file queue is changed
		Formidable.unattachEvent(this.oUploader, 'Refresh', this.onRefresh_handler.bind(this));			// Refresh(uploader:Uploader); Fires when the silverlight/flash or other shim needs to move.
		Formidable.unattachEvent(this.oUploader, 'StateChanged', this.onStateChanged_handler.bind(this));		// StateChanged(uploader:Uploader); Fires when the overall state is being changed for the upload queue
		Formidable.unattachEvent(this.oUploader, 'UploadComplete', this.onUploadComplete_handler.bind(this));	// UploadComplete(uploader:Uploader, files:Array); Fires when all files in a queue are uploaded
		Formidable.unattachEvent(this.oUploader, 'UploadFile', this.onUploadFile_handler.bind(this));		// UploadFile(uploader:Uploader, file:File); Fires when a file is to be uploaded by the runtime
		Formidable.unattachEvent(this.oUploader, 'UploadProgress', this.onUploadProgress_handler.bind(this));	// UploadProgress(uploader:Uploader, file:File); Fires while a file is being uploaded
	},
	onUploadBtnClick: function(e) {
		this.oUploader.start();
		e.preventDefault();
	},
	getHiddenField: function() {
		return $(document.getElementById(this.config.hidden_field));
	},
	getValue: function() {
		sValue = this.getHiddenField().val();
		
		if(sValue === "" || sValue === "{\"value\":\"{\\\"value\\\":null}\"}") {
			oValues = {value: []};
		} else {
			oValues = Formidable.jsonDecode(sValue);
		}

		return oValues;
	},
	maySelectMultiple: function() {
		return this.config.multi_selection;
	},
	setValue: function(aFiles) {
		this.getHiddenField().val(Formidable.jsonEncode(aFiles));
	},
	startUpload: function() {
		this.oUploader.start();
	},

	// Event handlers
	onBeforeUpload_handler: function(up, file) {
		for(var iKey in this.aHandlers["onbeforeupload"]) {
			this.aHandlers["onbeforeupload"][iKey](up, file);
		}
	},
	onChunkUploaded_handler: function(up, file, response) {
		for(var iKey in this.aHandlers["onchunkuploaded"]) {
			this.aHandlers["onchunkuploaded"][iKey](up, file, response);
		}
	},
	onDestroy_handler: function(up) {
		for(var iKey in this.aHandlers["ondestroy"]) {
			this.aHandlers["ondestroy"][iKey](up);
		}
	},
	onError_handler: function(up, error) {
		for(var iKey in this.aHandlers["onerror"]) {
			this.aHandlers["onerror"][iKey](up, error);
		}
	},
	onFilesAdded_handler: function(up, files) {
		for(var iKey in this.aHandlers["onfilesadded"]) {
			this.aHandlers["onfilesadded"][iKey](up, files);
		}
	},
	onFilesRemoved_handler: function(up, files) {
		for(var iKey in this.aHandlers["onfilesremoved"]) {
			this.aHandlers["onfilesremoved"][iKey](up, files);
		}
	},
	onFileUploaded_handler: function(up, file, response) {
		response.response = Formidable.jsonDecode(response.response);
		for(var iKey in this.aHandlers["onfileuploaded"]) {
			this.aHandlers["onfileuploaded"][iKey](up, file, response);
		}
	},
	onInit_handler: function(up) {
		for(var iKey in this.aHandlers["oninit"]) {
			this.aHandlers["oninit"][iKey](up);
		}
	},
	onPostInit_handler: function(up) {
		for(var iKey in this.aHandlers["onpostinit"]) {
			this.aHandlers["onpostinit"][iKey](up);
		}
	},
	onQueueChanged_handler: function(up) {
		for(var iKey in this.aHandlers["onqueuechanged"]) {
			this.aHandlers["onqueuechanged"][iKey](up);
		}
	},
	onRefresh_handler: function(up) {
		for(var iKey in this.aHandlers["onrefresh"]) {
			this.aHandlers["onrefresh"][iKey](up);
		}
	},
	onStateChanged_handler: function(up) {
		for(var iKey in this.aHandlers["onstatechanged"]) {
			this.aHandlers["onstatechanged"][iKey](up);
		}
	},
	onUploadComplete_handler: function(up, files) {
		for(var iKey in this.aHandlers["onuploadcomplete"]) {
			this.aHandlers["onuploadcomplete"][iKey](up, files);
		}
	},
	onUploadFile_handler: function(up, file) {
		for(var iKey in this.aHandlers["onuploadfile"]) {
			this.aHandlers["onuploadfile"][iKey](up, file);
		}
	},
	onUploadProgress_handler: function(up, file) {
		for(var iKey in this.aHandlers["onuploadprogress"]) {
			this.aHandlers["onuploadprogress"][iKey](up, file);
		}
	},

	// Default actions
	onFilesAdded_defaultAction: function(up, files) {

		sQueueId = this.config.files_queue;
		oQueue = Formidable.getElementById(sQueueId);

		$.each(files, function(i, file) {
			$(oQueue).append(
			    '<div id="' + file.id + '">' +
			    file.name + ' (' + plupload.formatSize(file.size) + ') <b></b>' +
			'</div>');
		});

		up.refresh();
	},
	onUploadProgress_defaultAction: function(up, file) {
		$('#' + Formidable.escapeSelectorId(file.id) + " b").html(file.percent + "%");
	},
	onError_defaultAction: function(up, err) {
		sQueueId = this.config.files_queue;

		$('#' + Formidable.escapeSelectorId(sQueueId)).append("<div>Error: " + err.code +
			", Message: " + err.message +
			(err.file ? ", File: " + err.file.name : "") +
			"</div>"
		);

		up.refresh();
	},
	onFileUploaded_defaultAction: function(up, file, response) {
		$('#' + Formidable.escapeSelectorId(file.id) + " b").html("100%");
	//	this.updateValue();
	},

	// System actions (mandatory)
	onFileUploaded_systemAction: function(up, file, response) {
		sNewFileName = response.response.result.filename;	// using response, as file might have been renamed server-side
		// bug on jsonencode : use an array in an object
		if(this.maySelectMultiple()) {
			// adding new file to current value
			oValue = this.getValue();
			if(oValue.value == null || oValue.value == '') {
				oValue.value = [];
			}
			oValue.value.push(sNewFileName);
		} else {
			// replacing value with the new file
			oValue = {};
			oValue.value = [];
			oValue.value.push(sNewFileName);
		}

		this.setValue(oValue);
	}
}, Formidable.Classes.RdtBaseClass);

/* Bug plupload : Use JQUERY for getSize function */
plupload.getSize = function(node) {
	return {
		w : $(node).width(),
		h : $(node).height()
	};
};

