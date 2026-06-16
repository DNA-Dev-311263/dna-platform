var Commcourse = {
	
	idOrg: 0,
    selComm: "",
    dateFrom: null,
	baseLink: "",
    perms: {},
    ajaxUrl: "",
    table: null,
	oLangs: new LanguageManager(),
	
  
	init: function(oConfig) {

        if (oConfig.idOrg) this.idOrg = oConfig.idOrg;
        if (oConfig.selComm) this.selComm = oConfig.selComm;
		if (oConfig.baseLink) this.baseLink = oConfig.baseLink;
		if (oConfig.perms) this.perms = oConfig.perms;
		if (oConfig.ajaxUrl) this.ajaxUrl= oConfig.ajaxUrl;
		if (oConfig.langs) this.oLangs.set(oConfig.langs);
		
		
		YAHOO.util.Event.onDOMReady(function(e) {
			
			var E = YAHOO.util.Event, D = YAHOO.util.Dom, C = Commcourse, L = C.oLangs;	
			
			//Recupero datatable
			C.table = $('#course_list_table').DataTable();
			
			//Traduzione testi bottoni
			YAHOO.dialogConstants.setProperties({CONFIRM:L.get("_CONFIRM"), UNDO:L.get("_UNDO"), CLOSE:L.get("_CLOSE")});	
			
			//Imposto i default
			C.setLayout(C.selComm);

			//Apro i parametri
			var aflds = document.getElementsByClassName("filedset-av");
			aflds[0].click();
			
			//Evento per gestire il cambiamento di azienda
			E.addListener('sel_id_org', "change", function(e) {
				C.idOrg = this.value;
				var u = C.ajaxUrl + "&id_org=" + C.idOrg;
				if(C.table) C.table.ajax.url(u).load();
			});	
			
			//Evento per gestire il cambiamento di comunicazione
			E.addListener('sel_comm', "change", function(e) {
				C.selComm = this.value;
				C.setLayout(C.selComm);
				C.table.ajax.reload();
			});
			
            //Evento per gestire cambiamento di data
            $("#txt_date_from").change(function(){
				C.dateFrom = this.value;
            });
			
			//Evento invio comunicazione
			E.addListener('btn_send', "click", function(e) {
				E.preventDefault(e);
				C.submitComm();
			});
			
		});
	},
	
	submitComm: function() {
		
		var C = this, L = C.oLangs;
		var list = this.getSelection();
		var res = false;
		var ajaxUrl ="ajax.adm_server.php?r="+this.baseLink+"/sendCommunication";
		
		if(list === false) {
			C.sendFeedback(L.get("_NO_COURSES"));
			
		} else if(!list && C.selComm != 'ReminderGapAssn') {
			C.sendFeedback(L.get("_EMPTY_SELECTION"), 'notice');
			
		} else {
			C.infoAction(list, function(info){
				
					if (info.communication_count == "0") {
						C.sendFeedback(L.get("_NO_DATA"));	
				
					} else {
						var relatedDesc = (C.selComm == 'ReminderGapAssn' ? 'Cataloghi' : 'Corsi');
						var msg = L.get("_CONFIRM_ACTION");
						
						msg = msg.replace("[operation]", info.operation);
						msg = msg.replace("[related_desc]", relatedDesc);
						msg = msg.replace("[related_count]", info.related_count);
						msg = msg.replace("[communication_count]", info.communication_count);
						
						var oPostArr = [	{id: 'course_list', name: 'course_list', value: list}, 
											{id: 'operation', name: 'operation', value: C.selComm},
											{id: 'id_org', name: 'id_org', value: C.idOrg},
											{id: 'date_from', name: 'date_from', value: C.dateFrom} ];
						
						
						var oCallBack = function(resp) { 
											var m = L.get("_SUCCESS").replace("[communication_count]", resp.count);
											C.sendFeedback(m);
										}
								
						C.submitDialog("confirm", L.get('_AREYOUSURE'), msg, null, 'warnicon', oCallBack, ajaxUrl, oPostArr);
					}	
			});
		}
		
		return res;
	},
	
	setLayout: function (comm_code) {
		var C = this;
		
		C.getParamDef(comm_code, function(params) {
			var def = params.defaults;
			var tabExists = params.tabExists;
			
			if(!def['date_from']) {
				$('#txt_date_from').hide();
				$('label[for="txt_date_from"]').hide();
				
				C.dateFrom  = null;
				//$('label[for="txt_date_from"]').css('visibility', 'hidden');
			} else {
				$('#txt_date_from').show();
				$('#txt_date_from').val(def['date_from']);
				$('#txt_date_from').datepicker('setDate', def['date_from']);
				$('label[for="txt_date_from"]').show();
				
				C.dateFrom  = def['date_from'];
			}
			if(!tabExists) {
				$('#course_list_table_wrapper').hide();
			} else {
				$('#course_list_table_wrapper').show();
			}
		});	
	},
	
	getParamDef: function (comm_code, callback) {
		
		var ajaxUrl ="ajax.adm_server.php?r="+this.baseLink+"/getParamJson&sel_comm="+comm_code;

		$.ajax({
		  type: "GET",
		  url:  ajaxUrl,
		  dataType: "json",
		  success: function(params){
					   callback( params );
		  },
		  error: function(){
			alert("Err: Table info param");
		  }
		});
	},
	
	getSelection: function() {
		
		var list = "";
		var tbl = this.table;
		
		if (tbl.rows().count() == 0) {
			list = false
			
		} else {
			var selRows = tbl.rows({selected: true});
				
			selRows.every(function () {list += this.id() + ","});
			list = list.substring(0,list.length-1);
		}
		return list;
		
	},
	
	infoAction: function (list, callBack) {
		
        var t = this;
        var dataString = "id_org="+t.idOrg+"&operation="+t.selComm+"&date_from="+t.dateFrom+"&course_list="+list;
        var action_url = "ajax.adm_server.php?r=" + t.baseLink + "/infoCommunication";
    
		$.ajax({
				type: "POST",
				url: action_url,
				data: dataString,
				async: true, 	
				success:
					function(data){
					   callBack( jQuery.parseJSON( data ) );
					}
			   });
	},
	
	submitDialog:  function (mode, headerText, messageText, evt = null, icon = 'infoicon', oCallBack = null, action = false, oPostArr = null) {
        //mode = "confirm" or "alert"

		var fieldString = '';
		
        if (action && oPostArr) {
			for (var i = 0; i < oPostArr.length; ++i) {
				fieldString += '<input type="hidden" id="'+oPostArr[i].id+'" name="'+oPostArr[i].name+'" value = "'+oPostArr[i].value+'" />';
			}
        }

	    var oDialog = CreateDialog("commcourse_dialog", {
				            width: "500px",
				            modal: true,
				            close: true,
				            visible: false,
				            fixedcenter: true,
				            constraintoviewport: true,
				            draggable: true,
				            hideaftersubmit: false,
				            isDynamic: false,
				            header: headerText,
                            confirmOnly: (mode == 'confirm' ? false : true),
				            body: '<div id="commmcourse_dialog_action"></div>'
					             +'<span class="yui-icon '+icon+'">&nbsp;</span>'
                                 +'<form method="POST" id="commcourse_dialog_form" action="'+action+'">'
					             + '    <p>'+messageText+'</p>'+fieldString
					             +'</form>',
				            callback: function(oResp){this.destroy(); if(oCallBack) oCallBack(oResp);}
				        });

		oDialog.call(this, evt);

    },
  
    sendFeedback: function(message, icon = 'info', autoFade = true) {

        var div = document.getElementById("container-feedback");

        if(div) div.remove();

        div = document.createElement("DIV"); 
        div.id = "container-feedback";
        div.className = "container-feedback";
        div.innerHTML = '<a href=""><div width="100%"><span class="ico-sprite fd_'+ icon +'"></span>&emsp;<b>'+ message +'</b></div></a>';
        div.style.display = "block";
        div.addEventListener("click", function(){event.preventDefault(); $(this).fadeOut(500)});
      	document.body.appendChild(div);
        
        if(autoFade)
		    $( "#" + div.id ).fadeOut( 5000, function() {});
    }
    
}
