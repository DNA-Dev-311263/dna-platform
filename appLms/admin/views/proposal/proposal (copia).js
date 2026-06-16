
class proposalJs {

    constructor() {
    }

    //Proprietà
    set statusList(val) {this._statusList = val;}
	set baseLink(val) {this._baseLink = val;}


    get statusList() {
        return this._statusList;
    }

    //Metodi
    ntz(val) {
        var retVal = val
        if(isNaN(val)) {
	       retVal = 0;
        }
        return retVal;
    }
    
    
    scoreSave(oEditor, new_value, callback) {
		
		var datatable = oEditor.getDataTable();  
		var record = oEditor.getRecord();
        var col_current = oEditor.getColumn().getKey();
        var col_related = (col_current == 'from_score' ? 'to_score' : 'from_score');
        var id_proposal = oEditor.getRecord().getData("id_proposal");
        //var old_value = oEditor.value;
  
        if(col_current == 'from_score' && new_value <= record.getData('to_score') || col_current == 'to_score' && new_value >= record.getData('from_score')) {
			//Salvo normalmente
			this.saveInline(id_proposal, col_current, new_value, callback);
			
  		} else {
			//Salvo prima il campo correlato con valore uguale e poi quello corrente
			var fnAlert = function(a=false, b=false){/*eventuale messaggio*/};
			this.saveInline(id_proposal, col_current, new_value, fnAlert);
			this.saveInline(id_proposal, col_related, new_value, callback);
			
			//Scrivo il dato modificato di nascosto e visualizzo sul datatable
			record.setData(col_related, new_value);
			datatable.render();
		}     
	}
	
	saveInline(id_proposal, col, val, callback) {
		
		var oCallback = {
			scope: this,
			success: function(o) {
				var res = YAHOO.lang.JSON.parse(o.responseText);
				
				if (res.success) {
					 callback(true, val);
					 
				}else{
					callback();
					alert(res.message);
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
    
 
    saveAsync(oEditor, new_value, callback) {
		
		var record = oEditor.getRecord();
        var col = oEditor.getColumn().getKey();
        var old_value = oEditor.value;
        var datatable = oEditor.getDataTable();         
		var id_proposal = oEditor.getRecord().getData("id_proposal");
			
		var oCallback = {
			scope: this,
			success: function(o) {
				var res = YAHOO.lang.JSON.parse(o.responseText);
				
				if (res.success) {
					 callback(true, new_value);
					 
				}else{
					callback();
					alert(res.message);
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

    saveInLine(oArgs) {
		alert(this._baseLink);
		var oEditor = oArgs.editor;
		var new_value = oArgs.newData;
		var old_value = oArgs.oldData;
		var col = oEditor.getColumn().getKey();
        var row = oEditor.getRecord().getId();
        var id_proposal = oEditor.getRecord().getData("id");
        
		var callback = {
            table: this,
			success: function(o) {
				var res = YAHOO.lang.JSON.parse(o.responseText);
				if (res.success) {
					//alert('ok');

				}else{
                    var dt = this.table;
                    var r = res.undo['row'];
                    var c = res.undo['col'];
                    var val = res.undo['old_value'];

                    dt.getRecord(r).setData(c, val);
                    dt.render();

                    alert(res.message);
                }
			},
			failure: function() {}
		};
		
	
		var url = "ajax.adm_server.php?r="+this._baseLink+"/edit";
		var post = "id_proposal=" + id_proposal
					+"&col=" + col
                    +"&row=" + row
					+"&new_value=" + new_value
					+"&old_value=" + old_value;
					
		YAHOO.util.Connect.asyncRequest("POST", url, callback, post);
	
    }


}


