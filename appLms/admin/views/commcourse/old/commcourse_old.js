
class formSubmission {

    constructor(form) {
        this._form = form;
    }

    set loadbar(value){ this._loadbar = value;}
    set base_link(value){ this._base_link = value;}
    set confirm_text(value){ this._confirm_text = value;}
    set confirm_hdtext(value){ this._confirm_hdtext = value;}
    set success_callback(value){ this._success_callback = value;}


    submitAction() {

        var t = this;
        var dataString = t._form.serialize();
        var action_url = "ajax.adm_server.php?r=" + t._base_link + "/sendCommunication";

        $.ajax({
           type: "POST",
           url: action_url,
           data: dataString,
           success: function(data)
            {
				if (t._success_callback)
					t._success_callback(data);
				else
					alert(data);
            },
           beforeSend: function(){t.layoutOnAction(true);},
           complete: function(){t.layoutOnAction(false);}

        });

    }
    
    
    infoAction() {
		
        var t = this;
        var dataString = t._form.serialize();
        var action_url = "ajax.adm_server.php?r=" + t._base_link + "/infoCommunication";
        
        var objRet = null;

		$.ajax({
				type: "POST",
				url: action_url,
				data: dataString,
				async: false, 		//la routine chiamante deve attendere la risposta
				success:
					function(data){
					   objRet = jQuery.parseJSON( data );   
					}
			   })

		return objRet
	}
	
	
	createDialog() {
		
		var t = this;
		var mySimpleDialog = new YAHOO.widget.SimpleDialog("dlg_commcourse", {
										width: "40em",
										effect:{
											effect: YAHOO.widget.ContainerEffect.FADE,
											duration: 0.25
										},
										close: true,
										fixedcenter: true,
										modal: true,
										visible: false,
										draggable: false
									});

		var handleYes = function() {
			
			t.submitAction();
			this.hide();
		};
		var handleNo = function() {
			this.hide();
		};
		
		var myButtons = [
			{ text: "Sì", handler: handleYes },
			{ text:"Annulla", handler: handleNo, isDefault:true}
		];
		 
		mySimpleDialog.cfg.queueProperty("buttons", myButtons);
		
		mySimpleDialog.render(document.body);
		
		return mySimpleDialog;
	}


    submitDialog() {
		
		var t = this;
		
		if (!t._dialog) t._dialog = t.createDialog();
		
		t._dialog.setHeader(t._confirm_hdtext);
		t._dialog.setBody(t._confirm_text);
		t._dialog.cfg.setProperty("icon", YAHOO.widget.SimpleDialog.ICON_WARN);
	
		t._dialog.show();
    }


    layoutOnAction(sending) {

        var formID = this._form.prop('id');
        var loadbarID = this._loadbar.prop('id');

	    $("#" + formID + " *").prop("disabled", sending);

	    if (sending) {
            $("#" + loadbarID).show();

	    } else {
            $("#" + loadbarID).hide();
	    }
	
    }
		
}


