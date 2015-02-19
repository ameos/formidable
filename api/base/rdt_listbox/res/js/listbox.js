Formidable.Classes.ListBox = Formidable.inherit({
	replaceData: function(oData) {
		this.clearData();
		for(var key in oData) {
			var oOption = new Option(oData[key], key);
			this.domNode().options[this.domNode().options.length] = oOption;

			if(oData[key] != null) {
				this.domNode().options[(this.domNode().options.length - 1)].innerHTML = oData[key];
			} else {
				this.domNode().options[(this.domNode().options.length - 1)].innerHTML = "";
			}
				// because new Option doesn't substitute HTML special chars like &gt;
				// has to be done after insertion in options[] in IE for unknown buggy reason
		}
	},
	getData: function (bValuesOnly) {
		var aOptions = this.domNode().options;
		var aRes = {};
		$A(aOptions).each(function(oOption, key) {
			if(!bValuesOnly) {
				aRes[oOption.value] = oOption.innerHTML;
			} else {
				aRes[key] = oOption.value;
			}
		}.bind(this));

		return aRes;
	},
	setValue: function(sData) {
		this.clearValue();
		if(typeof(sData) == "object" && (sData instanceof Array)) {
			$A(this.domNode().options).each(function(oOption, key) {
				for(iDataKey in sData) {
					if (sData[iDataKey] == this.domNode().options[key].value) {
						this.domNode().options[key].selected = true;
					}
				}
			}.bind(this));
		} else {
			this.domNode().value = sData;
		}
	},
	setSelected: function(sData) {
		if(this.domNode()) {
			this.domNode().value = sData;
		}
	},
	setAllSelected: function() {
		if(this.domNode()) {
			$A(this.domNode().options).each(function(oOption, key) {
				this.domNode().options[key].selected = true;
			}.bind(this));
		}
	},
	setNoneSelected: function() {
		if(this.domNode()) {
			aOptions = this.domNode().options;
			for(var i in aOptions) {
				if(aOptions[i]) {
					aOptions[i].selected = false;
				}
			}
			/*
			$A(this.domNode().options).each(function(oOption, key) {
				if(this.domNode().options[key]) {
					this.domNode().options[key].selected = false;
				}
			}.bind(this));
			*/
		}	
		this.domNode().selectedIndex = -1;
	},
	clearData: function(oData) {
		this.clearValue();
		this.domNode().options.length = 0;
	},
	clearValue: function(oData) {
		this.setNoneSelected();
	},
	rebirth: function(oValue) {
		this.domNode().value = oValue;
	},
	getCaptionForValue: function(sValue) {
		var aOptions = this.domNode().options;
		var mFound = false;

		$A(aOptions).each(function(oOption, key) {
			if(oOption.value == sValue) {
				mFound = oOption.text;
			}
		}.bind(this));

		return mFound;
	},
	addItem: function(aParams) {
		if(this.domNode()) {
			this.domNode().options[this.domNode().options.length] = new Option(
				aParams["caption"],
				aParams["value"]
			);
		}
	},
	removeItem: function(aParams) {
		if(this.domNode()) {
			$A(this.domNode().options).each(function(oOption, key) {
				if(oOption.value == aParams["value"]) {
					this.domNode().options[key] = null;
				}
			}.bind(this));
		}
	},
	modifyItem: function(aParams) {
		if(this.domNode()) {
			$A(this.domNode().options).each(function(oOption, key) {
				if(this.domNode().options[key].value == aParams["value"]) {
					this.domNode().options[key].text = aParams["caption"];
				}
			}.bind(this));
		}
	},
	transferSelectedTo: function(aParams) {
		if((oOtherList = this.oForm.o(aParams["list"]))) {
			aValues = this.getValue();
			$A(aValues).each(function(sValue) {
				oOtherList.domNode().options[oOtherList.domNode().options.length] = new Option(this.getCaptionForValue(sValue), sValue);
				if(aParams["removeFromSource"] == true) {
					this.removeItem({"value": sValue});
				}
			}.bind(this));
		}
	},
	moveSelectedTop: function() {
		oDomNode = this.domNode();
		var iIndex = oDomNode.selectedIndex;
		if(iIndex > 0) {
			oSelected = oDomNode.options[iIndex];
			oNewOption = new Hash();
			oNewOption.set(oSelected.value, oSelected.innerHTML);
			oNewData = oNewOption.merge($H(this.getData())).toObject();
			this.replaceData(oNewData);
			oDomNode.selectedIndex = 0;
		}
	},
	moveSelectedUp: function() {
		oDomNode = this.domNode();
		var iIndex = oDomNode.selectedIndex;
		if(iIndex > 0) {
			oSelected = oDomNode.options[iIndex];
			oTarget = oDomNode.options[iIndex-1];
			oNewTarget = new Option(oSelected.innerHTML, oSelected.value);
			oNewSelected = new Option(oTarget.innerHTML, oTarget.value);
			oDomNode.options[iIndex-1] = oNewTarget;
			oDomNode.options[iIndex] = oNewSelected;
			oDomNode.selectedIndex = (iIndex-1);
		}
	},
	moveSelectedDown: function() {
		oDomNode = this.domNode();
		var iIndex = oDomNode.selectedIndex;
		if(iIndex >= 0 && iIndex < (oDomNode.options.length - 1)) {
			oSelected = oDomNode.options[iIndex];
			oTarget = oDomNode.options[iIndex+1];
			oNewTarget = new Option(oSelected.innerHTML, oSelected.value);
			oNewSelected = new Option(oTarget.innerHTML, oTarget.value);
			oDomNode.options[iIndex+1] = oNewTarget;
			oDomNode.options[iIndex] = oNewSelected;
			oDomNode.selectedIndex = (iIndex+1);
		}
	},
	moveSelectedBottom: function() {
		oDomNode = this.domNode();
		var iIndex = oDomNode.selectedIndex;
		if(iIndex >= 0 && iIndex < (oDomNode.options.length - 1)) {
			oSelected = oDomNode.options[iIndex];
			oNewOption = new Hash();
			oNewOption.set(oSelected.value, oSelected.innerHTML);
			oNewData = $H(this.getData());
			oNewData.unset(oSelected.value);
			oNewData = oNewData.merge(oNewOption).toObject();
			this.replaceData(oNewData);
			oDomNode.selectedIndex = (oDomNode.options.length - 1);
		}
	},
	disableItem: function(sValue) {
		if(this.domNode()) {
			$A(this.domNode().options).each(function(oOption, key) {
				if(this.domNode().options[key].value == sValue) {
					this.domNode().options[key].disabled = 'disabled';
				}
			}.bind(this));
		}
	},
	enableAll: function(sValue) {
		if(this.domNode()) {
			$A(this.domNode().options).each(function(oOption, key) {
				this.domNode().options[key].disabled = '';
			}.bind(this));
		}
	}
}, Formidable.Classes.RdtBaseClass);
