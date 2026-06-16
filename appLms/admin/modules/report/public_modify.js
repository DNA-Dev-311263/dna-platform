var PublicModify = {

    startLink: "",
    allPerm: false,
    ajaxPath: "",
	oPanel: null,
	oLangs: new LanguageManager(),

    init: function(oConfig) {
		
        if (oConfig.startLink) this.startLink = oConfig.startLink;
        if (oConfig.ajaxPath) this.ajaxPath = oConfig.ajaxPath;
        if (oConfig.allPerm) this.allPerm  = oConfig.allPerm;
		if (oConfig.langs) this.oLangs.set(oConfig.langs);
        
        YAHOO.util.Event.onDOMReady(function(e) {
            var E = YAHOO.util.Event, D = YAHOO.util.Dom, P = PublicModify;

			//Creo panel informativo
			P.oPanel = P.createPanel();

            //Visibilità tabella profili
            P.changeOnPublic(D.get('is_public').value);

			E.addListener("btn_undo", "click", function(e) {
				E.preventDefault(e);
				P.undoModify();
			});
			
			E.addListener("is_public", "change", function(e) {     
                P.changeOnPublic(this.value);
			});
			
			E.addListener("adm_list_preview", "click", function(e) {
                P.openAdmPanel(P.oLangs.get('_PREVIEW'));
			});
			

			if (P.allPerm) {
				
				E.addListener("repcat_form", "submit", function(e) {
					E.preventDefault(e);

					if (P.beforeSubmit()) {
						D.get("repcat_form").submit();
					}
                });
                
                var adm_links = D.getElementsByClassName('adm_view_link');
				E.addListener(adm_links, "click", function(e) {

					var idst = this.getAttribute("data-idst");
					var groupName = this.getAttribute("data-name");

					P.openAdmPanel(groupName, idst);
				});
					
			}

        });
    },

    changeOnPublic: function(isPublic) {

        var D = YAHOO.util.Dom;
        var divTable = D.get("box_adminprofile_table");
        var spanPreview = D.get("adm_list_preview");
        
        spanPreview.style.visibility = (isPublic == 2 ? "visible" : "hidden");

        if (divTable)
            divTable.style.display = (isPublic == 2 ? "block" : "none");

    },

    beforeSubmit: function() {

          var D = document;
          var txtRecip = D.getElementById("report_recipients");
          var retVal = true;
          var chks = this.getOptChecked();

          txtRecip.value = "";

          for (var i = 0; i < chks.length; i++) {
               txtRecip.value += (txtRecip.value == "" ? "" : ",") + chks[i].value;
          }  

          return retVal;
    },
    
    getOptChecked() {

         var chks = document.getElementsByName('selgroup');
         var retOpt = [];

         for (var i = 0; i < chks.length; i++) {
               if (chks[i].checked) {
                    retOpt.push(chks[i]);
               }
         } 

         return retOpt;
    },

    undoModify: function() {
        var url = PublicModify.startLink;
        window.location.href = url;
    },

    selectAll: function() {

        var chks = this.getOptChecked();
        var chkVal = false;

        if (chks.length == 0) {
            chks = document.getElementsByName('selgroup');
            chkVal = true
        }

        for (var i = 0; i < chks.length; i++) {
            chks[i].checked = chkVal;
        }
    },

	createPanel: function() {
		
		var oPanel = null;
		var divPanel = document.getElementById('resizablepanel');

		divPanel.innerHTML = '<div class="hd"></div><div class="bd"></div><div class="ft"></div>'
		
		oPanel = 	new YAHOO.widget.Panel(divPanel, {
							draggable: "true",
							visible: false,
							width: document.body.clientWidth*0.6+'px',
							height: document.body.clientHeight*0.5+'px',
							constraintoviewport: true,
							fixedcenter: true,	
							zIndex: 99991
					});	
							
		oPanel.render(); 
		return oPanel;
	},

	getInfo: function(param, callback) {

			var url = this.ajaxPath

			YAHOO.util.Connect.asyncRequest("POST", url,
			{
				success: function (o) {
					var res = YAHOO.lang.JSON.parse(o.responseText);
					callback(res);
				},
				failure: null
			}, param);
	},

	openAdmPanel: function(title, idst = false) {
		var P = this;
		var param = "&op=profile_admins";
		var panel = P.oPanel;
		

		var setBody = function(res) {
			
				if (res.success && res.count > 0) {			
					
					var tb = getTable(res.fields, res.data);
					panel.setBody(tb);						
						
				} else if (res.success) {	
					panel.setBody(P.oLangs.get('_NO_CONTENT'));
									
				} else {
					panel.setBody(res.message);
				}			
		}		
			
		var getTable = function(fields, data) {
				var r, c, k;
				var tb = '<table class="pop_vtable"><tr>'; 
		
				for (c = 0; c < fields.length; c++) {
					tb += '<th>'+fields[c]['label']+'</th>';
				}
				tb += '</tr>';
				
				for (r = 0; r < data.length; r++) {
					tb += '<tr>';
					for (c = 0; c < fields.length; c++) {
						k = fields[c]['key'];
						tb += '<td>'+data[r][k]+'</td>';	
					} 
					tb += '</tr>';
				}
				tb += '</table>';
				
				return tb;
		} 
		
		// Preparo il parametro della richiesta
		if (idst && P.allPerm) {
			param += '&idst=' + idst;
			
		} else if (P.allPerm) {
			var tmpStr = "", chks = this.getOptChecked();

			for (var i = 0; i < chks.length; i++) 
               tmpStr += (tmpStr == "" ? "" : ",") + chks[i].value; 
			
			param += '&idst=' + tmpStr;
				
		} else {
			param += '&idst='
		}
		
		panel.setHeader(title);
		panel.setFooter(this.oLangs.get('_ADMIN_MANAGMENT_CAPTION'));
		panel.setBody('&nbsp;');

		this.getInfo(param, setBody);
		
		panel.show();
	}

}
