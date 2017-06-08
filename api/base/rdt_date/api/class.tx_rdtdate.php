<?php
/**
 * Plugin 'rdt_date' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdtdate extends formidable_mainrenderlet {

	var $aLibs = array("rdt_date_class" => "res/js/date.js");
	var $aJqueryLibs = array("rdt_date_class" => "res/js/date_jquery.js");

	var $sMajixClass = "Date";
	var $bCustomIncludeScript = TRUE;
	var $aPossibleCustomEvents = array(
		"onselect",
		"onupdate",
		"onclose",
	);

	var $mListDay = FALSE;
	var $mListMonth = FALSE;
	var $mListYear = FALSE;

	function _render() {

		if($this->isJqueryMode()) {
			return $this->_renderCalendarModeForJQuery();
		} else {
			return $this->_renderCalendarModeForPrototype();
		}
	}

	function _renderCalendarModeForJQuery() {

		$sUnflattenHscValue = htmlspecialchars(
			$this->_unFlatten(
				$this->getValue()
			)
		);
		$sUnflattenHscValueForHtml = $this->getValueForHtml($sUnflattenHscValue);
		$iTstamp = $this->_flatten(
			$this->getValue()
		);

		$sLabel = $this->getLabel();
		$sInput = "<input type=\"text\" name=\"" . $this->_getElementHtmlName() . "\" id=\"" . $this->_getElementHtmlId() . "\" value=\"" . $sUnflattenHscValueForHtml . "\"" . $this->_getAddInputParams() . " />";

		$sCompiled = $this->_displayLabel($sLabel) . $sInput;

		$this->_initJs();

		return array(
			"__compiled" => $sCompiled,
			"input" => $sInput,
			"value." => array(
				"timestamp" => $iTstamp,
				"readable" => $sUnflattenHscValue,
			)
		);
	}

	function _renderCalendarModeForPrototype() {

		$this->_includeLibraries();

		$sUnflattenHscValue = htmlspecialchars(
			$this->_unFlatten(
				$this->getValue()
			)
		);

		$sUnflattenHscValueForHtml = $this->getValueForHtml($sUnflattenHscValue);

		$iTstamp = $this->_flatten(
			$this->getValue()
		);

		$sLabel = $this->getLabel();

		$sTriggerId = $this->_getTriggerId();
		$sTrigger = " <img src='" . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv("TYPO3_SITE_URL") . $this->sExtRelPath . "res/lib/js_calendar/img.gif' id='" . $sTriggerId . "' style='cursor: pointer;' alt='Pick date' /> ";

		$this->_initJs();

		if($this->_allowManualEdition()) {

			$sInput = "<input type=\"text\" name=\"" . $this->_getElementHtmlName() . "\" id=\"" . $this->_getElementHtmlId() . "\" value=\"" . $sUnflattenHscValueForHtml . "\"" . $this->_getAddInputParams() . " />";

		} else {

			$sSpanId = "showspan_" . $this->_getElementHtmlId();

			if($this->_emptyFormValue($sUnflattenHscValue)) {
				$sDisplayed = $this->getEmptyString();
			} else {
				$sDisplayed = $sUnflattenHscValueForHtml;
			}



			$sInput =	"<span id='" . $sSpanId . "' " . $this->_getAddInputParams() . ">"
					.	$sDisplayed
					.	"</span>"
					.	"<input type=\"hidden\" name=\"" . $this->_getElementHtmlName() . "\" id=\"" . $this->_getElementHtmlId() . "\" value=\"" . $iTstamp . "\" />";
		}

		$sCompiled =
				$this->_displayLabel($sLabel)
			.	$sInput
			.	$sTrigger;

		return array(
			"__compiled" => $sCompiled,
			"input" => $sInput . " " . $sTrigger,
			"input." => array(
				"textbox" => $sInput,
				"textbox." => array(
					"emptystring" => $sEmptyString,
				),
				"datefield" => $sInput,
				"trigger" => $sTrigger,
			),
			"trigger" => $sTrigger,
			"trigger." => array(
				"id" => $sTriggerId,
				"tag" => $sTrigger,
			),
			"value." => array(
				"timestamp" => $iTstamp,
				"readable" => $sUnflattenHscValue,
			)
		);
	}

	function getEmptyString() {

		if(($sEmptyString = $this->_navConf("/data/datetime/emptystring")) !== FALSE) {

			if(tx_ameosformidable::isRunneable($sEmptyString)) {
				$sEmptyString = $this->callRunneable($sEmptyString);
			}

			if($sEmptyString !== FALSE) {
				return $sEmptyString;
			}
		}

		return "...";
	}

	function _renderReadOnly() {
		$aHtmlBag = parent::_renderReadOnly();
		$aHtmlBag["value."]["readable"] = $this->_getHumanReadableValue($aHtmlBag["value"]);
		return $aHtmlBag;
	}

	function _getTriggerId() {
		 return $this->_getElementHtmlId($this->_getName() . "_trigger");
	}

	function _getFormat() {

		$mFormat = $this->_navConf("/data/datetime/format/");

		if(tx_ameosformidable::isRunneable($mFormat)) {
			$mFormat = $this->callRunneable($mFormat);
		}

		if(!$mFormat) {
			$mFormat = "%Y/%m/%d";
		}

		return $mFormat;
	}

	function _getTimeFormat() {
		$bTime    = $this->oForm->defaultFalse("/data/datetime/displaytime/", $this->aElement);
		if(!$bTime) {
			return '';
		}
		
		$mFormat = $this->_navConf("/data/datetime/timeformat/");

		if(tx_ameosformidable::isRunneable($mFormat)) {
			$mFormat = $this->callRunneable($mFormat);
		}

		if(!$mFormat) {
			$mFormat = "%H/%M";
		}

		return $mFormat;
	}

	function _initJs() {
		if($this->isJqueryMode()) {
			$this->_initJQueryJs();
		} else {
			$this->_initPrototypeJs();
		}
	}

	function _initJQueryJs() {
		$sAbsName = $this->getAbsName();
		$bTime    = $this->oForm->defaultFalse("/data/datetime/displaytime/", $this->aElement);

		$sJQueryUIPath = $this->oForm->sExtPath . "res/jsfwk/jquery/core+ui/";
		$sJQueryUIMinifiedJsPath = $sJQueryUIPath . "development-bundle/ui/minified/";
		$sJQueryUIi18nJsPath = $sJQueryUIPath . "development-bundle/ui/i18n/";
		$sJQueryCssPath = $sJQueryUIPath . "development-bundle/themes/base/";
		$sRdtDatePath = $this->oForm->sExtPath . "api/base/rdt_date/";

		$this->oForm->additionalHeaderDataLocalStylesheet(
			$sJQueryCssPath . "jquery.ui.theme.css",
			"tx_ameosformidable_jquery_ui_theme_css"
		);

		$this->oForm->additionalHeaderDataLocalStylesheet(
			$sJQueryCssPath . "jquery.ui.core.css",
			"tx_ameosformidable_jquery_ui_core_css"
		);
		$this->oForm->additionalHeaderDataLocalStylesheet(
			$sJQueryCssPath . "jquery.ui.datepicker.css",
			"tx_ameosformidable_jquery_ui_datepicker_css"
		);

		$this->oForm->additionalHeaderDataLocalScript(
			$sJQueryUIMinifiedJsPath . "jquery.ui.datepicker.min.js",
			"tx_ameosformidable_jquery_ui_datepicker",
			FALSE,	# $bFirstPos
			FALSE,	# $sBefore
			FALSE,	#"tx_ameosformidable_jquery_ui_basecss",	# $sAfter
			TRUE	# mayCompile
		);

		if($bTime) {
			$this->oForm->additionalHeaderDataLocalStylesheet(
				$sRdtDatePath . "res/lib/addon/jquery-ui-timepicker-addon.css",
				"tx_ameosformidable_jquery_ui_timepicker_css"
			);

			$this->oForm->additionalHeaderDataLocalScript(
				$sRdtDatePath . "res/lib/addon/jquery-ui-timepicker-addon.js",
				"tx_ameosformidable_jquery_ui_timepicker",
				FALSE,	# $bFirstPos
				FALSE,	# $sBefore
				FALSE,	#"tx_ameosformidable_jquery_ui_basecss",	# $sAfter
				TRUE	# mayCompile
			);
		}
		
		if(isset($GLOBALS['TSFE']->tmpl->setup['config.']['language']) && $GLOBALS['TSFE']->tmpl->setup['config.']['language'] != 'en') {
			$sCode = $GLOBALS['TSFE']->tmpl->setup['config.']['language'];
			
			$sRegionalisation = "$.datepicker.setDefaults($.datepicker.regional['" . $sCode . "']);";			
			$this->oForm->additionalHeaderDataLocalScript(
				$sJQueryUIi18nJsPath . "jquery.ui.datepicker-" . $sCode . ".js",
				"tx_ameosformidable_jquery_ui_datepicker_regional",
				FALSE,	# $bFirstPos
				FALSE,	# $sBefore
				FALSE,	#"tx_ameosformidable_jquery_ui_basecss",	# $sAfter
				TRUE	# mayCompile
			);
		}
		
		

		$sHtmlId = str_replace("\\", "\\\\", $this->_getElementCssId());
		$sFormat = $this->convertFormatForJquery($this->_getFormat());
		$sTimeFormat = $this->convertTimeFormatForJquery($this->_getTimeFormat());

		$sJsmethod = $bTime ? 'datetimepicker' : 'datepicker';	
		$sInitScript =<<<INITSCRIPT
		try {
			if(Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").domNode()) {
				$(document).ready(function () {
					if($("#{$sHtmlId}")) {
						$("#{$sHtmlId}").{$sJsmethod}({
							changeMonth: true,
							changeYear: true,
							dateFormat: "{$sFormat}",
							timeFormat: "{$sTimeFormat}",
							showWeek: true,
							showTime: false,
							onSelect: Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").onSelect_handler.bind(Formidable.f("{$this->oForm->formid}").o("{$sAbsName}")),
							onClose: Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").onClose_handler.bind(Formidable.f("{$this->oForm->formid}").o("{$sAbsName}"))
						});
						
						{$sRegionalisation}
					}
				});
				
				
			}
		} catch(e) {}

INITSCRIPT;

		$this->sys_attachPostInitTask(
			$sInitScript,
			"Post-init DATE",
			$this->_getElementHtmlId()
		);

		parent::includeScripts(array());
	}

	function _initPrototypeJs() {
		//$sFormat	= $this->_navConf("/data/datetime/format/");
		$sFormat = $this->_getFormat();
		$bTime		= $this->oForm->defaultFalse("/data/datetime/displaytime/", $this->aElement);
		$sFieldName	= $this->oForm->formid . '::' . $this->_getName();
		$sHtmlId	= $this->_getElementHtmlId();

		$aConfig = array(
			"inputField"	=> $sHtmlId,	// id of the input field
			"ifFormat"		=> $sFormat,					// format of the input field
			"showsTime"		=> $bTime,						// will display a time selector
			"button"		=> $this->_getTriggerId(),		// trigger for the calendar (button ID)
			"singleClick"	=> true,						// single-click mode
			"step"			=> 1,							// show all years in drop-down boxes (instead of every other year as default)
		);

		if(!$this->_allowManualEdition()) {
			if($bTime) {
				$aConfig["ifFormat"] = "%s";
			} else {
				if($this->shouldConvertToTimestamp() === TRUE) {
					$aConfig["ifFormat"] = "%@";
				} else {
					$aConfig["ifFormat"] = $this->_getFormat();
				}
			}
			$aConfig["displayArea"] = "showspan_" . $sHtmlId;
			$aConfig["daFormat"] = $sFormat;
		}

		$this->includeScripts(array(
			"calendarconf" => $aConfig,
			"emptystring" => $this->getEmptyString(),
			"converttotimestamp" => $this->shouldConvertToTimestamp(),
			"allowmanualedition" => $this->_allowManualEdition(),
		));
	}

	function includeScripts($aConf = array()) {
		parent::includeScripts($aConf);
		$sAbsName = $this->_getElementHtmlId();

		$sInitScript =<<<INITSCRIPT
		try {
			if(Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").domNode()) {
				Calendar.setup(Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").config.calendarconf);
			}
		} catch(e) {}

INITSCRIPT;

		$sUninitScript =<<<UNINITSCRIPT

		try {
			Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").destroy();
		} catch(e) {}

UNINITSCRIPT;

		$this->sys_attachPostInitTask(
			$sInitScript,
			"Post-init DATE",
			$this->_getElementHtmlId()
		);

		$this->sys_attachPreUninitTask(
			$sUninitScript,
			"Pre-uninit DATE",
			$this->_getElementHtmlId()
		);
	}

	function _flatten($mData) {

		if(!$this->_emptyFormValue($mData)) {

			if($this->shouldConvertToTimestamp()) {

				if(!$this->__isTimestamp($mData)) {
					// on convertit la date en timestamp
					// on commence par r�cup�rer la configuration du format de date utilis�

					$sFormat = $this->_getFormat();
					$sFormatTime = $this->_getTimeFormat();
					$result = $this->__date2tstamp($mData, $sFormat, $sFormatTime);
					return $result;
				}
			}
		} else {
			return "";
		}

		return $mData;
	}

	function _unFlatten($mData) {

		if($this->__isTimestamp($mData)) {
			return $this->__tstamp2date($mData);
		}

		return $mData;
	}

	function __isTimestamp($mData) {
		return (("" . intval($mData)) === ("" . $mData));
	}

	function _allowManualEdition() {
		return
			$this->defaultFalse("/data/datetime/allowmanualedition")
			|| $this->defaultFalse("/allowmanualedition");
	}

	function isJqueryMode() {
		return ($this->oForm->_navConf($this->oForm->sXpathToMeta . "jsapi") === "jquery");
	}
	
	function convertJQueryDatePicker($strdate, $format) {
		if(strpos(setlocale(LC_TIME, 0), 'fr_FR') !== FALSE) {
			if(strpos($format, '%b') !== FALSE) {
				$strdate = str_replace(
					array('Janv.','Févr.','Mars','Avril','Mai','Juin', 'Juil.','Août','Sept.','Oct.','Nov.','Déc.'), 
					array('jan','fév','mar','avr','mai','jun', 'jui','aoû','sep','oct','nov','déc'), 
					$strdate
				);
			}
		}
		
		return $strdate;
	}
	
	function __date2tstamp($strdate, $dateformat, $timeformat) {
		
		if(version_compare(PHP_VERSION, '5.0.0', '>=')) {

			$bTime		= $this->oForm->defaultFalse("/data/datetime/displaytime/", $this->aElement);
			if($bTime) {
				$format = $dateformat . ' ' . $timeformat;
			} else {
				$format = $dateformat;
			}
				
		//	$strdate = $this->convertJQueryDatePicker($strdate, $format);
			$aDate = strptime($strdate, $format);

			if(empty($aDate)) {
				$aDate = strptime(utf8_decode($strdate), $format);
				if(empty($aDate)) {
					$aDate = strptime(utf8_encode($strdate), $format);
				}
			}
			if(!empty($aDate)) {
			
				return mktime(
					$aDate['tm_hour'], 
					$aDate['tm_min'], 
					$aDate['tm_sec'],	
					$aDate['tm_mon'] + 1, 
					$aDate['tm_mday'], 
					$aDate['tm_year'] + 1900
				);
			}
		}
		
		// strptime
		$aAvailableTokens = array(
			"%a", "%A", "%b", "%B", "%C", "%d", "%e",
			"%H", "%I", "%j", "%k", "%l", "%m", "%M",
			"%n", "%p", "%P", "%S", "%s", "%t", "%W",
			"%u", "%w", "%y", "%Y", "%%"
		);

		$aShortMonth = array(
			"C" => array (
				"Jan" => "01",
				"Feb" => "02",
				"Mar" => "03",
				"Apr" => "04",
				"May" => "05",
				"Jun" => "06",
				"Jul" => "07",
				"Aug" => "08",
				"Sep" => "09",
				"Oct" => "10",
				"Nov" => "11",
				"Dec" => "12"
			),
			"fr_FR" => array (
				"Jan" => "01",
				"Fev" => "02",
				"Fév" => "02",
				"Mar" => "03",
				"Avr" => "04",
				"Mai" => "05",
				"Juin" => "06",
				"Jun" => "06",
				"Juil" => "07",
				"Jui" => "07",
				"Aout" => "08",
				"Aoû" => "08",
				"Sep" => "09",
				"Oct" => "10",
				"Nov" => "11",
				"Dec" => "12",
				"Déc" => "12",
			),
			"de_DE" => array (
				"Jan" => "01",
				"Feb" => "02",
				"Mar" => "03",
				"Apr" => "04",
				"May" => "05",
				"Jun" => "06",
				"Jul" => "07",
				"Aug" => "08",
				"Sep" => "09",
				"Okt" => "10",
				"Nov" => "11",
				"Dez" => "12"
			)
		);

/*
%a				short name of the day (local)
%A				full name of the day (local)
%b				short month name (local)
%B        full month name (local)
%C        century number
%d        the day of the month (00 ... 31)
%e        the day of the month (0 ... 31)
%H        hour (00 ... 24)
%I        hour (01 ... 12)
%j        day of the year (000 ... 366)
%k        hour (0 ... 23)
%l        hour (1 ... 12)
%m        month (01 ... 12)
%M        mInute (00 ... 59)
%n        a newline character
%p        "PM" or "AM"
%P        "pm" or "am"
%s        number of seconds since Unix Epoch
%S        second (00 ... 59)
%t        a tab character
%W        the week number
%u        the day of the week (1 ... 7, 1 = MON)
%w        the day of the week (0 ... 6, 0 = SUN)
%y        year without the century (00 ... 99)
%Y        year including the century (eg. 1976)
%%        a literal % character
*/
		// on d�termine les s�parateurs
		$aSeparateurs = array();
		$separateurs = str_replace($aAvailableTokens, "", $format);

		if(strlen($separateurs) > 0) {
			for($k = 0; $k <= strlen($separateurs); $k++) {
				if(!in_array($separateurs[$k], $aSeparateurs)) {
					$aSeparateurs[] = $separateurs[$k];
				}
			}
		}
		$aFormat = explode("#", str_replace($aSeparateurs, "#", $format));
		$aTokens = explode("#", str_replace($aSeparateurs, "#", $strdate));

		$aDate = array();
		foreach($aFormat as $index => $format) {
			$aDate[$format] = $aTokens[$index];
		}
		reset($aDate);

		$day = strftime("%d");
		$month = strftime("%m");
		$year = strftime("%Y");
		$hour = 0;
		$minute = 0;
		$second = 0;

		if(array_key_exists("%d", $aDate)) {
			$day = $aDate["%d"];
		} elseif(array_key_exists("%e", $aDate)) {
			$day = $aDate["%e"];
		}

		if(array_key_exists("%m", $aDate)) {
			$month = $aDate["%m"];
		}

		if(array_key_exists("%Y", $aDate)) {
			$year = $aDate["%Y"];
		}

		if(array_key_exists("%H", $aDate)) {
			$hour = $aDate["%H"];
		}

		if(array_key_exists("%M", $aDate)) {
			$minute = $aDate["%M"];
		}

		if(array_key_exists("%S", $aDate)) {
			$second = $aDate["%S"];
		}

		$tstamp = mktime($hour, $minute, $second, $month, $day , $year);
		return $tstamp;
	}

	function _getHumanReadableValue($data) {
		return $this->_unFlatten($data);
	}

	function __tstamp2date($data) {

		if($this->shouldConvertToTimestamp()) {

			if(intval($data) != 0) {

				// il s'agit d'un champ timestamp
				// on convertit le timestamp en date lisible

				$elementname = $this->_navConf("/name/");
				//$format = $this->_navConf("/data/datetime/format/");
				$formatDate = $this->_getFormat();
				$formatTime = $this->_getTimeFormat();
				$bTime		= $this->oForm->defaultFalse("/data/datetime/displaytime/", $this->aElement);
				if($bTime) {
					$format = $formatDate . ' ' . $formatTime;
				} else {
					$format = $formatDate;
				}

				if(($locale = $this->_navConf("/data/datetime/locale/")) !== FALSE) {

					$sCurrentLocale = setlocale(LC_TIME, 0);

					// From the documentation of setlocale: "If locale is zero or "0", the locale setting
					// is not affected, only the current setting is returned."

					setlocale(LC_TIME, $locale);
				}

				if($this->defaultFalse("/data/datetime/gmt") === FALSE) {
					$sDate = strftime($format, $data);
				} else {
					$sDate = gmstrftime($format, $data);
				}
				
				if($this->defaultFalse("/data/datetime/utf8encode") === TRUE) {
					$sDate = utf8_encode($sDate);
				}

				$this->oForm->_debug($data . " in " . $format . " => " . $sDate, "AMEOS_FORMIDABLE_RDT_DATE " . $elementname . " - TIMESTAMP TO DATE CONV.");

				if($locale !== FALSE) {
					setlocale(LC_TIME, $sCurrentLocale);
				}

				return $sDate;
			} else {
				return "";
			}
		}

		return $data;
	}

	function _emptyFormValue($value) {
		return intval($value) <= 0;
	}

	function _sqlSearchClause($sValue, $sFieldPrefix = "", $sName = "", $bRec = TRUE) {
		if($sName === "") {
			$sName = $this->_getName();
		}

		$sFieldName = $sFieldPrefix . $sName;
		$sComparison = (($sTemp = $this->_navConf("/sql/comparison")) !== FALSE) ? $sTemp : "=";
		$sComparison = (($sTemp = $this->_navConf("/search/comparison")) !== FALSE) ? $sTemp : $sComparison;

		//return "(DATEDIFF(FROM_UNIXTIME(" . $sFieldName . "), FROM_UNIXTIME('" . $value . "')) " . $sComparison . "0)";
		$sSql = "((" . $sFieldName . " - '" . $GLOBALS['TYPO3_DB']->quoteStr($sValue, '') . "') " . $sComparison . " 0)";

		if($bRec === TRUE) {
			$sSql = $this->overrideSql(
				$sValue,
				$sFieldPrefix,
				$sName,
				$sSql
			);
		}

		return $sSql;
	}

	function _includeLibraries() {

		if(!$this->oForm->issetAdditionalHeaderData("ameosformidable_rdtdate_includeonce")) {

			$sLang = ($GLOBALS["TSFE"]->lang == "default") ? "en" : $GLOBALS["TSFE"]->lang;
			$sAbsLangFile = $this->sExtPath . "res/lib/js_calendar/lang/calendar-" . $sLang . ".js";

			if(!file_exists($sAbsLangFile)) {
				$sAbsLangFile = $this->sExtPath . "res/lib/js_calendar/lang/calendar-en.js";
			}

			$this->oForm->additionalHeaderDataLocalScript(
				$this->sExtPath . 'res/lib/js_calendar/calendar.js',
				"ameosformidable_rdtdate_includeonce"
			);

			$this->oForm->additionalHeaderDataLocalScript(
				$sAbsLangFile,
				"ameosformidable_rdtdate_includeonce_1"
			);

			$this->oForm->additionalHeaderDataLocalScript(
				$this->sExtPath . 'res/lib/js_calendar/calendar-setup.js',
				"ameosformidable_rdtdate_includeonce_2"
			);

			$this->oForm->additionalHeaderDataLocalStylesheet(
				$this->sExtPath . 'res/lib/js_calendar/calendar-win2k-1.css',
				"ameosformidable_rdtdate_includeonce_3"
			);
		}
	}

	function shouldConvertToTimestamp() {
		return $this->defaultTrue("/data/datetime/converttotimestamp") && $this->defaultTrue("/converttotimestamp");
	}

	function getValue() {
		$mValue = parent::getValue();
		if($this->_allowManualEdition() && $this->shouldConvertToTimestamp()) {
			if(!$this->_emptyFormValue($mValue)) {
				return $this->__date2tstamp(
					$mValue,
					$this->_getFormat(),
					$this->_getTimeFormat()
				);
			}

			return "";
		}

		return $mValue;
	}

	function convertFormatForJquery($sFormat) {
/*
%a 	Nom abr�g� du jour de la semaine 	De Sun � Sat
%A 	Nom complet du jour de la semaine 	De Sunday � Saturday
%d 	Jour du mois en num�rique, sur 2 chiffres (avec le z�ro initial) 	De 01 � 31
%e 	Jour du mois, avec un espace pr�c�dant le premier chiffre. L'impl�mentation Windows est diff�rente, voyez apr�s pour plus d'informations. 	De 1 � 31
%j 	Jour de l'ann�e, sur 3 chiffres avec un z�ro initial 	001 � 366
%u 	Repr�sentation ISO-8601 du jour de la semaine 	De 1 (pour Lundi) � 7 (pour Dimanche)
%w 	Repr�sentation num�rique du jour de la semaine 	De 0 (pour Dimanche) � 6 (pour Samedi)
Semaine 	--- 	---
%U 	Num�ro de la semaine de l'ann�e donn�e, en commen�ant par le premier Lundi comme premi�re semaine 	13 (pour la 13�me semaine pleine de l'ann�e)
%V 	Num�ro de la semaine de l'ann�e, suivant la norme ISO-8601:1988, en commen�ant comme premi�re semaine, la semaine de l'ann�e contenant au moins 4 jours, et o� Lundi est le d�but de la semaine 	De 01 � 53 (o� 53 compte comme semaine de chevauchement)
%W 	Une repr�sentation num�rique de la semaine de l'ann�e, en commen�ant par le premier Lundi de la premi�re semaine 	46 (pour la 46�me semaine de la semaine commen�ant par un Lundi)
Mois 	--- 	---
%b 	Nom du mois, abr�g�, suivant la locale 	De Jan � Dec
%B 	Nom complet du mois, suivant la locale 	De January � December
%h 	Nom du mois abr�g�, suivant la locale (alias de %b) 	De Jan � Dec
%m 	Mois, sur 2 chiffres 	De 01 (pour Janvier) � 12 (pour D�cembre)
Ann�e 	--- 	---
%C 	Repr�sentation, sur 2 chiffres, du si�cle (ann�e divis�e par 100, r�duit � un entier) 	19 pour le 20�me si�cle
%g 	Repr�sentation, sur 2 chiffres, de l'ann�e, compatible avec les standards ISO-8601:1988 (voyez %V) 	Exemple : 09 pour la semaine du 6 janvier 2009
%G 	La version compl�te � quatre chiffres de %g 	Exemple : 2008 pour la semaine du 3 janvier 2009
%y 	L'ann�e, sur 2 chiffres 	Exemple : 09 pour 2009, 79 pour 1979
%Y	L'ann�e, sur 4 chiffres 	Exemple : 2038

d - day of month (no leading zero)
dd - day of month (two digit)
o - day of the year (no leading zeros)
oo - day of the year (three digit)
D - day name short
DD - day name long
m - month of year (no leading zero)
mm - month of year (two digit)
M - month name short
MM - month name long
y - year (two digit)
yy - year (four digit)
*/
		$aMatches = array(
			'%a' => 'D',
			'%A' => 'DD',
			'%d' => 'dd',
			'%e' => 'd',
			'%j' => 'oo',

			'%b' => 'M',
			'%B' => 'MM',
			'%h' => 'M',
			'%m' => 'mm',

			'%g' => 'y',
			'%G' => 'yy',
			'%y' => 'y',
			'%Y' => 'yy',
		);

		foreach($aMatches as $cPhpToken => $cJqueryToken) {
			$sFormat = str_replace($cPhpToken, $cJqueryToken, $sFormat);
		}

		return $sFormat;
	}

	public function convertTimeFormatForJquery($sFormat) {
		$aMatches = array(
			'%H' => 'HH',
			'%h' => 'H',

			'%M' => 'mm',

			'%S' => 'ss',
		);

		foreach($aMatches as $cPhpToken => $cJqueryToken) {
			$sFormat = str_replace($cPhpToken, $cJqueryToken, $sFormat);
		}

		return $sFormat;
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_date/api/class.tx_rdtdate.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_date/api/class.tx_rdtdate.php"]);
	}

?>
