Formidable.Classes.Captcha = Formidable.inherit({
	oCaptcha: false,
	oReload: false,
	oInput: false,
	__constructor: function(oConfig) {
		this.oCaptcha = this.oReload = this.oInput = false;
		this.config = oConfig;
		this.oForm = Formidable.f(this.config.formid);

		if(oCaptcha = Formidable.getElementById(this.config.id + 'img')) {
			this.oCaptcha = oCaptcha;
		}

		if(oReload = Formidable.getElementById(this.config.id + '_reload')) {
			this.oReload = oReload;
			Formidable.attachEvent(this.oReload, "click", this.reload.bind(this));
		}

		if(oInput = Formidable.getElementById(this.config.id)) {
			this.oInput = oInput;
		}
	},
	reload: function() {
		if(this.oCaptcha) {
			this.oCaptcha.src = this.config.reloadurl + "&amp;" + Math.round(Math.random(0)*1000)+1;
		}
	}
}, Formidable.Classes.RdtBaseClass);
