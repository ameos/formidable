<?php
	
	if (!defined ("TYPO3_MODE")) 	die ("Access denied.");
	
	#define("PATH_formidable", t3lib_extMgm::extPath("ameos_formidable"));
	#define("PATH_formidableapi", PATH_formidable . "api/class.tx_ameosformidable.php");
	
	if(file_exists(PATH_site . "typo3conf/ext/ameos_formidable") && is_dir(PATH_site . "typo3conf/ext/ameos_formidable")) {
		define("PATH_formidable", PATH_site . "typo3conf/ext/ameos_formidable/");
	}
	
	define("PATH_formidableapi", PATH_formidable . "api/class.tx_ameosformidable.php");

	// define XCLASS to t3lib_tsparser
	$TYPO3_CONF_VARS['FE']['XCLASS']['t3lib/class.t3lib_tsparser.php'] = PATH_formidable . "res/xclass/class.ux_t3lib_tsparser.php";
	$TYPO3_CONF_VARS['BE']['XCLASS']['t3lib/class.t3lib_tsparser.php'] = PATH_formidable . "res/xclass/class.ux_t3lib_tsparser.php";

	// defines the Formidable ajax content-engine ID
	$TYPO3_CONF_VARS['FE']['eID_include']['tx_ameosformidable'] = 'EXT:ameos_formidable/remote/formidableajax.php';
	
	if(TYPO3_MODE === "FE") {
		$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = "EXT:ameos_formidable/api/class.tx_ameosformidable_hooks.php:&tx_ameosformidable_hooks->contentPostProc_output";
	}

	// defines content objects FORMIDABLE (cached) and FORMIDABLE_INT (not cached)
	$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClass'][] = array(
		0 => "FORMIDABLE",
		1 => "EXT:ameos_formidable/api/class.user_ameosformidable_cobj.php:user_ameosformidable_cobj",
	);

	$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClass'][] = array(
		0 => "FORMIDABLE_INT",
		1 => "EXT:ameos_formidable/api/class.user_ameosformidable_cobj.php:user_ameosformidable_cobj",
	);

	// defines the generic CACHED rendering plugin 
	t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_ameosformidable_pi1.php','_pi1','list_type',1);

	// defines the generic NOT CACHED rendering plugin 
	t3lib_extMgm::addPItoST43($_EXTKEY,'pi2/class.tx_ameosformidable_pi2.php','_pi2','list_type',0);
	
	if (!defined('PATH_tslib')) {
		if (@is_dir(PATH_site.TYPO3_mainDir.'sysext/cms/tslib/')) {
			define('PATH_tslib', PATH_site.TYPO3_mainDir.'sysext/cms/tslib/');
		} elseif (@is_dir(PATH_site.'tslib/')) {
			define('PATH_tslib', PATH_site.'tslib/');
		}
	}
	
	if(!array_key_exists("ameos_formidable", $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"])) {

		require_once(PATH_formidable . 'ext_emconf.php');

		$GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"] = array(
			"ext_emconf.php" => $EM_CONF[$_EXTKEY],
			"declaredobjects" => array(
				"validators" => array(),
				"datahandlers" => array(),
				"datasources" => array(),
				"renderers" => array(),
				"renderlets" => array(),
				"actionlets" => array(),
			),
			"validators" => array(),
			"datahandlers" => array(),
			"datasources" => array(),
			"renderers" => array(),
			"renderlets" => array(),
			"actionlets" => array(),
			"ajax_services" => array(),
			"context" => array(
				"forms" => array(),
				"be_headerdata" => array(),
			)
		);
	}
	
	$GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["ext_conf_template"] = unserialize($_EXTCONF);

	//require_once(PATH_formidable . 'api/class.tx_ameosformidable.php');

	$GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["declaredobjects"]["datasources"] = array(
		"DB"				=> array("key" => "ds_db",					"base" => TRUE),
		"PHPARRAY"			=> array("key" => "ds_phparray",			"base" => TRUE),
		"PHP"				=> array("key" => "ds_php",					"base" => TRUE),
		"CONTENTREPOSITORY"	=> array("key" => "ds_contentrepository",	"base" => TRUE),
	);

	$GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["declaredobjects"]["actionlets"] = array(
#		"MAIL"		=> array("key" => "act_mail",			"base" => TRUE),	// deprecated
		"REDIRECT"		=> array("key" => "act_redct",		"base" => TRUE),
		"USEROBJ"		=> array("key" => "act_userobj",	"base" => TRUE),
	);

	$GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["declaredobjects"]["datahandlers"] = array(
		"DB"			=> array("key" => "dh_db",		"base" => TRUE),
#		"LISTER"		=> array("key" => "dh_lister",	"base" => TRUE),		// deprecated
		"RAW"			=> array("key" => "dh_raw",		"base" => TRUE),
		"STANDARD"		=> array("key" => "dh_std",		"base" => TRUE),
		"VOID"		=> array("key" => "dh_void",		"base" => TRUE),
	);

	$GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["declaredobjects"]["renderers"] = array(
		"STANDARD"		=> array("key" => "rdr_std",		"base" => TRUE),
		"BACKEND"		=> array("key" => "rdr_be",			"base" => TRUE),
		"TEMPLATE"		=> array("key" => "rdr_template",	"base" => TRUE),
		"VOID"		=> array("key" => "rdr_void",			"base" => TRUE),
		"FLUID"		=> array("key" => "rdr_fluid",			"base" => TRUE),
	);

	$GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["declaredobjects"]["renderlets"] = array(
		"CHECKBOX"		=> array("key" => "rdt_checkbox",		"base" => TRUE),
		"CHECKSINGLE"	=> array("key" => "rdt_checksingle",	"base" => TRUE),
		"DATE"		=> array("key" => "rdt_date",				"base" => TRUE),
		"HIDDEN"		=> array("key" => "rdt_hidden",			"base" => TRUE),
		"LISTBOX"		=> array("key" => "rdt_listbox",		"base" => TRUE),
		"PASSTHRU"		=> array("key" => "rdt_passthru",		"base" => TRUE),
		"PASSWORD"		=> array("key" => "rdt_pwd",			"base" => TRUE),
		"RADIOBUTTON"	=> array("key" => "rdt_radio",			"base" => TRUE),
		"SUBMIT"		=> array("key" => "rdt_submit",			"base" => TRUE),
		"TEXT"		=> array("key" => "rdt_text",				"base" => TRUE),
		"BUTTON"		=> array("key" => "rdt_button",			"base" => TRUE),
		"IMAGE"		=> array("key" => "rdt_img",				"base" => TRUE),
#		"URL"			=> array("key" => "rdt_url",			"base" => TRUE),
		"TEXTAREA"		=> array("key" => "rdt_txtarea",		"base" => TRUE),
		"BOX"			=> array("key" => "rdt_box",			"base" => TRUE),
		"LINK"		=> array("key" => "rdt_link",				"base" => TRUE),
		"CHOOSER"		=> array("key" => "rdt_chooser",		"base" => TRUE),
		"CAPTCHA"		=> array("key" => "rdt_captcha",		"base" => TRUE),
		"DEWPLAYER"		=> array("key" => "rdt_dewplayer",		"base" => TRUE),
		"TINYMCE"		=> array("key" => "rdt_tinymce",		"base" => TRUE),
		"MODALBOX"		=> array("key" => "rdt_modalbox",		"base" => TRUE),
		"TABPANEL"		=> array("key" => "rdt_tabpanel",		"base" => TRUE),
		"TAB"			=> array("key" => "rdt_tab",			"base" => TRUE),
		"I18N"		=> array("key" => "rdt_i18n",				"base" => TRUE),
		"SEARCHFORM"	=> array("key" => "rdt_searchform",		"base" => TRUE),
		"ADVSEARCHFORM"	=> array("key" => "rdt_advsearchform",	"base" => TRUE),
		"LISTER"		=> array("key" => "rdt_lister",		"base" => TRUE),
		"UPLOAD"		=> array("key" => "rdt_upload",		"base" => TRUE),
		"SWFUPLOAD"		=> array("key" => "rdt_swfupload",		"base" => TRUE),
		"PLUPLOAD"		=> array("key" => "rdt_plupload",		"base" => TRUE),
		"SELECTOR"		=> array("key" => "rdt_selector",		"base" => TRUE),		
		"ACCORDION"		=> array("key" => "rdt_accordion",		"base" => TRUE),
		"PROGRESSBAR"	=> array("key" => "rdt_progressbar",	"base" => TRUE),
		"TICKER"		=> array("key" => "rdt_ticker",			"base" => TRUE),
		"AUTOCOMPLETE"	=> array("key" => "rdt_autocomplete",	"base" => TRUE),
		"MODALBOX2"		=> array("key" => "rdt_modalbox2",		"base" => TRUE),
		"JSTREE"		=> array("key" => "rdt_jstree",			"base" => TRUE),
		"SLIDER"		=> array("key" => "rdt_slider",			"base" => TRUE),
	);

	$GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["declaredobjects"]["validators"] = array(
		"DB"			=> array("key" => "va_db",		"base" => TRUE),
		"STANDARD"		=> array("key" => "va_std",		"base" => TRUE),
		"FILE"		=> array("key" => "va_file",		"base" => TRUE),
		"PREG"		=> array("key" => "va_preg",		"base" => TRUE),
		"NUM"			=> array("key" => "va_num",		"base" => TRUE),
	);

	$GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["ajax_services"]["tx_ameosformidable"]["ajaxevent"]["conf"] = array(
		"virtualizeFE"	=> TRUE,
		"initBEuser"	=> FALSE,
	);
	
	$GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["ajax_services"]["tx_ameosformidable"]["ajaxservice"]["conf"] = array(
		"virtualizeFE"	=> TRUE,
		"initBEuser"	=> FALSE,
	);

	$GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["ajax_services"]["rdt_ajaxlist"]["content"]["conf"] = array(
		"virtualizeFE"	=> TRUE,
		"initBEuser"	=> FALSE,
	);

	$GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["ajax_services"]["rdt_swfupload"]["upload"]["conf"] = array(
		"virtualizeFE"	=> FALSE,
		"initBEuser"	=> FALSE,
	);

	$GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["ajax_services"]["rdt_autocomplete"]["lister"]["conf"] = array(
		"virtualizeFE"	=> TRUE,
		"initBEuser"	=> FALSE,
	);
	
	$GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["ajax_services"]["rdt_plupload"]["upload"]["conf"] = array(
		"virtualizeFE"	=> FALSE,
		"initBEuser"	=> FALSE,
	);
	
	$GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["ajax_services"]["rdt_lister"]["getdata"]["conf"] = array(
		"virtualizeFE"	=> TRUE,
		"initBEuser"	=> FALSE,
	);
	
?>
