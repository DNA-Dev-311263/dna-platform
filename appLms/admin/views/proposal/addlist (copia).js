
class addlistJs {

    constructor() {
        this._filterText = "";
        
        //Metodi esotici chiamati da altri contesti (datatable)
        //Il costruttore associa al metodo l'oggetto dell'istanza 
        //(this non sarà più il chiamante)
        this.initTableEvent = this.initTableEvent.bind(this);
        this.statusFormatter = this.statusFormatter.bind(this);
        this.requestBuilder = this.requestBuilder.bind(this);
    }

    //Proprietà
    set table(val) {this._table = val;}
    set selector(val) {this._selector = val;}
    set statusList(val) {this._statusList = val;}
	set baseLink(val) {this._baseLink = val;}
    set idCategory(val) {this._idCategory = val;}
    set idProponent(val) {this._idProponent = val;}
    set authReq(val) {this._authReq = val;}
    set filterText(val) {this._filterText = val;}
    set idTable(val) {this._idTable = val;}

    get statusList() {return this._statusList;}
    get table() {return this._table;}
    get idCategory() {return this._idCategory;}
    

    //Metodi

	initTableEvent() {
        var t = this;
        t._table = eval('DataTable_' + t._idTable);
		t._selector = eval('DataTableSelector_' + t._idTable);

        t.subsUpgradeSelection();
	}


    subsUpgradeSelection() {
        var t = this;
        
        var updateSelected = function() {
            
			t.setNumUserSelected(t._selector.num_selected);
		};

        t._selector.subscribe("add", updateSelected);
		t._selector.subscribe("remove", updateSelected);
		t._selector.subscribe("reset", updateSelected);

    }


	statusFormatter (elLiner, oRecord, oColumn, oData) {
		var index = 'status_'+oData;
		elLiner.innerHTML = this.statusList[index] || "";
	}
    

    setFilterText(mode) {

        var txt = YAHOO.util.Dom.get("filter_text")
        event.preventDefault();

        if (mode == 0) txt.value = "";
     
        this._filterText = txt.value;  
        this._table.refresh();
    }
    
  
	requestBuilder (oState, oSelf) {
		var sort, dir, startIndex, results;

		oState = oState || {pagination: null, sortedBy: null};
		startIndex = (oState.pagination) ? oState.pagination.recordOffset : 0;
		results = (oState.pagination) ? oState.pagination.rowsPerPage : null;
		sort = (oState.sortedBy) ? oState.sortedBy.key : oSelf.getColumnSet().keys[0].getKey();
		dir = (oState.sortedBy && oState.sortedBy.dir === YAHOO.widget.DataTable.CLASS_DESC) ? "desc" : "asc";
		var i, output = "&results=" 	+ results +
				        "&startIndex=" 	+ startIndex +
				        "&sort="		+ sort +
				        "&dir="			+ dir +
				        this.selectAllFilter();

	    return output;
	}


	addCourse() {
			event.preventDefault()
            var t = this;
			var url = "index.php?r="+t._baseLink+"/addProposal&id_proponent="+t._idProponent;
			var form = '<form method="POST" id="courselist_table_add" action="'+url+'">'
                            +'<input type="hidden" id="authentic_request_1" name="authentic_request" value="'+t._authReq+'" /><input type="hidden" name="courses" value="'+t._selector.toString()+'" />'
                     + '</form>';
			$('body').append(form);
			$('#courselist_table_add').submit();
			
		}
	
	
	setNumUserSelected(num) {
		
		var prefix = "items_selected", D = YAHOO.util.Dom;
		D.get(prefix).innerHTML = num;
		
	}


	selectAllFilter() {
		return "&filter_text=" + this._filterText +
           "&id_category=" + this._idCategory +
		   "&id_proponent=" + this._idProponent;
	}

}


