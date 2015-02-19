Formidable.Classes.ListerRow = Formidable.inherit({
	uid: false,
	_parent: false,
	data: {},
	__constructor: function(oConfig) {
		this.uid = false;
		this._parent = false;
		this.data = {};

		Object.extend(this, oConfig);
	},
	rdt: function(sRdt) {
		if((sRdtId = this._parent.config.rdtbyrow[this.uid][sRdt])) {
			return this._parent.oForm.o(sRdtId);
		} else {
			sPath = sRdt.replace(".", "/");
			aPath = sPath.split("/");
			if((oRdt = this.rdt(aPath[0]))) {
				aPath.shift();
				i = 0;
				aPath.each(function(sPathSegment) {
					if((oRdt = oRdt.rdt(sPathSegment))) {
						i++;
					} else {
						throw $break;
					}
				}.bind(this));
				if(i == aPath.size()) {
					return oRdt;
				}
			}
		}

		return false;
	}
});

Formidable.Classes.Lister = Formidable.inherit({
	allselected: false,
	init: function() {
		if(this.config.isajaxlister) {

			oFirst = Formidable.getElementById(this.config.id + "_pagelink_first");
			oPrev = Formidable.getElementById(this.config.id + "_pagelink_prev");
			oNext = Formidable.getElementById(this.config.id + "_pagelink_next");
			oLast = Formidable.getElementById(this.config.id + "_pagelink_last");

			if(oFirst) {
				oFirst.href="javascript:void(0);";
				Formidable.attachEvent(oFirst, "click", this.repaintFirst.bind(this));
			}

			if(oPrev) {
				oPrev.href="javascript:void(0);";
				Formidable.attachEvent(oPrev, "click", this.repaintPrev.bind(this));
			}

			if(oNext) {
				oNext.href="javascript:void(0);";
				Formidable.attachEvent(oNext, "click", this.repaintNext.bind(this));
			}

			if(oLast) {
				oLast.href="javascript:void(0);";
				Formidable.attachEvent(oLast, "click", this.repaintLast.bind(this));
			}

			for (iPage in this.config.repaintwindow) {
				oWindow = Formidable.getElementById(this.config.id + "_pagelink_window_" + iPage);
				if(oWindow) {
					oWindow.href="javascript:void(0);";
					Formidable.attachEvent(oWindow, "click", this.repaintWindow.bind(this));
				}
			};

			for(var iKey in this.config.columns) {
				sCol = this.config.columns[iKey];
				oSortLink = Formidable.getElementById(this.config.id + "_sortlink_" + sCol)
				if(oSortLink) {
					oSortLink.href="javascript:void(0);";
					oSortLink.sortcol = sCol;
					if(this.config.sort.column == sCol) {
						oSortLink.sortdir = this.config.sort.direction;
					} else {
						oSortLink.sortdir = "no";
					}

					Formidable.attachEvent(oSortLink, "click", this.repaintSortBy.bind(this));
				}
			}

			this.initSelectrow();
		}
	},
	initSelectrow: function() {
		if(this.config.iseditablelister) {
			if(this.config.selectedrow == null) {
				this.config.selectedrow = [];
			}

			var elSelectAll = Formidable.getElementById("selectall-" + this.config.id);
			Formidable.attachEvent(elSelectAll, "click", this.displayBoxSelectAll.bind(this));

			if(Formidable.getElementById(this.config.id)) {
				checkboxes = Formidable.getElementsByAdvancedSelector('#' + this.config.id.split('.').join('\\.') + ' .formidable-selectrow')
				Formidable.attachEvent(checkboxes, "click", this.toggleSelectRow.bind(this));
			}

			this.checkSelectedRow();
		}
	},
	checkSelectedRow: function() {
		if(this.config.iseditablelister) {
			for(var key in this.config.selectedrow) {
				idRow = this.config.selectedrow[key];
				selectRow = Formidable.getElementById(this.config.id + '.' + idRow + '.selectrow');
				if(selectRow) {
					selectRow.checked = true;
				}
			}
		}
	},
	toggleSelectRow: function(event) {
		var selectRow = Formidable.getElementById(event.target.id);

		var value = event.target.id.replace(this.config.id + '.', '');
		value = value.replace('.selectrow', '');
		value = value.replace('row', '');
		
		if(selectRow.checked) { 
			this.selectRow(value);
		} else {
			this.unselectRow(value);
		}
	},
	selectRow: function(value) {
		this.config.currentrows = [];
		this.config.currentrows[value] = value;
		Formidable.globalEval(this.config.selectrow);
	},
	unselectRow: function(value) {
		var elSelectAll = Formidable.getElementById("selectall-" + this.config.id);
		elSelectAll.checked = false;
		
		this.config.currentrows = [];
		this.config.currentrows[value] = value;
		Formidable.globalEval(this.config.unselectrow);
	},
	unselectThisPage: function(event) {
		Formidable.unattachEvent(document, "click");
		var elWrap = Formidable.getElementById("selectallwrap-" + this.config.id);
		elWrap.style.display = 'none';

		this.config.currentrows = [];
			
		checkboxes = Formidable.getElementsBySelector('formidable-selectrow');
		for(var key in checkboxes.get()) {
			checkbox = checkboxes[key];
			if(checkbox) {
				value = checkbox.id.replace(this.config.id + '.', '');
				value = value.replace('.selectrow', '');
				value = value.replace('row', '');

				this.config.currentrows[value] = value;
				checkbox.checked = false;
			}
		}

		Formidable.globalEval(this.config.unselectrow);
	},
	unselectAllPages: function(event) {
		Formidable.unattachEvent(document, "click");
		var elWrap = Formidable.getElementById("selectallwrap-" + this.config.id);
		elWrap.style.display = 'none';

		checkboxes = Formidable.getElementsBySelector('formidable-selectrow');
		for(var key in checkboxes.get()) {
			checkbox = checkboxes[key];
			if(checkbox) {		
				checkbox.checked = false;
			}
		}
		Formidable.globalEval(this.config.unselectallrows);
	},
	selectThisPage: function(event) {
		Formidable.unattachEvent(document, "click");
		var elWrap = Formidable.getElementById("selectallwrap-" + this.config.id);
		elWrap.style.display = 'none';

		this.config.currentrows = [];
			
		checkboxes = Formidable.getElementsBySelector('formidable-selectrow');
		for(var key in checkboxes.get()) {
			checkbox = checkboxes[key];
			if(checkbox) {
				value = checkbox.id.replace(this.config.id + '.', '');
				value = value.replace('.selectrow', '');
				value = value.replace('row', '');

				this.config.currentrows[value] = value;
				checkbox.checked = true;
			}
		}

		Formidable.globalEval(this.config.selectrow);	
	},
	selectAllPages: function(event) {
		Formidable.unattachEvent(document, "click");
		var elWrap = Formidable.getElementById("selectallwrap-" + this.config.id);
		elWrap.style.display = 'none';

		checkboxes = Formidable.getElementsBySelector('formidable-selectrow');
		for(var key in checkboxes.get()) {
			checkbox = checkboxes[key];
			if(checkbox) {		
				checkbox.checked = true;
			}
		}
		Formidable.globalEval(this.config.selectallrows);
	},
	displayBoxSelectAll: function(event) {
		var elWrap = Formidable.getElementById("selectallwrap-" + this.config.id);
		var elSelectAll = Formidable.getElementById("selectall-" + this.config.id);
		var elSelectThisPage = Formidable.getElementById("thispage-" + this.config.id);
		var elSelectAllPages = Formidable.getElementById("allpages-" + this.config.id);
		
		elWrap.style.display = 'block';

		Formidable.unattachEvent(elSelectThisPage, "click");
		Formidable.unattachEvent(elSelectAllPages, "click");

		if(elSelectAll.checked) {
			Formidable.attachEvent(elSelectThisPage, "click", this.selectThisPage.bind(this));
			Formidable.attachEvent(elSelectAllPages, "click", this.selectAllPages.bind(this));
		} else {
			Formidable.attachEvent(elSelectThisPage, "click", this.unselectThisPage.bind(this));
			Formidable.attachEvent(elSelectAllPages, "click", this.unselectAllPages.bind(this));
		}
		window.currentId = this.config.id;
		var timeout = setTimeout('Formidable.attachEvent(document, "click", function() {var elWrap = Formidable.getElementById("selectallwrap-" + window.currentId); elWrap.style.display = "none"; Formidable.unattachEvent(document, "click");});', 100);
	},
	getValue: function() {
		oResults = {};

		aRows = this.getRows();
		aRows.each(function(oRow) {
			oResults[oRow.uid] = {};
			$H(this.config.columns).each(function(column, key) {
				oRdt = oRow.rdt(this.config.columns[key]);
				oResults[oRow.uid][this.config.columns[key]] = oRdt.getValue();
			}.bind(this));
		}.bind(this));

		return oResults;
	},
	getRow: function(iUid) {
		return new Formidable.Classes.ListerRow({
			"uid": iUid,
			"_parent": this,
			"data": this.config.clientified[iUid]
		});
	},
	getRows: function() {
		var aRows = $A();

		$H(this.config.rdtbyrow).each(function(oData) {
			aRows.push(this.getRow(oData[0]));
		}.bind(this));

		return aRows;
	},
	getCurrentRow: function() {
		aContext = this.oForm.getContext();
		if(typeof(aContext.currentrow) != "undefined") {
			return this.getRow(aContext.currentrow);
		}
	},
	repaintFirst: function() {
		Formidable.globalEval(this.config.repaintfirst);
		this.checkSelectedRow();
	},
	repaintPrev: function() {
		Formidable.globalEval(this.config.repaintprev);
		this.checkSelectedRow();
	},
	repaintNext: function() {
		Formidable.globalEval(this.config.repaintnext);
		this.checkSelectedRow();
	},
	repaintLast: function() {
		Formidable.globalEval(this.config.repaintlast);
		this.checkSelectedRow();
	},
	repaintSortBy: function(event) {
		eval(this.config.repaintsortby);	// not globalEval to pass local arguments (event) to further methods
		this.checkSelectedRow();
	},
	repaintWindow: function(event) {
		iNameLength = event.currentTarget.name.length;
		iPage = event.currentTarget.id.replace('tx_amswwf_pi1_list.evenements_pagelink_window_', '');
		
		eval(this.config.repaintwindow[iPage]); // not globalEval to pass local arguments (event) to further methods
		this.checkSelectedRow();
	},
	getParamsForMajix: function(aValues, sEventName, aParams, aRowParams, aLocalArgs) {
		aValues = this.base(aValues, sEventName, aParams, aRowParams, aLocalArgs);
		aValues["sys_event"] = {};

		for(var iParamKey in aParams) {
			if(aParams[iParamKey] == "sys_event.sortcol") {
				//oElement = Event.element(aLocalArgs[0]);
				oElement = aLocalArgs[0].target;
				aValues["sys_event"].sortcol = oElement.sortcol;
			}

			if(aParams[iParamKey] == "sys_event.sortdir") {
				//oElement = Event.element(aLocalArgs[0]);
				oElement = aLocalArgs[0].target;
				if(oElement.sortdir == "asc") {
					aValues["sys_event"].sortdir = "desc";

				} else {
					// covers "no" and "desc"
					aValues["sys_event"].sortdir = "asc";
				}
			}

			if(aParams[iParamKey] == "sys_event.currentrows") {
				//oElement = Event.element(aLocalArgs[0]);
				var currentRowsValue = '';
				for(var selectKey in this.config.currentrows) {
					if(currentRowsValue == '') {
						currentRowsValue = this.config.currentrows[selectKey];
					} else {
						currentRowsValue = currentRowsValue + "," + this.config.currentrows[selectKey];
					}
				}
				aValues["sys_event"].currentrows = currentRowsValue;
			}

			/*
			if(aParams[iParamKey] == "sys_event.selectedrow") {
				sValue = '';
				for(var iSelectKey in this.config.selectedrow) {
					if(sValue != '') {
						sValue = sValue + ',' + this.config.selectedrow[iSelectKey];
					} else {
						sValue = this.config.selectedrow[iSelectKey];
					}
				}
				aValues["sys_event"].selectedrow = sValue;
			}	*/
		}

		return aValues;
	}
}, Formidable.Classes.RdtBaseClass);
