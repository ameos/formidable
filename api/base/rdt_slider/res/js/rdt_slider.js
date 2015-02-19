Formidable.Classes.Slider = Formidable.inherit({
	aHandlers: {},
	oSlider: null,
	__constructor: function(oConfig) {
		this.aHandlers = {};
		this.oSlider = null;
		
		this.base(oConfig);
		
		oOptions = oConfig.options;
		oOptions.distance = parseInt(oOptions.distance);
		oOptions.min = parseInt(oOptions.min);
		oOptions.max = parseInt(oOptions.max);
		oOptions.step = parseInt(oOptions.step);
		
		// init mode
		if(oConfig.mode === "items") {
			oOptions.min = 0;
			oOptions.max = (this.countProps(oConfig.items) - 1);
			oOptions.step = 0;
			oOptions.value = this.value_fromItemToNumeric(oOptions.value);
			oOptions.values = null;
		}
		
		// attaching mandatory system actions
		this.addHandler("onslidestart", this.onSlideChange_systemAction.bind(this));
		this.addHandler("onslide", this.onSlideChange_systemAction.bind(this));
		this.addHandler("onslidechange", this.onSlideChange_systemAction.bind(this));
		this.addHandler("onslidestop", this.onSlideChange_systemAction.bind(this));
		
		this.config.options = oOptions;
		
		this.init();
	},
	countProps: function(obj) {
	    var count = 0;

	    for(var prop in obj) {
	        if(obj.hasOwnProperty(prop)) {
				count += 1;
			}
	    }
	
		return count;
	},
	init: function() {
		Formidable.onDomLoaded(function() {

			oOptions = this.config.options;
			oOptions.distance = parseInt(oOptions.distance);
			
			oOptions.start = this.onSlideStart_handler.bind(this);
			oOptions.slide = this.onSlide_handler.bind(this);
			oOptions.change = this.onSlideChange_handler.bind(this);
			oOptions.stop = this.onSlideStop_handler.bind(this);
			
			this.oPlaceHolder = Formidable.getElementById(this.config.placeholderid);
			this.oSlider = $(this.oPlaceHolder).slider(oOptions);
			
			// attaching custom events
			// this.attachEvents();
		}.bind(this));
	},
	
	// Event handlers
	onSlideStart_handler: function(event, ui) {
		for(var iKey in this.aHandlers["onslidestart"]) {
			this.aHandlers["onslidestart"][iKey](this.getValue(), {
				"value": ui.value
			});
		}
	},
	onSlide_handler: function(event, ui) {
		for(var iKey in this.aHandlers["onslide"]) {
			this.aHandlers["onslide"][iKey](this.getValue(), {
				"value": ui.value
			});
		}
	},
	onSlideChange_handler: function(event, ui) {
		for(var iKey in this.aHandlers["onslidechange"]) {
			this.aHandlers["onslidechange"][iKey](this.getValue(), {
				"value": ui.value
			});
		}
	},
	onSlideStop_handler: function(event, ui) {
		for(var iKey in this.aHandlers["onslidestop"]) {
			this.aHandlers["onslidestop"][iKey](this.getValue(), {
				"value": ui.value
			});
		}
	},
	
	// System action
	onSlideChange_systemAction: function(value, ui) {
		this.domNode().value = this.value_fromNumericToItem(ui.value);	// and not value, as it might be to early in the event process to have the correct value in this.domNode().value
	},
	
	getValue: function() {
		sValue = this.domNode().value;
		return sValue;
	},
	
	value_fromNumericToItem: function(sValue) {
		if(this.config.mode === "numeric") {
			return sValue;
		}
		
		return this.config.items[sValue]["value"];
	},
	value_fromItemToNumeric: function(sValue) {
		if(this.config.mode === "numeric") {
			return sValue;
		}
		
		for(var numericValue in this.config.items) {
			if(this.config.items[numericValue]["value"] === sValue) {
				return numericValue;
			}
		};
		
		// should never happen	
		return sValue;
	}
}, Formidable.Classes.RdtBaseClass);
