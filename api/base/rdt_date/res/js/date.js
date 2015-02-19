Formidable.Classes.Date = Formidable.inherit({
	aHandlers: $H(),
	constructor: function(oConfig) {
		this.base(oConfig);
		
		aRdtHandlers = {
			"onselect": $A(),
			"onupdate": $A(),
			"onclose": $A()
		};
		
		this.aHandlers[oConfig.idwithoutformid] = aRdtHandlers;
		
		if(this.domNode()) {
			
			
			// attaching our custom events, wrapping those of calendar.js
			this.config.calendarconf.onSelect = this.onSelect_handler.bind(this);
			this.config.calendarconf.onUpdate = this.onUpdate_handler.bind(this);
			this.config.calendarconf.onClose = this.onClose_handler.bind(this);
			
			//Calendar.setup(this.config.calendarconf);
			if(this.domNode().value != "") {
				this.replaceData(this.domNode().value);
			}
		}
	},
	addHandler: function(sHandler, fFunction) {
		this.aHandlers[this.config.idwithoutformid][sHandler].push(fFunction);
	},
	replaceData: function(sValue) {
		if(this.domNode()) {
			if(this.config.allowmanualedition) {
				this.setValue(sValue);
			} else {
				if(this.config.converttotimestamp) {
					iTstamp = parseInt(sValue);
					if(iTstamp == 0) {
						this.clearData();
					} else {
						this.setValue(iTstamp);
						if(this.getDisplayArea()) {
							oDate = new Date();
							oDate.setTime(iTstamp * 1000);
							this.getDisplayArea().innerHTML = oDate.print(this.config.calendarconf.daFormat);
						}
					}
				} else {
					this.setValue(sValue);
					if(this.getDisplayArea()) {
						this.getDisplayArea().innerHTML = sValue;
					}
				}
			}
		}
	},
	getDisplayArea: function() {
		oDisplay = $("showspan_" + this.config.id);
		if(oDisplay) {
			return oDisplay;
		}

		return false;
	},
	clearData: function() {
		if(this.getDisplayArea()) {
			this.getDisplayArea().innerHTML = this.config.emptystring;
		}
		this.domNode().value="";
	},
	clearValue: function() {
		this.clearData();
	},
	onSelect_handler: function(oCalendar, iTstamp) {
		this.default_selected(oCalendar);
		
		this.aHandlers[this.config.idwithoutformid]["onselect"].each(function(fFunc, iKey) {
			fFunc(oCalendar, iTstamp);
		});
	},
	onUpdate_handler: function(oCalendar, iTstamp) {
		this.default_selected(oCalendar);
		
		this.aHandlers[this.config.idwithoutformid]["onupdate"].each(function(fFunc, iKey) {
			fFunc(oCalendar, iTstamp);
		});
	},
	onClose_handler: function(oCalendar) {
		this.default_close(oCalendar);
		
		this.aHandlers[this.config.idwithoutformid]["onclose"].each(function(fFunc, iKey) {
			fFunc(oCalendar);
		});
	},
	default_selected: function(cal) {
		var p = cal.params;
		var update = (cal.dateClicked || p.electric);
		if (update && p.inputField) {
			p.inputField.value = cal.date.print(p.ifFormat);
			if (typeof p.inputField.onchange == "function")
				p.inputField.onchange();
		}
		if (update && p.displayArea)
			p.displayArea.innerHTML = cal.date.print(p.daFormat);

		if (update && p.singleClick && cal.dateClicked)
			cal.callCloseHandler();
	},
	default_close: function(cal) {
		cal.hide();
	}
}, Formidable.Classes.RdtBaseClass);


