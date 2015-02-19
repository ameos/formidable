Formidable.Classes.Date = Formidable.inherit({
	aHandlers: {},
	__constructor: function(oConfig) {
		this.base(oConfig);
		
		aRdtHandlers = {
			"onselect": [],
			"onclose": []
		};
		this.aHandlers[oConfig.idwithoutformid] = aRdtHandlers;
	},
	addHandler: function(sHandler, fFunction) {
		this.aHandlers[this.config.idwithoutformid][sHandler].push(fFunction);
	},
	onSelect_handler: function(sDate, oCalendar) {
		for(var iKey in this.aHandlers[this.config.idwithoutformid]["onselect"]) {
			this.aHandlers[this.config.idwithoutformid]["onselect"][iKey](sDate);
		}
	},
	onClose_handler: function(sDate, oCalendar) {
		for(var iKey in this.aHandlers[this.config.idwithoutformid]["onclose"]) {
			this.aHandlers[this.config.idwithoutformid]["onclose"][iKey](sDate);
		}
	}

}, Formidable.Classes.RdtBaseClass);


