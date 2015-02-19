Formidable.Classes.ModalBox = Formidable.inherit({
	
	aSelect: [],
	oImgClose: null,
	oHtmlContainer: null,
	iVerticalPosition: 30,
	iHorizontalPosition: "center",
	defConfig: {
		"effects": true,
		showclosebutton: true,
		followScrollVertical: true,
		followScrollHorizontal: true,
		positionStyle: {
			"display": "none",
			"position": "absolute",
			"zIndex": 200000		// z-index, camel case
		},
		style: {
			"width": "auto",
			"background": "silver",
			"padding": "10px",
			"borderWidth": "2px",
			"borderStyle": "solid",
			"borderColor": "white",
			"MozBorderRadius": "3px"	// -moz-border-radius, camel case
		}
	},
	__constructor: function(config) {
		this.aSelect = [];
		this.oImgClose = null;
		this.oHtmlContainer = null;
		
		this.base(config);
		
		this.overlay = "tx_ameosformidable_modalboxoverlay";
		this.box = "tx_ameosformidable_modalboxbox";
	},
	_constructor: function(config) {
		this.aSelect = [];
		this.oImgClose = null;
		this.oHtmlContainer = null;
		
		this.base(config);
		
		this.overlay = "tx_ameosformidable_modalboxoverlay";
		this.box = "tx_ameosformidable_modalboxbox";
	},
	domNode: function() {
		return $(this.box);
	},
	hideSelects: function() {
		
		this.aSelect = [];

		aTemp = $$("body select");
		aTempNoHide = $$("#" + this.box + " select");
		
		for(var sKey in aTemp) {
			oSelect = aTemp[sKey];
			if(aTempNoHide.indexOf(oSelect) == -1) {
				if(Formidable.getStyle(oSelect, "visibility") == "" || Formidable.getStyle(oSelect, "visibility") == "inherit" || Formidable.getStyle(oSelect, "visibility") == "visible") {
					oSelect.style.visibility = "hidden";
					this.aSelect.push(oSelect);
				}
			}
		}
	},
	showSelects: function() {
		
		this.aSelect.each(function(oSelect, k) {
			
			if(Element.getStyle(oSelect, "visibility") == "hidden") {
				oSelect.style.visibility = "visible";
			}

		});

		this.aSelect = [];
	},
	resizeOverlay: function() {
		Formidable.getElementById(this.overlay).style.width = document.body.clientWidth + "px";
	},
	showBox: function(aData){
		
		Formidable.objectExtend(this.config, this.defConfig);
		Formidable.objectExtend(aData.style || {}, this.config.style);

		this.config.preuninit = aData.preuninit;
		if(aData.verticalposition) {
			this.iVerticalPosition = aData.verticalposition;
		}
		if(aData.horizontalposition) {
			this.iHorizontalPosition = aData.horizontalposition;
		}
		
		this.config.overlaystyle = {
			"display": "none",
			"background": "black",
			"position": "absolute",
			"top": "0px",
			"left": "0px",
			"zIndex": "100000",
			"width": "100%",
			"height": "100%",
			"padding": "0px",
			"margin": "0px",
			"opacity": "0.6",
			"filter": "progid:DXImageTransform.Microsoft.Alpha(opacity=60)"
		}
		if(aData.overlaystyle) {
			this.config.overlaystyle = Object.extend(this.config.overlaystyle, aData.overlaystyle || {});
		}
		//this.config.showclosebutton = false;

		if(!Formidable.getElementById(this.overlay)) {

			oOverlay = Formidable.createElement("div", {
				id:		this.overlay
			});

			document.body.appendChild(oOverlay);
			Formidable.setStyle(oOverlay, this.config.overlaystyle);
		}

		if(!Formidable.getElementById(this.box)) {

			oDivBox = Formidable.createElement("div", {
				id:		this.box
			});

			if(this.config.showclosebutton) {
				this.oImgClose = Formidable.createElement("img", {
					src: Formidable.path + "res/images/modalboxclose.gif",
					style: "position:absolute; top:-5px; right:-5px; cursor:pointer;"
				});
			}

			oTextNode = Formidable.createElement("div");
			this.oHtmlContainer = oTextNode;
			oTextNode.innerHTML = aData.html;
			/*oTextNode.select("IMG").each(function(o, k) {
				Formidable.attachEvent(o, "load", function() {
					this.align();
				}.bind(this));
			}.bind(this));*/

			if(this.config.showclosebutton) {
				oDivBox.appendChild(this.oImgClose);
			}
			oDivBox.appendChild(oTextNode);
			document.body.appendChild(oDivBox);

			Formidable.setStyle(oDivBox, this.config.style);
			Formidable.setStyle(oDivBox, this.config.positionStyle);

			for(var sKey in aData.attachevents) {
				Formidable.globalEval(aData.attachevents[sKey]);
			};
		
			for(var sKey in aData.postinit) {
				Formidable.globalEval(aData.postinit[sKey]);
			};
		}


		if(Formidable.Browser.name == "internet explorer") {
			if(Formidable.Browser.version < 7) {
				this.hideSelects();
			}
			this.resizeOverlay();
		}

		this.onScrollPointer = this.scroll.bind(this);
		this.onClosePointer = this.close.bind(this);
		
		Formidable.attachEventWindowScrollEvent(this.onScrollPointer);
		
		this.alignFirst();
		
/*		if(this.config.effects) {

			new Effect.Appear($(this.box), {
				duration: 0.5,
				fps: 50,
				afterFinish: function() {
					if(this.config.showclosebutton) {
						Event.observe(this.oImgClose, "click", this.onClosePointer);
					}
				}.bind(this)
			});
			
			$(this.overlay).show();

		} else {
			if(this.config.showclosebutton) {
				Formidable.attachEvent(this.oImgClose, "click", this.onClosePointer);
			}

			Formidable.getElementObjectById(this.overlay).show();
			Formidable.getElementObjectById(this.box).show();
		}	*/
		if(this.config.showclosebutton) {
			Formidable.attachEvent(this.oImgClose, "click", this.onClosePointer);
		}

		Formidable.getElementObjectById(this.overlay).show();
		Formidable.getElementObjectById(this.box).show();
		
		return this;
	},
	closeBox: function() {
		
		for(var sKey in this.config.preuninit) {
			Formidable.globalEval(this.config.preuninit[sKey]);
		};
		
		/*if(this.config.effects) {

			window.setTimeout(
				function() {
					if($(this.overlay)) {
						$(this.overlay).hide();
					}
				}.bind(this),
				250
			);

			new Effect.Fade($(this.box), {
				duration: 0.3,
				fps: 50,
				afterFinish: function() {	
					this.restoreOnHide();
				}.bind(this)
			});
		} else {
			$(this.overlay).hide();
			$(this.box).hide();
			this.restoreOnHide();
		}*/
		
		Formidable.getElementObjectById(this.overlay).hide();
		Formidable.getElementObjectById(this.box).hide();
		this.restoreOnHide();
			
		return false;
	},
	restoreOnHide: function() {

		if(Formidable.Browser.name == "internet explorer") {
			if(Formidable.Browser.version < 7) {
				this.showSelects();
			}
		}

		if(Formidable.getElementById(this.box)) {
			if(this.config.showclosebutton) {
				Formidable.unattachEvent(this.oImgClose, "click", this.onClosePointer);
				Formidable.removeElement(this.oImgClose);
			}
			Formidable.removeElement(Formidable.getElementById(this.box));
		}

		if(Formidable.getElementById(this.overlay)) { Formidable.removeElement(Formidable.getElementById(this.overlay));}

		Formidable.unattachEvent(window, "scroll", this.onScrollPointer);
		this.onScrollPointer = null;
		this.onClosePointer = null;
	},
	onScrollPointer: null,
	onClosePointer: null,
	alignFirst: function() {
		Formidable.Position.fullScreen(this.overlay);
		Formidable.Position.putCenterHorizontal(this.box);
		Formidable.Position.putCenterVertical(this.box);
	},
	align: function() {
		Formidable.Position.fullScreen(this.overlay);
		Formidable.Position.putCenterHorizontal(this.box);
		Formidable.Position.putCenterVertical(this.box);
	},
	scroll: function() {
		this.align();
	},
	close: function(e) {
		this.closeBox();
	},
	repaint: function(sHtml) {
		this.oHtmlContainer.innerHTML = sHtml;
		this.align();
	}
}, Formidable.Classes.RdtBaseClass);
