
var Profile = {

	bChangedPassword: false,

	get: function(sId) {
		return document.getElementById(sId);
	},

	activate: function(sId) {
		var e = this.get(sId);
		e.name = e.id;
	},

	hidden: function(sId, bHidden) {
		if (bHidden) {
			this.get(sId).classList.add('hide');
		} else {
			this.get(sId).classList.remove('hide');
		}
		
		return bHidden;
	},

	disabled: function(sId, bDisabled) {
		if (bDisabled) {
			this.get(sId).classList.add('disabled');
		} else {
			this.get(sId).classList.remove('disabled');
		}
		
		return bDisabled;
	},

	correct: function(sId, bCorrect) {
		if (bCorrect) {
			this.get(sId).classList.add('glyphicon-ok');
			this.get(sId).classList.remove('glyphicon-remove');
		} else {
			this.get(sId).classList.remove('glyphicon-ok');
			this.get(sId).classList.add('glyphicon-remove');
		}
		
		return bCorrect;
	},

	valueFrom: function(sId, sIdLabel) {
		this.get(sId).value = this.get(sIdLabel).textContent;
	},

	click: function(sButton) {
		if (sButton === 'edit') {
			this.hidden('btn-profile-edit', true);
			this.hidden('btn-profile-cancel', false);
			this.hidden('btn-profile-save', false);
			
			this.hidden('sName-label', !this.hidden('sName', false));
			this.hidden('sMail-label', !this.hidden('sMail', false));
			this.hidden('sPassword-label', !(this.hidden('sPassword', false) & this.hidden('sPassword-repeat', false)));
			this.hidden('sPassword-repeat-correct', this.hidden('sPassword-correct', false));
		} else
		if (sButton === 'cancel') {
			this.hidden('btn-profile-edit', false);
			this.hidden('btn-profile-cancel', true);
			this.hidden('btn-profile-save', true);
			
			this.valueFrom('sName', 'sName-label');
			this.valueFrom('sMail', 'sMail-label');
			this.valueFrom('sPassword', 'sPassword-label');
			this.valueFrom('sPassword-repeat', 'sPassword-label');
			
			this.hidden('sName-label', !this.hidden('sName', true));
			this.hidden('sMail-label', !this.hidden('sMail', true));
			this.hidden('sPassword-label', !(this.hidden('sPassword', true) & this.hidden('sPassword-repeat', true)));
			this.hidden('sPassword-repeat-correct', this.hidden('sPassword-correct', true));
		} else
		if ((sButton == 'save') && (this.get('sPassword').value === this.get('sPassword-repeat').value)) {
			if (this.bChangedPassword) {
				this.activate('sPassword');
			}
			
			return this.get('fEditProfile').submit();
		}
		
		return false;
	},

	changePassword: function() {
		var sPassword = this.get('sPassword').value;
		var sPasswordRepeat = this.get('sPassword-repeat').value;
		
		this.bChangedPassword |= true;
		
		this.disabled('btn-profile-save', !this.correct('sPassword-repeat-correct', this.correct('sPassword-correct', sPassword.length > 0) && (sPasswordRepeat === sPassword)));
		
		return true;
	}

};
