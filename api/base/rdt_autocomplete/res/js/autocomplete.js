Formidable.Classes.Autocomplete = Formidable.inherit({

	oText: null,
	oList: null,

	__constructor: function(oConfig) {
		this.oText = this.oList = null;
		this.base(oConfig);
		
		if (this.domNode()) {
			this.initialize();
			this.initStartPosition();
			this.execEvents();
		}
	},

	initialize: function() {
		this.config.counter = 0;

		var aStyles = {};
		if (this.config.item.width) {
			aStyles["width"] = this.config.item.width + "px";
		}
		if (this.config.item.height) {
			aStyles["height"] = this.config.item.height + "px";
		}
		if (this.config.item.style) {
			var aCustomStyles = this.config.item.style.split(";");
			for (var i=0, len=aCustomStyles.length; i<len; ++i) {
				if (!aCustomStyles[i].strip().empty()) {
					var aCustomStyle = aCustomStyles[i].strip().split(":");
					aStyles[aCustomStyle[0].strip().camelize()] = aCustomStyle[1].strip();
				}
			}
		}
		this.config.item.style = aStyles;

		this.oText = $(this.domNode().id);
		this.oList = $(this.domNode().id + '.list');

		this.oList.setStyle({
			"width": "0px",
			"height": "0px"
		});
	},
	
	initStartPosition: function() {
		Element.makePositioned(this.oList.up(0));
		Element.absolutize(this.oList);
		Element.clonePosition(
			this.oList,
			this.oText, {
				setLeft: true,
				setTop: true,
				setWidth: false,
				setHeight: false,
				offsetLeft: 0,
				offsetTop: this.oText.getHeight()
			}
		);
	},
	
	execEvents: function() {
		this.oText.oObserver = new Form.Element.Observer(
			this.oText,
			this.config.timeObserver,
			this.execAjaxRequest.bind(this)
		);
	},

	execAjaxRequest: function() {

		var obj = this;		// save the current object for later use

		// hide the list of choices
		obj.hideItemList(obj);

		// if there is no search, than instantly exit
		if (obj.oText.value.blank()) return;
		

		// if there is a search, then execute an AJAX event to the server
		// execute only the last search, using a global counter
		obj.config.counter++;
		new Ajax.Request(
			obj.config.searchUrl, {
				method: 'post',
				asynchronous: false,
				parameters: {
					'searchType': obj.config.searchType,
					'searchText': obj.oText.value,
					'searchCounter': obj.config.counter
				},
				onSuccess: function (oResponse) {
					var sJSONtext = oResponse.responseText;
					if (sJSONtext.isJSON()) {
						var aJSON = sJSONtext.evalJSON(true);
						if(aJSON.tasks.counter == obj.config.counter) {
							if (aJSON.tasks.results > 0) obj.showItemList(obj, Object.values(aJSON.tasks.html));
						}
					}
				}
			}
		);
	},

	showItemList: function(obj, aHtml) {

		// set the text before and after the list
		obj.oList.innerHTML = aHtml[0] + aHtml[1];
		
		Object.values(aHtml[2]).each(
			function(sValue, sKey) {
				oElement = new Element('div');
				oElement.className = obj.config.item.class;
				oElement.setStyle(obj.config.item.style);
				oElement.innerHTML = sValue;

				Event.observe(oElement, 'mouseover', function() {
					this.addClassName(obj.config.selectedItemClass);
				});

				Event.observe(oElement, 'mouseout', function() {
					this.removeClassName(obj.config.selectedItemClass);
				});

				Event.observe(oElement, 'click', function() {
					sText = this.innerHTML.stripTags().replace(/\s+/g, " ").replace(/^\s+|\s+$/, "");
					obj.oText.oObserver.lastValue = sText;
					obj.oText.value = sText;
					obj.hideItemList(obj);
				});

				obj.oList.insertBefore(oElement, obj.oList.lastChild);
			}
		);

		var iWidth = 0;
		var iHeight = 0;
		var aChilds = obj.oList.immediateDescendants();
		for (var i=0, len=aChilds.length; i<len; ++i) {
			iWidth = (aChilds[i].getWidth() > iWidth) ? aChilds[i].getWidth() : iWidth;
			iHeight += aChilds[i].getHeight();

			Event.observe(aChilds[i], 'mouseover', function() {
				this.up(0).style.visibility = "visible";
			});
			Event.observe(aChilds[i], 'mouseout', function() {
				this.up(0).style.visibility = "hidden";
			});
		}
		obj.oList.setStyle({
			"width": iWidth + "px",
			"height": iHeight + "px"
		});

		obj.oList.up(0).style.zIndex = "20000";
		obj.oList.style.visibility = "visible";
	},
	
	hideItemList: function(obj) {
		obj.oList.innerHTML = "";
		obj.oList.up(0).style.zIndex = "0";
		obj.oList.style.visibility = "hidden";
	}

}, Formidable.Classes.RdtBaseClass);
