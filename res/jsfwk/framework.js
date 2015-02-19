if(!console) {
	var console={
		log: function(oObj) {},
		dir: function(oObj) {}
	};
}

Formidable.objectExtend(Formidable, {
	initialize: function(oConfig) {
		Formidable.objectExtend(this, oConfig);
		this.Browser.getBrowserInfo();
	},
	SUBMIT_FULL: "AMEOSFORMIDABLE_EVENT_SUBMIT_FULL",
	SUBMIT_REFRESH: "AMEOSFORMIDABLE_EVENT_SUBMIT_REFRESH",
	SUBMIT_TEST: "AMEOSFORMIDABLE_EVENT_SUBMIT_TEST",
	SUBMIT_DRAFT: "AMEOSFORMIDABLE_EVENT_SUBMIT_DRAFT",
	SUBMIT_CLEAR: "AMEOSFORMIDABLE_EVENT_SUBMIT_CLEAR",
	SUBMIT_SEARCH: "AMEOSFORMIDABLE_EVENT_SUBMIT_SEARCH",

	Classes: {},		// placeholder for classes definitions; used like this: var oObj = new Formidable.Classes.SomeObject(params)
	CodeBehind: {},
	Context: {
		Forms: {},		// placeholder for subscribed forms in the page
		Objects: {}		// placeholder for page-level objects ( like modalbox )
	},
	LoadedScripts: [],

	Lister: {
		Pager: {
			goTo: function(sFormId, iPage) {

				var aForm = Formidable.f(sFormId);
				var oForm = aForm.domNode();

				oForm[aForm.Misc.HiddenIds.Lister.page].value=iPage;
				aForm.submitRefresh();
			}
		},
		Sort: {
			by: function(sName, sDirection, sFormId) {

				var aForm = Formidable.f(sFormId);
				var oForm = aForm.domNode();

				oForm[aForm.Misc.HiddenIds.Lister.sortField].value=sName;
				oForm[aForm.Misc.HiddenIds.Lister.sortDirection].value=sDirection;
				oForm.action=aForm.Misc.Urls.Lister.sortAction;

				aForm.submitRefresh();
			}
		}
	},
	f: function(sFormId) {		// shortcut for getting form instance
		return this.Context.Forms[sFormId];
	},
	o: function(sObjectId) {
		return this.Context.Objects[sObjectId];
	},
	executeInlineJs: function(oJson) {
		$H(oJson).each(function(sJs) {
			Formidable.globalEval(unescape(sJs));
		}.bind(this));
	},
	alert: function(sMessage) {
		sMessage = sMessage.replace(/<br \/>/g, "\n");
		sMessage = sMessage.replace(/<b>/g, "");
		sMessage = sMessage.replace(/<\/b>/g, "");
		sMessage = sMessage.replace(/^\s+|\s+$/g,"");
		alert(sMessage);
	},
	debugMessage: function(sMessage) {
		Formidable.alert(sMessage);
	},
	Browser: {
		name: "",
		version: "",
		os: "",
		total: "",
		thestring: "",
		place: "",
		detect: navigator.userAgent.toLowerCase(),
		checkIt: function(string) {
			this.place = this.detect.indexOf(string) + 1;
			this.thestring = string;
			return this.place;
		},
		getBrowserInfo: function() {
			//Browser detect script originally created by Peter Paul Koch at http://www.quirksmode.org/
			if (this.checkIt('konqueror')) {
				this.name = "konqueror";
				this.os = "linux";
			}
			else if (this.checkIt('safari')) { this.name = "safari";}
			else if (this.checkIt('omniweb')) { this.name = "omniweb";}
			else if (this.checkIt('opera')) { this.name = "opera";}
			else if (this.checkIt('webtv')) { this.name = "webtv";}
			else if (this.checkIt('icab')) { this.name = "icab";}
			else if (this.checkIt('msie')) { this.name = "internet explorer";}
			else if (!this.checkIt('compatible')) {
				this.name = "netscape";
				this.version = this.detect.charAt(8);
			}
			else {
				this.name = "unknown";
			}

			if(!this.version) {
				this.version = this.detect.charAt(this.place + this.thestring.length);
			}

			if(!this.os) {
				if(this.checkIt('linux')) { this.os = "linux";}
				else if (this.checkIt('x11')) { this.os = "unix";}
				else if (this.checkIt('mac')) { this.os = "mac";}
				else if (this.checkIt('win')) { this.os = "windows";}
				else { this.os = "unknown";}
			}
		}
	},
	Position: {
		/* caught at http://textsnippets.com/tag/dimensions */
		putCenter: function(item, what) {
			var xy = Formidable.getElementDimension(item);
			var win = this.windowDimensions();
			var scrol = this.scrollOffset();

			if(!what || what === "h") {
				sLeft = (win.width / 2) + scrol.width - (xy.width / 2) + "px";
				Formidable.setStyle(Formidable.getElementObjectById(item), {'left': sLeft});
			}

			if(!what || what === "v") {
				sTop = (win.height / 2) + scrol.height - (xy.height / 2) + "px";
				Formidable.setStyle(Formidable.getElementObjectById(item), {'top': sTop});
			}

		},
		putCenterVertical: function(item) {
			this.putCenter(item, "v");
		},
		putCenterHorizontal: function(item) {
			this.putCenter(item, "h");
		},
		putFixedToWindowVertical: function(item, offset) {
			item = Formidable.getElementObjectById(item);
			var win = this.windowDimensions();
			var scrol = this.scrollOffset();

			sTop = (scrol.height + parseInt(offset, 10)) + "px";
			Formidable.setStyle(item, {'top': sTop});
		},
		putFixedToWindowHorizontal: function(item, offset) {
			item = $(item);
			var xy = item.getDimensions();
			var win = this.windowDimensions();
			var scrol = this.scrollOffset();

			item.style.left = (scrol.width + parseInt(offset)) + "px";
		},
		fullScreen: function(item) {
			item = Formidable.getElementObjectById(item);
			var win = this.windowDimensions();
			var scrol = this.scrollOffset();
			sHeight = scrol.height + win.height + "px";
			Formidable.setStyle(item, {'height': sHeight});
		},
		windowDimensions: function() {
			var x, y;
			if(self.innerHeight) {
				// all except Explorer
				x = self.innerWidth;
				y = self.innerHeight;
			} else if (document.documentElement && document.documentElement.clientHeight) {
				// Explorer 6 Strict Mode
				x = document.documentElement.clientWidth;
				y = document.documentElement.clientHeight;
			} else if (document.body) {
				// other Explorers
				x = document.body.clientWidth;
				y = document.body.clientHeight;
			}

			if (!x) { x = 0;}
			if (!y) { y = 0;}
			return {width: x, "height": y};
		},
		scrollOffset: function() {
			var x, y;
			if(self.pageYOffset) {
				// all except Explorer
				x = self.pageXOffset;
				y = self.pageYOffset;
			} else if (document.documentElement && document.documentElement.scrollTop) {
				// Explorer 6 Strict
				x = document.documentElement.scrollLeft;
				y = document.documentElement.scrollTop;
			} else if (document.body) {
				// all other Explorers
				x = document.body.scrollLeft;
				y = document.body.scrollTop;
			}

			if (!x) { x = 0;}
			if (!y) { y = 0;}
			return {width: x, height: y};
		}
	},
	getLocalAnchor: function() {
		return $A(window.location.href.replace(window.location.href.split('#')[0],'').split('/')).last().replace(/#/,'');
	},
	log: function() {
		console.log(arguments);
	},
	formatSize: function(iSizeInBytes) {
		iSizeInByte = parseInt(iSizeInBytes, 10);
		if(iSizeInBytes > 900) {
			if(iSizeInBytes>900000)	{	// MB
				return parseInt(iSizeInBytes/(1024*1024), 10) + ' MB';
			} else {	// KB
				return parseInt(iSizeInBytes/(1024), 10) + ' KB';
			}
		}

		// Bytes
		return iSizeInBytes + ' B';
	},
	globalEval: function(sScript) {
		// using window.eval here instead of eval
			// to ensure that the script is eval'd in the global context
			// and not the local one
				// see http://www.modulaweb.fr/blog/2009/02/forcer-l-evaluation-du-code-dans-un-contexte-global-en-javascript/
		// however this doesn't work in IE
			// so we have to use window.execScript instead
				// see http://ajaxian.com/archives/evaling-with-ies-windowexecscript
		// NOR in Safari, where we use the brute force approach

		if(Formidable.Browser.name === "internet explorer") {
			window.execScript(sScript); // eval in global scope for IE
		} else if(Formidable.Browser.name === "safari") {
			//window.setTimeout(sString, 0);

			var _script = document.createElement("script");
			_script.type = "text/javascript";
			_script.defer = false;
			_script.text = sScript;
			var _headNodeSet = document.getElementsByTagName("head");
			if(_headNodeSet.length) {
				_script = _headNodeSet.item(0).appendChild(_script);
			} else {
				var _head = document.createElement("head");
				_head = document.documentElement.appendChild(_head);
				_script = _head.appendChild(_script);
			}
		} else {
			window.eval(sScript);
		}
	},
	includeStylesheet: function(sUrl) {
		if(document.createStyleSheet) {
			document.createStyleSheet(sUrl);
		} else {
			var styles = "@import url('" + sUrl + "');";
			var newSS=document.createElement('link');
			newSS.rel='stylesheet';
			newSS.href='data:text/css,'+escape(styles);
			document.getElementsByTagName("head")[0].appendChild(newSS);
		}
	},
	appendArrays: function(a, b) {
		Array.prototype.push.apply((a || []), (b || []));
		return a;
	},
	appendObjects: function(a, b) {
		return Formidable.appendMixed(a, b);
	},
	declareLoadedScripts: function(aScripts) {
		Formidable.appendArrays(Formidable.LoadedScripts, aScripts);
	},
	getLoadedScripts: function() {
		return Formidable.LoadedScripts;
	},
	isNumber: function(n) {
	  return !isNaN(parseFloat(n)) && isFinite(n);
	},
	isArgumentsVar: function(aSomeVar) {
		return (typeof aSomeVar !== "undefined" && typeof aSomeVar.callee !== "undefined" && typeof aSomeVar.callee === "function");
	},
	argumentsVarToObject: function(a) {
		if(this.isArgumentsVar(a)) {
			// it's an 'arguments' Object (special kind of Array)
			aTemp = {};
			iLength = a.length;
			for(k = 0; k < iLength; k++) {
				aTemp[k] = a[k];
			}

			return aTemp;
		}

		return {};
	},
	indexOfArray: function(aHaystack, sNeedle, i) {
		i || (i = 0);
		var length = aHaystack.length;
		if (i < 0) i = length + i;
		for (; i < length; i++) {
			if (aHaystack[i] === sNeedle) {
				return i;
			}
		}

		return -1;
	}
});

Formidable.Classes.FormBaseClass = Formidable.inherit({
	domNode: function() {
		return document.forms[this.sFormId];
	},
	aParamsStack: [],
	aContextStack: [],
	aAddPostVars: [],
	clientEventTimeout: {},
	ajaxEventTimeout: {},
	ajaxCache: {},
	ViewState: [],
	Objects: {},		// placeholder for instanciated JS objects in the FORM
	aDynHeadersLoaded: [],
	oLoading: null,
	oDebugDiv: false,
	currentTriggeredArguments: false,
	Services: {},
	AjaxRequestsStack: {},
	__constructor: function(oConfig) {

		Formidable.objectExtend(this, oConfig);

		this.aParamsStack = [];
		this.aContextStack = [];
		this.aAddPostVars = [];
		this.clientEventTimeout = {};
		this.ajaxEventTimeout = {};
		this.ajaxCache = {};
		this.ViewState = [];
		this.Objects = {};
		this.aDynHeadersLoaded = [];
		this.oLoading = null;
		this.oDebugDiv = false;
		this.currentTriggeredArguments = false;
		this.Services = {};
		this.AjaxRequestsStack = {};

		this.initLoader();
	},
	getParams: function() {
		a = $A(this.aParamsStack).last();
		return a;
	},
	getContext: function() {
		return $A(this.aContextStack).last();
	},
	getSender: function() {
		return this.getContext().sender;
	},
	o: function(sObjectId) {	// shortcut for getting object instance

		if(sObjectId === "tx_ameosformidable") {
			return Formidable;	// the static Formidable object
		} else if(Formidable.f(sObjectId)) {
			return Formidable.f(sObjectId);	// instance of FormBaseClass object
		} else if(this.Objects[sObjectId]) {
			return this.Objects[sObjectId];	// a renderlet
		} else if(this.Objects[this.sFormId + "." + sObjectId]) {
			return this.Objects[this.sFormId + "." + sObjectId];	// a renderlet that was not prefixed with the formid
		} else if(Formidable.getElementById(sObjectId)) {
			oObj = Formidable.getElementById(sObjectId);

			if(Formidable.hasClassName(oObj, "readonly")) {	// giving a chance to readonly rdt to be caught
				return new Formidable.Classes.RdtBaseClass({
					"formid": this.sFormId,
					"id": sObjectId
				});
			}

			return oObj;
		}

		return null;
	},
	rdt: function(sObjectId) {
		return this.o(sObjectId);
	},
	executePostInit: function() {
		Formidable.fireEvent('formidable:formpostinit-' + this.sFormId);
	},
	attachEvent: function(sRdtId, sEventHandler, fFunc) {
		var oObj = this.o(sRdtId);
		if(oObj && typeof(oObj) !== 'undefined') {
			if(typeof(oObj.domNode) !== 'undefined') {
				oObj.attachEvent(sEventHandler, fFunc);
			}

		}
	},
	unattachEvent: function(sRdtId, sEventHandler) {
		var oObj = this.o(sRdtId);
		if(oObj && typeof(oObj) !== 'undefined') {
			if(typeof(oObj.domNode) !== 'undefined') {
				oObj.unattachEvent(sEventHandler);
			}
		}
	},
	updateViewState: function(oExecuter) {
		this.ViewState.push(oExecuter);
	},
	executeServerEvent: function(sEventId, sSubmitMode, sParams, sHash, sJsConfirm) {
		var bThrow = false;

		if(sJsConfirm !== false) {
			bThrow = confirm(unescape(sJsConfirm));
		} else {
			bThrow = true;
		}

		if(bThrow) {
			//$(this.sFormId + "_AMEOSFORMIDABLE_SERVEREVENT").value=sEventId;
			Formidable.getElementById(this.sFormId + "_AMEOSFORMIDABLE_SERVEREVENT").value=sEventId;

			if(sParams !== false) {
				Formidable.getElementById(this.sFormId + "_AMEOSFORMIDABLE_SERVEREVENT_PARAMS").value=sParams;
				Formidable.getElementById(this.sFormId + "_AMEOSFORMIDABLE_SERVEREVENT_HASH").value=sHash;

				//$(this.sFormId + "_AMEOSFORMIDABLE_SERVEREVENT_PARAMS").value=sParams;
				//$(this.sFormId + "_AMEOSFORMIDABLE_SERVEREVENT_HASH").value=sHash;
			}


			if(sSubmitMode) {
				this.doSubmit(sSubmitMode, true);
			} else {
				this.domNode().submit();
			}
		}
	},
	_clientData: function(oData) {
		if(oData) {
			if(typeof oData === "string") {
				if(oData.slice(0, 12) === "clientData::") {
					sObjectId = oData.slice(12);
					oObj = this.o(sObjectId);
					if(oObj && typeof oObj.getValue === "function") {
						oData = oObj.getValue();
					}
				}
			} else if(typeof oData === "object") {
				for(var i in oData) {
					oData[i] = this._clientData(oData[i]);
				}
			}
		} else {
			oData = {};
		}

		return oData;
	},
	executeClientEvent: function(sObjectId, bPersist, oTask, sEventName, aLocalArguments, sJsConfirm, iDelay) {
		//console.log("Executing client event", "sObjectId:", sObjectId, "bPersist:", bPersist, "oTask", oTask, "aLocalArguments", aLocalArguments, "sJsConfirm", sJsConfirm);

		if(sJsConfirm !== false) {
			bThrow = confirm(unescape(sJsConfirm));
		} else {
			bThrow = true;
		}

		if(bThrow) {
			if(iDelay !== 0) {
				iDelay = iDelay / 1000;
				var collectionTimeId = $H();

				if(oTask.tasks.object) {
					// it's a single task to execute
					collectionTimeId.set(
						'0', this.executeClientTask.bind(this).delay(iDelay, oTask.tasks, bPersist, sEventName, sObjectId, aLocalArguments)
					);
				} else {
					// it's a collection of tasks to execute

					$H(oTask.tasks).each(function(value, key) {
						collectionTimeId.set(
							key, this.executeClientTask.bind(this).delay(iDelay, oTask.tasks[key], bPersist, sEventName, sObjectId, aLocalArguments)
						);
					}.bind(this));
				}

				if(this.clientEventTimeout.get(sObjectId)) {
					var listTimeOut = this.clientEventTimeout.get(sObjectId);
					listTimeOut.each(function(value, key) {
						window.clearTimeout(
							listTimeOut.get(key)
						);
					});
				}

				this.clientEventTimeout.set(sObjectId, collectionTimeId);
			} else {
				if(oTask.tasks.object) {
					this.executeClientTask(oTask.tasks, bPersist, sEventName, sObjectId, aLocalArguments);
				} else {
					// it's a collection of tasks to execute
					for(var iKey in oTask.tasks) {
						this.executeClientTask(oTask.tasks[iKey], bPersist, sEventName, sObjectId, aLocalArguments);
					}
				}
			}
			if(bPersist) {
				this.updateViewState(oTask);
			}
		}
	},
	executeClientTask: function(oTask, bPersist, sEventName, sSenderId, aLocalArguments) {
		delete this.clientEventTimeout.sSenderId;
		//console.log(oTask);

		if(oTask.formid) {
			// execute it on given formid
			var oForm = Formidable.f(oTask.formid);
		} else {
			var oForm = this;
		}

		var oObject = oForm.o(oTask.object);
		var oSender = oForm.o(sSenderId);

		if(oObject) {
			if(oObject[oTask.method]) {
				oData = oForm._clientData(oTask.data);

				if(oData.params) {
					var aParams = oData.params;
				} else {
					var aParams = {};
				}

				// uniquement params="abc,def" dans le XML
				var aXmlParams = oSender.getParamsForMajix(
					{},
					sEventName,
					{},//aParams,
					{},//aRowParams,
					aLocalArguments
				);

				aParams = Formidable.appendMixed(aParams, aXmlParams);
				aParams = Formidable.appendMixed(aParams, oTask.databag.params);
				aParams = Formidable.appendMixed(aParams, aLocalArguments);

				oContext = oTask.databag.context || {};
				oContext.sender = oSender;
				oContext.event = aLocalArguments[0];
				if(oContext.event) {
					oContext.event[0] = oContext.event;	// back compat
				}

				this.aContextStack.push(oContext);
				this.aParamsStack.push(aParams);

				oData.params = aParams;
				oObject[oTask.method](oData);

				this.aParamsStack.pop();
				this.aContextStack.pop();

			} else {
				console.log("executeClientEvent: single task: No method named " + oTask.method + " on " + oTask.object);
			}
		} else {
			console.log("executeClientEvent: single task: No object named " + oTask.object);
		}
	},
	executeAjaxEvent: function(sEventName, sObjectId, sEventId, sSafeLock, sSessionHash, bCache, bPersist, aParams, aRowParams, aLocalArguments, sJsConfirm, iDelay, bRefreshContext) {
		if(Formidable.isArgumentsVar(aLocalArguments)) {
			aLocalArguments = Formidable.argumentsVarToObject(aLocalArguments);
		}

		aLocalLocalArguments = Formidable.objectClone(aLocalArguments);

		if(sJsConfirm !== false) {
			bThrow = confirm(unescape(sJsConfirm));
		} else {
			bThrow = true;
		}

		if(bThrow) {
			var aValues = {};

			if(aParams) {
				for(var sKey in aParams) {

					sName = aParams[sKey];
					if(sName.slice(0, 10) === "rowInput::") {
						aInfo = sName.split("::");
						sReturnName = aInfo[1];
						sId = aInfo[2];
					} else {
						sReturnName = sName;
						sId = sName;
					}

					if((oElement = this.o(sId))) {
						aValues[sReturnName] = oElement.getParamsForMajix(
							oElement.getValue(),
							sEventName,
							aParams,
							aRowParams,
							aLocalArguments
						);
					} else if((oElement = Formidable.getElementById(this.rdtIdByName(sName)))) {
						aValues[sReturnName] = $F(oElement);
					} else {
						aValues[sKey] = sName; // should be the value itselves
					}
				}
			}

			if(aRowParams) {
				for(var sName in aRowParams) {
					aValues[sName] = aRowParams[sName];
				}
			}

			var oObject = this.o(sObjectId);
			if(oObject.getMajixThrowerIdentity !== undefined) {
				var sThrower = oObject.getMajixThrowerIdentity(sObjectId);
				aValues = oObject.getParamsForMajix(aValues, sEventName, aParams, aRowParams, aLocalArguments);
			} else {
				var sThrower = sObjectId;
			}

			var sValue = Formidable.jsonEncode(aValues);

			if(this.currentTriggeredArguments) {
				var sTrueArgs = Formidable.jsonEncode(this.currentTriggeredArguments);
			} else if(aLocalArguments) {
				/* Convention:
					When simultaneously requesting params in event (params="a, b, c")
					and system params (ie, generated by renderlets like "FileUploaded", ...)
						Then the first parameter will be the requested params and system params will follow
				*/
				var b = false;
				if(aLocalLocalArguments && aLocalLocalArguments[0]) {
					if(aLocalLocalArguments[0].view) {
						//delete aLocalLocalArguments[0].view;
						b = true;
					}

					if(aLocalLocalArguments[0].currentTarget) {
						//delete aLocalLocalArguments[0].currentTarget;
						b = true;
					}

					if(aLocalLocalArguments[0].target) {
						//delete aLocalLocalArguments[0].target;
						b = true;
					}

					if(aLocalLocalArguments[0].srcElement) {
						//delete aLocalLocalArguments[0].srcElement;
						b = true;
					}

					if(aLocalLocalArguments[0].handleObj) {
						//delete aLocalLocalArguments[0].handleObj;
						b = true;
					}

					if(aLocalLocalArguments[0].fromElement) {
						//delete aLocalLocalArguments[0].fromElement;
						b = true;
					}

					if(aLocalLocalArguments[0].originalEvent) {
						//delete aLocalLocalArguments[0].originalEvent;
						b = true;
					}

					if(aLocalLocalArguments[0].toElement) {
						//delete aLocalLocalArguments[0].toElement;
						b =  true;
					}
				}

				//aLocalLocalArguments = Array.prototype.slice.call(aLocalLocalArguments, 0);
				
				if(sValue !== "{}") {
					if(typeof aLocalLocalArguments == 'array') {
						aLocalLocalArguments.unshift(aValues);
					}
				}

				if(b) {
					var sTrueArgs = false;
				} else {
					//var sTrueArgs = false;
					if(aLocalLocalArguments && (aLocalLocalArguments[0] && aLocalLocalArguments[0].sys_event) || aLocalLocalArguments.sys_event) {
						var sTrueArgs = false;
					} else {
						var sTrueArgs = JSON.stringify(aLocalLocalArguments);
					}					
				}

			} else {
				var sTrueArgs = false;
			}

			var aContext = {};
			if(bRefreshContext) {
				aContext = this.getRenderletContext(this.Objects, false);
			}

			var sContext = Formidable.jsonEncode(aContext);

			var sUrl = this.Misc.Urls.Ajax.event + "&formid=" + this.sFormId + "&eventid=" + sEventId + "&safelock=" + sSafeLock + "&value=" + escape(sValue) + "&thrower=" + escape(sThrower) + "&trueargs=" + escape(sTrueArgs);

			if(!bCache) { sUrl += "&random=" + escape(Math.random());}

			if(bCache && this.ajaxCache[sUrl] !== undefined) {
				this.executeAjaxResponse(
					this.ajaxCache[sUrl],
					bPersist,
					bFromCache = true
				);
			} else {
				if(iDelay !== 0) {
					iDelay = iDelay / 1000;
					var iTimeId = this.executeAjaxRequest.bind(this).delay(iDelay,
						sEventId, sSafeLock, sSessionHash, sValue, sContext, sThrower, sTrueArgs, bCache, bPersist, bFromCache = false, sUrl
					);

					if(this.ajaxEventTimeout.get(sEventId)) {
						window.clearTimeout(this.ajaxEventTimeout.get(sEventId));
					}

					this.ajaxEventTimeout.set(sEventId, iTimeId);
				} else {
					this.executeAjaxRequest(sEventId, sSafeLock, sSessionHash, sValue, sContext, sThrower, sTrueArgs, bCache, bPersist, bFromCache = false, sUrl);
				}
			}
		}
	},
	executeAjaxRequest:function(sEventId, sSafeLock, sSessionHash, sValue, sContext, sThrower, sTrueArgs, bCache, bPersist, bFromCache, sUrl) {
		this.displayLoader();
		delete this.ajaxEventTimeout.sEventId;
		Formidable.ajaxRequest(sEventId, sSafeLock, sSessionHash, sValue, sContext, sThrower, sTrueArgs, bCache, bPersist, bFromCache, sUrl, this);
	},
	executeAjaxResponse: function(oResponse, bPersist, bFromCache) {
		/*
			Sequence is:
				01 attach headers
				02 ajax init
				03 execute tasks
				04 execute events
				05 execute post-init
		*/

		this.executeAjaxAttachHeaders(oResponse.attachheaders);

		try{
			this.executeAjaxInit(oResponse.init);
		} catch(e) {
			// allows catching of unexpected js, for easier debug
			console.log("AJAX INIT - Exception:", e);
		}

		if(oResponse.tasks.object) {
			// it's a single task to execute
			this.executeAjaxTask(oResponse.tasks);
		} else {

			// it's a collection of tasks to execute
			for(var key in oResponse.tasks) {
				this.executeAjaxTask(oResponse.tasks[key]);
			}
		}

		this.executeAjaxAttachEvents(oResponse.attachevents);

		try{
			this.executeAjaxInit(oResponse.postinit);
		} catch(e) {
			// allows catching of unexpected js, for easier debug
			console.log("AJAX POST-INIT - Exception:", e);
		}
		if(bPersist) {
			this.updateViewState(oResponse);
		}
	},
	executeAjaxInit: function(oInit) {
		for(var sKey in oInit) {
			Formidable.globalEval(oInit[sKey]);
		}
	},
	executeAjaxAttachEvents: function(oAttach) {
		var _this = this;
		for(var sKey in oAttach) {
			Formidable.globalEval(oAttach[sKey]);
		}
	},
	executeAjaxAttachHeaders: function(oAttach) {

		// takes the headers returned by Formidable
			// and tries to dynamically load them in the document
			// this is done via synchronous (a)jax
			// to load resources before using them
			// in the executed event

		for(var sKey in oAttach) {
			if(Formidable.indexOfArray(this.aDynHeadersLoaded, oAttach[sKey]) > -1) {
				//console.log("AJAX attach header avoided:" + oAttach[sKey]);
			} else {
				aMatches = oAttach[sKey].match(/src=["|'](.+)["|']/);	// js headers only
				if(aMatches && aMatches.length > 0) {

					// it's a JS Script

					sSrc = aMatches[1];
					if(Formidable.indexOfArray(Formidable.getLoadedScripts(), sSrc) === -1) {
						Formidable.attachAjaxJs(sSrc, this, oAttach[sKey]);

					} else {
						console.log("AJAX attach header avoided(bis):" + oAttach[sKey]);
					}
				} else {
					aMatches = oAttach[sKey].match(/<link rel=["|']stylesheet["|'] type=["|']text\/css["|'] href=["|'](.+)["|'] \/>/);	// css headers only
					if(aMatches && aMatches.length > 0) {
						sSrc = aMatches[1];
						Formidable.includeStylesheet(sSrc);
					}
				}
			}
		}
	},
	executeAjaxTask: function(oTask) {
		if(oTask.formid) {
			// execute it on given formid
			var oForm = Formidable.f(oTask.formid);
			if(!oForm) {
				console.log("executeClientEvent: single task: on formid " + oTask.formid + ": No method named " + oTask.method + " on " + oTask.object);
			}
		} else {
			var oForm = this;
		}

		var oObject = oForm.o(oTask.object);
		if(oObject) {
			if(oObject[oTask.method]) {
				//console.log("calling", oTask.method, "on", oTask.object, oObject);
				//this.aParamsStack
				oContext = oTask.databag.context || {};
				aParams = oTask.databag.params || {};
				//oContext.sender = oSender;
				//oContext.event = aLocalArguments;
				this.aContextStack.push(oContext);
				this.aParamsStack.push(aParams);
				oObject[oTask.method](oTask.data);
				this.aParamsStack.pop();
				this.aContextStack.pop();

			} else {
				console.log("executeAjaxResponse: single task: No method named " + oTask.method + " on " + oTask.object);
			}
		} else {
			console.log("executeAjaxResponse: single task: No object named " + oTask.object);
		}
	},
	getRenderletContext: function(aObjects, bIsChild) {
		var aContext = {};
		for(var sKey in aObjects) {
			if(aObjects[sKey] != null) {
				if(aObjects[sKey].config != null) {
					if(aObjects[sKey].config.parent === false || bIsChild === true) {
						aContext[aObjects[sKey].config.idwithoutformid] = {};
	
						if(aObjects[sKey].hasChilds()) {
							var aChilds = {};
	
							for(var sChild in aObjects[sKey].childs()) {
								aChilds[sChild] = aObjects[sKey].child(sChild);
							}
	
							aContext[aObjects[sKey].config.idwithoutformid] = this.getRenderletContext(aChilds, true);
						} else {
							aContext[aObjects[sKey].config.idwithoutformid]['value'] = aObjects[sKey].getValue();
						}
					}
				}
			}
		}

		return aContext;
	},
	initPersistedData: function(oData) {
		for(var key in oData) {
			if(this.o(key)) {
				try {
					this.o(key).rebirth(oData[key]);
				} catch(e) {
					// rebirth not implemented on this object
				}
			}
		}
	},
	rdtIdByName: function(sName) {
		return this.sFormId + "_" + sName;
	},
	submitClear: function(oSender) {
		this.doSubmit(Formidable.SUBMIT_CLEAR, false, oSender || false);
	},
	submitSearch: function(oSender) {
		this.doSubmit(Formidable.SUBMIT_SEARCH, false, oSender || false);
	},
	submitDraft: function(oSender) {
		this.doSubmit(Formidable.SUBMIT_DRAFT, false, oSender || false);
	},
	submitTest: function(oSender) {
		this.doSubmit(Formidable.SUBMIT_TEST, false, oSender || false);
	},
	submitRefresh: function(oSender) {
		this.doSubmit(Formidable.SUBMIT_REFRESH, false, oSender || false);
	},
	submitFull: function(oSender) {
		this.doSubmit(Formidable.SUBMIT_FULL, false, oSender || false);
	},

	submitOnEnter: function(sFromUniqueId, myfield, e) {

		var keycode;

		if(window.event) {
			keycode = window.event.keyCode;
		} else if (e) {
			keycode = e.which;

		} else {
			return true;
		}

		if(keycode === 13) {
			this.doSubmit(Formidable.SUBMIT_FULL);
			return false;
		} else {
			return true;
		}
	},
	cleanSysFields: function(bAll) {
		$(this.sFormId + "_AMEOSFORMIDABLE_SERVEREVENT").value="";
		$(this.sFormId + "_AMEOSFORMIDABLE_SERVEREVENT_PARAMS").value="";
		$(this.sFormId + "_AMEOSFORMIDABLE_SERVEREVENT_HASH").value="";
		$(this.sFormId + "_AMEOSFORMIDABLE_ADDPOSTVARS").value="";
		if(bAll) {
			if($(this.sFormId + "_AMEOSFORMIDABLE_ENTRYID")) {$(this.sFormId + "_AMEOSFORMIDABLE_ENTRYID").value="";}
			$(this.sFormId + "_AMEOSFORMIDABLE_VIEWSTATE").value="";
			$(this.sFormId + "_AMEOSFORMIDABLE_SUBMITTED").value="";
			$(this.sFormId + "_AMEOSFORMIDABLE_SUBMITTER").value="";
		}
	},
	doSubmit: function(iMode, bServerEvent, oSender) {
		if(!iMode) { iMode = "";}
		if(!bServerEvent) {
			this.cleanSysFields();
		}

		if(oSender || (this.getContext() && (oSender = this.getSender()))) {
			var aAddVars = {};
			if(typeof(oSender.isNaturalSubmitter) == 'function') {
				if(oSender.isNaturalSubmitter()) {
					aAddVars[oSender.config.namewithoutformid] = iMode;
					this.addFormData(aAddVars);
					Formidable.getElementById(this.sFormId + "_AMEOSFORMIDABLE_SUBMITTER").value=oSender.config.idwithoutformid;
				} else {
					Formidable.getElementById(this.sFormId + "_AMEOSFORMIDABLE_SUBMITTER").value=oSender.config.idwithoutformid;
				}
			}
		}

		Formidable.getElementById(this.sFormId + "_AMEOSFORMIDABLE_ADDPOSTVARS").value = Formidable.jsonEncode(this.aAddPostVars);
		// submitting Main form
		Formidable.getElementById(this.sFormId + "_AMEOSFORMIDABLE_SUBMITTED").value=iMode;

		if(this.ViewState.length > 0) {
			// saving viewstate
			//$(this.sFormId + "_AMEOSFORMIDABLE_VIEWSTATE").value=JSONstring.make(this.ViewState, true);
			$(this.sFormId + "_AMEOSFORMIDABLE_VIEWSTATE").value = Formidable.jsonEncode(this.ViewState);
		} else {
			$(this.sFormId + "_AMEOSFORMIDABLE_VIEWSTATE").value="";
		}

		this.domNode().submit();
	},
	doNothing: function(oSource) {
		return true;
	},
	displayErrors: function(aErrors) {
		sMessage = '';
		for(var iKey in aErrors) {
			sMessage = sMessage + aErrors[iKey].message + "\n";
		}
		alert(sMessage);
	},
	scrollTo: function(sName) {
		var oObj = this.o(sName);
		if(oObj) {
			if(typeof oObj.domNode === "undefined") {
				Formidable.scrollTo(oObj);
			} else {
				Formidable.scrollTo(oObj.domNode());
			}
		}
	},
	sendToPage: function(sUrl) {
		document.location.href = sUrl;
	},
	openPopup: function(mUrl) {
		if(typeof mUrl["url"] !== 'undefined') {
			// it's an array of parameters

			var aProps = [];

			if(typeof mUrl["name"] !== 'undefined') {
				var sName = mUrl["name"];
			} else {
				var sName = "noname";
			}

			if(typeof mUrl["menubar"] !== 'undefined') {
				if(mUrl["menubar"] === true) {
					aProps.push("menubar=yes");
				} else {
					aProps.push("menubar=no");
				}
			}

			if(typeof mUrl["status"] !== 'undefined') {
				if(mUrl["status"] === true) {
					aProps.push("status=yes");
				} else {
					aProps.push("status=no");
				}
			}

			if(typeof mUrl["scrollbars"] !== 'undefined') {
				if(mUrl["scrollbars"] === true) {
					aProps.push("scrollbars=yes");
				} else {
					aProps.push("scrollbars=no");
				}
			}

			if(typeof mUrl["resizable"] !== 'undefined') {
				if(mUrl["resizable"] === true) {
					aProps.push("resizable=yes");
				} else {
					aProps.push("resizable=no");
				}
			}

			if(typeof mUrl["width"] !== 'undefined') {
				aProps.push("width=" + mUrl["width"]);
			}

			if(typeof mUrl["height"] !== 'undefined') {
				aProps.push("height=" + mUrl["height"]);
			}

			window.open(mUrl["url"], sName, aProps.join(", "));
		} else {
			window.open(mUrl);
		}
	},
	toggleDebug: function() {
		var oDiv = Formidable.getElementById(this.sFormId + '_debugzone');

		if(oDiv && oDiv.style.display === 'none'){
			oDiv.style.display='block';

			aDivs = Formidable.getElementsBySelector("ameosformidable_debugcontainer_void");
			for(sKey in aDivs) { aDivs[sKey].className = "ameosformidable_debugcontainer";}

			aDivs = Formidable.getElementsBySelector("ameosformidable_debughandler_void");
			for(sKey in aDivs) { aDivs[sKey].className = "ameosformidable_debughandler";}

		} else {
			oDiv.style.display='none';

			aDivs = Formidable.getElementsBySelector("ameosformidable_debugcontainer_void");
			for(sKey in aDivs) { aDivs[sKey].className = "ameosformidable_debugcontainer_void";}

			aDivs = Formidable.getElementsBySelector("ameosformidable_debughandler_void");
			for(sKey in aDivs) { aDivs[sKey].className = "ameosformidable_debughandler_void";}
		}
	},
	toggleBacktrace: function(iNumCall) {

		var oDiv = Formidable.getElementById(this.sFormId + '_formidable_call' + iNumCall + '_backtrace');

		if(oDiv && oDiv.style.display === 'none') {
			oDiv.style.display='block';
		} else {
			oDiv.style.display='none';
		}
	},
	debug: function(sMessage) {

		if(this.oDebugDiv === false) {
			this.oDebugDiv = Formidable.createElement("div", {
				id: this.sFormId + "-majixdebug",
				//style: "padding: 5px; border: 2px solid red; background-color: white; height: 500px; overflow: scroll;"
				style: "position:fixed; z-index:500; right:0px; top:0px; width:600px; height: 100%; padding: 5px; border: 2px solid red; background-color: white; overflow: scroll;"
			});

			//this.domNode().appendChild(this.oDebugDiv);
			document.body.appendChild(this.oDebugDiv);
		}
		var oDate = new Date();
		var sTime = oDate.getHours() + ":" + oDate.getMinutes() + ":" + oDate.getSeconds();

		this.oDebugDiv.innerHTML =
			"<div style='font-weight: bold; font-size: 20px;'>DEBUG - " + sTime + "</div>" +
			sMessage +
			"<hr style='margin: 20px; padding:0; border: 0; border-top:2px solid black; color: black; '/>" +
			this.oDebugDiv.innerHTML;

		Formidable.scrollTo(this.oDebugDiv.id);
		this.oDebugDiv.scrollTop = 0;
	},
	requestNewI18n: function(aParams) {
		this.cleanSysFields(true);
		this.addPostVar({
			"action": "requestNewI18n",
			"params": aParams
		});
		this.submitClear();
	},
	requestEdition: function(aParams) {
		this.cleanSysFields(true);
		this.addPostVar({
			"action": "requestEdition",
			"params": aParams
		});
		this.submitClear();
	},
	execOnNextPage: function(aTask) {
		this.addPostVar({
			"action": "execOnNextPage",
			"params": aTask
		});
	},
	addFormData: function(aData) {
		this.addPostVar({
			"action": "formData",
			"params": aData
		});
	},
	addPostVar: function(aVar) {
		this.aAddPostVars.push(aVar);
	},
	initLoader: function() {
		this.oLoading = Formidable.createElement("img", {
			style: "position: fixed; left: 50%; top: 50%; margin: 0; padding: 0; z-index: 999999999;",
			src: Formidable.path + "res/images/loading.gif"
		});
	},
	displayLoader: function() {

		if(Formidable.Browser.name === "internet explorer") {
			this.oLoading.style.position = "absolute";
			Formidable.Position.putCenter(this.oLoading);
		}

		if(this.Misc.MajixSpinner.left) {
			posLeft = this.Misc.MajixSpinner.left;
			if((parseInt(posLeft, 10) + "") === posLeft) {
				posLeft += "px";
			}

			this.oLoading.style.left = posLeft;
		}

		try {
			document.body.appendChild(this.oLoading);
		} catch(e) {}
	},
	removeLoader: function() {
		try {
			Formidable.removeElement(this.oLoading);
		} catch(e) {}
	},
	execJs: function(sJs) {
		eval(sJs);
	},
	executeCbMethod: function(aArgs) {
		oContext = aArgs.context || {};
		this.aContextStack.push(oContext);

		aParams = [];
		for(var iParamKey in aArgs.params) {
			aParams[iParamKey] = aArgs.params[iParamKey];
		}

		Formidable.CodeBehind[aArgs["cb"]["class"]][aArgs.method].apply(
			Formidable.CodeBehind[aArgs["cb"]["class"]],
			aParams
		);

		this.aContextStack.pop();
	},
	filterKeypress: function(oEv, sPattern) {
		iKeyCode = oEv.keyCode;
		bOk = true;
		var iCharCode = oEv.charCode ? oEv.charCode : oEv.keyCode;
		var sChar = String.fromCharCode(iCharCode);

		if(
			(Formidable.indexOfArray([8,9,16,17,18,20,27,37,39,40,46,144], iKeyCode) === -1) &&
			!sChar.match(sPattern)
		) {
			bOk = false;
			Formidable.stopEvent(oEv);
		} else if(iKeyCode === 39 || iKeyCode === 40) {
			if(!Prototype.Browser.Gecko) {
				// On IE and Safari, 39 and 40 are codes given for ' AND (
					// BUT: for firefox, it's left and right arrow
					// BUT2: only firefox triggers keypress for left and right arrow
					// CONCLUSION: we block 39 and 40 for anything else than firefox
				bOk = false;
				Formidable.stopEvent(oEv);
			}
		}

		return bOk;
	},
	declareAjaxService: function(sName, sId, sSafeLock) {
		this.Services[sName] = function() {
			aParams = Array.from(arguments);
			aParams.unshift(sId, sSafeLock);
			this.invokeAjaxService.apply(
				this,
				aParams
			);
		}.bind(this);
	},
	invokeAjaxService: function() {
		aParams = Array.from(arguments);
		sId = aParams.shift();
		sSafeLock = aParams.shift();

		if(aParams.length > 0 && typeof aParams.first() === "function") {
			fCbk = aParams.shift();
		} else {
			fCbk = Prototype.emptyFunction;
		}

		if(this.AjaxRequestsStack.get(sId)) {
			this.AjaxRequestsStack.get(sId).abort();
			this.AjaxRequestsStack.unset(sId);
		}

		//var sTrueArgs = JSONstring.make(aParams, true);
		var sTrueArgs = Formidable.jsonEncode(aParams);
		this.displayLoader();

		this.AjaxRequestsStack.set(sId, new Ajax.Request(
			this.Misc.Urls.Ajax.service, {
				method:'post',
				parameters: {
					'formid': this.sFormId,
					'safelock': sSafeLock,
					'serviceid': sId,
					'trueargs': sTrueArgs
				},
				onSuccess: function(transport) {
					this.removeLoader();
					eval("var oJson=" + transport.responseText.strip() + ";");
					this.AjaxRequestsStack.unset(sId);
					fCbk(oJson);
				}.bind(this),
				onFailure: function(){
					this.removeLoader();
					this.AjaxRequestsStack.unset(sId);
					console.log("Ajax request failed");
				}.bind(this)
			}
		));
	},
	trigger: function() {	/* sWhat[, argument_1, argument_2, ..., argument_n] */
		sWhat = arguments[0];

		// stripping the "on" prefix, if present
		if(sWhat.length > 2 && sWhat.substr(0, 2) === "on") {
			sWhat = sWhat.substr(2);
		}

		if(arguments.length > 1) {
			this.oForm.currentTriggeredArguments = Array.prototype.slice.call(arguments, 1);
			Formidable.fireEvent("formidable:" + sWhat, this.domNode());
			this.oForm.currentTriggeredArguments = false;
		} else {
			this.oForm.currentTriggeredArguments = false;
			Formidable.fireEvent("formidable:" + sWhat, this.domNode());
		}
	}
});


Formidable.Classes.RdtBaseClass = Formidable.inherit({
	oForm: null,
	config: {},
	__constructor: function(oConfig) {
		this.config = oConfig;
		this.oForm = Formidable.f(this.config.formid);
	},
	doNothing: function() {},
	domNode: function() {
		return document.getElementById(this.config.id);
	},
	rdt: function(sName) {
		return this.oForm.o(this.config._rdts[sName]);
	},
	child: function(sName) {
		return this.rdt(sName);
	},
	childs: function() {
		if(this.config._rdts) {
			return this.config._rdts;
		}

		return {};
	},
	hasChilds: function() {
		var bChild = false;
		for(var sKey in this.config._rdts) {
			bChild = true;
		}

		return bChild;
	},
	parent: function() {
		if(this.config.parent) {
			return this.oForm.o(this.config.parent);
		}

		return false;
	},
	replaceData: function(sData) {
		this.clearData();
		this.domNode().value = sData;
	},
	clearData: function(oData) {
		this.domNode().value = "";
	},
	clearValue: function() {
		this.domNode().value = "";
	},
	getValue: function() {
		try {
			if(this.domNode()) {
				return $F(this.domNode());
			}
		} catch(e) { }

		return "";
	},
	getIdWithoutFormIdRelativeTo: function(oParentRdt) {
		sOurs = this.config.idwithoutformid;
		sTheirs = oParentRdt.config.idwithoutformid;

		if(sOurs.startsWith(sTheirs)) {
			return sOurs.substr(sTheirs.length + 1);
		}

		return sOurs;
	},
	setValue: function(sData) {
		this.clearValue();
		this.domNode().value = sData;
	},
	appendValue: function(sData) {
		sValue = this.domNode().value;
		if(sValue !== "") {
			this.domNode().value += ", " + sData;
		} else {
			this.domNode().value = sData;
		}
	},
	displayBlock: function() {
		if((oDomNode=this.domNode())) {
			oDomNode.style.display="block";
			this.displayBlockLabel();
		}
	},
	displayNone: function() {
		if((oDomNode=this.domNode())) {
			oDomNode.style.display="none";
			this.displayNoneLabel();
		}
	},
	displayDefault: function() {
		this.domNode().style.display="";
		this.displayDefaultLabel();
	},
	displayNoneLabel: function() {
		if(this.getLabel()) {
			this.getLabel().style.display="none";
		}
	},
	displayBlockLabel: function() {
		if(this.getLabel()) {
			this.getLabel().style.display="block";
		}
	},
	displayDefaultLabel: function() {
		if(this.getLabel()) {
			this.getLabel().style.display="";
		}
	},
	displayError: function(aError) {
		alert(aError.message);
	},
	getLabel: function() {
		return Formidable.getElementById(this.config.id + "_label");
	},
	replaceLabel: function(sLabel) {
		oLabel = this.getLabel();
		if(oLabel) {
			oLabel.innerHTML = sLabel;
		}
	},
	visibleLabel: function() {
		if(this.getLabel()) {
			this.getLabel().style.visibility="visible";
		}
	},
	visible: function() {
		this.visibleLabel();
		this.domNode().style.visibility="visible";
		if(this.domNode().style.display==="none") {
			this.domNode().style.display="";
		}
	},
	hiddenLabel: function() {
		if(this.getLabel()) {
			this.getLabel().style.visibility="hidden";
		}
	},
	hidden: function() {
		this.hiddenLabel();
		this.domNode().style.visibility="hidden";
	},
	enable: function() {
		Form.Element.enable(this.config.id);
	},
	disable: function() {
		Form.Element.disable(this.config.id);
	},
	toggleDisplay: function() {
		if(this.domNode().style.display==="none") {
			this.displayBlock();
		} else {
			this.displayNone();
		}
	},
	toggleVisibility: function() {
		if(this.domNode().style.visibility==="hidden") {
			this.visible();
		} else {
			this.hidden();
		}
	},
	focus: function() {
		this.domNode().focus();
	},
	blur: function() {
		this.domNode().blur();
	},
	rebirth: function(oValue) {
		/* none in superclass */
	},
	setVisible: function() {
		this.displayDefault();
	},
	setInvisible: function() {
		this.displayNone();
	},
	Fx: function(aParams) {
		if(typeof Scriptaculous!=='undefined') {

			var sType = typeof(aParams);
			if(sType.toLowerCase() === "string") {
				var aParams = {"effect": aParams, "params": {}};
			}

			if(aParams["params"]["afterFinish"] && (typeof aParams["params"]["afterFinish"] !== "function")) {
				aParams["params"]["_afterFinish"] = aParams["params"]["afterFinish"];
				aParams["params"]["afterFinish"] = function() {
					this.oForm.executeAjaxTask(aParams["params"]["_afterFinish"]);
				}.bind(this);
			}

			oLabel = this.getLabel();


			switch(aParams["effect"].toLowerCase()) {
				case "appear":	{
					if(oLabel) {
						new Effect.Parallel([
							new Effect.Appear(this.domNode()),
							new Effect.Appear(oLabel)
						], aParams["params"]);
					} else {
						new Effect.Appear(this.domNode(), aParams["params"]);
					}
					break;
				}
				case "fade": {
					if(oLabel) {
						new Effect.Parallel([
							new Effect.Fade(this.domNode()),
							new Effect.Fade(oLabel)
						], aParams["params"]);
					} else {
						new Effect.Fade(this.domNode(), aParams["params"]);
					}
					break;
				}
				case "puff": { new Effect.Puff(this.domNode(), aParams["params"]); break; }
				case "blinddown": { new Effect.BlindDown(this.domNode(), aParams["params"]); break; }
				case "blindup": { new Effect.BlindUp(this.domNode(), aParams["params"]); break; }
				case "switchoff": { new Effect.SwitchOff(this.domNode(), aParams["params"]); break; }
				case "slidedown": { new Effect.SlideDown(this.domNode(), aParams["params"]); break; }

				case "slideup": { new Effect.SlideUp(this.domNode(), aParams["params"]); break; }
				case "dropout": { new Effect.DropOut(this.domNode(), aParams["params"]); break; }
				case "shake": { new Effect.Shake(this.domNode(), aParams["params"]); break; }
				case "pulsate": { new Effect.Pulsate(this.domNode(), aParams["params"]); break; }
				case "squish": { new Effect.Squish(this.domNode(), aParams["params"]); break; }
				case "fold": { new Effect.Fold(this.domNode(), aParams["params"]); break; }
				case "grow": { new Effect.Grow(this.domNode(), aParams["params"]); break; }
				case "shrink": { new Effect.Shrink(this.domNode(), aParams["params"]); break; }
				case "highlight": { new Effect.Highlight(this.domNode(), aParams["params"]); break; }
				case "toggleappear": { new Effect.toggle(this.domNode(), "appear", aParams["params"]); break; }
				case "toggleslide": { new Effect.toggle(this.domNode(), "slide", aParams["params"]); break; }
				case "toggleblind": { new Effect.toggle(this.domNode(), "blind", aParams["params"]); break; }
				case "scrollto": { new Effect.ScrollTo(this.config.id); break; }
			}
		} else {
			console.log("Scriptaculous is not loaded. Add /meta/libs = scriptaculous to your formidable");
		}
	},
	getMajixThrowerIdentity: function(sObjectId) {
		return sObjectId;
	},
	getParamsForMajix: function(aValues, sEventName, aParams, aRowParams, aLocalArguments) {
		//console.log(aValues, sEventName, aParams, aRowParams, aLocalArguments);

		for(var sKey in aValues) {
			sValue = aValues[sKey];
			if(sKey.match(/^majix:/)) {
				aParts = sKey.split(":");
				oObj = this.oForm.o(aParts[1]);
				if(oObj) {
					if(typeof(oObj[aParts[2]]) === "function") {
						aValues[sKey] = oObj[aParts[2]]();
					}
				}
			}
		}

		return aValues;
	},
	getName: function() {
		return this.config.id.substr(this.config.formid.length + 1);
	},
	repaint: function(sHtml) {
		oLabel = this.getLabel();
		if(oLabel) {
			Formidable.removeElement(oLabel);
		}
		Formidable.replaceElement(this.domNode(), sHtml);
	},
	attachEvent: function(sEventHandler, fFunc) {
		oObj = this.domNode();

		if(typeof(oObj) !== 'undefined' && oObj !== null) {
			oObj = $(oObj);
			Formidable.attachEvent(oObj, sEventHandler, fFunc);
		}
	},
	unattachEvent: function(sEventHandler) {
		oObj = this.domNode();

		if(typeof(oObj) !== 'undefined' && oObj !== null) {
			oObj = $(oObj);
			Formidable.unattachEvent(oObj, sEventHandler);
		}
	},
	addClass: function(sClass) {
		Formidable.addClass(this.domNode(), sClass);
	},
	removeClass: function(sClass) {
		Formidable.removeClass(this.domNode(), sClass);
	},
	removeAllClass: function() {
		this.domNode().classNames().each(function(sClass) {
			this.removeClass(sClass);
		}.bind(this));
	},
	setStyle: function(aStyles) {
		this.domNode().setStyle(aStyles);
		return this;
	},
	isNaturalSubmitter: function() {
		return this.domNode() && ((this.domNode().nodeName.toUpperCase() === "SUBMIT") || (
				this.domNode().nodeName.toUpperCase() === "INPUT" && (
					this.domNode().type.toUpperCase() === "SUBMIT" ||
					this.domNode().type.toUpperCase() === "IMAGE"
				)
			));
	},
	removeErrorStatus: function() {

		if(this.config.error) {
			this.removeClass("hasError");
			sType = this.config.error.info.type;
			sTypeClass = "hasError" + sType.substr(0,1).toUpperCase() + sType.substr(1, sType.length);
			this.removeClass(sTypeClass);

			if(this.getLabel()) {
				this.getLabel().removeClassName("hasError");
				this.getLabel().removeClassName(sTypeClass);
			}

			$$("SPAN.rdterror").each(function(oObj) {
				if(oObj.hasClassName(this.config.idwithoutformid)) {
					oObj.style.display='none';
				}
			}.bind(this));

			this.config.error = false;
		}
	},
	setErrorStatus: function(oError) {
		if(!this.config.error) {
			this.addClass("hasError");
			if(!oError.type) {
				oError.type = "undetermined";
			}

			if(oError.type) {
				sTypeClass = "hasError" + oError.type.substr(0,1).toUpperCase() + oError.type.substr(1, oError.type);
				this.addClass(sTypeClass);
			}

			if(this.getLabel()) {
				this.getLabel().addClassName("hasError");
				if(oError.type) {
					this.getLabel().addClassName(sTypeClass);
				}
			}

			$$("SPAN.rdterror").each(function(oObj) {
				if(oObj.hasClassName(this.config.idwithoutformid)) {
					oObj.style.display='';
				}
			}.bind(this));

			this.config.error = {
				"info": {
					"type": oError.type
				}
			};
		}
	},
	isIterable: function() {	// typically, lister are iterable
		return this.config.isiterable === true;
	},
	isIterated: function() {	// renderlets inside listers are iterated
		return this.config.iterated === true;
	},
	triggerSubmit: function(sMode) {

		if(sMode == "refresh") { this.oForm.submitRefresh(this);}
		else if(sMode == "test") { this.oForm.submitTest(this);}
		else if(sMode == "draft") { this.oForm.submitDraft(this);}
		else if(sMode == "clear") { this.oForm.submitClear(this);}
		else if(sMode == "search") { this.oForm.submitSearch(this);}
		else { this.oForm.submitFull(this); }
	},
	trigger: function() {	/* sWhat[, argument_1, argument_2, ..., argument_n] */
		sWhat = arguments[0];

		// stripping the "on" prefix, if present
		if(sWhat.length > 2 && sWhat.substr(0, 2) === "on") {
			sWhat = sWhat.substr(2);
		}

		if(arguments.length > 1) {
			this.oForm.currentTriggeredArguments = Array.prototype.slice.call(arguments, 1);
			Formidable.fireEvent("formidable:" + sWhat, this.domNode());
			this.oForm.currentTriggeredArguments = false;
		} else {
			this.oForm.currentTriggeredArguments = false;
			Formidable.fireEvent("formidable:" + sWhat, this.domNode());
		}
	},
	addHandler: function(sHandler, fFunction) {
		if(!this.aHandlers) {
			this.aHandlers = {};
		}
		
		if(typeof this.aHandlers === "undefined") {
			this.aHandlers = {};
		}
		
		if(typeof this.aHandlers[sHandler] === "undefined") {
			this.aHandlers[sHandler] = [];
		}
	
		this.aHandlers[sHandler].push(fFunction);	
	}
});

Formidable.Classes.CodeBehindClass = Formidable.inherit({
	config: {},
	__constructor: function(oConfig) {
		this.config = oConfig;
		this.oForm = Formidable.f(this.config.formid);
		if(this.init && typeof this.init == "function") {
			this.init();
		}
	}
});
