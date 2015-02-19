Formidable.Classes.TabPanel = Formidable.inherit({
	oTabPanel: null,
	/*__constructor: function(oConfig) {
		this.oTabPanel = null;
		
		this.base(oConfig);
		Formidable.objectExtend(this, oConfig);
		console.log(this.config);
		console.log(oConfig);
		console.log(this.domNode(), 'ici');
		this.oTabPanel = new Control.Tabs(
			this.domNode(),
			this.config.libconfig
		);
	},*/
	next: function() { this.oTabPanel.next();},
	previous: function() { this.oTabPanel.previous();},
	first: function() { this.oTabPanel.first();},
	last: function() { this.oTabPanel.last();},
	setActiveTab: function(sTabId) { this.oTabPanel.setActiveTab(sTabId);}
}, Formidable.Classes.RdtBaseClass);
