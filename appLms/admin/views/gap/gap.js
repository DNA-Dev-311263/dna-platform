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

var Gap = {
	
    idOrg: 0,
    selYear: 0,
	baseLink: "",
	filterText: "",
	levelList: null,
	statusList: null,
	cataList: null,
    perms: {},
	oLangs: new LanguageManager(),
	oPanel: null,
	validatorRules: null,
    

	init: function(oConfig) {

        if (oConfig.idOrg) this.idOrg = oConfig.idOrg;
        if (oConfig.selYear) this.selYear = oConfig.selYear;

		if (oConfig.baseLink) this.baseLink = oConfig.baseLink+"";
		this.statusList = oConfig.statusList || [];
		this.cataList = oConfig.cataList || [];
        if (oConfig.perms) this.perms = oConfig.perms;
		if (oConfig.langs) this.oLangs.set(oConfig.langs);
		if (oConfig.filterText) G.filterText = oConfig.filterText;

        if (oConfig.dynSelection) this.dynSelection = oConfig.dynSelection;
		if (oConfig.fieldList) this.fieldList = oConfig.fieldList;
		if (oConfig.validatorRules) this.validatorRules = oConfig.validatorRules;
		if (oConfig.numVarFields) this.numVarFields = oConfig.numVarFields;
		if (oConfig.templatePath) this.templatePath = oConfig.templatePath;

		YAHOO.util.Event.onDOMReady(function(e) {
			var E = YAHOO.util.Event, D = YAHOO.util.Dom, G = Gap;
			var oDt = DataTable_gap_table, oDtS = DataTableSelector_gap_table;
            var L = G.oLangs;
                
            // Traduzione testi bottoni
			YAHOO.dialogConstants.setProperties({CONFIRM:L.get("_CONFIRM"), UNDO:L.get("_UNDO"), CLOSE:L.get("_CLOSE")});
			
			// Evento click su un link della tabella
            oDt.subscribe('linkClickEvent', Gap.linkClick);  //oDt.subscribe('cellClickEvent', Gap.test);
			
			//Creo panel informativo
			G.oPanel = G.createPanel();

            // Funzioni azioni combo
			var exportEvent = function() {

			    var f = D.get("csv_form");
				var i = D.get("csv_input");
                var o = D.get("csv_operation");

                o.value = "export_gap"
				i.value = oDtS.toString();
				f.submit(); 
			};

            var exportActiveEvent = function() {

			    var f = D.get("csv_form");
				var i = D.get("csv_input");
                var o = D.get("csv_operation");

                o.value = "export_gap_active"
				i.value = '{"id_org":'+G.idOrg+',"year":'+G.selYear+',"filter_text":"'+G.filterText+'"}';
				f.submit(); 

            };

            var updateManager = function() {
                var url = "index.php?r="+G.baseLink+"/updateGapWizard&id_org="+G.idOrg;
                $(location).attr('href',url);
            }
            
            var cancelActiveEvent = function() {
				var url = "ajax.adm_server.php?r="+G.baseLink+"/cancelActive&id_org="+G.idOrg+"&year="+G.selYear;		
				var oCallBack = function(o) {oDt.refresh()};
								
				G.submitDialog("confirm", L.get('_CANCEL_GAP_ACTIVE'), L.get('_AREYOUSURE'), null, 'warnicon', oCallBack, url);
			};


            // Combo azioni su tabella
            var items = [];
          
            if (G.perms.view_gap) {
                items.push({id:"opt0", text: L.get('_EXPORT_CSV'), onclick: { fn: exportEvent }});
                items.push({id:"opt1", text: L.get('_EXPORT_GAP_ACTIVE'), onclick: { fn: exportActiveEvent }});
            }
            if (G.perms.mod_gap) {
                items.push({id:"opt2", text: L.get('_UPDATE_GAP'), onclick: { fn: updateManager }});
                items.push({id:"opt3", text: L.get('_CANCEL_GAP_ACTIVE'), onclick: { fn: cancelActiveEvent }});
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
						G.filterText = this.value;
						oDt.refresh();
					} break;
				}
			});

			E.addListener("filter_year", "change", function(e) {
				E.preventDefault(e);
				G.selYear = D.get("filter_year").value;
				oDt.refresh();
			});

			E.addListener("filter_org", "change", function(e) {
				E.preventDefault(e);
				G.idOrg = D.get("filter_org").value;
				oDt.refresh();
			});

			E.addListener("filter_set", "click", function(e) {
				E.preventDefault(e);
				G.filterText = D.get("filter_text").value;
				oDt.refresh();
			});

			E.addListener("filter_reset", "click", function(e) {
				E.preventDefault(e);
				D.get("filter_text").value = "";
				G.filterText = "";
				oDt.refresh();
			});
			
			//multi delete
			var multidel_links = YAHOO.util.Dom.getElementsByClassName('ico-wt-sprite subs_del');

			YAHOO.util.Event.addListener(multidel_links, "click", function(e) {
				var count_sel = oDtS.num_selected;

				if (count_sel > 0) {
					
					var message = G.oLangs.get('_DEL')+': '+count_sel+' '+G.oLangs.get('_GAPS');
					var oPostData = {id: 'delGap', name: 'gaps', value: oDtS.toString()};
					
					var oCallBack = function(o) 
									{
										if (o.deleted) {
											for (var i=0; i<o.deleted.length; i++)
												oDtS.remsel(o.deleted[i]);
										}
										oDt.refresh();
									}
									
					G.submitDialog("confirm", G.oLangs.get('_AREYOUSURE'), message, e, 'warnicon', oCallBack, this.href, oPostData);
					
				} else {
					G.submitDialog("alert", G.oLangs.get('_AREYOUSURE'), G.oLangs.get('_EMPTY_SELECTION'), e);
				}
			});
		
		});

	},
	
	createPanel: function() {
		
		var oPanel = null;
		var divPanel = document.getElementById('resizablepanel');

		divPanel.innerHTML = '<div class="hd"></div><div class="bd"></div><div class="ft"></div>'
		
		oPanel = 	new YAHOO.widget.Panel(divPanel, {
							draggable: "true",
							visible: false,
							width: document.body.clientWidth*0.6+'px',
							height: document.body.clientHeight*0.4+'px',
							constraintoviewport: true,
							fixedcenter: true,	
							zIndex: 99991
					});	
							
		oPanel.render(); 
		return oPanel;
	},
	
	initEvent: function() {
		var updateSelected = function() {
			Gap.setNumUserSelected(this.num_selected);
		};
		var ds = DataTableSelector_gap_table;
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
		var slist = YAHOO.util.Selector.query('select[id^=_dyn_field_selector_]');
		var blist = YAHOO.util.Selector.query('a[id^=_dyn_field_sort_]');
		var oDt = DataTable_gap_table;		 
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

			Gap.setDropDownRefreshEvent.call(slist[i]);
		}
		for (i=0; i<blist.length; i++) {
			Gap.setSortButtonRefreshEvent.call(blist[i]);
		}
	},

	selectAllFilter: function() {
		return "&filter_text=" + Gap.filterText +
		   "&id_org=" + Gap.idOrg +
		   "&year=" + Gap.selYear;
	},

	requestBuilder: function (oState, oSelf) {
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
				Gap.selectAllFilter();
		for (i=0; i<Gap.numVarFields; i++) {
			output += "&_dyn_field["+i+"]=" + YAHOO.util.Dom.get("_dyn_field_selector_"+i).value
		}
		return output;
	},
	
	statusFormatter: function(elLiner, oRecord, oColumn, oData) {
		var i, valid = false, list = Gap.statusList;
		for (i=0; i<list.length; i++) {
			if (list[i].value == oData) {
				elLiner.innerHTML = list[i].label;
				valid = true;
				break;
			}
		}
		if (!valid) elLiner.innerHTML = '&nbsp;';
	},
	
	cataFormatter: function(elLiner, oRecord, oColumn, oData) {
		var i, valid = false, list = Gap.cataList;
		for (i=0; i<list.length; i++) {
			if (list[i].value == oData) {
				elLiner.innerHTML = list[i].label;
				valid = true;
				break;
			}
		}
		if (!valid) elLiner.innerHTML = '&nbsp;';
	},
	
	aslinkFormatter: function(elLiner, oRecord, oColumn, oData) {
		if(oData) 
			elLiner.innerHTML = '<a style="cursor:pointer;">'+oData+'</a>';		
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
		
		var id = '_dyn_field_selector_'+index, sort_str = Gap.oLangs.get('_SORT');
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
		
		output += '</select>';

		output += '<a id="_dyn_field_sort_'+index+'" href="javascript:;">';
		output += '<img src="'+Gap.templatePath+'images/standard/sort.png" ';
		output += 'title="'+sort_str+'" alt="'+sort_str+'" />';
		output += '</a>';

		Gap.dynSelection[id] = selected;
		return output;
	},

	setDropDownRefreshEvent: function() {
		YAHOO.util.Event.addListener(this, "change", function() {
			DataTable_gap_table.refresh();
		});
	},

	setSortButtonRefreshEvent: function() {
		var oDt = DataTable_gap_table;
		YAHOO.util.Event.addListener(this, "click", function(e) {
			YAHOO.util.Event.preventDefault(e);

			var oColumn = oDt.getColumn(this);

			//load adjusted <select> into column label
			var index = this.id.replace('_dyn_field_sort_', '');
			var selected = YAHOO.util.Dom.get('_dyn_field_selector_'+index).value;
			oColumn.label = Gap.getDynLabelMarkup(index, selected);
			
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
	
	rqmtValidator: function(newVal, oldVal, oEditor) {	
		if(isNaN(newVal)) {
			return oldVal;
		} else {
			return Math.round(newVal);
		}
	},
	
	colSave: function(callback, newValue) {
		var oEditor = this;
		var oldValue = oEditor.value;
		var datatable = oEditor.getDataTable();  
		var record = oEditor.getRecord();
		var col = oEditor.getColumn().getKey();
        var id_gap = oEditor.getRecord().getData("id");
        
        
        switch(col) {
			case 'status':
				Gap.saveInline(id_gap, col, newValue, function(res){
					if (res.data) {
						
						datatable.updateCell(record, 'count_assn', (res.data['count_assn'] || ' '));
					}
					if(res.success) {
						callback(true, newValue);
					}
					else {
						callback();
						Gap.submitDialog('alert','Info', res.message, null, 'warnicon');
					}
				});
			break;
			case 'requirement':
			case 'id_catalogue':
				Gap.saveInline(id_gap, col, newValue, function(res){
					//Aggiornamenti del server (utile per di modifiche non ancora caricate)
					if (res.data) {
						
						datatable.updateCell(record, 'status', res.data['status']);
						datatable.updateCell(record, 'count_assn', res.data['count_assn']);
					}
					//Callback e avvisi
					if(res.success) {
						callback(true, newValue);
					}
					else {
						callback();
						Gap.submitDialog('alert','Info', res.message, null, 'warnicon');
					}	
				});
			break;
		} 
		
	},
	
	saveInline: function(id_gap, col, val, callback) {

		var oCallback = {
			success: function(o) {
				var res = YAHOO.lang.JSON.parse(o.responseText);
				
				callback(res);
			},
			failure: function() {}
		};
		
		var url = "ajax.adm_server.php?r="+Gap.baseLink+"/edit";
		var post = "id_gap=" + id_gap
					+"&col=" + col
					+"&new_value=" + val;
					
		YAHOO.util.Connect.asyncRequest("POST", url, oCallback, post);
	},
	
	submitDialog:  function (mode, headerText, messageText, evt = null, icon = 'infoicon', oCallBack = null, action = false, oPostData = null) {
        //mode = "confirm" or "alert"

		var fieldString = '';
		
        if (action && oPostData) {
			fieldString = '<input type="hidden" id="'+oPostData.id+'" name="'+oPostData.name+'" value = "'+oPostData.value+'" />';
        }

	    var oDialog = CreateDialog("gap_dialog", {
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
				            body: '<div id="gap_dialog_action"></div>'
					             +'<span class="yui-icon '+icon+'">&nbsp;</span>'
                                 +'<form method="POST" id="gap_dialog_form" action="'+action+'">'
					             + '    <p>'+messageText+'</p>'+fieldString
					             +'</form>',
				            callback: function(oResp){this.destroy(); if(oCallBack) oCallBack(oResp);}
				        });

		oDialog.call(this, evt);

    },
	
	linkClick: function(oArgs) {

		YAHOO.util.Event.preventDefault(oArgs.event);
		
		var column = this.getColumn(oArgs.target);
		
		if(column.key == 'count_assn'){
			Gap.openAssnPanel(this.getRecord(oArgs.target), column.label);
		}
		
	},
	
	getInfo: function(method, param, callback) {
		
			var url = "ajax.adm_server.php?r="+Gap.baseLink+"/"+method;
		
			YAHOO.util.Connect.asyncRequest("POST", url,
			{
				success: function (o) {
					var res = YAHOO.lang.JSON.parse(o.responseText);
					callback(res);
				},
				failure: null
			}, param);
	},
	
	openAssnPanel: function(oRecord, title) {

		var idGap = oRecord.getData("id");
		var userName = oRecord.getData("user_fullname");
		var dateIns = oRecord.getData("date_ins");
		var cataName = oRecord.getData("cata_name");
		var param = '&id_gap=' + idGap;
		var panel = this.oPanel;

		var setBody = function(res) {
				if (res.success) {
					var tb = getTable(res.fields, res.data);
					panel.setBody(tb);
					
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
			
		panel.setHeader(title+' - '+userName);
		panel.setFooter(dateIns+', '+cataName);
		panel.setBody('&nbsp;');

		this.getInfo('getAssnInfo', param, setBody);
		
		panel.show();
	}
}
