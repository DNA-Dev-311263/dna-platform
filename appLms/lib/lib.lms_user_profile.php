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

require_once Forma::inc(_base_ . '/lib/lib.user_profile.php');

/**
 * @category library
 */
class LmsUserProfile extends UserProfile
{
    /**
     * class constructor.
     */
    public function LmsUserProfile($id_user, $edit_mode = false)
    {
        parent::__construct($id_user, $edit_mode);
    }

    // initialize functions ===========================================================

    /**
     * instance the viewer class of the profile.
     */
    public function initViewer($varname_action)
    {
        $this->_up_viewer = new LmsUserProfileViewer($this, $varname_action);
    }
}

// ========================================================================================================== //
// ========================================================================================================== //
// ========================================================================================================== //

/**
 * @category library
 */
class LmsUserProfileViewer extends UserProfileViewer
{
    /**
     * class constructor.
     */
    public function LmsUserProfileViewer(&$user_profile, $varname_action)
    {
        parent::UserProfileViewer($user_profile, $varname_action);
    }

    /**
     * print the title of the page.
     *
     * @param mixed  $text  the title of the area, or the array with zone path and name
     * @param string $image the image to load before the title
     *
     * @return string the html code for space open
     */
    public function getTitleArea()
    {
        return '';
    }

    /**
     * Print the head of the module space after the getTitle area.
     *
     * @return string the html code for space open
     */
    public function getHead()
    {
        return '<div class="up_main">' . "\n";
    }

    /**
     * Print the footer of the module space.
     *
     * @return string the html code for space close
     */
    public function getFooter()
    {
        return '</div>' . "\n";
    }

    /**
     * Front office user profile, redesigned with the pui-* design system.
     * Shows identity, personal data, contacts, signature and training history.
     */
    public function getUserInfo()
    {
        $viewer = $this->getViewer();

        $this->loadUserData($viewer);
        $user_field = $this->loadUserField();
        $user_contacts = $this->loadUserContact();

        $edit_mode = $this->_user_profile->editMode();

        $html = Util::get_css(FormaLms\lib\Get::rel_path('base') . '/css/pandp-ui.css', true, true);

        $html .= '<div class="pui-page">';
        $html .= '<h2 class="pui-profile-title">' . $this->_lang->def('_PROFILE') . '</h2>';
        $html .= '<div class="pui-layout">';

        // ---- sidebar: identity + actions ------------------------------------------------
        $html .= '<div class="pui-sidebar">';

        $html .= '<div class="pui-sidebar-section pui-profile-identity">';
        if ($this->_user_profile->useAvatar()) {
            $avatar_html = $this->getAvailablePhotoAvatar('medium');
            $html .= '<div class="pui-profile-avatar">'
                . ($edit_mode
                    ? '<a href="' . $this->_url_man->getUrl($this->_varname_action . '=uploadavatar') . '" title="' . $this->_lang->def('_MOD') . '">' . $avatar_html . '</a>'
                    : $avatar_html)
                . '</div>';
        }
        $html .= '<div class="pui-profile-name">' . htmlspecialchars($this->resolveUsername()) . '</div>';
        $html .= '<div class="pui-profile-username">' . htmlspecialchars($this->acl_man->relativeId($this->user_info[ACL_INFO_USERID])) . '</div>';

        if ($this->_user_profile->godMode()) {
            $lv_lang = &DoceboLanguage::createInstance('admin_directory', 'framework');
            $acl_man = &Docebo::user()->getAclManager();
            switch ($acl_man->getUserLevelId($this->_user_profile->getIdUser())) {
                case ADMIN_GROUP_GODADMIN: $user_level_string = $lv_lang->def('_DIRECTORY_' . ADMIN_GROUP_GODADMIN); break;
                case ADMIN_GROUP_ADMIN: $user_level_string = $lv_lang->def('_DIRECTORY_' . ADMIN_GROUP_ADMIN); break;
                case ADMIN_GROUP_USER: $user_level_string = $lv_lang->def('_DIRECTORY_' . ADMIN_GROUP_USER); break;
                default: $user_level_string = $acl_man->getUserLevelId($this->_user_profile->getIdUser());
            }
            $html .= '<div class="pui-profile-level"><span class="pui-badge pui-badge--info">' . htmlspecialchars($user_level_string) . '</span></div>';
        }
        $html .= '</div>';

        if ($edit_mode) {
            $html .= '<div class="pui-sidebar-section">';
            $html .= '<div class="pui-section-label">' . $this->_lang->def('_ACTIONS') . '</div>';
            $html .= '<a class="pui-btn pui-btn--primary" href="' . $this->_url_man->getUrl($this->_varname_action . '=mod_profile') . '" title="' . $this->_lang->def('_MOD') . '">'
                . $this->_lang->def('_MOD') . '</a>';
            if (!$this->_user_profile->godMode()) {
                $html .= '<a class="pui-btn pui-btn--ghost" href="' . $this->_url_man->getUrl($this->_varname_action . '=mod_password') . '" title="' . $this->_lang->def('_CHANGEPASSWORD') . '">'
                    . $this->_lang->def('_CHANGEPASSWORD') . '</a>';
            }
            $html .= '</div>';
        }

        $html .= '</div>'; // end pui-sidebar

        // ---- main: personal data, contacts, signature, training history -----------------
        $html .= '<div class="pui-main">';

        // Dati personali ----------------------------------------------------------------
        $html .= '<div class="pui-section-block">';
        $html .= '<div class="pui-section-label">' . $this->_lang->def('_PERSONAL_DATA') . '</div>';
        $html .= $this->getPuiFieldRow($this->_lang->def('_USERNAME'), htmlspecialchars($this->acl_man->relativeId($this->user_info[ACL_INFO_USERID])));
        $html .= $this->getPuiFieldRow($this->_lang->def('_LASTNAME'), $this->user_info[ACL_INFO_LASTNAME] !== false
            ? htmlspecialchars($this->user_info[ACL_INFO_LASTNAME])
            : $this->_lang->def('_HIDDEN'));
        $html .= $this->getPuiFieldRow($this->_lang->def('_FIRSTNAME'), $this->user_info[ACL_INFO_FIRSTNAME] !== false
            ? htmlspecialchars($this->user_info[ACL_INFO_FIRSTNAME])
            : $this->_lang->def('_HIDDEN'));
        if (!empty($user_field)) {
            foreach ($user_field as $value) {
                $html .= $this->getPuiFieldRow($value['name'], $value['value']);
            }
        }
        $html .= '</div>';

        // Contatti ------------------------------------------------------------------------
        $html .= '<div class="pui-section-block">';
        $html .= '<div class="pui-section-label">' . $this->_lang->def('_CONTACTS') . '</div>';
        if (!empty($user_contacts)) {
            foreach ($user_contacts as $value) {
                if ($value['head']) {
                    $GLOBALS['page']->add($value['head'], 'page_head');
                }
                $contact_value = $value['value'];
                if ($value['href']) {
                    $contact_value = '<a href="' . $value['href'] . '">' . $contact_value . '</a>';
                }
                $html .= $this->getPuiFieldRow($value['name'], $contact_value);
            }
        }
        $html .= $this->getPuiFieldRow($this->_lang->def('_EMAIL'), $this->user_info[ACL_INFO_EMAIL] !== false
            ? '<a href="mailto:' . $this->user_info[ACL_INFO_EMAIL] . '">' . htmlspecialchars($this->user_info[ACL_INFO_EMAIL]) . '</a>'
            : $this->_lang->def('_HIDDEN'));
        $html .= '</div>';

        // Storia formativa --------------------------------------------------------------
        $html .= '<div class="pui-section-block">';
        $html .= '<div class="pui-section-title">' . $this->_lang->def('_TRAINING_HISTORY') . '</div>';
        $html .= $this->getTrainingHistoryTable();
        $html .= '</div>';

        // Ruoli nei corsi (docente/tutor/mentor) -----------------------------------------
        $course_roles_table = $this->getCourseRolesTable();
        if ($course_roles_table !== '') {
            $html .= '<div class="pui-section-block">';
            $html .= '<div class="pui-section-title">' . $this->_lang->def('_COURSE_ROLES') . '</div>';
            $html .= $course_roles_table;
            $html .= '</div>';
        }

        $html .= '</div>'; // end pui-main
        $html .= '</div>'; // end pui-layout
        $html .= '</div>'; // end pui-page

        return $html;
    }

    /**
     * Disabled: the front office profile no longer shows the community block
     * (forum messages, uploaded files, member-since date, friends, profile views).
     */
    public function getCommunityInfo()
    {
        return '';
    }

    /**
     * Disabled: the old "Profilo docente" block (managed courses, curriculum,
     * publications) is replaced by the "Ruoli nei corsi" recap in getUserInfo().
     */
    public function getUserTeacherProfile($link_to = false)
    {
        return '';
    }

    /**
     * "Cambia password" form, redesigned with the pui-* design system to match
     * the rest of the front office profile.
     */
    public function getUserPwdModUi()
    {
        require_once _base_ . '/lib/lib.form.php';

        $html = Util::get_css(FormaLms\lib\Get::rel_path('base') . '/css/pandp-ui.css', true, true);

        $html .= '<div class="pui-page">';
        $html .= '<h2 class="pui-profile-title">' . $this->_lang->def('_CHANGEPASSWORD') . '</h2>';

        $html .= '<div class="pui-card">';

        $html .= Form::openForm('mod_pwd', $this->_url_man->getUrl($this->_varname_action . '=savepwd'));

        if (!$this->_user_profile->godMode()) {
            $html .= $this->getPuiFormGroup($this->_lang->def('_OLD_PWD'), 'up_old_pwd', 'up_old_pwd');
        }
        $html .= $this->getPuiFormGroup(Lang::t('_NEW_PASSWORD', 'register'), 'up_new_pwd', 'up_new_pwd');
        $html .= $this->getPuiFormGroup(Lang::t('_RETYPE_PASSWORD', 'register'), 'up_repeat_pwd', 'up_repeat_pwd');

        $html .= '<div class="pui-form-actions">';
        $html .= '<input type="submit" class="pui-btn pui-btn--primary" id="save" name="save" value="' . $this->_lang->def('_SAVE') . '" />';
        if (FormaLms\lib\Get::sett('profile_modify') == 'limit') {
            $html .= '<input type="submit" class="pui-btn pui-btn--ghost" id="undo" name="undo" value="' . $this->_lang->def('_UNDO') . '" />';
        } else {
            $html .= '<a class="pui-btn pui-btn--ghost" href="' . $this->_url_man->getUrl($this->_varname_action . '=profileview') . '">' . $this->_lang->def('_UNDO') . '</a>';
        }
        $html .= '</div>';

        $html .= Form::closeForm();

        $html .= '</div>'; // end pui-card
        $html .= '</div>'; // end pui-page

        return $html;
    }

    /**
     * Render a labeled password input used in the "Cambia password" form.
     */
    private function getPuiFormGroup($label, $id, $name)
    {
        return '<div class="pui-form-group">'
            . '<label class="pui-form-label" for="' . $id . '">' . $label . '</label>'
            . '<input class="pui-input" type="password" id="' . $id . '" name="' . $name . '" maxlength="255" />'
            . '</div>';
    }

    /**
     * "Modifica profilo" form, redesigned with the pui-* design system to match
     * the rest of the front office profile.
     */
    public function getUserInfoModUi()
    {
        require_once _base_ . '/lib/lib.form.php';
        require_once _base_ . '/lib/lib.preference.php';

        $this->loadUserData($this->_user_profile->getIdUser());

        $preference = new UserPreferences($this->_user_profile->getIdUser());

        $html = Util::get_css(FormaLms\lib\Get::rel_path('base') . '/css/pandp-ui.css', true, true);

        $html .= '<div class="pui-page">';
        $html .= '<h2 class="pui-profile-title">' . $this->_lang->def('_MOD') . ': ' . htmlspecialchars($this->resolveUsername()) . '</h2>';

        $html .= '<div class="pui-card">';

        $html .= Form::openForm('mod_up', $this->_url_man->getUrl($this->_varname_action . '=saveinfo'), false, false, 'multipart/form-data');

        // Dati personali ------------------------------------------------------------------
        $html .= '<div class="pui-section-block">';
        $html .= '<div class="pui-section-label">' . $this->_lang->def('_PERSONAL_DATA') . '</div>';

        if ($this->_user_profile->godMode()) {
            $html .= Form::getTextfield($this->_lang->def('_USERNAME'),
                                        'up_userid',
                                        'up_userid',
                                        '255',
                                        FormaLms\lib\Get::req('up_userid', DOTY_MIXED, $this->acl_man->relativeId($this->user_info[ACL_INFO_USERID]), true));
        } else {
            $html .= Form::getLineBox($this->_lang->def('_USERNAME'),
                                        $this->acl_man->relativeId($this->user_info[ACL_INFO_USERID]));
        }
        $html .= Form::getTextfield($this->_lang->def('_LASTNAME'),
                                        'up_lastname',
                                        'up_lastname',
                                        '255',
                                        FormaLms\lib\Get::req('up_lastname', DOTY_MIXED, $this->user_info[ACL_INFO_LASTNAME], true))
                . Form::getTextfield($this->_lang->def('_FIRSTNAME'),
                                        'up_firstname',
                                        'up_firstname',
                                        '255',
                                        FormaLms\lib\Get::req('up_firstname', DOTY_MIXED, $this->user_info[ACL_INFO_FIRSTNAME], true))
                . Form::getTextfield($this->_lang->def('_EMAIL'),
                                        'up_email',
                                        'up_email',
                                        '255',
                                        FormaLms\lib\Get::req('up_email', DOTY_MIXED, $this->user_info[ACL_INFO_EMAIL], true));

        // user extra field (Azienda, Cod Fisc, ...) ----------------------------------------
        $html .= $this->getPlayField();

        $html .= '</div>';

        // Preferenze --------------------------------------------------------------------
        $html .= '<div class="pui-section-block">';
        $html .= '<div class="pui-section-label">' . $this->_lang->def('_PREFERENCES') . '</div>';
        $html .= $preference->getModifyMask('ui.');
        $html .= '</div>';

        // Sicurezza (solo godMode) -------------------------------------------------------
        if ($this->_user_profile->godMode()) {
            $html .= '<div class="pui-section-block">';
            $html .= '<div class="pui-section-label">' . $this->_lang->def('_CHANGEPASSWORD') . '</div>';

            $acl_man = &Docebo::user()->getAclManager();

            $html .= Form::getPassword(Lang::t('_NEW_PASSWORD', 'register'),
                                    'up_new_pwd',
                                    'up_new_pwd',
                                    '255');

            $html .= Form::getPassword(Lang::t('_RETYPE_PASSWORD', 'register'),
                                    'up_repeat_pwd',
                                    'up_repeat_pwd',
                                    '255');

            if (Docebo::user()->getUserLevelId() == ADMIN_GROUP_GODADMIN && FormaLms\lib\Get::cur_plat() === 'framework') {
                $html .= Form::getCheckBox(Lang::t('_FORCE_PASSWORD_CHANGE', 'admin_directory'), 'force_changepwd', 'force_changepwd', 1, $this->user_info[ACL_INFO_FORCE_CHANGE]);
            }

            $lv_lang = &DoceboLanguage::createInstance('admin_directory', 'framework');
            if (Docebo::user()->getUserLevelId() == ADMIN_GROUP_GODADMIN) {
                $level_list = [
                    ADMIN_GROUP_GODADMIN => $lv_lang->def('_DIRECTORY_' . ADMIN_GROUP_GODADMIN),
                    ADMIN_GROUP_ADMIN => $lv_lang->def('_DIRECTORY_' . ADMIN_GROUP_ADMIN),
                    ADMIN_GROUP_USER => $lv_lang->def('_DIRECTORY_' . ADMIN_GROUP_USER),
                ];
            } else {
                $level_list = [
                    ADMIN_GROUP_USER => $lv_lang->def('_DIRECTORY_' . ADMIN_GROUP_USER),
                ];
            }

            $html .= Form::getDropdown($this->_lang->def('_LEVEL'),
                                    'up_level',
                                    'up_level',
                                    $level_list,
                                    $acl_man->getUserLevelId($this->_user_profile->getIdUser()));

            $html .= '</div>';
        }

        // Firma: il campo non viene piu' mostrato (sezione disattivata anche nella vista
        // profilo), ma il valore esistente viene preservato in un campo nascosto per
        // evitare che venga azzerato al salvataggio.
        $html .= Form::getHidden('up_signature', 'up_signature', $this->user_info[ACL_INFO_SIGNATURE]);

        // Azioni -----------------------------------------------------------------------------
        $html .= '<div class="pui-form-actions">';
        $html .= '<input type="submit" class="pui-btn pui-btn--primary" id="save" name="save" value="' . $this->_lang->def('_SAVE') . '" />';
        $undo_name = (isset($_GET['modname']) && $_GET['modname'] == 'reservation') ? 'undo_profile' : 'undo';
        $html .= '<input type="submit" class="pui-btn pui-btn--ghost" id="' . $undo_name . '" name="' . $undo_name . '" value="' . $this->_lang->def('_UNDO') . '" />';
        $html .= '</div>';

        $html .= Form::closeForm();

        $html .= '</div>'; // end pui-card
        $html .= '</div>'; // end pui-page

        return $html;
    }

    /**
     * Render a label/value row used in the "Dati personali" and "Contatti" sections.
     */
    private function getPuiFieldRow($label, $value)
    {
        return '<div class="pui-field-row">'
            . '<div class="pui-field-label">' . $label . '</div>'
            . '<div class="pui-field-value">' . $value . '</div>'
            . '</div>';
    }

    /**
     * Build the "Storia formativa" table: every course the user is enrolled in,
     * with type, status, enrollment/completion dates and credits.
     */
    private function getTrainingHistoryTable()
    {
        $db = DbConn::getInstance();

        $query = 'SELECT c.idCourse, c.name, c.course_type, c.credits, cu.status, cu.date_inscr, cu.date_complete'
            . ' FROM %lms_courseuser AS cu'
            . ' INNER JOIN %lms_course AS c ON c.idCourse = cu.idCourse'
            . ' WHERE cu.idUser = ' . (int) $this->_id_user
            . ' ORDER BY cu.date_inscr DESC';

        $res = $db->query($query);
        $rows = [];
        if ($res) {
            while ($row = $db->fetch_obj($res)) {
                $rows[] = $row;
            }
        }

        if (empty($rows)) {
            return '<p>' . $this->_lang->def('_NO_TRAINING_HISTORY') . '</p>';
        }

        $course_type_labels = [
            'elearning' => Lang::t('_COURSE_TYPE_ELEARNING', 'course'),
            'classroom' => Lang::t('_CLASSROOM', 'standard'),
            'edition' => Lang::t('_COURSE_TYPE_EDITION', 'course'),
        ];

        $status_map = [
            _CUS_SUBSCRIBED => ['label' => $this->_lang->def('_STATUS_SUBSCRIBED'), 'badge' => 'neutral'],
            _CUS_BEGIN => ['label' => $this->_lang->def('_STATUS_INPROGRESS'), 'badge' => 'info'],
            _CUS_END => ['label' => $this->_lang->def('_STATUS_COMPLETED'), 'badge' => 'success'],
            _CUS_SUSPEND => ['label' => $this->_lang->def('_STATUS_SUSPENDED'), 'badge' => 'danger'],
        ];

        $html = '<table class="pui-table">'
            . '<thead><tr>'
            . '<th>' . Lang::t('_COURSE', 'standard') . '</th>'
            . '<th>' . Lang::t('_STATUS', 'standard') . '</th>'
            . '<th>' . $this->_lang->def('_ENROLLMENT_DATE') . '</th>'
            . '<th>' . $this->_lang->def('_COMPLETION_DATE') . '</th>'
            . '<th>' . Lang::t('_CREDITS', 'standard') . '</th>'
            . '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $type_label = isset($course_type_labels[$row->course_type]) ? $course_type_labels[$row->course_type] : $row->course_type;
            $status_info = isset($status_map[$row->status]) ? $status_map[$row->status] : $status_map[_CUS_SUBSCRIBED];

            $html .= '<tr>'
                . '<td>' . htmlspecialchars($row->name) . '<div class="pui-course-type">' . htmlspecialchars($type_label) . '</div></td>'
                . '<td><span class="pui-badge pui-badge--' . $status_info['badge'] . '">' . htmlspecialchars($status_info['label']) . '</span></td>'
                . '<td class="pui-course-date">' . ($row->date_inscr ? date('d/m/Y', strtotime($row->date_inscr)) : '<span class="pui-course-empty">&mdash;</span>') . '</td>'
                . '<td class="pui-course-date">' . ($row->date_complete ? date('d/m/Y', strtotime($row->date_complete)) : '<span class="pui-course-empty">&mdash;</span>') . '</td>'
                . '<td>' . (int) $row->credits . '</td>'
                . '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * Build the "Ruoli nei corsi" table: a recap of the courses where the user
     * is enrolled as Docente, Mentore or Tutor.
     */
    private function getCourseRolesTable()
    {
        require_once _lms_ . '/lib/lib.levels.php';

        $id_user = $this->_user_profile->getIdUser();

        $level_lang = &DoceboLanguage::createInstance('levels', 'lms');

        $roles = [
            CourseLevel::COURSE_LEVEL_TEACHER => ['label' => $level_lang->def('_LEVEL_' . CourseLevel::COURSE_LEVEL_TEACHER), 'courses' => $this->_up_data_man->getCourseAsTeacher($id_user)],
            CourseLevel::COURSE_LEVEL_MENTOR => ['label' => $level_lang->def('_LEVEL_' . CourseLevel::COURSE_LEVEL_MENTOR), 'courses' => $this->_up_data_man->getCourseAsMentor($id_user)],
            CourseLevel::COURSE_LEVEL_TUTOR => ['label' => $level_lang->def('_LEVEL_' . CourseLevel::COURSE_LEVEL_TUTOR), 'courses' => $this->_up_data_man->getCourseAsTutor($id_user)],
        ];

        $has_rows = false;
        foreach ($roles as $role) {
            if (!empty($role['courses'])) {
                $has_rows = true;
                break;
            }
        }
        if (!$has_rows) {
            return '';
        }

        $html = '<table class="pui-table">'
            . '<thead><tr>'
            . '<th>' . Lang::t('_COURSE', 'standard') . '</th>'
            . '<th>' . $this->_lang->def('_ROLE') . '</th>'
            . '</tr></thead><tbody>';

        foreach ($roles as $role) {
            foreach ($role['courses'] as $course) {
                $html .= '<tr>'
                    . '<td>[' . htmlspecialchars($course['code']) . '] ' . htmlspecialchars($course['name']) . '</td>'
                    . '<td><span class="pui-badge pui-badge--info">' . htmlspecialchars($role['label']) . '</span></td>'
                    . '</tr>';
            }
        }

        $html .= '</tbody></table>';

        return $html;
    }
}
