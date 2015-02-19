Formidable.Classes.ModalBox2 = Formidable.inherit({
	domNode: function() {
		return $(this.box);
	},
	showBox: function(aData){
		
		this.preuninit = aData.preuninit;
		
		oTextNode = new Element("div");
		oTextNode.innerHTML = aData.html;
		oOptions = {
			afterLoad: function() {
				//alert("afterLoad");
				for(var sKey in aData.attachevents) {
					Formidable.globalEval(aData.attachevents[sKey]);
				};

				for(var sKey in aData.postinit) {
					Formidable.globalEval(aData.postinit[sKey]);
				};
			},
			beforeHide: function removeObservers() {
				//alert("removeObservers");
			}
		};
		
		oOptions = Object.extend(oOptions, aData || {});
		Modalbox.show(oTextNode, oOptions);
		
		return this;
	},
	closeBox: function(oOptions) {
		for(var sKey in this.preuninit) {
			Formidable.globalEval(this.preuninit[sKey]);
		};
		
		Modalbox.hide();
		return false;
	},
	close: function(e) {
		this.closeBox();
	},
	resizeToContent: function() {
		Modalbox.resizeToContent();
	},
	resizeToInclude: function(oElement) {
		if(oElement && typeof oElement == "string") {
			oElement = this.oForm.o(oElement);
		}

		if(oElement && oElement.domNode) {
			oNode = oElement.domNode();
		} else {
			oNode = oElement;
		}

		if(oNode) {
			Modalbox.resizeToInclude(oNode);
		}
	},
	repaint: function(sHtml) {
		this.oHtmlContainer.innerHTML = sHtml;
		this.align();
	}
}, Formidable.Classes.RdtBaseClass);
