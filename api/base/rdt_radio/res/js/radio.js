Formidable.Classes.Radio = Formidable.inherit({

	isParentObj: function() {
		return this.config.bParentObj == true;
	},
	getValue: function() {
		if(this.isParentObj()) {
			for(var k in this.config.radiobuttons) {
				var oRadio = document.getElementById(this.config.radiobuttons[k]);
				if(oRadio && oRadio.checked) {
					return oRadio.value;
				}
			}
		}
		
		return "";
	},
	attachEvent: function(sEventHandler, fFunc) {
		for(var k in this.config.radiobuttons) {
//			oObj = this.oForm.o(this.config.radiobuttons[k]);
			oObj = Formidable.getElementById(this.config.radiobuttons[k]);
			Formidable.attachEvent(oObj, sEventHandler, fFunc);			
		}
	},
	isNaturalSubmitter: function() {
		return false;
	},
	uncheck: function() {
		if(this.isParentObj()) {
			for(var k in this.config.radiobuttons) {
				this.oForm.o(this.config.radiobuttons[k]).checked = false;
			}
		}
	},
	check: function(sValue) {
		if(this.isParentObj()) {
			this.uncheck();
			for(var k in this.config.radiobuttons) {
				if(this.oForm.o(this.config.radiobuttons[k]).value == sValue) {
					this.oForm.o(this.config.radiobuttons[k]).checked = true;
				}
			}
		}
	},
	disable: function() {
		if(this.isParentObj()) {
			for(var k in this.config.radiobuttons) {
				Form.Element.disable(this.oForm.o(this.config.radiobuttons[k]));
			}
		}
	},
	enable: function() {
		if(this.isParentObj()) {
			for(var k in this.config.radiobuttons) {
				Form.Element.enable(this.oForm.o(this.config.radiobuttons[k]));
			}
		}
	}
}, Formidable.Classes.RdtBaseClass);
