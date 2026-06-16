<?php echo getTitleArea(Lang::t('_COURSE', 'course')); ?>
<div class="std_block">
<?php

    //Categories tree
    $languages = [
    '_ROOT' => $root_name,
    '_NEW_FOLDER_NAME' => Lang::t('_NEW_CATEGORY', 'course'),
    '_MOD' => Lang::t('_MOD', 'course'),
    '_AREYOUSURE' => Lang::t('_AREYOUSURE', 'standard'),
    '_NAME' => Lang::t('_NAME', 'standardt'),
    '_MOD' => Lang::t('_MOD', 'standard'),
    '_DEL' => Lang::t('_DEL', 'standard'),
    '_MOVE' => Lang::t('_MOVE', 'standard'),
    '_SAVE' => Lang::t('_SAVE', 'standard'),
    '_CONFIRM' => Lang::t('_CONFIRM', 'standard'),
    '_UNDO' => Lang::t('_UNDO', 'standard'),
    '_ADD' => Lang::t('_ADD', 'standard'),
    '_YES' => Lang::t('_YES', 'standard'),
    '_NO' => Lang::t('_NO', 'standard'),
    '_INHERIT' => Lang::t('_ORG_CHART_INHERIT', 'organization_chart'),
    '_NEW_FOLDER' => Lang::t('_NEW_FOLDER', 'organization_chart'),
    '_DEL' => Lang::t('_DEL', 'standard'),
    '_AJAX_FAILURE' => Lang::t('_CONNECTION_ERROR', 'standard'),
    ];

    //** CR : LR TABLE OF COURSE , RESPONSIVE **
    $modifica = $languages['_MOD'];
    $cancella = $languages['_DEL'];
    $nome = $languages['_NAME'];

     $info_course = '<style>
              @media
        only screen and (max-width: 870px),
        (min-device-width: 870px) and (max-device-width: 1024px)  {            

                    #yuievtautoid-0 td:nth-of-type(1):before { content: "' . Lang::t('_DIRECTORY_GROUPID', 'admin_directory') . '"; }
                    #yuievtautoid-0 td:nth-of-type(1):before { content: "' . Lang::t('_CODE', 'cart') . '"; }
                    #yuievtautoid-0 td:nth-of-type(2):before { content: "' . $nome . '"; }
                    #yuievtautoid-0 td:nth-of-type(3):before { content: "' . Lang::t('_TYPE', 'standard') . '"; }
                    #yuievtautoid-0 td:nth-of-type(4):before { content: "' . Lang::t('_STUDENTS', 'coursereport') . '"; }
                    #yuievtautoid-0 td:nth-of-type(5):before { content: "' . Lang::t('_WAITING', 'standard') . '"; }
                    #yuievtautoid-0 td:nth-of-type(6):before { content: "' . Lang::t('_INSCR', 'report') . '"; }
                    #yuievtautoid-0 td:nth-of-type(7):before { content: "' . Lang::t('_CLASSROOM_EDITION', 'course') . '"; }
                    #yuievtautoid-0 td:nth-of-type(8):before { content: "' . Lang::t('_CERTIFICATE_ASSIGN', 'certificate') . '"; }
                    #yuievtautoid-0 td:nth-of-type(9):before { content: "' . Lang::t('_MYCOMPETENCES', 'menu_over') . '"; }
                    #yuievtautoid-0 td:nth-of-type(10):before { content: "' . Lang::t('_ASSIGN_MENU', 'course') . '"; } 
                    #yuievtautoid-0 td:nth-of-type(11):before { content: "' . Lang::t('_MAKE_A_COPY', 'standard') . '"; } 
                    #yuievtautoid-0 td:nth-of-type(12):before { content: "' . $modifica . '"; } 
                    #yuievtautoid-0 td:nth-of-type(13):before { content: "' . $cancella . '"; } 
                    }        
                    </style>
                ';

     echo $info_course;
    //***********************

$_tree_params = [
    'id' => 'category_tree',
    'ajaxUrl' => 'ajax.adm_server.php?r=' . $base_link_course . '/gettreedata',
    'treeClass' => 'CourseFolderTree',
    'treeFile' => FormaLms\lib\Get::rel_path('lms') . '/admin/views/course/coursefoldertree.js',
    'languages' => $languages,
    'initialSelectedNode' => $initial_selected_node,
    'dragDrop' => true,
];

if ($permissions['add_category']) {
    $rel_title = Lang::t('_NEW_CATEGORY', 'course');
    $rel_action = '<a class="ico-wt-sprite subs_add" id="category_tree_add_folder_button" href="ajax.adm_server.php?r=adm/course/addfolder&id=' . $initial_selected_node . '" '
        . ' title="' . $rel_title . '"><span>' . $rel_title . '</span></a>';
    $_tree_params['rel_action'] = $rel_action;
    $_tree_params['addFolderButton'] = 'add_folder_button';
}

$this->widget('tree', $_tree_params);

echo '<div class="quick_search_form">'
        . '<div class="common_options">'
        . Form::getInputCheckbox('classroom', 'classroom', '1', ($filter['classroom'] ? true : false), '')
            . ' <label class="label_normal" for="classroom">' . Lang::t('_CLASSROOM', 'admin_directory') . '</label>'
            . '&nbsp;&nbsp;&nbsp;&nbsp;'
        . Form::getInputCheckbox('descendants', 'descendants', '1', ($filter['descendants'] ? true : false), '')
            . ' <label class="label_normal" for="descendants">' . Lang::t('_DIRECTORY_FILTER_FLATMODE', 'admin_directory') . '</label>'
            . '&nbsp;&nbsp;&nbsp;&nbsp;'
        . Form::getInputCheckbox('waiting', 'waiting', '1', ($filter['waiting'] ? true : false), '')
            . ' <label class="label_normal" for="waiting">' . Lang::t('_WAITING_USERS', 'organization_chart') . '</label>'
        . '</div>'
        . '<div>'
        . Form::openForm('course_filters', 'index.php?r=' . $base_link_course . '/show')
        . Form::getInputTextfield('search_t', 'text', 'text', $filter['text'], '', 255, '') //TO DO: value from SESSION
        . Form::getButton('c_filter_set', 'c_filter_set', Lang::t('_SEARCH', 'standard'), 'search_b')
        . Form::getButton('c_filter_reset', 'c_filter_reset', Lang::t('_RESET', 'standard'), 'reset_b')
        . Form::closeForm()
        . '</div>'
        . '</div>';

$columns_arr = [
    ['key' => 'code', 'label' => Lang::t('_CODE', 'course'), 'sortable' => true],
    ['key' => 'name', 'label' => Lang::t('_NAME', 'course'), 'sortable' => true],
    ['key' => 'type', 'label' => Lang::t('_TYPE', 'course'), 'className' => 'min-cell'],
    ['key' => 'students', 'label' => Lang::t('_STUDENTS', 'coursereport'), 'className' => 'img-cell1'],
];

if ($permissions['moderate']) {//if(checkPerm('moderate', true, 'course', 'lms'))
    $columns_arr[] = ['key' => 'wait', 'label' => Lang::t('_WAITING', 'course'), 'className' => 'img-cell1'];
}

if ($permissions['subscribe']) {//if(checkPerm('subscribe', true, 'course', 'lms'))
    $columns_arr[] = ['key' => 'user', 'label' => FormaLms\lib\Get::sprite('subs_users', Lang::t('_USER_STATUS_SUBS', 'course')), 'className' => 'img-cell1'];
}

if ($permissions['view']) {
    $columns_arr[] = ['key' => 'edition', 'label' => FormaLms\lib\Get::sprite('subs_date', Lang::t('_CLASSROOM_EDITION', 'course')), 'className' => 'img-cel1l'];
}

$perm_assign = checkPerm('assign', true, 'certificate', 'lms');
$perm_release = checkPerm('release', true, 'certificate', 'lms');

if ($perm_assign) {
    $columns_arr[] = ['key' => 'certificate', 'label' => FormaLms\lib\Get::sprite('subs_pdf', Lang::t('_CERTIFICATE_ASSIGN_STATUS', 'course')), 'className' => 'img-cell1'];
}

if ($permissions['view_cert'] && $perm_release) {
    $columns_arr[] = ['key' => 'certreleased', 'label' => FormaLms\lib\Get::sprite('subs_print', Lang::t('_CERTIFICATE_RELEASE', 'course')), 'className' => 'img-cell1'];
}

if ($permissions['mod']) {
    $columns_arr[] = ['key' => 'competences', 'label' => FormaLms\lib\Get::sprite('subs_competence', Lang::t('_COMPETENCES', 'course')), 'className' => 'img-cell1'];
    $columns_arr[] = ['key' => 'menu', 'label' => FormaLms\lib\Get::sprite('subs_menu', Lang::t('_ASSIGN_MENU', 'course')), 'className' => 'img-cell1'];
}

if ($permissions['add']) {
    $columns_arr[] = ['key' => 'dup', 'label' => FormaLms\lib\Get::sprite('subs_dup', Lang::t('_MAKE_A_COPY', 'course')), 'className' => 'img-cell1', 'formatter' => 'dup'];
}

if ($permissions['mod']) {
    $columns_arr[] = ['key' => 'mod', 'label' => FormaLms\lib\Get::sprite('subs_mod', Lang::t('_MOD', 'course')), 'className' => 'img-cell1'];
}

if ($permissions['del'] && !FormaLms\lib\Get::cfg('demo_mode')) {
    $columns_arr[] = ['key' => 'del', 'label' => FormaLms\lib\Get::sprite('subs_del', Lang::t('_DEL', 'course')), 'formatter' => 'doceboDelete', 'className' => 'img-cell1'];
}

$fields = ['id', 'code', 'name', 'type', 'type_id', 'students', 'wait', 'user', 'edition', 'certificate', 'certreleased', 'competences', 'menu', 'dup', 'mod', 'del'];

$event = Events::trigger('core.course.columns.listing', ['columns' => $columns_arr, 'fields' => $fields, 'permissions' => $permissions]);

$_table_params = [
    'id' => 'course_table',
    'ajaxUrl' => 'ajax.adm_server.php?r=' . $base_link_course . '/getcourselist',
    'rowsPerPage' => FormaLms\lib\Get::sett('visuItem', 25),
    'startIndex' => 0,
    'results' => FormaLms\lib\Get::sett('visuItem', 25),
    'sort' => 'name',
    'dir' => 'asc',
    'columns' => $event['columns'],
    'fields' => $event['fields'],
    'show' => 'table',
    'delDisplayField' => 'name',
    'generateRequest' => 'Courses.requestBuilder',
];

$_table_params['rel_actions'] = '';

if ($permissions['add']) {
    $_table_params['rel_actions'] .= '<a class="ico-wt-sprite subs_add" href="index.php?r=' . $base_link_course . '/newcourse"><span>' . Lang::t('_NEW_COURSE', 'standard') . '</span></a>';
}

if ($permissions['subscribe']) {
    $_table_params['rel_actions'] .= ' <a class="ico-wt-sprite subs_users" href="index.php?r=' . $base_link_subscription . '/multiplesubscription"><span>' . Lang::t('_MULTIPLE_SUBSCRIPTION', 'course') . '</span></a>'
        . ((int) $unsubscribe_requests > 0
            ? '<a class="ico-wt-sprite subs_users" href="index.php?r=' . $base_link_subscription . '/unsubscriberequests">'
                . '<span>' . Lang::t('_UNSUBSCRIBE_REQUESTS', 'course') . ' (' . (int) $unsubscribe_requests . ')</span></a>'
            : '')
        . ' <a class="ico-wt-sprite subs_users" href="#" onclick="FindSubscriptions.open(); return false;"><span>Trova Iscrizioni</span></a>';
}

$this->widget('table', $_table_params);

?>
<!-- TROVA ISCRIZIONI MODAL -->
<div id="find-sub-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9998;" onclick="FindSubscriptions.close()"></div>
<div id="find-sub-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:9999;width:720px;max-width:95vw;background:#fff;border-radius:10px;box-shadow:0 20px 60px rgba(0,0,0,0.35);overflow:hidden;">
  <div style="background:linear-gradient(135deg,#1a3a5c,#2e6da4);padding:18px 24px;display:flex;align-items:center;justify-content:space-between;">
    <div>
      <div style="color:#fff;font-size:16px;font-weight:700;">Trova Iscrizioni</div>
      <div style="color:rgba(255,255,255,0.65);font-size:12px;margin-top:2px;">Cerca un utente per visualizzare e gestire le sue iscrizioni</div>
    </div>
    <span onclick="FindSubscriptions.close()" style="color:rgba(255,255,255,0.7);font-size:24px;cursor:pointer;line-height:1;">&times;</span>
  </div>
  <div style="padding:20px 24px;">
    <div style="display:flex;gap:10px;margin-bottom:16px;">
      <input type="text" id="find-sub-query" placeholder="Cerca per username, nome o cognome..."
        style="flex:1;border:1.5px solid #c8d8e8;border-radius:6px;padding:9px 14px;font-size:13px;color:#333;outline:none;" />
      <button id="find-sub-btn" onclick="FindSubscriptions.search()"
        style="background:#2e6da4;color:#fff;border:none;border-radius:6px;padding:9px 20px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;">Cerca</button>
    </div>
    <div id="find-sub-info" style="font-size:12px;color:#888;margin-bottom:10px;min-height:18px;"></div>
    <div id="find-sub-results" style="max-height:360px;overflow-y:auto;"></div>
    <div style="margin-top:18px;display:flex;justify-content:flex-end;">
      <button onclick="FindSubscriptions.close()"
        style="background:#f0f2f5;border:1px solid #ddd;border-radius:6px;padding:9px 20px;font-size:13px;color:#555;cursor:pointer;">Chiudi</button>
    </div>
  </div>
</div>
<script type="text/javascript">
var FindSubscriptions = {
    baseLink: '<?php echo $base_link_subscription; ?>',

    escapeHtml: function (s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    },

    open: function () {
        document.getElementById('find-sub-overlay').style.display = 'block';
        document.getElementById('find-sub-modal').style.display   = 'block';
        setTimeout(function () { document.getElementById('find-sub-query').focus(); }, 50);
    },

    close: function () {
        document.getElementById('find-sub-overlay').style.display = 'none';
        document.getElementById('find-sub-modal').style.display   = 'none';
        document.getElementById('find-sub-results').innerHTML     = '';
        document.getElementById('find-sub-info').innerHTML        = '';
        document.getElementById('find-sub-query').value           = '';
    },

    search: function () {
        var q = document.getElementById('find-sub-query').value.trim();
        if (q.length < 2) {
            document.getElementById('find-sub-info').innerHTML =
                '<span style="color:#c00;">Inserisci almeno 2 caratteri.</span>';
            return;
        }
        document.getElementById('find-sub-info').innerHTML    = 'Ricerca in corso...';
        document.getElementById('find-sub-results').innerHTML = '';

        $.ajax({
            url:     'ajax.adm_server.php?r=' + FindSubscriptions.baseLink + '/searchusersubscriptions',
            method:  'GET',
            data:    { q: q },
            success: function (data) {
                try {
                    var res = (typeof data === 'string') ? JSON.parse(data) : data;
                    FindSubscriptions.renderResults(res);
                } catch (e) {
                    document.getElementById('find-sub-info').innerHTML =
                        '<span style="color:#c00;">Errore nel parsing della risposta.</span>';
                }
            },
            error: function () {
                document.getElementById('find-sub-info').innerHTML =
                    '<span style="color:#c00;">Errore di connessione.</span>';
            }
        });
    },

    renderResults: function (res) {
        var info    = document.getElementById('find-sub-info');
        var results = document.getElementById('find-sub-results');

        if (!res || !res.success) {
            info.innerHTML = '<span style="color:#c00;">' + FindSubscriptions.escapeHtml(res.message) + '</span>';
            return;
        }
        if (!res.rows || res.rows.length === 0) {
            info.innerHTML    = 'Nessuna iscrizione trovata.';
            results.innerHTML = '';
            return;
        }

        info.innerHTML = res.rows.length + ' iscrizione/i trovata/e'
            + (res.user ? ' per <strong style="color:#2e6da4;">' + FindSubscriptions.escapeHtml(res.user) + '</strong>' : '');

        var html = '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
            + '<thead><tr style="background:#f4f7fa;border-bottom:2px solid #dde6f0;">'
            + '<th style="text-align:left;padding:9px 12px;color:#4a6080;font-weight:600;font-size:12px;">CORSO</th>'
            + '<th style="text-align:left;padding:9px 12px;color:#4a6080;font-weight:600;font-size:12px;">STATO</th>'
            + '<th style="text-align:left;padding:9px 12px;color:#4a6080;font-weight:600;font-size:12px;">DATA ISCRIZIONE</th>'
            + '<th style="text-align:center;padding:9px 12px;color:#4a6080;font-weight:600;font-size:12px;">AZIONE</th>'
            + '</tr></thead><tbody>';

        for (var i = 0; i < res.rows.length; i++) {
            var row = res.rows[i];
            var bg  = (i % 2 === 0) ? '#fff' : '#fafcff';
            html += '<tr id="find-sub-row-' + row.id_user + '-' + row.id_course + '"'
                  + ' style="border-bottom:1px solid #edf2f7;background:' + bg + ';">'
                  + '<td style="padding:10px 12px;color:#1a2a3a;font-weight:500;">' + row.course_name + '</td>'
                  + '<td style="padding:10px 12px;">' + row.status_badge + '</td>'
                  + '<td style="padding:10px 12px;color:#555;">' + row.date_subscribed + '</td>'
                  + '<td style="padding:10px 12px;text-align:center;">'
                  + '<button style="background:#fff;border:1.5px solid #dc3545;color:#dc3545;border-radius:5px;'
                  + 'padding:4px 12px;font-size:12px;cursor:pointer;font-weight:500;"'
                  + ' data-uid="' + row.id_user + '" data-cid="' + row.id_course + '"'
                  + ' data-cname="' + FindSubscriptions.escapeHtml(row.course_name) + '"'
                  + ' onclick="FindSubscriptions.confirmDelete(+this.dataset.uid,+this.dataset.cid,this.dataset.cname)"'
                  + '>Elimina</button></td></tr>';
        }
        html += '</tbody></table>';
        results.innerHTML = html;
    },

    confirmDelete: function (id_user, id_course, course_name) {
        if (!confirm('Sei sicuro di voler cancellare l\'iscrizione a "' + course_name + '"?')) {
            return;
        }
        $.ajax({
            url:     'ajax.adm_server.php?r=' + FindSubscriptions.baseLink + '/deletesubscription',
            method:  'POST',
            data:    { id_user: id_user, id_course: id_course },
            success: function (data) {
                try { var res = (typeof data === 'string') ? JSON.parse(data) : data; } catch (e) { alert('Errore di connessione.'); return; }
                if (res && res.success) {
                    var row = document.getElementById('find-sub-row-' + id_user + '-' + id_course);
                    if (row) { row.parentNode.removeChild(row); }
                    document.getElementById('find-sub-info').innerHTML =
                        '<span style="color:#1e7e34;">&#10003; Iscrizione cancellata con successo.</span>';
                } else {
                    alert('Errore: ' + (res.message || 'Impossibile cancellare l\'iscrizione.'));
                }
            },
            error: function () {
                alert('Errore di connessione.');
            }
        });
    }
};

YAHOO.util.Event.onDOMReady(function () {
    var q = document.getElementById('find-sub-query');
    if (q) {
        q.addEventListener('keypress', function (e) {
            if (e.keyCode === 13) { FindSubscriptions.search(); }
        });
    }
});
</script>
</div>
<script type="text/javascript">
	YAHOO.util.Event.onDOMReady(function(){
		var classroom = YAHOO.util.Dom.get('classroom');
		var descendants = YAHOO.util.Dom.get('descendants');
		var waiting = YAHOO.util.Dom.get('waiting');
		var button_sub = YAHOO.util.Dom.get('c_filter_set');
		var button_res = YAHOO.util.Dom.get('c_filter_reset');
		var form = YAHOO.util.Dom.get('course_filters');

		YAHOO.util.Event.addListener(classroom, 'change', filterEvent);
		YAHOO.util.Event.addListener(descendants, 'change', filterEvent);
		YAHOO.util.Event.addListener(waiting, 'change', filterEvent);
		YAHOO.util.Event.addListener(button_sub, 'click', filterEvent);
		YAHOO.util.Event.addListener(button_res, 'click', resetEvent);
		YAHOO.util.Event.addListener(form, 'submit', filterEvent);
	});

	function filterEvent(e)
	{
		YAHOO.util.Event.preventDefault(e);

		var classroom = YAHOO.util.Dom.get('classroom');
		var descendants = YAHOO.util.Dom.get('descendants');
		var waiting = YAHOO.util.Dom.get('waiting');
		var text = YAHOO.util.Dom.get('text');

		var postdata =	'waiting=' + waiting.checked
						+ '&descendants=' + descendants.checked
						+ '&classroom=' + classroom.checked;

		if(text.value !== '')
			postdata += '&text=' + text.value;

		YAHOO.util.Connect.asyncRequest("POST", "ajax.adm_server.php?r=<?php echo $base_link_course; ?>/filterevent&", {
			success: function(o) {
				DataTable_course_table.refresh();
			},
			failure: function() {
				DataTable_course_table.refresh();
			}
		}, postdata);
	}

	function resetEvent(e)
	{
		var classroom = YAHOO.util.Dom.get('classroom');
		var descendants = YAHOO.util.Dom.get('descendants');
		var waiting = YAHOO.util.Dom.get('waiting');
		var text = YAHOO.util.Dom.get('text');

		text.value = '';
		waiting.checked = false;
		descendants.checked = false;
		classroom.checked = false;

		YAHOO.util.Connect.asyncRequest("POST", "ajax.adm_server.php?r=<?php echo $base_link_course; ?>/resetevent&", {
			success: function(o) {
				DataTable_course_table.refresh();
			},
			failure: function() {
				DataTable_course_table.refresh();
			}
		});
	}

var Courses = {

	selectedFolder: <?php echo (int) $initial_selected_node; ?>,
	selectedCourse: <?php echo (int) $idCourse; ?>,

	requestBuilder: function(oState, oSelf) {
		var sort, dir, startIndex, results;
		oState = oState || {pagination: null, sortedBy: null};
		startIndex = (oState.pagination) ? oState.pagination.recordOffset : 0;
		results = (oState.pagination) ? oState.pagination.rowsPerPage : null;
		sort = (oState.sortedBy) ? oState.sortedBy.key : oSelf.getColumnSet().keys[0].getKey();
		dir = (oState.sortedBy && oState.sortedBy.dir === YAHOO.widget.DataTable.CLASS_DESC) ? "desc" : "asc";
		var output = "&results=" + results
			+ "&startIndex=" + startIndex
			+ "&sort=" + sort
			+ "&dir=" + dir
			+	"&node_id=" + Courses.selectedFolder;

		if(Courses.selectedCourse > 0) {
			output += "&idCourse=" + Courses.selectedCourse
		}
		return output;
	}
}

</script>
