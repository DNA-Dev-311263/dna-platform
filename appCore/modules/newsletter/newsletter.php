<?php

/*
 * FORMA - The E-Learning Suite
 *
 * Copyright (c) 2013-2023 (Forma)
 * https://www.formalms.org
 * License https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 *
 * from docebo 4.0.5 CE 2008-2012 (c) docebo
 * License https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 */

defined('IN_FORMA') or exit('Direct access is forbidden.');

define('_ANY_LANG_CODE', '-any-');

if (!function_exists('aout')) {
    function aout($string)
    {
        echo $string;
        exit;
    }
}

/**
 * Returns [ ['id' => idCourse, 'name' => ...], ... ] for courses the current admin can access.
 */
function nl_getAccessibleCourses()
{
    $courses = [];

    if (Docebo::user()->getUserLevelId() == ADMIN_GROUP_GODADMIN) {
        $qtxt = 'SELECT idCourse, name FROM ' . $GLOBALS['prefix_lms'] . '_course ORDER BY name';
    } else {
        require_once _base_ . '/lib/lib.preference.php';
        $adminManager = new AdminPreference();
        $admin_courses = $adminManager->getAdminCourseResolved(Docebo::user()->getIdSt());
        $course_ids = $admin_courses['course'];

        // Key 0 in $admin_courses['course'] means the admin has access to ALL courses
        if (isset($course_ids[0])) {
            $qtxt = 'SELECT idCourse, name FROM ' . $GLOBALS['prefix_lms'] . '_course ORDER BY name';
        } elseif (!empty($course_ids)) {
            $qtxt = 'SELECT idCourse, name FROM ' . $GLOBALS['prefix_lms'] . '_course'
                . ' WHERE idCourse IN (' . implode(',', array_map('intval', $course_ids)) . ')'
                . ' ORDER BY name';
        } else {
            $qtxt = false;
        }
    }

    if ($qtxt) {
        $q = sql_query($qtxt);
        while ($row = sql_fetch_assoc($q)) {
            $courses[] = ['id' => (int) $row['idCourse'], 'name' => $row['name']];
        }
    }

    return $courses;
}

/**
 * Returns [ ['id' => id_date, 'name' => ...], ... ] for the editions of a course.
 * Empty array if the course has no editions (course_edition = 0).
 */
function nl_getCourseEditions($id_course)
{
    $id_course = (int) $id_course;
    $editions = [];

    $qtxt = 'SELECT id_date, name, code FROM ' . $GLOBALS['prefix_lms'] . '_course_date'
        . ' WHERE id_course = ' . $id_course . ' ORDER BY name, code';
    $q = sql_query($qtxt);

    while ($row = sql_fetch_assoc($q)) {
        $label = ($row['name'] !== '') ? $row['name'] : $row['code'];
        $editions[] = ['id' => (int) $row['id_date'], 'name' => $label];
    }

    return $editions;
}

/**
 * Returns the idst list of users enrolled in a course (or in one specific edition),
 * filtered to the current admin's scope when not godadmin.
 */
function nl_getCourseRecipientsIdst($id_course, $id_date = 0)
{
    $id_course = (int) $id_course;
    $id_date = (int) $id_date;

    if ($id_date > 0) {
        $qtxt = 'SELECT id_user FROM ' . $GLOBALS['prefix_lms'] . '_course_date_user'
            . ' WHERE id_date = ' . $id_date;
        $field = 'id_user';
    } else {
        $qtxt = 'SELECT idUser FROM ' . $GLOBALS['prefix_lms'] . '_courseuser'
            . ' WHERE idCourse = ' . $id_course;
        $field = 'idUser';
    }

    if (Docebo::user()->getUserLevelId() != ADMIN_GROUP_GODADMIN) {
        require_once _base_ . '/lib/lib.preference.php';
        $adminManager = new AdminPreference();
        $qtxt .= ' AND ' . $adminManager->getAdminUsersQuery(Docebo::user()->getIdSt(), $field);
    }

    $idst = [];
    $q = sql_query($qtxt);
    while ($row = sql_fetch_row($q)) {
        $idst[] = (int) $row[0];
    }

    return $idst;
}

function nl_ajaxGetCourses()
{
    checkPerm('view');
    require_once _base_ . '/lib/lib.json.php';
    $json = new Services_JSON();

    aout($json->encode(nl_getAccessibleCourses()));
}

function nl_ajaxGetCourseEditions()
{
    checkPerm('view');
    require_once _base_ . '/lib/lib.json.php';
    $json = new Services_JSON();

    $id_course = FormaLms\lib\Get::req('id_course', DOTY_INT, 0);

    aout($json->encode(nl_getCourseEditions($id_course)));
}

function nl_ajaxGetCourseRecipientsCount()
{
    checkPerm('view');
    require_once _base_ . '/lib/lib.json.php';
    $json = new Services_JSON();

    $id_course = FormaLms\lib\Get::req('id_course', DOTY_INT, 0);
    $id_date = FormaLms\lib\Get::req('id_date', DOTY_INT, 0);
    $with_list = FormaLms\lib\Get::req('list', DOTY_INT, 0);

    $idst = nl_getCourseRecipientsIdst($id_course, $id_date);

    $response = ['count' => count($idst)];
    if ($with_list) {
        $response['idst'] = $idst;
    }

    aout($json->encode($response));
}

function nl_ajaxDeleteHistory()
{
    checkPerm('view');
    require_once _base_ . '/lib/lib.json.php';
    require_once _base_ . '/lib/lib.upload.php';
    $json = new Services_JSON();

    $id_send = FormaLms\lib\Get::req('id_send', DOTY_INT, 0);

    $qtxt = 'SELECT status, file, id_user_creator FROM ' . $GLOBALS['prefix_fw'] . '_newsletter'
        . ' WHERE id_send = ' . $id_send;
    $row = sql_fetch_assoc(sql_query($qtxt));

    if (!$row) {
        aout($json->encode(['success' => false]));

        return;
    }

    if (Docebo::user()->getUserLevelId() != ADMIN_GROUP_GODADMIN
        && (int) $row['id_user_creator'] !== Docebo::user()->getIdSt()) {
        aout($json->encode(['success' => false]));

        return;
    }

    if ($row['status'] === 'draft' && $row['file'] !== '') {
        $file_array = $json->decode($row['file']);
        $path = '/appCore/newsletter/';

        if (is_array($file_array)) {
            sl_open_fileoperations();
            foreach ($file_array as $file) {
                sl_unlink($path . $file);
            }
            sl_close_fileoperations();
        }
    }

    sql_query('DELETE FROM ' . $GLOBALS['prefix_fw'] . '_newsletter WHERE id_send = ' . $id_send);
    sql_query('DELETE FROM ' . $GLOBALS['prefix_fw'] . '_newsletter_sendto WHERE id_send = ' . $id_send);

    aout($json->encode(['success' => true]));
}

/**
 * Resolves the selection made in the inline Users/Groups/Org chart selector
 * (Section B.2 of the "Nuova comunicazione" page) into a flat, deduplicated
 * list of user idst, applying the same admin-scope and anonymous-user
 * filters used by the legacy selSendTo() flow.
 */
function nl_ajaxResolveRecipients()
{
    checkPerm('view');
    require_once _base_ . '/lib/lib.json.php';
    require_once _base_ . '/lib/lib.userselector.php';
    $json = new Services_JSON();

    $admin_users = null;
    if (Docebo::user()->getUserLevelId() != ADMIN_GROUP_GODADMIN) {
        require_once _base_ . '/lib/lib.preference.php';
        $adminManager = new AdminPreference();
        $admin_tree = $adminManager->getAdminTree(Docebo::user()->getIdST());
        $admin_users = Docebo::aclm()->getAllUsersFromSelection($admin_tree);
    }

    $mdir = new UserSelector();
    $arr_selection = $mdir->getSelection($_POST);

    $idst_arr = [];
    foreach ($arr_selection as $idstMember) {
        $arr = Docebo::aclm()->getGroupAllUser($idstMember);
        if (is_array($arr) && count($arr) > 0) {
            $idst_arr = array_merge($idst_arr, $arr);
        } else {
            $idst_arr[] = $idstMember;
        }
    }
    $idst_arr = array_unique(array_map('intval', $idst_arr));

    if ($admin_users !== null) {
        $idst_arr = array_intersect($idst_arr, $admin_users);
    }

    $anonymous_id = Docebo::user()->getAclManager()->getAnonymousId();
    $idst_arr = array_filter($idst_arr, function ($idst) use ($anonymous_id) {
        return $idst != $anonymous_id;
    });

    $idst_arr = array_values($idst_arr);

    aout($json->encode(['idst' => $idst_arr, 'count' => count($idst_arr)]));
}

function newsletter()
{
    //access control
    checkPerm('view');

    require_once _base_ . '/lib/lib.form.php';
    require_once _base_ . '/lib/lib.json.php';

    $out = &$GLOBALS['page'];
    $out->setWorkingZone('content');
    $lang = &DoceboLanguage::createInstance('admin_newsletter', 'framework');

    YuiLib::load();
    $out->add('<link rel="stylesheet" type="text/css" href="' . _deeppath_ . '/css/pandp-ui.css" />' . "\n", 'page_head');
    $out->add('<link rel="stylesheet" type="text/css" href="' . _deeppath_ . $GLOBALS['where_framework_relative'] . '/modules/newsletter/newsletter.css" />' . "\n", 'page_head');
    addJs($GLOBALS['where_framework_relative'] . '/modules/newsletter/', 'newsletter.js');

    $form = new Form();
    $json = new Services_JSON();

    $active_tab = (FormaLms\lib\Get::req('tab', DOTY_ALPHANUM, '') === 'history') ? 'history' : 'new';

    $title_html = getTitleArea($lang->def('_NEWSLETTER'), 'newsletter');
    if ($active_tab === 'history') {
        $out->add($title_html);
    } else {
        $out->add('<div class="nl-title-row">' . $title_html
            . '<a class="pui-history-link" href="index.php?modname=newsletter&amp;op=newsletter&amp;tab=history">' . $lang->def('_NL_HISTORY_LINK') . ' &rarr;</a>'
            . '</div>');
    }

    $p_size = intval(ini_get('post_max_size'));
    $u_size = intval(ini_get('upload_max_filesize'));
    $max_kb = ($p_size < $u_size ? $p_size : $u_size);
    $max = ' (Max. ' . $max_kb . ' Mb) ';

    $out->add('<script>'
        . 'var _DEL=\'' . $lang->def('_DEL') . '\';'
        . 'var _FILE_TO_SEND=\'' . $lang->def('_ATTACHMENT') . '\';'
        . 'var _MAX=\'' . $max . '\';'
        . 'var _NL_ALL_EDITIONS=\'' . $lang->def('_NL_ALL_EDITIONS') . '\';'
        . 'var _NL_COURSE_RECIPIENTS_COUNT=\'' . $lang->def('_NL_COURSE_RECIPIENTS_COUNT') . '\';'
        . 'var _NL_SELECTOR_RECIPIENTS_COUNT=\'' . $lang->def('_NL_SELECTOR_RECIPIENTS_COUNT') . '\';'
        . '</script>');

    // Default values (overridden below when resending a past communication)
    $sub_value = '';
    $msg_value = '';
    $fromemail_value = '';
    $sel_lang_value = _ANY_LANG_CODE;
    $send_type_email_checked = true;
    $send_type_sms_checked = false;
    $prefill_idst = '';

    $acl_manager = Docebo::user()->getAclManager();
    $user_info = $acl_manager->getUser(Docebo::user()->getIdSt(), false);
    $fromemail_value = $user_info[ACL_INFO_EMAIL];

    $resend_id = FormaLms\lib\Get::req('resend_id', DOTY_INT, 0);
    if ($resend_id > 0) {
        $info = get_send_info($resend_id);

        $sub_value = $info['sub'];
        $msg_value = $info['msg'];
        if (!empty($info['fromemail'])) {
            $fromemail_value = $info['fromemail'];
        }
        if (!empty($info['sel_lang'])) {
            $sel_lang_value = $info['sel_lang'];
        }
        if ($info['send_type'] === 'sms') {
            $send_type_email_checked = false;
            $send_type_sms_checked = true;
        }

        $qtxt = 'SELECT idst FROM ' . $GLOBALS['prefix_fw'] . "_newsletter_sendto WHERE id_send='" . (int) $resend_id . "'";
        $q = sql_query($qtxt);
        $resend_idst = [];
        while ($r = sql_fetch_row($q)) {
            $resend_idst[] = (int) $r[0];
        }
        $prefill_idst = implode(',', $resend_idst);
    }

    $out->add('<div class="pui-page nl-page">');

    if ($active_tab == 'history') {
        // ---- Storico ----
        $out->add('<div class="pui-back-link"><a href="index.php?modname=newsletter&amp;op=newsletter">&larr; ' . $lang->def('_NL_BACK_TO_COMPOSE') . '</a></div>');
        $out->add('<div class="pui-card">');
        newsletterHistory();
        $out->add('</div>');
    } else {
        // ---- Nuova comunicazione ----
        $out->add('<div class="pui-stepper">'
            . '<div class="pui-step pui-step--active"><div class="pui-step__num">1</div><span>' . $lang->def('_NL_STEP1_LABEL') . '</span></div>'
            . '<div class="pui-step__line"></div>'
            . '<div class="pui-step"><div class="pui-step__num">2</div><span>' . $lang->def('_NL_STEP2_LABEL') . '</span></div>'
            . '</div>');

        $out->add($form->openForm('newsletter_form', 'index.php?modname=newsletter&amp;op=initsend', '', false, 'multipart/form-data'));

        // Section A: content
        $out->add('<div class="pui-card">');
        $out->add('<div class="pui-section-label">' . $lang->def('_NL_CONTENT_SECTION') . '</div>');

        $out->add('<div class="pui-field"><label for="fromemail">' . $lang->def('_SENDER') . '</label>'
            . '<input type="text" class="pui-text-input nl-input--narrow" id="fromemail" name="fromemail" maxlength="255" value="' . htmlspecialchars($fromemail_value, ENT_QUOTES) . '" /></div>');

        $out->add('<div class="pui-field"><label for="sub">' . $lang->def('_SUBJECT') . '</label>'
            . '<input type="text" class="pui-text-input nl-input--narrow" id="sub" name="sub" maxlength="255" value="' . htmlspecialchars($sub_value, ENT_QUOTES) . '" /></div>');

        $out->add('<div class="pui-field"><label for="msg">' . $lang->def('_DESCRIPTION') . '</label>'
            . '<textarea class="pui-textarea" id="msg" name="msg">' . htmlspecialchars($msg_value) . '</textarea></div>');

        $out->add('<div class="pui-field"><label>' . $lang->def('_NL_ATTACHMENTS') . '</label>'
            . '<div id="file">'
            . $form->getHidden('file_number', 'file_number', '1')
            . '<div id="div_file_1">'
            . $form->getFilefield($lang->def('_ATTACHMENT'), 'file_1', 'file_1', '', '', '<a href="#" onclick="delFile(\'1\'); return false;"><span id="rem_span">' . $lang->def('_DEL') . '</span><a>')
            . '</div>'
            . '</div>'
            . '<br/><a href="#" onclick="addFile(); return false;"><span id="add_span">' . $lang->def('_MORE_ATTACHMENT') . '</span></a>'
            . '</div>');

        $lang_list = Docebo::langManager()->getAllLangCode();
        $lang_list = [_ANY_LANG_CODE => $lang->def('_ALL')] + $lang_list;

        $lang_options = '';
        foreach ($lang_list as $lang_key => $lang_label) {
            $selected_attr = ((string) $lang_key === (string) $sel_lang_value) ? ' selected="selected"' : '';
            $lang_options .= '<option value="' . htmlspecialchars($lang_key, ENT_QUOTES) . '"' . $selected_attr . '>' . htmlspecialchars($lang_label) . '</option>';
        }
        $out->add('<div class="pui-field"><label for="sel_lang">' . $lang->def('_LANGUAGE') . '</label>'
            . '<select class="pui-select" style="max-width:240px" id="sel_lang" name="sel_lang">' . $lang_options . '</select></div>');

        $out->add('<div class="pui-section-block">');
        $out->add('<div class="pui-section-label">' . $lang->def('_NL_SEND_TYPE') . '</div>');
        $out->add('<div class="pui-radio-group">'
            . '<label class="pui-radio-pill"><input type="radio" id="send_type_email" name="send_type" value="email"' . ($send_type_email_checked ? ' checked="checked"' : '') . ' /> ' . $lang->def('_EMAIL') . '</label>'
            . '<label class="pui-radio-pill"><input type="radio" id="send_type_sms" name="send_type" value="sms"' . ($send_type_sms_checked ? ' checked="checked"' : '') . ' /> ' . $lang->def('_SEND_SMS') . '</label>'
            . '</div>');
        $out->add('</div>'); // pui-section-block

        $out->add('</div>'); // pui-card (Section A)

        // Section B: recipients (course-based + additional Users/Groups/Org chart), unified
        $out->add('<div class="pui-card">');
        $out->add('<div class="pui-section-label">' . $lang->def('_NL_RECIPIENTS_SECTION') . '</div>');
        $out->add('<div class="pui-notice pui-notice--info">' . $lang->def('_NL_RECIPIENTS_SECTION_INFO') . '</div>');

        $out->add('<div class="pui-layout">');

        // Sidebar: recipients from course/edition
        $out->add('<div class="pui-sidebar"><div class="pui-sidebar-section">');
        $out->add('<div class="pui-section-label">' . $lang->def('_NL_RECIPIENTS_FROM_COURSE') . '</div>');
        $out->add('<div class="pui-field"><select class="pui-select" id="nl_course_select"><option value="">--</option></select></div>');
        $out->add('<div class="pui-field"><select class="pui-select" id="nl_edition_select" style="display:none;"></select></div>');
        $out->add('<div style="display:flex; gap:8px; flex-wrap:wrap;">'
            . '<button type="button" id="nl_add_course_recipients" class="pui-btn pui-btn--primary" style="padding:8px 16px; font-size:13px;">' . $lang->def('_NL_ADD_COURSE_RECIPIENTS') . '</button>'
            . '<button type="button" id="nl_remove_course_recipients" class="pui-btn pui-btn--ghost" style="padding:8px 16px; font-size:13px;">' . $lang->def('_NL_REMOVE_COURSE_RECIPIENTS') . '</button>'
            . '</div>');
        $out->add('<div id="nl_course_recipients_info" class="pui-count-info"></div>');
        $out->add($form->getHidden('course_recipients_idst', 'course_recipients_idst', ''));
        $out->add('</div></div>'); // .pui-sidebar-section, .pui-sidebar

        // Main: additional recipients via Users / Groups / Org chart selector
        $out->add('<div class="pui-main">');
        $out->add('<div class="pui-section-label">' . $lang->def('_NL_RECIPIENTS_ADDITIONAL') . '</div>');

        require_once _base_ . '/lib/lib.userselector.php';

        $out->add(Util::widget('userselector', [
            'id' => 'main_selector',
            'show_user_selector' => true,
            'show_group_selector' => true,
            'show_orgchart_selector' => true,
            'show_orgchart_simple_selector' => false,
            'show_fncrole_selector' => false,
            'use_suspended' => false,
            'initial_selection' => [],
        ], true));

        $out->add('<div style="margin-top:16px;"><button type="button" id="nl_add_selector_recipients" class="pui-btn pui-btn--primary" style="padding:8px 16px; font-size:13px;">' . $lang->def('_NL_SELECT_RECIPIENTS') . '</button></div>');
        $out->add('<div id="nl_selector_recipients_info" class="pui-count-info"></div>');
        $out->add($form->getHidden('selector_recipients_idst', 'selector_recipients_idst', ''));
        $out->add('</div>'); // .pui-main

        $out->add('</div>'); // .pui-layout

        // Shared accumulation: prefill (resend) + course-based + additional selector
        // (the combined total is shown on the summary page before sending)
        $out->add($form->getHidden('prefill_idst', 'prefill_idst', htmlspecialchars($prefill_idst, ENT_QUOTES)));
        $out->add($form->getHidden('recipients_idst', 'recipients_idst', htmlspecialchars($prefill_idst, ENT_QUOTES)));

        $out->add('</div>'); // pui-card (Section B)

        // Buttons
        $out->add('<div class="pui-btn-row">');
        $out->add('<a href="index.php?modname=newsletter&amp;op=newsletter" class="pui-btn pui-btn--ghost">' . $lang->def('_CANCEL') . '</a>');
        $out->add($form->getButton('send', 'send', $lang->def('_NEXT') . ' &rarr;', 'pui-btn pui-btn--primary'));
        $out->add('</div>');

        $out->add($form->closeForm());
    }

    $out->add('</div>'); // .pui-page
}

function send_newsletter($send_id)
{
    checkPerm('view');

    require_once _base_ . '/lib/lib.json.php';

    $json = new Services_JSON();

    $path = '/appCore/newsletter/';

    //access control
    $nl_sendpercycle = FormaLms\lib\Get::sett('nl_sendpercycle', 1);
    //-TP// funAdminAccess('OP');

    //@set_time_limit(60*15); // 15 minutes!

    $out = &$GLOBALS['page'];
    $out->setWorkingZone('content');
    $lang = &DoceboLanguage::createInstance('admin_newsletter', 'framework');

    $out->add(getTitleArea($lang->def('_NEWSLETTER'), 'newsletter'));

    $out->add("<div class=\"std_block\">\n");

    $info = get_send_info($send_id);

    $sel_groups = $info['sel_groups'];
    $sel_lang = $info['sel_lang'];
    $tot = $info['tot'];

    $sub = $info['sub'];
    $msg = $info['msg'];

    $msg = str_replace('{site_base_url}', getSiteBaseUrl(), $msg);

    $fromemail = $info['fromemail'];
    $sender = FormaLms\lib\Get::sett('sender_event');
    $file_array = $json->decode($info['file']);

    $attach = [];

    foreach ($file_array as $file) {
        $attach[] = _files_ . $path . $file;
    }

    $cycle = FormaLms\lib\Get::gReq('cycle', DOTY_INT, 0);

    if ($cycle === 0) {
        sql_query('UPDATE ' . $GLOBALS['prefix_fw'] . "_newsletter SET status='sending' WHERE id_send='" . (int) $send_id . "'");
    }

    // Items per cycle
    $ipc = $nl_sendpercycle;

    if (($cycle + 1) * $ipc < $tot) {
        $sendcomplete = 0;
    } else {
        $sendcomplete = 1;
    }

    $limit = $cycle * $ipc . ', ' . $ipc;
    $arr_st = getSendToIdst($send_id, $limit);
    $acl_manager = Docebo::user()->getAclManager();
    if ((!empty($sel_lang)) && ($sel_lang != _ANY_LANG_CODE)) {
        $user_info = $acl_manager->getUsersByLanguage($sel_lang, $arr_st);
    } else { // Send to all languages
        $user_info = $acl_manager->getUsers($arr_st);
    }

    $send_type = $info['send_type'];

    switch ($send_type) {
        case 'email':
                $tempemail = [];
                foreach ($user_info as $info) {
                    // Send the email: ------------------------------
                    $email = $info[ACL_INFO_EMAIL];

                    if ($email != '') {
                        $tempemail[] = $email;
                    }
                    // ----------------------------------------------
                }

                $mailer = FormaMailer::getInstance();
                
                // Mod. ABR
				if(count($attach))
					$mailer->SendMail($sender, $tempemail, $sub, $msg, $attach, 
					array(MAIL_REPLYTO => $fromemail, MAIL_SENDER_ACLNAME => false, MAIL_QUEUE_INFO => ['caller' => 'ContentNewsletter']));
				else
					$mailer->SendMail($sender, $tempemail, $sub, $msg, false, 
					array(MAIL_REPLYTO => $fromemail, MAIL_SENDER_ACLNAME => false, MAIL_QUEUE_INFO => ['caller' => 'ContentNewsletter']));

            break;

        case 'sms':
                // Collect users sms numbers

                require_once _adm_ . '/lib/lib.field.php';

                $acl_man = &Docebo::user()->getACLManager();
                $field_man = new FieldList();

                $arr_sms_recipients = [];
                $send_to_field = FormaLms\lib\Get::sett('sms_cell_num_field');
                $users_sms = $field_man->showFieldForUserArr($arr_st, [$send_to_field]);
                $users_info = $acl_man->getUsers($arr_st);
                foreach ($users_info as $user_dett) {
                    // recover media setting
                    $idst_user = $user_dett[ACL_INFO_IDST];

                    if ($users_sms[$idst_user][$send_to_field] != '') {
                        $arr_sms_recipients[$idst_user] = $users_sms[$idst_user][$send_to_field];
                    }
                }

                require_once _adm_ . '/lib/lib.sms.php';
                $sms_manager = new SmsManager();
                $sms_manager->sendSms($msg, $arr_sms_recipients);

            break;
    }

    if ($sendcomplete) {
        sql_query('UPDATE ' . $GLOBALS['prefix_fw'] . "_newsletter SET status='sent' WHERE id_send='" . (int) $send_id . "'");

		/*
		// ABR: Rimuovo il file con la funzione di invio (necessario se l'invio è gestito con la coda)
        require_once _base_ . '/lib/lib.upload.php';
        if (count($attach)) {
            foreach ($attach as $file) {
                sl_open_fileoperations();

                sl_unlink(str_replace(_files_, '', $file));

                sl_close_fileoperations();
            }
        }
        */
        $url = 'index.php?modname=newsletter&op=complete';
        Util::jump_to($url);
        
    } else {
        $url = 'index.php?modname=newsletter&op=pause&ipc=' . $ipc . '&cycle=' . ($cycle + 1) . '&id_send=' . $send_id;
        Util::jump_to($url);
    }

    $out->add("</div><br />\n");

    $out->add("<form action=\"index.php?modname=newsletter&amp;op=newsletter\" method=\"post\">\n");
    $out->add("<div class=\"std_block\">\n"
        . '<input type="hidden" id="authentic_request_newsletter" name="authentic_request" value="' . Util::getSignature() . '" />');
    $out->add('<input class="button" type="submit" value="' . $lang->def('_BACK') . "\" />\n");
    $out->add("</div>\n");
    $out->add("</form>\n");
}

function getSendToIdst($id_send, $limit)
{
    checkPerm('view');

    $res = [];

    $qtxt = 'SELECT idst FROM ' . $GLOBALS['prefix_fw'] . "_newsletter_sendto WHERE id_send='" . (int) $id_send . "' LIMIT " . $limit;
    $q = sql_query($qtxt);

    if (($q) && (sql_num_rows($q) > 0)) {
        while ($row = sql_fetch_array($q)) {
            $res[] = $row['idst'];
        }
    }

    return $res;
}

function nl_pause()
{
    checkPerm('view');

    $delay = FormaLms\lib\Get::sett('nl_sendpause', 20);

    $out = &$GLOBALS['page'];
    $out->setWorkingZone('content');
    $lang = &DoceboLanguage::createInstance('admin_newsletter', 'framework');

    $out->add(getTitleArea($lang->def('_NEWSLETTER'), 'newsletter'));

    $out->add("<div class=\"std_block\">\n");

    $cycle = (int) $_GET['cycle'];
    $ipc = (int) $_GET['ipc'];
    $id_send = (int) $_GET['id_send'];

    $out->add('<br />' . $lang->def('_SEND') . ': ' . ($cycle * $ipc) . ' - ' . ($cycle * $ipc + $ipc) . "<br />\n");

    $out->add('<br /><br />...' . $delay . ' ' . $lang->def('_SEC_OF_PAUSE') . "...\n");
    //Non chiudere la pagina finch&eacute; non compare la scritta \"Operazione completata\"
    $out->add('<br />' . $lang->def('_LOADING') . "<br /><br />\n");

    $out->add("</div>\n");

    $url = 'index.php?modname=newsletter&amp;op=send&amp;cycle=' . $cycle . '&amp;id_send=' . $id_send;
    $out->add('<meta http-equiv="refresh" content="' . $delay . ';url=' . $url . "\">\n", 'page_head');
}

function nl_sendcomplete()
{
    checkPerm('view');

    //-TP// funAdminAccess('OP');

    $out = &$GLOBALS['page'];
    $out->setWorkingZone('content');
    $lang = &DoceboLanguage::createInstance('admin_newsletter', 'framework');

    $out->add(getTitleArea($lang->def('_NEWSLETTER'), 'newsletter'));

    $out->add("<div class=\"std_block\">\n");

    $out->add('<br /><b>' . $lang->def('_OPERATION_SUCCESSFUL') . "</b><br /><br />\n");

    $out->add("</div><br />\n");

    $out->add("<form action=\"index.php?modname=newsletter&amp;op=newsletter\" method=\"post\">\n");
    $out->add("<div class=\"std_block\">\n"
        . '<input type="hidden" id="authentic_request_newsletter" name="authentic_request" value="' . Util::getSignature() . '" />');
    $out->add('<input class="button" type="submit" value="' . $lang->def('_BACK') . "\" />\n");
    $out->add("</div>\n");
    $out->add("</form>\n");
}

function init_send()
{
    checkPerm('view');

    require_once _base_ . '/lib/lib.upload.php';
    require_once _base_ . '/lib/lib.json.php';

    $json = new Services_JSON();

    $savefile = '';
    $max_file = FormaLms\lib\Get::req('file_number', DOTY_INT, 0);

    $savefile = [];
    for ($i = 1; $i <= $max_file; ++$i) {
		
		// Mod. ABR
		if(isset($_FILES['file_'.$i]) && $_FILES['file_'.$i]['error'] == 0)
		{
			$path = '/appCore/newsletter/';
			
			//ABR: Controllo se esiste già un file con lo stesso nome in attesa di essere inviato.
			$cnt = 0;
			$fname = &$_FILES['file_'.$i]['name'];
			
			while (sl_file_exists($path.$fname)) {
				$cnt++;
				$point = strrpos($fname, ".");
				$suffix =  "(".$cnt.")";
				
				if($point)
					$fname = substr_replace($fname, $suffix .".", $point, 1);
				else
					$fname .= $suffix;
			}
			
			$savefile[] = $fname;
			
			sl_open_fileoperations();

			sl_upload($_FILES['file_'.$i]['tmp_name'], $path.$_FILES['file_'.$i]['name']);
			
			sl_close_fileoperations();
		}
    }

    $lang_list = Docebo::langManager()->getAllLangCode();

    $sel_lang = importVar('sel_lang');
    if ($sel_lang > 0) {
        $lang_selected = $lang_list[$sel_lang];
    } elseif ($sel_lang === 0) { // Default language
        $lang_selected = getLanguage();
    } else {
        $lang_selected = $sel_lang;
    }

    $translate_table = getTranslateTable();

    $sub = translateChr($_POST['sub'], $translate_table, true);
    $msg = translateChr($_POST['msg'], $translate_table, true);
    $fromemail = $_POST['fromemail'];

    $send_type = $_POST['send_type'];

    $qtxt = 'INSERT INTO ' . $GLOBALS['prefix_fw'] . '_newsletter (sub, msg, fromemail, id_user_creator, language, send_type, status, stime, file) ';
    $qtxt .= "VALUES ('" . $sub . "', '" . $msg . "', '" . $fromemail . "', '" . (int) Docebo::user()->getIdSt() . "', '" . $lang_selected . "', '" . $send_type . "', 'draft', NOW(), '" . str_replace("'", "\'", $json->encode($savefile)) . "')";
    $q = sql_query($qtxt); //echo sql_error();

    $qtxt = 'SELECT LAST_INSERT_ID() as last_id FROM ' . $GLOBALS['prefix_fw'] . '_newsletter';
    $q = sql_query($qtxt);

    $row = sql_fetch_array($q);
    $last_id = $row['last_id'];

    $qtxt = 'UPDATE ' . $GLOBALS['prefix_fw'] . "_newsletter SET id_send='" . $last_id . "' WHERE id='$last_id'";
    $q = sql_query($qtxt);

    // Merge recipients accumulated on the "Nuova comunicazione" page (course-based
    // recipients, additional Users/Groups/Org chart selections and, on resend,
    // the prefilled recipients of the original communication), applying the
    // language filter selected for this send.
    $course_recipients_idst = FormaLms\lib\Get::pReq('course_recipients_idst', DOTY_MIXED, '');
    $course_count = count(array_filter(explode(',', $course_recipients_idst), 'is_numeric'));

    $selector_recipients_idst = FormaLms\lib\Get::pReq('selector_recipients_idst', DOTY_MIXED, '');
    $selector_count = count(array_filter(explode(',', $selector_recipients_idst), 'is_numeric'));

    $recipients_idst = FormaLms\lib\Get::pReq('recipients_idst', DOTY_MIXED, '');
    $pre_filter_count = 0;
    if ($recipients_idst !== '') {
        $recipients_idst_arr = array_map('intval', array_filter(explode(',', $recipients_idst), 'is_numeric'));
        $pre_filter_count = count($recipients_idst_arr);

        if (!empty($recipients_idst_arr)) {
            if ($lang_selected != _ANY_LANG_CODE) {
                $recipients_idst_arr = Docebo::aclm()->getUsersIdstByLanguage($lang_selected, $recipients_idst_arr);
            }

            foreach ($recipients_idst_arr as $idst_to_add) {
                $qtxt = 'INSERT IGNORE INTO ' . $GLOBALS['prefix_fw'] . '_newsletter_sendto (id_send, idst, stime) ';
                $qtxt .= "VALUES ('" . $last_id . "', '" . (int) $idst_to_add . "', NOW())";
                sql_query($qtxt);
            }
        }
    }

    $qtxt = 'SELECT COUNT(*) FROM ' . $GLOBALS['prefix_fw'] . "_newsletter_sendto WHERE id_send='" . $last_id . "'";
    list($tot) = sql_fetch_row(sql_query($qtxt));

    $qtxt = 'UPDATE ' . $GLOBALS['prefix_fw'] . "_newsletter SET tot='" . $tot . "' WHERE id='$last_id'";
    sql_query($qtxt);

    $lang_filter = ($lang_selected != _ANY_LANG_CODE) ? $lang_selected : '';

    $url = 'index.php?modname=newsletter&amp;op=summary'
        . '&amp;tot=' . $tot
        . '&amp;id_send=' . $last_id
        . '&amp;course_count=' . $course_count
        . '&amp;selector_count=' . $selector_count
        . '&amp;pre_filter_count=' . $pre_filter_count
        . '&amp;lang_filter=' . urlencode($lang_filter);
    Util::jump_to(str_replace('&amp;', '&', $url));
}

function get_send_info($send_id)
{
    $sel_lang = '';
    $send_type = 'email';
    $sel_groups = [];
    $res = [];

    $qtxt = 'SELECT * FROM ' . $GLOBALS['prefix_fw'] . "_newsletter WHERE id='" . $send_id . "'";
    $q = sql_query($qtxt); //echo $qtxt;

    if (($q) && (sql_num_rows($q) > 0)) {
        while ($row = sql_fetch_assoc($q)) {
            if ($sel_lang == '') {
                $sel_lang = $row['language'];
            }

            $tot = (int) $row['tot'];
            $sub = $row['sub'];
            $msg = $row['msg'];
            $fromemail = $row['fromemail'];
            if ($row['send_type'] != '') {
                $send_type = $row['send_type'];
            }
            $file = $row['file'];
        }
    }

    $res['sel_lang'] = $sel_lang;
    $res['sel_groups'] = $sel_groups;
    $res['tot'] = $tot;
    $res['sub'] = $sub;
    $res['msg'] = $msg;
    $res['fromemail'] = $fromemail;
    $res['send_type'] = $send_type;
    $res['file'] = $file;

    return $res;
}

function selSendTo()
{
    checkPerm('view');

    if ((isset($_GET['id_send'])) && ($_GET['id_send'] > 0)) {
        $id_send = $_GET['id_send'];
    } else {
        exit('Newsletter setup error.');
    }

    require_once _base_ . '/lib/lib.userselector.php';
    $mdir = new UserSelector();
    if (defined('IN_LMS')) {
        $mdir->learning_filter = 'course';
        $mdir->show_fncrole_selector = false;
    }

    if (Docebo::user()->getUserLevelId() != ADMIN_GROUP_GODADMIN) {
        require_once _base_ . '/lib/lib.preference.php';
        $adminManager = new AdminPreference();
        $admin_tree = $adminManager->getAdminTree(Docebo::user()->getIdST());
        $admin_users = Docebo::aclm()->getAllUsersFromSelection($admin_tree);

        $mdir->setUserFilter('user', $admin_users);
        $mdir->setUserFilter('group', $admin_tree);
    }

    $out = &$GLOBALS['page'];
    $out->setWorkingZone('content');
    $lang = &DoceboLanguage::createInstance('admin_newsletter', 'framework');

    $back_url = 'index.php?modname=newsletter&amp;op=selsendto&amp;id_send=' . $id_send;

    if (isset($_POST['okselector'])) {
        $arr_selection = $mdir->getSelection($_POST);

        $send_to_idst = [];

        foreach ($arr_selection as $idstMember) {
            $arr = Docebo::aclm()->getGroupAllUser($idstMember);
            if ((is_array($arr)) && (count($arr) > 0)) {
                $send_to_idst = array_merge($arr, $send_to_idst);
                $send_to_idst = array_unique($send_to_idst);
            } else {
                $send_to_idst[] = $idstMember;
            }

            if (Docebo::user()->getUserLevelId() != ADMIN_GROUP_GODADMIN) {
                $send_to_idst = array_intersect($send_to_idst, $admin_users);
            }
        }

        foreach ($send_to_idst as $key => $val) {
            $qtxt = 'INSERT IGNORE INTO ' . $GLOBALS['prefix_fw'] . '_newsletter_sendto (id_send, idst, stime) ';
            $qtxt .= "VALUES ('" . (int) $id_send . "', '" . (int) $val . "', NOW())";
            $q = sql_query($qtxt);
        }

        // tot now reflects the de-duplicated recipients already stored for this
        // send (course-based recipients merged in init_send + selector recipients above).
        $qtxt = 'SELECT COUNT(*) FROM ' . $GLOBALS['prefix_fw'] . "_newsletter_sendto WHERE id_send='" . (int) $id_send . "'";
        list($tot) = sql_fetch_row(sql_query($qtxt));

        $qtxt = 'UPDATE ' . $GLOBALS['prefix_fw'] . "_newsletter SET tot='" . $tot . "' WHERE id='$id_send'";
        $q = sql_query($qtxt);

        $back_url = 'index.php?modname=newsletter&amp;op=summary&amp;tot=' . $tot . '&amp;id_send=' . $id_send;
        Util::jump_to(str_replace('&amp;', '&', $back_url));
    } elseif (isset($_POST['cancelselector'])) {
        $info = get_send_info($id_send);

        $file = $info['file'];

        $path = '/appCore/newsletter/';

        require_once _base_ . '/lib/lib.upload.php';
        if ($file != '') {
            sl_open_fileoperations();

            sl_unlink($path . $file);

            sl_close_fileoperations();
        }

        Util::jump_to('index.php?modname=newsletter&op=newsletter');
    } else {
        if (isset($_GET['prefill_idst']) && $_GET['prefill_idst'] !== '') {
            $prefill_idst = array_map('intval', array_filter(explode(',', $_GET['prefill_idst']), 'is_numeric'));
            $mdir->resetSelection($prefill_idst);
        } elseif (isset($_GET['load'])) {
            $mdir->resetSelection([]);
        }

        $url = 'index.php?modname=newsletter&amp;op=selsendto&amp;id_send=' . $id_send . '&amp;stayon=1';
        $mdir->show_user_selector = true;
        $mdir->show_group_selector = true;
        $mdir->show_orgchart_selector = true;
        $mdir->show_orgchart_simple_selector = false;

        $acl_manager = &Docebo::user()->getAclManager();
        if (defined('IN_LMS')) {
            $id_course = (int) \FormaLms\lib\Session\SessionManager::getInstance()->getSession()->get('idCourse');
            $arr_idstGroup = $acl_manager->getGroupsIdstFromBasePath('/lms/course/' . $id_course . '/subscribed/');
            $mdir->setUserFilter('group', $arr_idstGroup);
            $mdir->setGroupFilter('path', '/lms/course/' . $id_course . '/group');
            $mdir->show_orgchart_selector = false;
        }

        // Exclude anonymous user!
        $mdir->setUserFilter('exclude', [$acl_manager->getAnonymousId()]);

        $mdir->loadSelector($url,
            [Lang::t('_NEWSLETTER', 'admin_newsletter'), Lang::t('_RECIPIENTS', 'admin_newsletter')], '', true);
    }
}

function newsletterSummary($id_send)
{
    checkPerm('view');

    require_once _base_ . '/lib/lib.form.php';

    $out = &$GLOBALS['page'];
    $out->setWorkingZone('content');
    $lang = &DoceboLanguage::createInstance('admin_newsletter', 'framework');

    YuiLib::load();
    $out->add('<link rel="stylesheet" type="text/css" href="' . _deeppath_ . '/css/pandp-ui.css" />' . "\n", 'page_head');

    $tot = (int) $_GET['tot'];
    $course_count = FormaLms\lib\Get::req('course_count', DOTY_INT, 0);
    $selector_count = FormaLms\lib\Get::req('selector_count', DOTY_INT, 0);
    $pre_filter_count = FormaLms\lib\Get::req('pre_filter_count', DOTY_INT, 0);
    $lang_filter = FormaLms\lib\Get::req('lang_filter', DOTY_ALPHANUM, '');

    $form = new Form();

    $out->add(getTitleArea($lang->def('_NEWSLETTER'), 'newsletter'));

    $out->add('<div class="pui-page">');

    $out->add('<div class="pui-stepper">'
        . '<div class="pui-step pui-step--done"><div class="pui-step__num">&#10003;</div><span>' . $lang->def('_NL_STEP1_LABEL') . '</span></div>'
        . '<div class="pui-step__line pui-step__line--done"></div>'
        . '<div class="pui-step pui-step--active"><div class="pui-step__num">2</div><span>' . $lang->def('_NL_STEP2_LABEL') . '</span></div>'
        . '</div>');

    $out->add('<div class="pui-card">');

    $out->add('<div class="pui-back-link"><a href="index.php?modname=newsletter&amp;op=newsletter">&larr; ' . $lang->def('_NL_BACK_EDIT_RECIPIENTS') . '</a></div>');

    $out->add('<div class="pui-section-label">' . $lang->def('_NL_SUMMARY_TITLE') . '</div>');

    $out->add('<div class="pui-stats-row">');
    $out->add('<div class="pui-stat-box pui-stat-box--success"><div class="pui-stat-box__value">' . $tot . '</div><div class="pui-stat-box__label">' . $lang->def('_NL_TOTAL_UNIQUE_RECIPIENTS') . '</div></div>');
    $out->add('<div class="pui-stat-box"><div class="pui-stat-box__value">' . $course_count . '</div><div class="pui-stat-box__label">' . $lang->def('_NL_COURSE_RECIPIENTS_COUNT') . '</div></div>');
    $out->add('<div class="pui-stat-box"><div class="pui-stat-box__value">' . $selector_count . '</div><div class="pui-stat-box__label">' . $lang->def('_NL_ADDITIONAL_RECIPIENTS_STAT') . '</div></div>');
    $out->add('</div>'); // pui-stats-row

    $notice_txt = str_replace('[tot]', $tot, $lang->def('_NL_UNIQUE_RECIPIENTS_NOTICE'));
    $out->add('<div class="pui-notice pui-notice--info">' . $notice_txt . '</div>');

    if ($lang_filter !== '' && $pre_filter_count > $tot) {
        $excluded = $pre_filter_count - $tot;
        $lang_filter_txt = str_replace(['[lang]', '[excluded]'], [htmlspecialchars($lang_filter), $excluded], $lang->def('_NL_LANG_FILTER_NOTICE'));
        $out->add('<div class="pui-notice pui-notice--info">' . $lang_filter_txt . '</div>');
    }

    $out->add($form->openForm('newsletter_form', 'index.php?modname=newsletter&amp;op=send&amp;id_send=' . $id_send, ''));
    $out->add('<div class="pui-btn-row pui-btn-row--space-between">');
    $out->add('<a href="index.php?modname=newsletter&amp;op=newsletter" class="pui-btn pui-btn--ghost">&larr; ' . $lang->def('_NL_BACK_EDIT_RECIPIENTS') . '</a>');
    $out->add($form->getButton('send', 'send', $lang->def('_SEND'), 'pui-btn pui-btn--primary'));
    $out->add('</div>');
    $out->add($form->closeForm());

    $out->add('</div>'); // pui-card
    $out->add('</div>'); // pui-page
}

function newsletterHistory()
{
    checkPerm('view');

    $out = &$GLOBALS['page'];
    $lang = &DoceboLanguage::createInstance('admin_newsletter', 'framework');

    $page_size = 20;
    $page_num = FormaLms\lib\Get::req('history_page', DOTY_INT, 0);
    if ($page_num < 0) {
        $page_num = 0;
    }

    $where = '1=1';
    if (Docebo::user()->getUserLevelId() != ADMIN_GROUP_GODADMIN) {
        $where .= ' AND id_user_creator = ' . (int) Docebo::user()->getIdSt();
    }

    $qtxt_count = 'SELECT COUNT(*) FROM ' . $GLOBALS['prefix_fw'] . "_newsletter WHERE $where";
    list($total) = sql_fetch_row(sql_query($qtxt_count));

    $qtxt = 'SELECT id_send, sub, send_type, tot, status, stime FROM ' . $GLOBALS['prefix_fw'] . '_newsletter'
        . " WHERE $where ORDER BY stime DESC LIMIT " . ($page_num * $page_size) . ", $page_size";
    $q = sql_query($qtxt);

    $status_labels = [
        'draft' => $lang->def('_NL_STATUS_DRAFT'),
        'sending' => $lang->def('_NL_STATUS_SENDING'),
        'sent' => $lang->def('_NL_STATUS_SENT'),
    ];

    $out->add('<div class="nl_history">');
    $out->add('<table class="nl_history_table">');
    $out->add('<tr><th>' . $lang->def('_DATE') . '</th><th>' . $lang->def('_SUBJECT') . '</th>'
        . '<th>' . $lang->def('_NL_SEND_TYPE') . '</th><th>' . $lang->def('_RECIPIENTS') . '</th>'
        . '<th>' . $lang->def('_STATUS') . '</th><th></th></tr>');

    if (sql_num_rows($q) == 0) {
        $out->add('<tr><td colspan="6">' . $lang->def('_NL_NO_HISTORY') . '</td></tr>');
    }

    while ($row = sql_fetch_assoc($q)) {
        $status_label = isset($status_labels[$row['status']]) ? $status_labels[$row['status']] : $row['status'];

        $out->add('<tr>');
        $out->add('<td>' . htmlspecialchars($row['stime']) . '</td>');
        $out->add('<td>' . htmlspecialchars($row['sub']) . '</td>');
        $out->add('<td>' . htmlspecialchars($row['send_type']) . '</td>');
        $out->add('<td>' . (int) $row['tot'] . '</td>');
        $out->add('<td>' . htmlspecialchars($status_label) . '</td>');
        $out->add('<td>'
            . '<a href="index.php?modname=newsletter&amp;op=newsletter&amp;resend_id=' . (int) $row['id_send'] . '">' . $lang->def('_NL_RESEND') . '</a>'
            . ' | <a href="#" class="nl_delete_history" data-id="' . (int) $row['id_send'] . '" data-confirm="' . htmlspecialchars($lang->def('_NL_CONFIRM_DELETE'), ENT_QUOTES) . '">' . $lang->def('_NL_DELETE') . '</a>'
            . '</td>');
        $out->add('</tr>');
    }

    $out->add('</table>');

    $total_pages = (int) ceil($total / $page_size);
    if ($total_pages > 1) {
        $out->add('<div class="nl_history_pagination">');
        for ($p = 0; $p < $total_pages; ++$p) {
            $class = ($p == $page_num) ? 'nl_page nl_page_current' : 'nl_page';
            $out->add('<a class="' . $class . '" href="index.php?modname=newsletter&amp;op=newsletter&amp;tab=history&amp;history_page=' . $p . '">' . ($p + 1) . '</a> ');
        }
        $out->add('</div>');
    }

    $out->add('</div>');
}

function add_to_array($arr, &$add_to)
{
    if (!is_array($add_to)) {
        $add_to = [];
    }

    if (!is_array($arr)) {
        return 0;
    }

    foreach ($arr as $key => $val) {
        if (!in_array($val, $add_to)) {
            $add_to[] = $val;
        }
    }
}

$op = importVar('op');
switch ($op) {
    case 'view':
    case 'newsletter':
            newsletter();

        break;

    case 'initsend':
            init_send();

        break;

    case 'selsendto':
            selSendTo();

        break;

    case 'summary':
            $id_send = (int) $_GET['id_send'];
            newsletterSummary($id_send);

        break;

    case 'send':
            $id_send = (int) $_GET['id_send'];
            send_newsletter($id_send);

        break;

    case 'pause':
            nl_pause();

        break;

    case 'ajax_get_courses':
            nl_ajaxGetCourses();

        break;

    case 'ajax_get_course_editions':
            nl_ajaxGetCourseEditions();

        break;

    case 'ajax_get_course_recipients_count':
            nl_ajaxGetCourseRecipientsCount();

        break;

    case 'ajax_delete_history':
            nl_ajaxDeleteHistory();

        break;

    case 'ajax_resolve_recipients':
            nl_ajaxResolveRecipients();

        break;

    case 'complete':
        nl_sendcomplete();
}
