Formidable.Classes.Box = Formidable.inherit({
	domNodeHidden: function() {
		if(this.hasData()) {
			var oNodeHidden = $(this.config.id + "_value");
			if(oNodeHidden) {
				return oNodeHidden;
			}
		}

		return null;
	},
	hasData: function() {
		return (this.config.hasdata == true);
	},
	replaceData: function(sData) {
		this.setHtml(sData);
		this.setValue(sData);
	},
	getValue: function() {
		if(this.hasData()) {
			return this.domNodeHidden().value;
		} else {			
			aResult = {};
			for(var sName in this.childs()) {
				aResult[sName] = this.child(sName).getValue()
			}

			return aResult;
		}
	},
	getParamsForMajix: function(aValues, sEventName, aParams, aRowParams, aLocalArgs) {

		aValues = this.base(aValues, sEventName, aParams, aRowParams, aLocalArgs);
		if(sEventName == "ondragdrop") {
			aValues["sys_draggable"] = this.oForm.o(aLocalArgs[0].id).getName();
			aValues["sys_draggable_value"] = this.oForm.o(aLocalArgs[0].id).getValue();
			aValues["sys_droppable"] = this.oForm.o(aLocalArgs[1].id).getName();
			aValues["sys_event"] = {
				"type": aLocalArgs[2].type,
				"ctrlKey": aLocalArgs[2].ctrlKey,
				"shiftKey": aLocalArgs[2].shiftKey,
				"altKey": aLocalArgs[2].altKey,
				"metaKey": aLocalArgs[2].metaKey
			};
			
			if(typeof aValues["sys_xy"] != "undefined") {
				aValues["sys_xy"] = {
					"screenX": aLocalArgs[2].screenX,
					"screenY": aLocalArgs[2].screenY,
					"clientX": aLocalArgs[2].clientX,
					"clientY": aLocalArgs[2].clientY,
					"layerX": aLocalArgs[2].layerX,
					"layerY": aLocalArgs[2].layerY,
					"pageX": aLocalArgs[2].pageX,
					"pageY": aLocalArgs[2].pageY
				};
			}
		}

		return aValues;
	},
	setHtml: function(sHtml) {
		this.domNode().innerHTML = sHtml;
	},
	setValue: function(sValue) {
		if(this.hasData()) {
			this.domNodeHidden().value = sValue;
		}
	},
	displayError: function(aErrors) {
		sMessage = '';
		$H(aErrors).each(function(value, key) {
			sMessage = sMessage + value[1].message + "\n";
		}.bind(this));
		alert(sMessage);
 	}
}, Formidable.Classes.RdtBaseClass);
