/* ======================================================================== \
|   FORMA - The E-Learning Suite                                            |
|                                                                           |
|   Copyright (c) 2013 (Forma)                                              |
|   http://www.formalms.org                                                 |
|   License  http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt           |
|                                                                           |
|   from docebo 4.0.5 CE 2008-2012 (c) docebo                               |
|   License http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt            |
\ ======================================================================== */

var Courseassn = {

    idOrg: 0,
    selYear: 0,
	baseLink: "",
	filterText: "",
	statusList: null,
    perms: {},
	oLangs: new LanguageManager(),
    

	init: function(oConfig) {

        if (oConfig.idOrg) this.idOrg = oConfig.idOrg;
        if (oConfig.selYear) this.selYear = oConfig.selYear;

		if (oConfig.baseLink) this.baseLink = oConfig.baseLink;
		this.statusList = oConfig.statusList || [];
        if (oConfig.perms) this.perms = oConfig.perms;
		if (oConfig.langs) this.oLangs.set(oConfig.langs);
		if (oConfig.filterText) A.filterText = oConfig.filterText;

        if (oConfig.dynSelection) this.dynSelection = oConfig.dynSelection;
		if (oConfig.fieldList) this.fieldList = oConfig.fieldList;
		if (oConfig.numVarFields) this.numVarFields = oConfig.numVarFields;
		if (oConfig.templatePath) this.templatePath = oConfig.templatePath;
		
        

		YAHOO.util.Event.onDOMReady(function(e) {
			var E = YAHOO.util.Event, D = YAHOO.util.Dom, A = Courseassn;
			var oDt = DataTable_courseassn_table, oDtS = DataTableSelector_courseassn_table;
            var L = A.oLangs;

            // Traduzione testi bottoni
			YAHOO.dialogConstants.setProperties({CONFIRM:L.get("_CONFIRM"), UNDO:L.get("_UNDO"), CLOSE:L.get("_CLOSE")});	
			
            // Funzioni azioni combo
			var exportEvent = function() {

			    var f = D.get("csv_form");
				var i = D.get("csv_input");
                var o = D.get("csv_operation");

                o.value = "export_assn"
				i.value = oDtS.toString();
				f.submit(); 
			};

            var exportActiveEvent = function() {

			    var f = D.get("csv_form");
				var i = D.get("csv_input");
                var o = D.get("csv_operation");

                o.value = "export_assn_active"
				i.value = '{"id_org":'+A.idOrg+',"year":'+A.selYear+',"filter_text":"'+A.filterText+'"}';
				f.submit(); 

            };

            var updateManager = function() {
                var url = "index.php?r="+A.baseLink+"/updateAssnWizard&id_org="+A.idOrg;
                $(location).attr('href',url);
            };
            
            var cancelActiveEvent = function() {
				var url = "ajax.adm_server.php?r="+A.baseLink+"/cancelActive&id_org="+A.idOrg+"&year="+A.selYear;		
				var oCallBack = function(o) {oDt.refresh()};
								
				A.submitDialog("confirm", L.get('_CANCEL_ASSN_ACTIVE'), L.get('_AREYOUSURE'), null, 'warnicon', oCallBack, url);
			};
    

            // Combo azioni su tabella
            var items = [];
            
            if (A.perms.view_assn) {
                items.push({id:"opt0", text: L.get('_EXPORT_CSV'), onclick: { fn: exportEvent }});
                items.push({id:"opt1", text: L.get('_EXPORT_ASSN_ACTIVE'), onclick: { fn: exportActiveEvent }});
            }
            if (A.perms.mod_assn) {
                items.push({id:"opt2", text: L.get('_UPDATE_ASSN'), onclick: { fn: updateManager }});
                items.push({id:"opt3", text: L.get('_CANCEL_ASSN_ACTIVE'), onclick: { fn: cancelActiveEvent }});
            }

            if (items.length > 0) {
               
                var oMenu = new YAHOO.widget.Menu("ma_over_container", {visible: false});
                oMenu.addItems(items);
                //oMenu.render();
                var oButtonOver = new YAHOO.widget.Button("ma_over", {
	                label: L.get('_MORE_ACTIONS'),
	                type: "menu",
	                menu: items
                });

				var oButtonBottom = new YAHOO.widget.Button("ma_bottom", {
					label: L.get('_MORE_ACTIONS'),
					type: "menu",
					menu: items
				});
            }
            // Fine combo azioni

            // Funzioni evento controlli pagina

			E.addListener('filter_text', "keypress", function(e) {
				switch (E.getCharCode(e)) {
					case 13: {
						E.preventDefault(e);
						A.filterText = this.value;
						oDt.refresh();
					} break;
				}
			});

			E.addListener("filter_year", "change", function(e) {
				E.preventDefault(e);
				A.selYear = D.get("filter_year").value;
				oDt.refresh();
			});

			E.addListener("filter_org", "change", function(e) {
				E.preventDefault(e);
				A.idOrg = D.get("filter_org").value;
				oDt.refresh();
			});

			E.addListener("filter_set", "click", function(e) {
				E.preventDefault(e);
				A.filterText = D.get("filter_text").value;
				oDt.refresh();
			});

			E.addListener("filter_reset", "click", function(e) {
				E.preventDefault(e);
				D.get("filter_text").value = "";
				A.filterText = "";
				oDt.refresh();
			});

            //Export da dati pagina (non più usata)
            /*
             * 
            var export_links = D.getElementsByClassName('ico-wt-sprite subs_csv');
			E.addListener(export_links, "click", function(e) {
                var d = new Date();
                var filename = 'course_assignment.csv';
                Courseassn.downloadCsv(oDt, filename);
			});
			*/

			//multi delete
			var multidel_links = YAHOO.util.Dom.getElementsByClassName('ico-wt-sprite subs_del');

			YAHOO.util.Event.addListener(multidel_links, "click", function(e) {
				var count_sel = oDtS.num_selected;

				if (count_sel > 0) {
					
					var message = A.oLangs.get('_DEL')+': '+count_sel+' '+A.oLangs.get('_ASSIGNMENTS');
					var oPostData = {id: 'delAssn', name: 'assignments', value: oDtS.toString()};
					
					var oCallBack = function(o) 
									{
										if (o.deleted) {
											for (var i=0; i<o.deleted.length; i++)
												oDtS.remsel(o.deleted[i]);
										}
										oDt.refresh();
									}
									
					A.submitDialog("confirm", A.oLangs.get('_AREYOUSURE'), message, e, 'warnicon', oCallBack, this.href, oPostData);
					
				} else {
					A.submitDialog("alert", A.oLangs.get('_AREYOUSURE'), A.oLangs.get('_EMPTY_SELECTION'), e);
				}
			});
		});
	},

	initEvent: function() {
		var updateSelected = function() {
			Courseassn.setNumUserSelected(this.num_selected);
		};
		var ds = DataTableSelector_courseassn_table;
		ds.subscribe("add", updateSelected);
		ds.subscribe("remove", updateSelected);
		ds.subscribe("reset", updateSelected);
		
		this.doBeforeShowCellEditor = function(oEditor) {
			//Utilizzabile per default
			var key = oEditor.getColumn().getKey();
			switch (key) {
				case "col_name_1":   
					var dt=oEditor.getRecord().getData("col_name_2")
						oEditor.value = 'def_value';

					break;
			}
			return true;
		};
	},

	beforeRenderEvent: function() {
		//dynamic field(s)
		var slist = YAHOO.util.Selector.query('select[id^=_dyn_field_selector_]');
		var blist = YAHOO.util.Selector.query('a[id^=_dyn_field_sort_]');
		var i;
		
		for (i=0; i<slist.length; i++) {
			slist[i].disabled = true;
			YAHOO.util.Event.purgeElement(slist[i]);
		}
		for (i=0; i<blist.length; i++) {
			YAHOO.util.Event.purgeElement(blist[i]);
		}
	},

	postRenderEvent: function() {

		//dynamic field(s)
		var oDt = DataTable_courseassn_table
		var slist = YAHOO.util.Selector.query('select[id^=_dyn_field_selector_]');
		var blist = YAHOO.util.Selector.query('a[id^=_dyn_field_sort_]');
		var oDt = DataTable_courseassn_table;		 
		var oSortedBy = oDt.get("sortedBy")
		var i;
		
		for (i=0; i<slist.length; i++) {

			//ABR: sistemazione comportamento 'cambio dyn field' dopo ordinamento
			//Impedisco la selezione del campo se ho un ordinamento attivo sulla colonna.
			
			if (oSortedBy.key.replace('dyn_field', 'dyn_field_selector') == slist[i].id) {
				//Blocco la combo se il campo è ordinato
				slist[i].disabled = true;
			} else {
				
				//Sblocco la combo
				slist[i].disabled = false;
				
				//Mostro i pulsanti di ordinamento solo se previsto
				if(slist[i].options[slist[i].selectedIndex].getAttribute('sortable') == 1){
					blist[i].style.visibility  = "visible";
				}else{
					blist[i].style.visibility  = "hidden";
				}
			}

			Courseassn.setDropDownRefreshEvent.call(slist[i]);
		}
		for (i=0; i<blist.length; i++) {
			Courseassn.setSortButtonRefreshEvent.call(blist[i]);
		}
	},

	selectAllFilter: function() {
		return "&filter_text=" + Courseassn.filterText +
		   "&id_org=" + Courseassn.idOrg +
		   "&year=" + Courseassn.selYear;
	},

	requestBuilder: function (oState, oSelf) {
		var sort, dir, startIndex, results;
		oState = oState || {pagination: null, sortedBy: null};
		startIndex = (oState.pagination) ? oState.pagination.recordOffset : 0;
		results = (oState.pagination) ? oState.pagination.rowsPerPage : null;
		sort = (oState.sortedBy) ? oState.sortedBy.key : oSelf.getColumnSet().keys[0].getKey();
		dir = (oState.sortedBy && oState.sortedBy.dir === YAHOO.widget.DataTable.CLASS_DESC) ? "desc" : "asc";
		var i, output = "&results=" + results +
				"&startIndex=" 	+ startIndex +
				"&sort="		+ sort +
				"&dir="			+ dir +
				Courseassn.selectAllFilter();
		for (i=0; i<Courseassn.numVarFields; i++) {
			output += "&_dyn_field["+i+"]=" + YAHOO.util.Dom.get("_dyn_field_selector_"+i).value
		}
		return output;
	},

	statusFormatter: function(elLiner, oRecord, oColumn, oData) {
		var i, valid = false, list = Courseassn.statusList;
		for (i=0; i<list.length; i++) {
			if (list[i].value == oData) {
				elLiner.innerHTML = list[i].label;
				valid = true;
				break;
			}
		}
		if (!valid) elLiner.innerHTML = '&nbsp;';
		//elLiner.innerHTML = (YAHOO.lang.isNumber(parseInt(oData)) ? oRecord.getData("status_tr") : oData);//oRecord.getData("status_tr");
	},

	dateFormatter: function(elLiner, oRecord, oColumn, oData) {
		if (!oData || oData == "00-00-00" || oData == "00-00-0000") {
			elLiner.innerHTML = '-';
		} else {
			elLiner.innerHTML = oData;
		}
	},


	//dynamic table label management functions
	numVarFields: 0,
	fieldList: [],
	templatePath: "",
	dynSelection: [],

	getDynLabelMarkup: function(index, selected) {
		
		var id = '_dyn_field_selector_'+index, sort_str = Courseassn.oLangs.get('_SORT');
		var oldSelector = document.getElementById(id);
		var val, txt, sortable
		var output = '<select id="'+id+'" name="_dyn_field_selector['+index+']">';
		
		for (i = 0; i < oldSelector.options.length; i++) { 
			//Mod. ABR, realizzo di nuovo le opzioni da quelle sulla pagina (così prendo attributi)
			val = oldSelector.options.item(i).value
			txt = oldSelector.options.item(i).text
			sortable = oldSelector.options.item(i).getAttribute('sortable')
			
			output += '<option sortable = "' + sortable + '" value="'+val+'"'
			+( selected == val ? ' selected="selected"' : '' )
			+ '>'+txt+'</option>';
		}

		/*
		for (x in Courseassn.fieldList) {
			output += '<option value="'+x+'"'
			+( selected == x ? ' selected="selected"' : '' )
			+'>'+Courseassn.fieldList[x]+'</option>';
		}
		*/
		
		output += '</select>';

		output += '<a id="_dyn_field_sort_'+index+'" href="javascript:;">';
		output += '<img src="'+Courseassn.templatePath+'images/standard/sort.png" ';
		output += 'title="'+sort_str+'" alt="'+sort_str+'" />';
		output += '</a>';

		Courseassn.dynSelection[id] = selected;
		return output;
	},

	setDropDownRefreshEvent: function() {
		YAHOO.util.Event.addListener(this, "change", function() {
			DataTable_courseassn_table.refresh();
		});
	},

	setSortButtonRefreshEvent: function() {
		var oDt = DataTable_courseassn_table;
		YAHOO.util.Event.addListener(this, "click", function(e) {
			YAHOO.util.Event.preventDefault(e);

			var oColumn = oDt.getColumn(this);

			//load adjusted <select> into column label
			var index = this.id.replace('_dyn_field_sort_', '');
			var selected = YAHOO.util.Dom.get('_dyn_field_selector_'+index).value;
			oColumn.label = Courseassn.getDynLabelMarkup(index, selected);
			
			var oSortedBy = oDt.get("sortedBy"), sDir = oDt.CLASS_ASC;
			if (oSortedBy.key == oColumn.getKey()) {
				sDir = (oSortedBy.dir == oDt.CLASS_ASC ? oDt.CLASS_DESC : oDt.CLASS_ASC);
			}

			oDt.sortColumn(oColumn, oSortedBy);
		});
	},

	setNumUserSelected: function(num) {
		var prefix = "items_selected_", D = YAHOO.util.Dom;
		D.get(prefix+"top").innerHTML = num;
		D.get(prefix+"bottom").innerHTML = num;
	},

	editorSaveEvent: function(oArgs) {
		var oEditor = oArgs.editor;
		var new_value = oArgs.newData;
		var old_value = oArgs.oldData;
		var col = oEditor.getColumn().getKey();
        var row = oEditor.getRecord().getId();
        var id_assn = oEditor.getRecord().getData("id");
        var table = this;
        
		var callback = {
			success: function(o) {
				var res = YAHOO.lang.JSON.parse(o.responseText);
				if (res.success) {
					//alert('ok');

				}else{
					table.updateCell(table.getRecord(row), col, old_value);
					Courseassn.submitDialog('alert','Info', res.message, null, 'warnicon');
                }
			},
			failure: function() {}
		};

		var url = "ajax.adm_server.php?r="+Courseassn.baseLink+"/edit";
		var post = "id_assn=" + id_assn
					+"&col=" + col
					+"&new_value=" + new_value
					+"&old_value=" + old_value;
					
		YAHOO.util.Connect.asyncRequest("POST", url, callback, post);

	},
	
	
	submitDialog:  function (mode, headerText, messageText, evt = null, icon = 'infoicon', oCallBack = null, action = false, oPostData = null) {
        //mode = "confirm" or "alert"

		var fieldString = '';
		
        if (action && oPostData) {
			fieldString = '<input type="hidden" id="'+oPostData.id+'" name="'+oPostData.name+'" value = "'+oPostData.value+'" />';
        }

	    var oDialog = CreateDialog("courseassn_dialog", {
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
				            body: '<div id="courseassn_dialog_action"></div>'
					             +'<span class="yui-icon '+icon+'">&nbsp;</span>'
                                 +'<form method="POST" id="courseassn_dialog_form" action="'+action+'">'
					             + '    <p>'+messageText+'</p>'+fieldString
					             +'</form>',
				            callback: function(oResp){this.destroy(); if(oCallBack) oCallBack(oResp);}
				        });

		oDialog.call(this, evt);

    }
	
    /* Funzioni per esportazione da dati pagina (non usate) */
    /*
    dataToCSV: function dataTableToCSV (myDataTable) {
		       
        var i, j, oData,
            aRecs = myDataTable.getRecordSet().getRecords(), strOut = '',
            aCols = myDataTable.getColumnSet().keys, col_invalid = new Array();

        //Recupero le intestazioni di colonna
        for (j=0; j<aCols.length; j++) {

            var label, optSelPos
            label = aCols[j].label;

            if (label.indexOf('dyn_field') !== -1) {

              // Dei campi dinamici (select) recupero solo il valore
              optSelPos = label.indexOf('"selected">');
              strOut += label.substring(optSelPos +11, label.indexOf('</', optSelPos))+";";

            }else if (label.indexOf('checkbox') !== -1 || label.indexOf('img') !== -1){

                // Memorizzo il numero di colonna non valida
                col_invalid.push(j);

            } else {
                 // Recupero il nome delle colonne normali
                 strOut += aCols[j].label + ";";
            }
        }
        strOut += "\n";

        //Recupero i dati
        for (i=0; i<aRecs.length; i++) {
			
			//Se l'indice non esiste, forzo il passaggio successivo (necessario quando il recordset è impaginato)
			if (aRecs[i] == null) { continue; }
			
			//Altrimenti procedo recuperando i dati del record
            oData = aRecs[i].getData();

            for (j=0; j<aCols.length; j++) {
                
                if (col_invalid.indexOf(j) == -1) {
					// Se è un dato di campo valido, recupero il valore
					var val = oData[aCols[j].key];
					
					if (val !== null && val.indexOf('<') != -1 && val.indexOf('>') != -1) {
						// Se il valore è in un tag html, rimuovo i tag
						strOut += '"' + Courseassn.tagRemover(val) + '"' + ";";
					} else {
						// altrimenti prendo il valore direttamente
						strOut += '"'+ val + '"' + ";";
					} 
                }
            }

            strOut += "\n";
        }

        return strOut;
    },

   downloadCsv: function downloadCsvFile(myDataTable, filename) {
        var csvFile;
        var downloadLink;
        var csv = Courseassn.dataToCSV(myDataTable);


        // CSV FILE
        csvFile = new Blob([csv], {type: "text/csv"});

        // Download link
        downloadLink = document.createElement("a");

        // File name
        downloadLink.download = filename;

        // Creo il link nel file
        downloadLink.href = window.URL.createObjectURL(csvFile);

        // Nascondo il link
        downloadLink.style.display = "none";

        // Aggiungo il link al body
        document.body.appendChild(downloadLink);

        // Lancio download
        downloadLink.click();
    },

	tagRemover: function tagHtmlRemove(htmlString) {
		var stripedHtml = htmlString.replace(/<[^>]+>/g, '');
		return stripedHtml;
	},
    */
	


}
