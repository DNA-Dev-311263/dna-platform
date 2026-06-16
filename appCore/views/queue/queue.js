var Queue = {
	
    selStatus: "",
	baseLink: "",
    perms: {},
    ajaxUrl: "",
    table: null,
	oLangs: new LanguageManager(),
	
  
	init: function(oConfig) {

        if (oConfig.selStatus) this.selStatus = oConfig.selStatus;
        if (oConfig.dateFrom) this.dateFrom = oConfig.dateFrom;
		if (oConfig.baseLink) this.baseLink = oConfig.baseLink;
		if (oConfig.perms) this.perms = oConfig.perms;
		if (oConfig.ajaxUrl) this.ajaxUrl= oConfig.ajaxUrl;
		if (oConfig.langs) this.oLangs.set(oConfig.langs);
		
		
		YAHOO.util.Event.onDOMReady(function(e) {
			
			var E = YAHOO.util.Event, D = YAHOO.util.Dom, Q = Queue, L = Q.oLangs;	
			
			//Recupero datatable
			Q.table = $('#queue_list_table').DataTable();
			
			//Traduzione testi bottoni
			YAHOO.dialogConstants.setProperties({CONFIRM:L.get("_CONFIRM"), UNDO:L.get("_UNDO"), CLOSE:L.get("_CLOSE")});	

            //Evento per gestire cambiamento di data
            $("#txt_date_from").change(function(){
				Q.dateFrom = this.value;
                var u = Q.getUrl();
                if(Q.table) Q.table.ajax.url(u).load();
            });
			
			//Evento per gestire il filtro inviato
			E.addListener('sel_status', "change", function(e) {
				Q.selStatus = this.value;
                var u = Q.getUrl();
                if(Q.table) Q.table.ajax.url(u).load();
			});
			
			
		});
	},
	
    getUrl: function() {
        var Q = this;
        return  Q.ajaxUrl + '&date_from=' + Q.dateFrom + '&status=' + Q.selStatus;
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
	
	restart: function() {
		
		var Q = this, L = Q.oLangs;
		
		var url = "ajax.adm_server.php?r=" + Q.baseLink + "/restart";
		var message = L.get('_RESTART');
		
		var oCallBack = function(o) 
						{
							if (o.success) {
								Q.sendFeedback(L.get('_OPERATION_SUCCESSFUL'));
							} else {
								Q.sendFeedback(L.get('_OPERATION_FAILURE'));
							}
						}
						
		Q.submitDialog("confirm", L.get('_AREYOUSURE'), message, null, 'warnicon', oCallBack, url);

	},
	
	refreshTable: function() {
		var Q = this;
		Q.table.ajax.url(Q.getUrl()).load();
	},
	
	exportTaskDetail: function() {

		var D = YAHOO.util.Dom;
		var f = D.get("export_form");
		var i = D.get("export_input");
		
		var list = this.getSelection();
		
		if (list) {
			i.value = list;
			f.submit();
		} else {
			this.sendFeedback(this.oLangs.get("_EMPTY_SELECTION"));
		}
	},
	
	delItems: function() {
		var Q = this, L = Q.oLangs;
		var tbl = this.table;
		var count_sel = tbl.rows({selected: true}).count();
		var url = "ajax.adm_server.php?r=" + Q.baseLink + "/multidel";

		if (count_sel > 0) {
			
			var itemList = this.getSelection();
			var message = L.get('_DEL')+': '+count_sel+' record';
			var oPostData = {id: 'delQueue', name: 'queue', value: itemList};
			
			var oCallBack = function(o) 
							{
								if (o.success) {
									if (o.deleted == count_sel)
										tbl.rows('.selected').remove().draw(false);
									else
										Q.refreshTable();
								} else {
									Q.sendFeedback(L.get('_OPERATION_FAILURE'));
								}
							}
							
			Q.submitDialog("confirm", L.get('_AREYOUSURE'), message, null, 'warnicon', oCallBack, url, oPostData);
			
		} else {
			
			Q.sendFeedback(L.get("_EMPTY_SELECTION"));
		}
	},
	
	submitDialog:  function (mode, headerText, messageText, evt = null, icon = 'infoicon', oCallBack = null, action = false, oPostData = null) {
        //mode = "confirm" or "alert"

		var fieldString = '';
		
        if (action && oPostData) {
			fieldString = '<input type="hidden" id="'+oPostData.id+'" name="'+oPostData.name+'" value = "'+oPostData.value+'" />';
        }

	    var oDialog = CreateDialog("queue_dialog", {
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
				            body: '<div id="queue_dialog_action"></div>'
					             +'<span class="yui-icon '+icon+'">&nbsp;</span>'
                                 +'<form method="POST" id="queue_dialog_form" action="'+action+'">'
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
