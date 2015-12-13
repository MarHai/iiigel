
var Group = {

	url: "",
	hashId: "",
	idIndex: 0,

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
			this.admins.push(this.admins[sHashId]);
		} else
		if (nType == 2) {
			this.modules[sHashId] = aData;
			this.modules.push(this.modules[sHashId]);
		} else {
			this.users[sHashId] = aData;
			this.users.push(this.users[sHashId]);
		}
	},

	open: function(nType, sHashId) {
		if (nType == 1) {
			var oTemp = this.admins[sHashId];
			var sHtml = "<h4>" + oTemp.sName + " ( " + i18n('user2group.leader') + " )</h4>";
			
			sHtml += "</div><div>";
			
			sHtml += "<a href='" + this.url + "Group/remove/" + this.hashId + "/" + sHashId + "' class='btn btn-danger'>" + i18n('remove') + "</a>";
			
			sHtml += "</div>";
			
			bootbox.alert(sHtml);
		} else
		if (nType == 2) {
			var oTemp = this.modules[sHashId];
			
			// ...
		} else {
			var oTemp = this.users[sHashId];
			var sHtml = "<h4>" + oTemp.sName + " ( " + i18n('user2group.member') + " )</h4>";
			
			sHtml += "<div style='margin: 2em;'>";
			sHtml += "<form id='Group-editUser-" + this.idIndex + "' action='" + this.url + "Group/editUser/" + this.hashId + "/" + sHashId + "' method='GET'>";
			sHtml += "<table style='width: 100%;'><tr><th></th><th></th></tr>";
			
			var oModule = this.modules[oTemp.sModuleHashId];
			
			sHtml += "<tr><td>" + i18n('module') + "</td>";
			sHtml += "<td><select name='sHashIdModule' style='width: 100%;'>";
			
			for (var i = 0; i < this.modules.length; i++) {
				sHtml += "<option value='" + this.modules[i].sHashId + "'";
				
				if (this.modules[i].sHashId === oTemp.sModuleHashId) {
					sHtml += " selected='selected'";
				}
				
				sHtml += ">" + this.modules[i].sName + "</option>";
			}
			
			sHtml += "</select></td></tr>";
			
			sHtml += "<tr><td>" + i18n('mode.chapter') + "</td>";
			sHtml += "<td><select name='sHashIdChapter' style='width: 100%;'>";
			
			for (var i = 0; i < oModule.aChapters.length; i++) {
				sHtml += "<option value='" + oModule.aChapters[i].sHashId + "'";
				
				if (oModule.aChapters[i].nId == oTemp.nCurrentChapterId) {
					sHtml += " selected='selected'";
				}
				
				sHtml += ">" + oModule.aChapters[i].nOrder + " - " + oModule.aChapters[i].sName + "</option>";
			}
			
			sHtml += "</select></td></tr>";
			
			sHtml += "</table></form></div><div>";
			
			sHtml += "<a href='" + this.url + "Group/remove/" + this.hashId + "/" + sHashId + "' class='btn btn-danger'>" + i18n('remove') + "</a>";
			sHtml += "<a class='btn btn-primary' style='margin-left: 1em;' onclick='return Group.click(\"Group-editUser-" + this.idIndex + "\");' >" + i18n('savechanges') + "</a>";
			
			sHtml += "</div>";
			
			this.idIndex++;
			bootbox.alert(sHtml);		
		}
	}

};
