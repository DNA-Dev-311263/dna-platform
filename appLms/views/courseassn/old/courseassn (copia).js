/* ======================================================================== \
 |   FORMA - The E-Learning Suite                                            |
 |                                                                           |
 |   Copyright (c) 2013 (Forma)                                              |
 |   http://www.formalms.org                                                 |
 |   License  http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt           |
 |   BKO                                                                     |
 |   from docebo 4.0.5 CE 2008-2012 (c) docebo                               |
 |   License http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt            |
 \ ======================================================================== */
var glob_serverUrl = "ajax.server.php?r=catalog/"; //ABR uso le funzioni del catalogo per l'iscrizione
var dialog;
var panel;

function initialize(undo_name) {

    var b_width = getWidthResolution();
    var b_height = getHeightResolution();
    var ratio = b_width/b_height;
    var pnlwidth;
    var pnlheigth;
    var div;
    
    // Container Feedback
    if (!document.getElementById('container-feedback')) {
        div = document.createElement('div');
        div.id = 'container-feedback';
        document.body.appendChild(div);
    }

	//pop-up di dialogo
	dialog = new YAHOO.widget.Dialog('pop_up_container', {
        width: "100%",
        fixedcenter: true,
        visible: true,
        dragdrop: true,
        modal: true,
        close: true,
        visible: false,
        constraintoviewport: true
      });
      
    //render del pop-up modale
    dialog.render(document.body);
    
    //aggiusto dimensioni del suo contenitore
	var divContainer = document.getElementById('pop_up_container_c');
	divContainer.style.width = "40%";
	divContainer.style.left = "25%";

    if (ratio < 1) {
        //schermo verticale
        pnlwidth = parseInt(b_width * 0.8);
        pnlheigth = parseInt(b_height * 0.8);
    
    }else{
        //schermo orizzontale
        pnlwidth = parseInt(b_width * 0.6);
        pnlheigth = parseInt(b_height * 0.7);
    }

	//pop-up informativo
	
    // Se il pannello non esiste nel DOM, lo creo dinamicamente
    if (!document.getElementById('resizablepanel')) {
        div = document.createElement('div');
        div.id = 'resizablepanel';
        div.innerHTML = `
            <div class="hd"></div>
            <div class="bd"></div>
            <div class="ft"></div>
        `;
        document.body.appendChild(div);
    }

	// Create a panel Instance, from the 'resizablepanel' DIV standard module markup
	panel = new YAHOO.widget.Panel("resizablepanel", {
	    draggable: true,
	    width: pnlwidth  + "px",
	    height: pnlheigth + "px",
	    //autofillheight: "body", 
	    visible: false,
	    constraintoviewport: true,
	    fixedcenter: true,
	    context: ["showbtn", "tl", "bl"]
	});

	// Create Resize instance, binding it to the 'resizablepanel' DIV 
	var resize = new YAHOO.util.Resize("resizablepanel", {
		handles: ["br"],
		autoRatio: false,
		minWidth: 300,
		minHeight: 100,
		status: false 
	});

	// Setup startResize handler, to constrain the resize width/height
	resize.on("startResize", function(args) {

		if (this.cfg.getProperty("constraintoviewport")) {
			var D = YAHOO.util.Dom;

			var clientRegion = D.getClientRegion();
			var elRegion = D.getRegion(this.element);

			resize.set("maxWidth", clientRegion.right - elRegion.left - YAHOO.widget.Overlay.VIEWPORT_OFFSET);
			resize.set("maxHeight", clientRegion.bottom - elRegion.top - YAHOO.widget.Overlay.VIEWPORT_OFFSET);
		} else {
			resize.set("maxWidth", null);
			resize.set("maxHeight", null);
		}

	}, panel, true);

	// Setup resize handler to update the Panel's 'height' configuration property 
	resize.on("resize", function(args) {
		var panelHeight = args.height;
		this.cfg.setProperty("height", panelHeight + "px");
	}, panel, true);
  
	//render del pop-up informativo
	panel.render();
}

function courseinfoPopUp(id_course, id_edition) {
	
	var serverUrl = "ajax.server.php?r=courseassn/"; 
	var course_info = '&id_course=' + id_course + '&id_edition=' + id_edition;

	
	YAHOO.util.Connect.asyncRequest("POST", serverUrl + 'courseEditionInfo&',
		{
			success: function (o) {

			  var res = YAHOO.lang.JSON.parse(o.responseText);
		
			  if (res.success) {
				  
				panel.setHeader(res.title);
				panel.setBody(res.body);
				
				if (res.footer) panel.setFooter('<div class="align-right" style="padding-right:15px;">' + res.footer + '</div>');
				
				else panel.setFooter('');

				panel.show();

			  }
			  else {
				  
			  }
			},
			failure: function () {

			}
		}, course_info);
		
}

function subscriptionPopUp(id_course, id_date, id_edition, selling) {
  
  var course_info = '&id_course=' + id_course + '&id_date=' + id_date + '&id_edition=' + id_edition + '&selling=' + selling;
  var _iframe = YAHOO.util.Selector.query('#overlay_iframe');
  
  console.log(_iframe);

  YAHOO.util.Connect.asyncRequest("POST", glob_serverUrl + 'subscribeInfo&',
      {
        success: function (o) {
          var res = YAHOO.lang.JSON.parse(o.responseText);
          if (res.success) {


            dialog.setHeader(res.title);
            dialog.setBody(res.body);
            
            if (res.footer) dialog.setFooter('<div class="align-right">' + res.footer + '</div>');
            else dialog.setFooter('');

            dialog.center();
            dialog.show();

            hideIframe();
          }
          else {

          }
        },
        failure: function () {

        }
      }, course_info);
}

function subscribeToCourse(id_course, id_date, id_edition, selling) {
  var course_info = '&id_course=' + id_course + '&id_date=' + id_date + '&id_edition=' + id_edition;
  var div_course = YAHOO.util.Dom.get('action_' + id_course);
  var div_feedback = YAHOO.util.Dom.get('container-feedback');
  var div_infolevel = YAHOO.util.Dom.get('infolevel_' + id_course);

//  var _iframe = YAHOO.util.Selector.query('#overlay_iframe');

//  YAHOO.util.Dom.setStyle(_iframe, 'display', 'inline');

  showIframe();

  if (selling == 0) {
    YAHOO.util.Connect.asyncRequest("POST", glob_serverUrl + 'subscribeToCourse&',
        {
          success: function (o) {
            var res = YAHOO.lang.JSON.parse(o.responseText);
            if (res.success) {
              if (res.new_status != '' && res.new_status_code == 'subscribed')
				//iscrizione corsi senza edizioni
                div_course.innerHTML = '<a href="index.php?modname=course&op=aula&idCourse=' + id_course + '">' + res.new_status +'</a>';
              else if (res.new_status != '')
				//iscrizione corsi con edizioni
                div_course.innerHTML = res.new_status;
              
              //ABR: aggiunta per info su box courseassn
              if (div_infolevel){
                div_infolevel.innerText = res.userlevel_subscrip_desc;
                div_infolevel.className = "course-box__owner course-box__owner--3";
              }
              
              //cambio argomenti del link informativo e button
              var id_ed = (id_date != 0 ? id_date : id_edition);
              $( "#link_info_" + id_course ).attr("onclick","courseinfoPopUp(" + id_course + ", " + id_ed  + ")");
              div_course.onclick = function() {courseinfoPopUp(id_course, id_ed);}; 
              div_course.style = "cursor: pointer;"
              
              
              //Messaggio di conferma con fadeout (il div è già presente sulla pagina)
              if (div_feedback) 
				div_feedback.innerHTML = res.message;
				$( "#" + div_feedback.id ).fadeOut( 2000, function() {});
              
              dialog.hide();
            }
            else {
              div_feedback.innerHTML = res.message;

              dialog.hide();
            }
          },
          failure: function () {

          }
        }, course_info);
  }
  else {
    YAHOO.util.Connect.asyncRequest("POST", glob_serverUrl + 'addToCart&',
        {
          success: function (o) {
            var res = YAHOO.lang.JSON.parse(o.responseText);
            if (res.success) {
              if (res.new_status != '')
                div_course.innerHTML = res.new_status;

              //div_feedback.innerHTML = res.message;

              var cart_element = YAHOO.util.Dom.get('cart_element');

              if (cart_element)
                cart_element.innerHTML = res.cart_element;

              dialog.hide();
              setTimeout(function () {
                location.reload();
              }, 100);

              if (res.num_element > 0) {
                var cart = YAHOO.util.Dom.get('cart_box');
                cart.style.display = 'inline';
                cart.focus();

                var cart_overlay = new YAHOO.widget.Overlay('cart_overlay', {
                  context: ["cart_action", 'tr', 'br', ["beforeShow", "windowResize"]],
                  visible: true
                });

                cart_overlay.setHeader('');
                cart_overlay.setBody(res.cart_message);
                cart_overlay.setFooter('');

                cart_overlay.render(document.body);
                cart_overlay.show();

                var cart_overlay_div = YAHOO.util.Dom.get('cart_overlay');
                cart_overlay_div.style.backgroundColor = '#ffffcc';
                cart_overlay_div.style.padding = '6px 12px 6px 12px';
              }
            }
            else {
              div_feedback.innerHTML = res.message;

              dialog.hide();

            }

          },
          failure: function () {

          }
        }, course_info);
  }
}


    function chooseEdition(id_course) {

        var posting = $.get(
            'ajax.server.php',
            {
                r: 'catalog/chooseEdition',
                id_course: id_course,
                type_course: null,
                id_catalogue:null,
                id_category:null
            }
        )
        posting.done(function (r) {
			alert(r);
            dialog.setBody(r);
            dialog.center();
            dialog.show();
        });
        posting.fail(function () {
            alert('call failed')
        })
    }

function courseSelection(id_course, selling) {
  var course_info = '&id_course=' + id_course + '&selling=' + selling;

  YAHOO.util.Connect.asyncRequest("POST", glob_serverUrl + 'chooseEdition&',
      {
        success: function (o) {
          var res = YAHOO.lang.JSON.parse(o.responseText);
          if (res.success) {
            dialog.setHeader(res.title);
            dialog.setBody(res.body);
            if (res.footer) dialog.setFooter('<div class="align-right">' + res.footer + '</div>');
            else dialog.setFooter('');

            dialog.center();
            dialog.show();
          }
          else {

          }
        },
        failure: function () {

        }
      }, course_info);
}


function hideDialog() {
  showIframe();
  dialog.hide();
}
function hidePanel() {
  panel.hide();
}

function toggler(divId) {
	//nasconde/visualizza il div passato in argomento (id)
	$("#" + divId).toggle();
}

function hideIframe() {
  var _iframe = YAHOO.util.Selector.query('#overlay_iframe');

  YAHOO.util.Dom.setStyle(_iframe, 'display', 'none');
}

function showIframe() {
  var _iframe = YAHOO.util.Selector.query('#overlay_iframe');


  YAHOO.util.Dom.setStyle(_iframe, 'display', 'inline');
}

function printElement(elId, pgTitle, classHidden) {
    //Stampa un elemento (es. div) in una nuova finestra

    var links = document.getElementsByTagName("link");
    var content = document.getElementById(elId).innerHTML;
    var htmlstr = '';
    var mywindow = window.open('', '', '');
    
    htmlstr += "<html><head>";
    
    //Fogli di stile
    for (var i = 0; i < links.length; i++) {
        if(links[i].rel == 'stylesheet') {
            htmlstr += '<link rel="stylesheet" type="text/css" href="' + links[i].href + '" media = "' + links[i].media + '"/>';
        }
    }

    //Stile della pagina (nascondimento elementi)
    htmlstr += "<style>";

    if(classHidden != ''){
        var classname = classHidden.split(',');
        for (var i = 0; i < classname.length; i++) {
            htmlstr += '.' + classname[i].trim() + '{visibility: hidden;}';
        }
    }

    htmlstr += "</style>";
    
    htmlstr += "</head><title>" + pgTitle + "</title>";
    htmlstr += '<body>' + content + '</body></html>';

    //Scrivo
    mywindow.document.write(htmlstr);
    
    //Chiudo l'output stream
    mywindow.document.close();
    
    //Stampo
    mywindow.print();

    return true;
}

function getWidthResolution() {
        //var w = window.screen.width * window.devicePixelRatio;
        return document.body.clientWidth;
}
function getHeightResolution() {
        //var h = window.screen.height * window.devicePixelRatio
        return document.body.clientHeight;
}

