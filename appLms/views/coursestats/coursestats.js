

var CourseStats = {
	/* Ripulita da ABR */
	oLanguage: {},
	oDialogCaller: {}, //ABR
	footerData: false,
	idCourse: 0,
	countLOs: 0,

	init: function(oConfig) {
		
		this.oLanguage = new LanguageManager(); //ABR
		
		if (!oConfig) oConfig = {};
		if (oConfig.idCourse) this.idCourse = oConfig.idCourse;
		if (oConfig.langs) this.oLanguage.set(oConfig.langs);
		if (oConfig.footerData) this.footerData = oConfig.footerData;
	},

	initEvent: function() { //this == DataTable_coursestats_table
		var C = CourseStats;
		if (C.countLOs > 0) {
			var i, id, td, tfoot = document.createElement("TFOOT");
			var tr1 = tfoot.appendChild(document.createElement('TR'));
			var tr2 = tfoot.appendChild(document.createElement('TR'));

			td = document.createElement('TD');
			td.id = 'footer_title_0';
			td.colSpan = 4;
			td.innerHTML = '<div class="yui-dt-liner"><b>'+C.oLanguage.get('_COMPLETED')+'</b></div>';
			tr1.appendChild(td);

			td = document.createElement('TD');
			td.id = 'footer_title_1';
			td.colSpan = 4;
			td.innerHTML = '<div class="yui-dt-liner"><b>'+C.oLanguage.get('_PERCENTAGE')+'</b></div>';
			tr2.appendChild(td);

			for (i=0; i<C.footerData.length; i++) {
				td = document.createElement('TD');
				td.id = 'lo_0_'+i;
				td.innerHTML = '<div class="yui-dt-liner">'+C.footerData[i].total+'</div>';
				tr1.appendChild(td);

				td = document.createElement('TD');
				td.id = 'lo_1_'+i;
				td.innerHTML = '<div class="yui-dt-liner">'+C.footerData[i].percent+'</div>';
				tr2.appendChild(td);
			}

			td = document.createElement('TD');
			td.id = 'footer_end_0';
			tr1.appendChild(td);

			td = document.createElement('TD');
			td.id = 'footer_end_1';
			tr2.appendChild(td);

			this.getTableEl().appendChild(tfoot);
		}

		this.doBeforeShowCellEditor = function(oEditor) {
			var key = oEditor.getColumn().getKey();
			switch (key) {
				case "status":  oEditor.value = oEditor.getRecord().getData("status_id"); break;
			}
			return true;
		};
	},

	fullnameFormatter: function(elLiner, oRecord, oColumn, oData) {
		elLiner.innerHTML = oRecord.getData("lastname") + " " + oRecord.getData("firstname");
	},

	useridFormatter: function(elLiner, oRecord, oColumn, oData) {
		var url = 'index.php?r=lms/coursestats/show_user&amp;id_user='+oRecord.getData("id");
		elLiner.innerHTML = '<a href="'+url+'" title="">'+oData+'</a>';
	},

	completedFormatter: function(elLiner, oRecord, oColumn, oData) {
		elLiner.innerHTML = oData+' / '+CourseStats.countLOs;
	},

	LOFormatter: function(elLiner, oRecord, oColumn, oData) {
		var content;
		if (!oData) {
			content = CourseStats.oLanguage.get('_LO_NOT_STARTED');
		} else {
			var id_lo = oColumn.getKey().replace('lo_', ''); //extract LO id by column key
			var url = 'index.php?r=lms/coursestats/show_user_object&amp;id_user='+oRecord.getData("id")+'&amp;id_lo='+id_lo;
			content = '<a href="'+url+'" title="">'+oData+'</a>';
		}
		elLiner.innerHTML = content;
	},


	rowDrawEvent: function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
		//ABR

		let C = CourseStats;
		let table = this;
		
		let colResetIdx = table.api().column('restrack_id:name').index();
		let colUserIdx = table.api().column('userid:name').index();      
        let img = $("img[src*='remove.png']");
        
        if(aData){ 
			
			let idst = aData[ colResetIdx ]; 		// id utente per azzeramento
			let userid = aData[ colUserIdx ]; 	// nume utente
			let linkReset = $('<a>');
			
			linkReset.attr('id',  'link_restrack_'+idst);
			linkReset.html('<img src="'+img.attr("src")+'" />');
			linkReset.css('cursor', 'pointer');
			linkReset.on('click', function(e){ C.resetAllTrack(e, idst, userid) });
			
				
			$('td:eq('+colResetIdx+')', nRow).addClass('text-center');  // classe bootstrap
			$('td:eq('+colResetIdx+')', nRow).empty().append( linkReset );
		
			
       } else {
            $('td:eq('+colResetIdx+')', nRow).html( '<p style="width:16px;"></p>' );
       }
	
	},
	
	postEdit: function(o) {
		//ABR
		
		let table = $('#coursestats').DataTable();
		if (o.success) {
			//Azioni al reset
		}
          
		this.destroy();
		table.ajax.reload();		
		
	},
	
    resetAllTrack: function(e, idst, userid) {
        //ABR
        
        let C = CourseStats;
        let url = 'ajax.adm_server.php?r=coursestats/resetall';

		let body = '<form method="POST" id="confirm_dialog_form" action="'+url+'">'
			        +'<p>'+C.oLanguage.get('_AREYOUSURE')+'<br>'+ C.oLanguage.get('_USER')+': <b>'+userid+'</b></p>'
			        +'<input type="hidden" name="id_user" value="'+idst+'" />'
			        +'</form>';
			        
        let params = {
			header: C.oLanguage.get('_RESET'),
			body: body
		};
        
        C.oDialogCaller['confirm_dialog'](e, params);
        
         // Per modifiche post creazione
         //let oDialog = getDialog('confirm_dialog');
         //oDialog.setHeader("Reset");
		 //oDialog.setBody( body );
    },
}
