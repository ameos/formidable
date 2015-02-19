Formidable.Classes.Advsearchform = Formidable.inherit({
	init: function() {
		if(this.config.searchmanagement) {
			oSave = Formidable.getElementById(this.config.id + '.managesearchbtn');
			Formidable.attachEvent(oSave, "click", this.toggleManageSearch.bind(this));
			
			oSaveBtn = Formidable.getElementById(this.config.id + '.managesearchbox.savesearchbtn');
			if(oSaveBtn) {
				Formidable.attachEvent(oSaveBtn, "click", this.saveSearch.bind(this));
			}

			oLoadBtn = Formidable.getElementById(this.config.id + '.managesearchbox.loadsearchbtn');
			if(oLoadBtn) {
				Formidable.attachEvent(oLoadBtn, "click", this.loadSearch.bind(this));
			}
			
			oRemoveBtn = Formidable.getElementById(this.config.id + '.managesearchbox.removesearchbtn');
			if(oRemoveBtn)  {
				Formidable.attachEvent(oRemoveBtn, "click", this.removeSearch.bind(this));
			}
		}
		
		for(var iKey in this.config.searchrows) {
			oRow = this.config.searchrows[iKey]
			this.initRowEvent(oRow);
		}
		
	},
	initRowEvent: function(oRow) {
		
		oAdd = Formidable.getElementById(oRow['childs']['add']['htmlid']);
		Formidable.attachEvent(oAdd, "click", this.addRow.bind(this));
	
		oRemove = Formidable.getElementById(oRow['childs']['remove']['htmlid']);
		Formidable.attachEvent(oRemove, "click", this.removeRow.bind(this));
		
		oSubquery = Formidable.getElementById(oRow['childs']['subquery']['htmlid']);
		Formidable.attachEvent(oSubquery, "click", this.addSubquery.bind(this));
	
		oSubject = Formidable.getElementById(oRow['childs']['subject']['htmlid']);
		Formidable.attachEvent(oSubject, "change", this.modifyRow.bind(this));
		
		for(var sKey in oRow['childs']) {
			oChild = oRow['childs'][sKey];
			if(sKey.substr(0, 9) == 'searchrow') {
				this.initRowEvent(oChild);
			}
		}
		
		if(oRow['childs']['childsbox']) {
			this.initRowEvent(oRow['childs']['childsbox']);
		}
	},
	addRow: function(event) {
		eval(this.config.add);
	},
	removeRow: function(event) {
		eval(this.config.remove);
	},
	modifyRow: function(event) {
		oSubject = Formidable.getElementById(event.target.id);
		oValue = Formidable.getElementById(oSubject.id.replace('.subject', '') + '.value');
		oValue_trigger = Formidable.getElementById(oSubject.id.replace('.subject', '') + '.value_trigger');
		oType = Formidable.getElementById(oSubject.id.replace('.subject', '') + '.type');

		if(oType) { Formidable.removeElement(oType); }
		if(oValue) { Formidable.removeElement(oValue); }
		if(oValue_trigger) { Formidable.removeElement(oValue_trigger); }
		
		eval(this.config.modifiy);
	},
	addSubquery: function(event) {
		eval(this.config.addsubquery);
	},
	toggleManageSearch: function(event) {
		Formidable.getElementsBySelector('.formidable_managesearchbox').toggle();
	},
	saveSearch: function() { 
		eval(this.config.savesearch);
	},
	loadSearch: function() {
		eval(this.config.loadsearch);
	},
	removeSearch: function() {
		eval(this.config.removesearch);
	},
	getRowValue: function(oRow, sKey, aRowsValue) {
		if(oRow.childs.subject) {
			aRowsValue['subject'] = $F(Formidable.getElementById(oRow.childs.subject.htmlid));
		}
				
		if(oRow.childs.type) {
			aRowsValue['type'] = $F(Formidable.getElementById(oRow.childs.type.htmlid));
		}
		
		if(oRow.childs.value) {		
			aRowsValue['value'] = $F(Formidable.getElementById(oRow.childs.value.htmlid));
		}
		
		aRowsValue['childs'] = {};

		for(var sKey in oRow.childs) {
			oChild = oRow.childs[sKey];
			if(sKey.substr(0, 9) == 'searchrow') {
				aRowsValue['childs'][sKey.substr(10)] = {};
				aRowsValue['childs'][sKey.substr(10)] = this.getRowValue(
					oChild, 
					sKey, 
					aRowsValue['childs'][sKey.substr(10)]
				);
			}
		}
		
		return aRowsValue;
	},
	getParamsForMajix: function(aValues, sEventName, aParams, aRowParams, aLocalArgs) {
		aValues = this.base(aValues, sEventName, aParams, aRowParams, aLocalArgs);
		aValues["sys_event"] = {};

		for(var iParamKey in aParams) {
			if(aParams[iParamKey] == "sys_event.searchrows") {
				searchValue = {};

				for(var sKey in this.config.searchrows) {
					oRow = this.config.searchrows[sKey];
					aRowsValue = {};

					searchValue[sKey] = this.getRowValue(oRow, sKey, aRowsValue);
				}
				aValues["sys_event"].searchrows = searchValue;
			}
			
			if(aParams[iParamKey] == "sys_event.target") {
				//oElement = Event.element(aLocalArgs[0]);
				oElement = aLocalArgs[0].target;
				sTarget = oElement.id.replace(this.config.id + '.', '');
				sTarget = sTarget.split(/searchrow_/).join('');
				sTarget = sTarget.replace('.add', '');
				sTarget = sTarget.replace('.remove', '');
				sTarget = sTarget.replace('.subject', '');
				sTarget = sTarget.replace('.subquery', '');
			
				aValues["sys_event"].target = sTarget;
			}
			
			if(aParams[iParamKey] == "sys_event.savesearch") {
				oSaveName = Formidable.getElementById(this.config.id + '.managesearchbox.savesearchname');
				oGlobalSearch = Formidable.getElementById(this.config.id + '.managesearchbox.globalsearchbtn');
				aValues["sys_event"].savesearch = $F(oSaveName);
				if(oGlobalSearch.checked) {
					aValues["sys_event"].globalsearch = 1;
				} else {
					aValues["sys_event"].globalsearch = 0;
				}
			}
			
			if(aParams[iParamKey] == "sys_event.loadsearch") {
				oLoadName = Formidable.getElementById(this.config.id + '.managesearchbox.loadsearchname');
				aValues["sys_event"].loadsearch = $F(oLoadName);
			}
		}
		
		return aValues;
	}
}, Formidable.Classes.RdtBaseClass);
