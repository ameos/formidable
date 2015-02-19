Formidable.Classes.Searchform = Formidable.inherit({
	getValue: function() {
		aResult = $H();
		this.childs().each(function(aRow) {
			sName = aRow[0];
			sAbsName = aRow[1];
			
			aResult.set(
				sName,
				this.child(sName).getValue()
			);

		}.bind(this));
		return aResult.toObject();	
	}
}, Formidable.Classes.RdtBaseClass);
