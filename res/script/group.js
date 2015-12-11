
var Group = {

	admins: [],
	users: [],
	modules: [],

	get: function(sId) {
		return document.getElementById(sId);
	},

	click: function(sForm) {
		return this.get(sForm).submit();
	},
	
	create: function(nType, sHashId, aData) {
		if (nType == 1) {
			this.admins[sHashId] = aData;
		} else
		if (nType == 2) {
			this.modules[sHashId] = aData;
		} else {
			this.users[sHashId] = aData;
		}
	},

	open: function(nType, sHashId) {
		if (nType == 1) {
			var oTemp = this.admins[sHashId];
			
			bootbox.alert("<h4>" + oTemp.sName + "</h4>"+
				"<div><table style='width: 100%;'>"+
					"<tr><th></th><th></th></tr>"+
				"</table></div>"
			);
		} else
		if (nType == 2) {
			var oTemp = this.modules[sHashId];
			
			// ...
		} else {
			var oTemp = this.users[sHashId];
			
			bootbox.alert("<h4>" + oTemp.sName + "</h4>"+
				"<div><table style='width: 100%;'>"+
					"<tr><th></th><th></th></tr>"+
					"<tr><td>" + i18n('module') + "</td><td>" + oTemp.sModuleHashId + "</td></tr>"+
				"</table></div>"
			);
		}
	}

};
