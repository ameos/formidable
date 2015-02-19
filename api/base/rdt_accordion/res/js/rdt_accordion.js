Formidable.Classes.Accordion = Formidable.inherit({
	oAccordion: null,
	aHandlers: {/*
		"ontabopen": $A(),
		"ontabclose": $A(),
		"ontabchange": $A()
	*/},
	__constructor: function(oConfig) {
		this.oAccordion = null;
		this.aHandlers = {};
		
		this.base(oConfig);
		this.config.libconf["parent"] = this;
		this.oAccordion = new accordion(
			this.domNode(),
			this.config.libconf
		);
	},
	setActiveTab: function(sTab) {
		this.oAccordion.activate(
			this.rdt(sTab).domNode()
		);
	},
	next: function() {
		if(this.getCurrent()) {
			var iCurKey = this.oAccordion.accordions.indexOf(this.getCurrent().config.id);
			if(this.oAccordion.accordions[iCurKey+1]) {
				this.setActiveTab(this.oForm.o(this.oAccordion.accordions[iCurKey+1]).config.localname);
			}
		} else {
			this.first();
		}
	},
	previous: function() {
		if(this.getCurrent()) {
			var iCurKey = this.oAccordion.accordions.indexOf(this.getCurrent().config.id);
			if(this.oAccordion.accordions[iCurKey-1]) {
				this.setActiveTab(this.oForm.o(this.oAccordion.accordions[iCurKey-1]).config.localname);
			}
		} else {
			this.last();
		}
	},
	first: function() {
		this.setActiveTab(
			this.oForm.o(this.oAccordion.accordions.first()).config.localname
		);
	},
	last: function() {
		this.setActiveTab(
			this.oForm.o(this.oAccordion.accordions.last()).config.localname
		);
	},
	getCurrent: function() {
		
		if(this.oAccordion.currentAccordion) {
			oCurrentBox = $(this.oAccordion.currentAccordion.id);
			oCurrentAccordion = oCurrentBox.previous(0);
			return this.oForm.o(oCurrentAccordion.id);
		}
		
		return false;
	},
	
	onTabOpen_eventHandler: function(sTabName) {
		this.aHandlers["ontabopen"].each(function(fFunc, iKey) {
			fFunc(sTabName);
		});
	},
	onTabClose_eventHandler: function(sTabName) {
		this.aHandlers["ontabclose"].each(function(fFunc, iKey) {
			fFunc(sTabName);
		});
	},
	onTabChange_eventHandler: function(sTabName, sAction) {
		this.aHandlers["ontabchange"].each(function(fFunc, iKey) {
			fFunc(sTabName, sAction);
		});
	},
	getParamsForMajix: function(aValues, sEventName, aParams, aRowParams, aLocalArgs) {

		aValues = this.base(aValues, sEventName, aParams, aRowParams, aLocalArgs);

		aValues["sys_event"] = {};
		if(sEventName == "ontabopen" || sEventName == "ontabclose" || sEventName == "ontabchange") {
			aValues["sys_event"].tab = aLocalArgs[0];
		}

		if(sEventName == "ontabchange") {
			aValues["sys_event"].action = aLocalArgs[1];
		}

		return aValues;
	}
}, Formidable.Classes.RdtBaseClass);
