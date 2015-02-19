var Formidable = {
	appendMixed: function(a, b) {
		if(this.isArgumentsVar(a)) {
			a = this.argumentsVarToObject(a);
		}

		if(this.isArgumentsVar(b)) {
			b = this.argumentsVarToObject(b);
		}

		return $.extend(a, b);
		//return Formidable.inherit(a, b); // ????
	},
	scalarToHash: function(a) {
		return $H(a);
	},
	inherit: function(props, baseObj) {
		if(baseObj) {
			return $.inherit(baseObj, props);
		} else {
			return $.inherit(props);
		}
	},
	onDomLoaded: function(fFunc) {
		$(document).ready(fFunc);
	},
	objectExtend: function(oExtended, oExtender) {
		jQuery.extend(oExtended, oExtender);
	},
	objectClone: function(oExtender) {
		oExtended = [];
		jQuery.extend(true, oExtended, oExtender);
		return oExtended;
	},
	fireEvent: function(sEventName, oObj) {

		if(typeof oObj === "undefined") {
			$(document).trigger(sEventName);
		} else {
			$(oObj).trigger(sEventName);
		}
	},
	hasClassName: function(oObj, sClass) {
		if(jQuery.fn.extend($(oObj)).hasClass(sClass)) {
			return true;
		}

		return false;
	},
	scrollTo: function(oObj) {
		jQuery.fn.extend(oObj).scrollTop();
	},
	getElementById: function(sId) {
		if(document.getElementById(sId)) {
			return $(document.getElementById(sId)).get(0);
		}

		return false;
	},
	getElementObjectById: function(sId) {
		if(typeof sId === "string") {
			return $('#' + Formidable.escapeSelectorId(sId));
		}

		return $(sId);
	},
	getElementsBySelector: function(sSelector) {
		if(sSelector.substr(0,1) != '.') {
			sSelector = '.' + sSelector;
		}
		return $(sSelector);
	},
	getElementsByAdvancedSelector: function(sSelector) {
		return $(sSelector);
	},
	createElement: function(sTagName, oConfig) {
		oObj = $('<' + sTagName + '/>');
		for(var attr in oConfig) {
			oObj.attr(attr, oConfig[attr]);
		}

		return oObj.get(-1);
	},
	removeElement: function(oElement) {
		oElement.parentNode.removeChild(oElement);
	},
	replaceElement: function(oElement, sContent) {
		$(oElement).replaceWith(sContent);
	},
	jsonEncode: function(aValues) {
		return JSON.stringify(aValues);
//		return JSONstring.make(aValues, true);
	},
	jsonDecode: function(sJson) {
		return JSON.parse(sJson);
		if(sJson === null) {
			return "";
		}

		//return $.secureEvalJSON(sJson);
		if(sJson.substr(0,1) === "{") {
			return JSONstring.toObject(sJson);
		}

		aTemp = JSONstring.toObject("{0:\'" + sJson + "\'}");
		return aTemp[0];
	},
	attachAjaxJs: function(sSrc, oObj, sAttached) {
		$.ajax({
			url: sSrc,
			type:'GET',
			async:false,
			dataTypeString:'script',
			success: function(transport) {
				Formidable.globalEval(transport);
				oObj.aDynHeadersLoaded.push(sAttached);
			}.bind(oObj)
		});
	},
	ajaxRequest: function(sEventId, sSafeLock, sSessionHash, sValue, sContext, sThrower, sTrueArgs, bCache, bPersist, bFromCache, sUrl, oObj) {
		$.ajax({
			url: oObj.Misc.Urls.Ajax.event,
			type:'POST',
			data: ({
				'formid': oObj.sFormId,
				'eventid': sEventId,
				'safelock': sSafeLock,
				'sessionhash': sSessionHash,
				'value': sValue,
				'context': sContext,
				'thrower': sThrower,
				'trueargs': sTrueArgs
			}),
			success: function(transport) {
				this.removeLoader();

				if(!transport) {
					sResponse = transport.responseText.strip();

					if(sResponse !== "null") {
						this.debug(sResponse);
					}
				} else {
					if(bCache) {
						this.ajaxCache[sUrl] = transport;
					}

					this.executeAjaxResponse(transport, bPersist, bFromCache = false);
				}

			}.bind(oObj),

			onFailure: function(){
				//console.log("Ajax request failed");
			}.bind(oObj)
		});
	},
	attachEvent: function(oObj, sEventHandler, fFunc, oEventParams) {
		if(oObj === null) {
			return;
		}
		if(typeof oObj.bind != 'function') {
			oObj = $(oObj)
		}

		if(typeof oEventParams !== "undefined") {
			oObj.bind(
				sEventHandler,
				oEventParams,
				fFunc
			);
		} else {
			oObj.bind(
				sEventHandler,
				fFunc
			);
		}
	},
	unattachEvent: function(oObj, sEventHandler, fFunc) {
		if(typeof oObj.unbind != 'function') {
			oObj = $(oObj)
		}

		oObj.unbind(
			sEventHandler,
			fFunc
		);
	},
	attachEventWindowScrollEvent: function(fFunc) {
		$(window).scroll(fFunc);
	},
	stopEvent: function(event) {
		event.stopPropagation();
	},

	draggable: function(sSelector, oConfig) {
		//console.log("draggable", sSelector, oConfig);
	},
	droppable: function(sSelector, oConfig) {
		//console.log("droppable", sSelector, oConfig);
	},
	sortable: function(sSelector, oConfig) {

		oTranslatedConfig = {};

		for(var sKey in oConfig) {

			sTranslatedKey = sKey;

			switch(sKey) {
				case "elements": {
					sTranslatedKey = "items";
					break;
				}
				case "handles": {
					sTranslatedKey = "handle";
				}
			}

			oTranslatedConfig[sTranslatedKey] = oConfig[sKey];
		}

		//$(sSelector).sortable(oTranslatedConfig).disableSelection();
		$(sSelector).sortable(oTranslatedConfig);
	},
	setCookie: function(options) {

		if(typeof options.json !== "undefined" && options.json) {
			sValue = Formidable.jsonEncode(options.value);
		} else {
			sValue = options.value;
		}

		$.cookie(
			options.name,
			sValue, {
				expires: options.expires || null,	// expressed in days
				path: options.path || null,
				domain: options.domain || null,
				secure: options.secure || null
			}
		);
	},
	getCookie: function(cookiename, bJson) {
		if(typeof bJson !== "undefined" && bJson) {
			return Formidable.jsonDecode($.cookie(cookiename));
		} else {
			return $.cookie(cookiename);
		}
	},
	deleteCookie: function(cookiename) {
		return $.cookie(cookiename);
	},
	getElementDimension: function(item) {
		return {
			"width": Formidable.getElementObjectById(item).width(),
			"height": Formidable.getElementObjectById(item).height()
		};
	},
	addClass: function(oObj, sClass) {
		$(oObj).addClass(sClass);
	},
	removeClass: function(oObj, sClass) {
		$(oObj).removeClass(sClass);
	},
	setStyle: function(oObj, oStyle) {
		$(oObj).css(oStyle);
	},
	getStyle: function(oObj, sStyle) {
		$(oObj).css(sStyle);
	},

	// jQuery needs dots in IDs to be escaped when used in selectors
	escapeSelectorId: function(sId) {
		return sId.replace(".", "\\.");
	}

};

$H = function(oObj) {
	return jQuery.fn.extend(oObj);
}

$A = function(oObj) {
	return jQuery.fn.extend(oObj);
}

$F = function(o){
	return $(o).val();
};

$$ = function(selector) {
	return $(selector);
}

