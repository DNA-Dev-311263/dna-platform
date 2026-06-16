
class proposalJs {

    constructor() {
        this._oLangs = new LanguageManager();
        this._table = null;

        this.initTableEvent = this.initTableEvent.bind(this);
        this.statusFormatter = this.statusFormatter.bind(this);
        this.scoreFormatter = this.scoreFormatter.bind(this);
    }

    //Proprietà

    set statusList(val) {this._statusList = val;}
	set baseLink(val) {this._baseLink = val;}
    set idProponent(val) {this._idProponent = val;}
    set idTable(val) {this._idTable = val;}
    set langs(obj) {this._oLangs.set(obj);this.setConstButton();}

    get statusList() {return this._statusList;}


	setConstButton() {
		var t = this;
        YAHOO.dialogConstants.setProperties({CONFIRM:t._oLangs.get("_CONFIRM"), UNDO:t._oLangs.get("_UNDO"), CLOSE:t._oLangs.get("_CLOSE")});		
	}


	initTableEvent() {
        this._table = eval('DataTable_' + this._idTable); 
	}


	scoreFormatter (elLiner, oRecord, oColumn, oData) {
	    elLiner.innerHTML =  Math.round(oData);    
    }


	statusFormatter (elLiner, oRecord, oColumn, oData) {
		var index = 'status_'+oData;
		elLiner.innerHTML = this._statusList[index] || "";
	}
    
    
    scoreSave(oEditor, new_value, callback) {
		
		var datatable = oEditor.getDataTable();  
		var record = oEditor.getRecord();
        var col_current = oEditor.getColumn().getKey();
        var col_related = (col_current == 'from_score' ? 'to_score' : 'from_score');
        var id_proposal = oEditor.getRecord().getData("id");
        //var old_value = oEditor.value;
        

        //Controllo valore
        if(!new_value) {new_value=0};
  
        if(col_current == 'from_score' && new_value <= record.getData('to_score') || col_current == 'to_score' && new_value >= record.getData('from_score')) {
			//Salvo normalmente
			this.saveInline(id_proposal, col_current, new_value, callback);
			
  		} else {
			//Salvo prima il campo correlato con valore uguale e poi quello corrente
			var fnAlert = function(a=false, b=false){/*eventuale messaggio*/};
			this.saveInline(id_proposal, col_current, new_value, fnAlert);
			this.saveInline(id_proposal, col_related, new_value, callback);
			
			//Scrivo il dato modificato di nascosto e visualizzo sul datatable
            datatable.updateCell(record, col_related, new_value);
			//record.setData(col_related, new_value);
			//datatable.render();
		}     
	}
	

	saveInline(id_proposal, col, val, callback) {

		var oCallback = {
			success: function(o) {
				var res = YAHOO.lang.JSON.parse(o.responseText);
				
				if (res.success) {
					 callback(true, val);
					 
				}else{
					callback();
					this.submitDialog('alert', 'Info', res.message, icon = 'warnicon');
				}
			},
			failure: function() {}
		};
		
		var url = "ajax.adm_server.php?r="+this._baseLink+"/edit";
		var post = "id_proposal=" + id_proposal
					+"&col=" + col
					+"&new_value=" + val;
					
		YAHOO.util.Connect.asyncRequest("POST", url, oCallback, post);	
	}
    
	/*
    saveAsync(oEditor, new_value, callback) {
		
		var record = oEditor.getRecord();
        var col = oEditor.getColumn().getKey();
        var old_value = oEditor.value;
        var datatable = oEditor.getDataTable();         
		var id_proposal = oEditor.getRecord().getData("id_proposal");
			
		var oCallback = {
			success: function(o) {
				var res = YAHOO.lang.JSON.parse(o.responseText);
				
				if (res.success) {
					 callback(true, new_value);
					 
				}else{
					callback();
					alert('Err: ' + res.message);
				}
			},
			failure: function() {}
		};
		
		
		var url = "ajax.adm_server.php?r="+this._baseLink+"/edit";
		var post = "id_proposal=" + id_proposal
					+"&col=" + col
					+"&new_value=" + new_value
					+"&old_value=" + old_value;
					
		YAHOO.util.Connect.asyncRequest("POST", url, oCallback, post);
		
	}
	* */


    delAll(evt = null){

        if (evt) YAHOO.util.Event.preventDefault(evt);
        
        var t = this;
        var url = "ajax.adm_server.php?r="+ this._baseLink +"/del&";
		var post = "op=all&id_proponent=" + this._idProponent;

        var count = this._table.getRecordSet().getLength();

        if(count > 0)
            this.submitDialog('confirm', t._oLangs.get("_DEL_ALL"), t._oLangs.get("_AREYOUSURE"), 'warnicon', url + post);
        else
            this.submitDialog('alert', t._oLangs.get("_DEL_ALL"), t._oLangs.get("_NO_DATA"));

    }

    
    submitDialog (mode, headerText, messageText, icon = 'infoicon', action = false, oPostData = null, oCallBack = null) {
        //mode = "confirm" or "alert"

        var t = this;
        var fieldString = ''

        if (action) {
            if(!oCallBack){
                oCallBack = function(res) {this._table.refresh();};
            }
             oCallBack = oCallBack.bind(this);

            if (oPostData) {
                fieldString = '<input type="hidden" id="'+oPostData.id+'" name="'+oPostData.name+'" value = "'+oPostData.value+'" />';
            }
        }

	    var oDialog = CreateDialog("proposal_dialog", {
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
				            body: '<div id="proposal_dialog_message"></div>'
					             +'<span class="yui-icon '+icon+'">&nbsp;</span>'
                                 +'<form method="POST" id="proposal_dialog_form" action="'+action+'">'
					             + '    <p>'+messageText+'</p>'+fieldString
					             +'</form>',
				            callback: function(oResp){this.destroy(); oCallBack(oResp)}
				        });
        

		oDialog.call(this, null);

    }

}


