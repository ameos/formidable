Formidable.Classes.CheckBox = Formidable.inherit({
	isParentObj: function() {
		return this.config.bParentObj == true;
	},
	getParentObj: function() {
		if(!this.isParentObj()) {
			return this.oForm.o(this.config.parentid);
		} else {
			return this;
		}
	},
	uncheckAll: function() {
		var oParent = this.getParentObj();
		for(var k in oParent.config.checkboxes) {
			this.oForm.o(oParent.config.checkboxes[k]).checked = false;
		}
	},
	checkAll: function() {
		var oParent = this.getParentObj();
		for(var k in oParent.config.checkboxes) {
			this.oForm.o(oParent.config.checkboxes[k]).checked = true;
		}
	},
	checkNone: function() {
		var oParent = this.getParentObj();
		for(var k in oParent.config.checkboxes) {
			this.oForm.o(oParent.config.checkboxes[k]).checked = false;
		}
	},
	checkItem: function(sValue) {
		if(this.isParentObj()) {
			for(var k in this.config.checkboxes) {
				var oItem = this.oForm.o(this.config.checkboxes[k]);
				if(oItem.value == sValue) {
					oItem.checked = true;
					break;
				}
			}
		}
	},
	unCheckItem: function(sValue) {
		if(this.isParentObj()) {
			for(var k in this.config.checkboxes) {
				var oItem = this.oForm.o(this.config.checkboxes[k]);
				if(oItem.value == sValue) {
					oItem.checked = false;
					break;
				}
			}
		}
	},
	getValue: function() {
		if(this.isParentObj()) {

			var aValues = [];

			for(var k in this.config.checkboxes) {
				oChk = document.getElementById(this.config.checkboxes[k]);
				if(oChk && oChk.checked) {
					aValues[aValues.length] = oChk.value;
				}
				/*
				sValue = $F(this.oForm.o(this.config.checkboxes[k]));
				if(sValue != null) {
					
				}*/
			}
			return aValues;
		} else {
			//return $F(this);
			return "";
		}
	},
	getMajixThrowerIdentity: function(sObjectId) {
		var oParent = this.getParentObj();

		for(var k in oParent.config.checkboxes) {
			var oItem = this.oForm.o(oParent.config.checkboxes[k]);
			if(oItem.id == sObjectId) {
				return oItem.value;
				break;
			}
		}

		return sObjectId;
	},
	attachEvent: function(sEventHandler, fFunc) {
		var oParent = this.getParentObj();
		for(var k in oParent.config.checkboxes) {
			oObj = this.oForm.o(oParent.config.checkboxes[k]);
			Formidable.attachEvent(oObj, 'click', fFunc.bind(oObj));
		}
	},
	getItem: function(sValue) {
		var oParent = this.getParentObj();

		for(var k in oParent.config.checkboxes) {
			var oItem = this.oForm.o(oParent.config.checkboxes[k]);
			if(oItem.value == sValue) {
				return oItem;
				break;
			}
		}

		return false;
	},
	disableAll: function() {
		var oParent = this.getParentObj();

		for(var k in oParent.config.checkboxes) {
			var oItem = this.oForm.o(oParent.config.checkboxes[k]);
			oParent.disableItem(oItem.value);
		}
	},
	disableItem: function(sValue) {
		oCheckBox = this.getItem(sValue);
		if(oCheckBox) {
			oCheckBox.disabled = true;
		}
	},
	enableAll: function() {
		var oParent = this.getParentObj();

		for(var k in oParent.config.checkboxes) {
			var oItem = this.oForm.o(oParent.config.checkboxes[k]);
			oParent.enableItem(oItem.value);
		}
	},
	enableItem: function(sValue) {
		oCheckBox = this.getItem(sValue);
		if(oCheckBox) {
			oCheckBox.disabled = false;
		}
	}
}, Formidable.Classes.RdtBaseClass);
