var Formidable = {
	appendMixed: function(a, b) {
		hA = Formidable.scalarToHash(a);
		hB = Formidable.scalarToHash(b);

		hB.each(function(row) {
			iNewKey = row.key;
			iNewKeyCount=1;

			//console.log("V+K", v, k);

			while(typeof hA.get(iNewKey) !== "undefined") {
				if(Formidable.isNumber(iNewKey)) {
					iNewKey = iNewKey + iNewKeyCount;
				} else {
					iNewKey = iNewKey + "_" + iNewKeyCount;
				}

				iNewKeyCount++;
			}

			hA.set(iNewKey, row.value);
		});

		return hA.toObject();
	},
	scalarToHash: function(a) {
		if(!a) {
			return new Hash();
		}


		if(a instanceof Array) {
			return $H($A(a));	// is a []
		}

		if(a.callee && typeof a.callee === "function") {
			// it's an 'arguments' Object (special kind of Array)
			aTemp = $H();
			iLength = a.length;
			for(k = 0; k < iLength; k++) {
				aTemp.set(k, a[k]);
			}

			return aTemp;
		}

		if(typeof a.toObject !== "function") {
			return $H(a);		// is a {}
		}

		return $H(a);
	},
	inherit: function(props, baseObj) {
		if(baseObj && typeof baseObj.extend === "function") {
			return baseObj.extend(props);
		} else {
			return Base.extend(props);
		}
	},
	onDomLoaded: function(fFunc) {
		Event.observe(document, 'dom:loaded', fFunc);
	},
	objectExtend: function(oExtended, oExtender) {
		Object.extend(oExtended, oExtender);
	},
	objectClone: function(oExtender) {
		oExtended = [];
		Object.extend(oExtended, oExtender);
		return oExtended;
	},
	fireEvent: function(sEventName, oObj) {
		if(typeof oObj === "undefined") {
			Element.fire(document, sEventName);
		} else {
			Element.fire(oObj, sEventName);
		}
	},
	hasClassName: function(oObj, sClass) {
		return Element.hasClassName(oObj, sClass);
	},
	scrollTo: function(oObj) {
		Element.scrollTo(oObj);
	},
	getElementById: function(sId) {
		return $(sId);
	},
	getElementObjectById: function(sId) {
		return $(sId);
	},
	getElementsBySelector: function(sSelector) {
		return document.getElementsByClassName(sSelector);
	},
	getElementsByAdvancedSelector: function(sSelector) {
		return $$(sSelector);
	},
	createElement: function(sTagName, oConfig) {
		return new Element(sTagName, oConfig);
	},
	removeElement: function(oElement) {
		Element.remove(oElement);
	},
	replaceElement: function(oElement, sContent) {
		Element.replace(oElement, sContent);
	},
	jsonEncode: function(aValues) {
		/* aValues = JSON.decycle(aValues);
		return JSON.stringify(aValues); */
		return JSONstring.make(aValues, true);		
	},
	jsonDecode: function(sJson) {
	//	return JSON.parse(sJson);
		alert("jsonDecode prototype not implemented!!!");
	},
	attachAjaxJs: function(sSrc, oObj, sAttached) {
		new Ajax.Request(
			sSrc,
			{
				method:'get',
				asynchronous: false,
				evalJS: false,
				onSuccess: function(transport) {
					Formidable.globalEval(transport.responseText);
					oObj.aDynHeadersLoaded.push(sAttached);
				}.bind(oObj)
			}
		);
	},
	ajaxRequest: function(sEventId, sSafeLock, sSessionHash, sValue, sContext, sThrower, sTrueArgs, bCache, bPersist, bFromCache, sUrl, oObj) {
		new Ajax.Request(
			oObj.Misc.Urls.Ajax.event, {
				method:'post',
				parameters: {
					'formid': oObj.sFormId,
					'eventid': sEventId,
					'safelock': sSafeLock,
					'sessionhash': sSessionHash,
					'value': sValue,
					'context': sContext,
					'thrower': sThrower,
					'trueargs': sTrueArgs
				},
				onSuccess: function(transport) {
					oObj.removeLoader();

					if(!transport.responseJSON) {
						sResponse = transport.responseText.strip();
						if(sResponse !== "null" && transport.status == 200) {
							oObj.debug(sResponse);
						}
					} else {
						if(bCache) {
							oObj.ajaxCache[sUrl] = transport.responseJSON;
						}
						oObj.executeAjaxResponse(transport.responseJSON, bPersist, bFromCache = false);
					}

				}.bind(oObj),

				onFailure: function(){
					console.log("Ajax request failed");
				}.bind(oObj)
			}
		);
	},
	attachEvent: function(oObj, sEventHandler, fFunc, oBinder) {
		if(!oBinder) {
			oBinder = oObj;
		}

		Event.observe(
			oObj,
			sEventHandler,
			fFunc.bindAsEventListener(oBinder)
		);
	},
	unattachEvent: function(oObj, sEventHandler, fFunc) {
		Event.stopObserving(oObj, sEventHandler, fFunc);
	},
	attachEventWindowScrollEvent: function(fFunc) {
		Event.observe(window, 'scroll', fFunc);
	},
	stopEvent: function(event) {
		Event.stop(event);
	},
	getElementDimension: function(item) {
		return Element.getDimensions(item);

	},
	addClass: function(oObj, sClass) {
		oObj.addClassName(sClass);
	},
	removeClass: function(oObj, sClass) {
		oObj.removeClassName(sClass);
	},
	setStyle: function(oObj, oStyle) {
		Element.setStyle(oObj, oStyle);
	},
	getStyle: function(oObj, sStyle) {
		Element.getStyle(oObj, sStyle);
	},

	// Prototype doe not need espacing
	escapeSelectorId: function(sId) {
		return sId;
	},
	getElementDimension: function(item) {
		iWidth = 0;
		iHeight = 0;
		oItem = Formidable.getElementObjectById(item);
		
		if(oItem) {
			iWidth = Element.getWidth(oItem);
			iHeight = Element.getHeight(oItem);
		}
		
		return {
			"width": iWidth,
			"height": iHeight
		};
	}
};


