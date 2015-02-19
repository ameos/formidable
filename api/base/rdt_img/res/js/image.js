Formidable.Classes.Image = Formidable.inherit({
	setSrc: function(sPath) {
		if(this.domNode()) {
			this.domNode().src=sPath;
			return this;
		}
	},
	setWidthPx: function(iWidth) {
		if(this.domNode()) {
			this.domNode().style.width = iWidth + "px";
			return this;
		}
	},
	setHeightPx: function(iHeight) {
		if(this.domNode()) {
			this.domNode().style.height = iHeight + "px";
			return this;
		}
	}
}, Formidable.Classes.RdtBaseClass);
