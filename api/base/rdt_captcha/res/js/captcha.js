Formidable.Classes.Captcha = Formidable.inherit({
	oCaptcha: false,
	oReload: false,
	oInput: false,
	__constructor: function(oConfig) {
		this.oCaptcha = this.oReload = this.oInput = false;
		this.base(oConfig);

		if(oCaptcha = $(this.config.id + 'img')) {
			this.oCaptcha = oCaptcha;
		}

		if(oReload = $(this.config.id + '_reload')) {
			this.oReload = oReload;
			Event.observe(this.oReload, "click", this.reload.bind(this));
		}

		if(oInput = $(this.config.id)) {
			this.oInput = oInput;
		}
	},
	reload: function() {
		if(this.oCaptcha) {
			this.oCaptcha.src = this.config.reloadurl + "&amp;" + Math.round(Math.random(0)*1000)+1;
		}
	}
}, Formidable.Classes.RdtBaseClass);
