Formidable.Classes.Link = Formidable.inherit({
	getLabel: function() {
		return false;
	},
	replaceLabel: function(sLabel) {
		this.domNode().innerHTML = sLabel;
	}
}, Formidable.Classes.RdtBaseClass);
