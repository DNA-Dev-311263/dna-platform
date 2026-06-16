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

$css_url = $GLOBALS['where_files_relative'] ? rtrim($GLOBALS['where_files_relative'], '/') . '/../css/pandp-ui.css' : '../css/pandp-ui.css';
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($css_url); ?>">

<?php switch ($step) { case 1: ?>
<div class="pui-page">
    <div class="pui-stepper">
        <div class="pui-step pui-step--active">
            <div class="pui-step__num">1</div>
            <span><?php echo Lang::t('_IMPORT_STEP_FILE', 'admin_directory', null, false, 'Carica file'); ?></span>
        </div>
        <div class="pui-step__line"></div>
        <div class="pui-step">
            <div class="pui-step__num">2</div>
            <span><?php echo Lang::t('_IMPORT_STEP_CONFIG', 'admin_directory', null, false, 'Configura'); ?></span>
        </div>
        <div class="pui-step__line"></div>
        <div class="pui-step">
            <div class="pui-step__num">3</div>
            <span><?php echo Lang::t('_IMPORT_STEP_RESULTS', 'admin_directory', null, false, 'Risultati'); ?></span>
        </div>
    </div>

    <form id="directory_importgroupuser"
          action="index.php?r=<?php echo $this->link; ?>/importusers"
          method="post"
          enctype="multipart/form-data">

        <input type="hidden" name="authentic_request" value="<?php echo Util::getSignature(); ?>">
        <input type="hidden" name="id"   value="<?php echo (int)$id_org; ?>">
        <input type="hidden" name="step" value="2">

        <div class="pui-card">

            <!-- File upload zone -->
            <div class="pui-section-block">
                <div class="pui-section-label"><?php echo Lang::t('_GROUP_USER_IMPORT_FILE', 'admin_directory'); ?></div>
                <div class="pui-upload-zone">
                    <div class="pui-upload-zone__icon">&#8679;</div>
                    <div class="pui-upload-zone__title"><?php echo Lang::t('_IMPORT_DROP_TITLE', 'admin_directory', null, false, 'Trascina il file CSV qui'); ?></div>
                    <div class="pui-upload-zone__sub">.csv &nbsp;&bull;&nbsp; .txt</div>
                    <input type="file" name="file_import" id="file_import">
                </div>
            </div>

            <!-- Separator -->
            <div class="pui-section-block">
                <div class="pui-section-label"><?php echo Lang::t('_GROUP_USER_IMPORT_SEPARATOR', 'admin_directory'); ?></div>
                <div class="pui-radio-group">
                    <label class="pui-radio-pill">
                        <input type="radio" name="import_separator" id="import_separator_0" value="auto" checked>
                        <?php echo Lang::t('_AUTODETECT', 'standard'); ?>
                    </label>
                    <label class="pui-radio-pill">
                        <input type="radio" name="import_separator" id="import_separator_1" value="comma">
                        <b>,</b>&nbsp;<?php echo Lang::t('_COMMA', 'standard', null, false, 'Virgola'); ?>
                    </label>
                    <label class="pui-radio-pill">
                        <input type="radio" name="import_separator" id="import_separator_2" value="dotcomma">
                        <b>;</b>&nbsp;<?php echo Lang::t('_SEMICOLON', 'standard', null, false, 'Punto e virgola'); ?>
                    </label>
                    <label class="pui-radio-pill pui-radio-pill--wide">
                        <input type="radio" name="import_separator" id="import_separator_3" value="manual">
                        <?php echo Lang::t('_MANUAL', 'standard'); ?>:&nbsp;
                        <input type="text" name="import_separator_manual" id="import_separator_manual"
                               class="pui-text-input pui-text-input--sm" value="" maxlength="255" placeholder="|">
                    </label>
                </div>
            </div>

            <!-- First row header -->
            <div class="pui-section-block">
                <label class="pui-check-row">
                    <input type="checkbox" name="import_first_row_header" id="import_first_row_header" value="true" checked>
                    <?php echo Lang::t('_GROUP_USER_IMPORT_HEADER', 'admin_directory'); ?>
                </label>
            </div>

            <!-- Charset -->
            <div class="pui-section-block">
                <div class="pui-inline-field">
                    <label for="import_charset"><?php echo Lang::t('_GROUP_USER_IMPORT_CHARSET', 'admin_directory'); ?></label>
                    <input type="text" name="import_charset" id="import_charset"
                           class="pui-text-input" value="UTF-8" maxlength="20">
                </div>
            </div>

            <div class="pui-btn-row">
                <input type="submit" name="import_groupcancel" id="import_groupcancel"
                       class="pui-btn pui-btn--ghost"
                       value="<?php echo Lang::t('_UNDO', 'standard'); ?>">
                <input type="submit" name="import_groupuser_2" id="import_groupuser_2"
                       class="pui-btn pui-btn--primary"
                       value="<?php echo Lang::t('_FORWARD', 'standard'); ?> &rarr;">
            </div>

        </div><!-- /.pui-card -->
    </form>
</div>
<script type="text/javascript">
    (function() {
        var zone = document.querySelector('.pui-upload-zone');
        var input = document.getElementById('file_import');
        var subText = zone ? zone.querySelector('.pui-upload-zone__sub') : null;
        var subTextDefault = subText ? subText.innerHTML : '';
        if (!zone || !input) return;

        ['dragenter', 'dragover'].forEach(function(evt) {
            zone.addEventListener(evt, function(e) {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.add('pui-upload-zone--dragover');
            });
        });
        ['dragleave', 'dragend', 'drop'].forEach(function(evt) {
            zone.addEventListener(evt, function(e) {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.remove('pui-upload-zone--dragover');
            });
        });
        zone.addEventListener('drop', function(e) {
            var files = e.dataTransfer ? e.dataTransfer.files : null;
            if (files && files.length) {
                input.files = files;
                if (subText) {
                    subText.textContent = files[0].name;
                }
            }
        });
        input.addEventListener('change', function() {
            if (subText) {
                subText.innerHTML = (input.files && input.files.length) ? input.files[0].name : subTextDefault;
            }
        });
    }());
</script>
<?php break; case 2: ?>
<?php
    $reached_max_user_created = false;
    if (Docebo::user()->getUserLevelId() != ADMIN_GROUP_GODADMIN) {
        $admin_pref = new AdminPreference();
        $pref = $admin_pref->getAdminRules(Docebo::user()->getIdSt());
        if ($pref['admin_rules.limit_user_insert'] == 'on') {
            $user_pref = new UserPreferences(Docebo::user()->getIdSt());
            if (($user_pref->getPreference('user_created_count') + $tot_row) > $pref['admin_rules.max_user_insert']) {
                echo UIFeedback::perror(Lang::t('_USER_CREATED_MAX_REACHED', 'admin_directory'));
                $reached_max_user_created = true;
            }
        }
    }
?>
<div class="pui-page">
    <div class="pui-stepper">
        <div class="pui-step pui-step--done">
            <div class="pui-step__num">&#10003;</div>
            <span><?php echo Lang::t('_IMPORT_STEP_FILE', 'admin_directory', null, false, 'Carica file'); ?></span>
        </div>
        <div class="pui-step__line pui-step__line--done"></div>
        <div class="pui-step pui-step--active">
            <div class="pui-step__num">2</div>
            <span><?php echo Lang::t('_IMPORT_STEP_CONFIG', 'admin_directory', null, false, 'Configura'); ?></span>
        </div>
        <div class="pui-step__line"></div>
        <div class="pui-step">
            <div class="pui-step__num">3</div>
            <span><?php echo Lang::t('_IMPORT_STEP_RESULTS', 'admin_directory', null, false, 'Risultati'); ?></span>
        </div>
    </div>

    <form id="directory_importgroupuser"
          action="index.php?r=<?php echo $this->link; ?>/importusers"
          method="post"
          enctype="multipart/form-data">

        <input type="hidden" name="authentic_request" value="<?php echo Util::getSignature(); ?>">
        <input type="hidden" name="id"                     value="<?php echo (int)$id_org; ?>">
        <input type="hidden" name="step"                   value="3">
        <input type="hidden" name="filename"               value="<?php echo htmlspecialchars($filename); ?>">
        <input type="hidden" name="import_first_row_header" value="<?php echo $first_row_header ? 'true' : 'false'; ?>">
        <input type="hidden" name="import_separator"       value="<?php echo htmlspecialchars($separator); ?>">
        <input type="hidden" name="import_charset"         value="<?php echo htmlspecialchars($import_charset); ?>">

        <div class="pui-layout">

            <!-- ── LEFT sidebar — settings ── -->
            <div class="pui-sidebar">

                <div class="pui-sidebar-section">
                    <div class="pui-section-label"><?php echo Lang::t('_SEND_NEW_CREDENTIALS_ALERT', 'user_managment'); ?></div>
                    <label class="pui-check-row">
                        <input type="checkbox" name="send_alert" id="send_alert" value="1">
                        <?php echo Lang::t('_SEND_NEW_CREDENTIALS_ALERT', 'user_managment'); ?>
                    </label>
                </div>

                <div class="pui-sidebar-section">
                    <div class="pui-section-label"><?php echo Lang::t('_DIRECTORY_MEMBERTYPETREE', 'admin_directory'); ?></div>
                    <select name="id" id="id" class="pui-select">
                        <?php foreach ($orgchart_list as $oc_id => $oc_name): ?>
                            <?php
                                // $oc_name has a literal "&nbsp;&nbsp;" prefix per tree depth level for indentation
                                preg_match('/^(?:&nbsp;)*/', $oc_name, $oc_indent_match);
                                $oc_indent = $oc_indent_match[0];
                                $oc_label  = substr($oc_name, strlen($oc_indent));
                            ?>
                            <option value="<?php echo (int)$oc_id; ?>"<?php echo ($oc_id == $id_org ? ' selected' : ''); ?>>
                                <?php echo $oc_indent . htmlspecialchars($oc_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="pui-sidebar-section">
                    <div class="pui-section-label"><?php echo Lang::t('_ACTION_ON_USERS', 'user_managment'); ?></div>
                    <div class="pui-radio-stack">
                        <label class="pui-radio-item">
                            <input type="radio" name="action_on_users" id="action_on_users_0" value="create_and_update">
                            <?php echo Lang::t('_CREATE_AND_UPDATE', 'user_managment'); ?>
                        </label>
                        <label class="pui-radio-item">
                            <input type="radio" name="action_on_users" id="action_on_users_1" value="only_create" checked>
                            <?php echo Lang::t('_ONLY_CREATE', 'user_managment'); ?>
                        </label>
                        <label class="pui-radio-item">
                            <input type="radio" name="action_on_users" id="action_on_users_2" value="only_update">
                            <?php echo Lang::t('_ONLY_UPDATE', 'user_managment'); ?>
                        </label>
                    </div>
                </div>

                <div class="pui-sidebar-section">
                    <div class="pui-section-label"><?php echo Lang::t('_FORCE_PASSWORD_CHANGE', 'admin_directory'); ?></div>
                    <div class="pui-radio-stack">
                        <label class="pui-radio-item">
                            <input type="radio" name="pwd_force_change_policy" id="pwd_force_change_policy_0" value="false" checked>
                            <?php echo Lang::t('_NO', 'standard'); ?>
                        </label>
                        <label class="pui-radio-item">
                            <input type="radio" name="pwd_force_change_policy" id="pwd_force_change_policy_1" value="true">
                            <?php echo Lang::t('_YES', 'standard'); ?>
                        </label>
                        <label class="pui-radio-item">
                            <input type="radio" name="pwd_force_change_policy" id="pwd_force_change_policy_2" value="by_setting">
                            <?php echo Lang::t('_SERVERINFO', 'configuration'); ?>
                        </label>
                    </div>
                </div>

                <div class="pui-sidebar-section">
                    <div class="pui-section-label"><?php echo Lang::t('_SET_PASSWORD', 'user_managment'); ?></div>
                    <div class="pui-radio-stack">
                        <label class="pui-radio-item">
                            <input type="radio" name="set_password" id="set_password_0" value="from_file">
                            <?php echo Lang::t('_FROM_FILE', 'user_managment'); ?>
                        </label>
                        <label class="pui-radio-item">
                            <input type="radio" name="set_password" id="set_password_1" value="insert_all" checked>
                            <?php echo Lang::t('_INSERT_ALL', 'user_managment'); ?>
                        </label>
                    </div>
                    <div class="pui-sub-control">
                        <div class="pui-radio-stack">
                            <label class="pui-radio-item">
                                <input type="radio" name="password_to_insert" id="password_to_insert_0" value="use_automatic_password" checked>
                                <?php echo Lang::t('_AUTOMATIC_PASSWORD', 'user_managment'); ?>
                            </label>
                            <label class="pui-radio-item">
                                <input type="radio" name="password_to_insert" id="password_to_insert_1" value="use_manual_password">
                                <?php echo Lang::t('_MANUAL_PASSWORD', 'user_managment'); ?>:
                                <input type="text" name="manual_password" id="manual_password"
                                       class="pui-text-input pui-text-input--sm" value="" maxlength="50">
                            </label>
                        </div>
                    </div>
                </div>

            </div><!-- /.pui-sidebar -->

            <!-- ── RIGHT main — column mapping ── -->
            <div class="pui-main">
                <div class="pui-section-label" style="margin-bottom:14px"><?php echo Lang::t('_IMPORT_MAP', 'organization_chart'); ?></div>
                <div class="pui-notice pui-notice--info">
                    &#8505;&nbsp;<?php echo Lang::t('_IMPORT_MAP_HINT', 'admin_directory', null, false, 'Associa ogni colonna del CSV al campo corrispondente. Imposta "Ignora" per le colonne non necessarie.'); ?>
                </div>
                <div class="pui-uimap-wrap">
                    <?php echo $UIMap; ?>
                </div>
                <div class="pui-btn-row pui-btn-row--space-between">
                    <div style="display:flex; gap:10px;">
                        <a href="index.php?r=<?php echo $this->link; ?>/importusers&id=<?php echo (int)$id_org; ?>"
                           class="pui-btn pui-btn--ghost">&larr; <?php echo Lang::t('_BACK', 'standard', null, false, 'Indietro'); ?></a>
                        <input type="submit" name="import_groupcancel" id="import_groupcancel"
                               class="pui-btn pui-btn--ghost"
                               value="<?php echo Lang::t('_CANCEL', 'standard', null, false, 'Annulla'); ?>">
                    </div>
                    <input type="submit" name="next_importusers_3" id="next_importusers_3"
                           class="pui-btn pui-btn--primary"
                           <?php echo $reached_max_user_created ? 'disabled' : ''; ?>
                           value="<?php echo Lang::t('_FORWARD', 'standard'); ?> &rarr;">
                </div>
            </div><!-- /.pui-main -->

        </div><!-- /.pui-layout -->
    </form>
</div>
<?php break; case 3: ?>
<div class="pui-page">
    <div class="pui-stepper">
        <div class="pui-step pui-step--done">
            <div class="pui-step__num">&#10003;</div>
            <span><?php echo Lang::t('_IMPORT_STEP_FILE', 'admin_directory', null, false, 'Carica file'); ?></span>
        </div>
        <div class="pui-step__line pui-step__line--done"></div>
        <div class="pui-step pui-step--done">
            <div class="pui-step__num">&#10003;</div>
            <span><?php echo Lang::t('_IMPORT_STEP_CONFIG', 'admin_directory', null, false, 'Configura'); ?></span>
        </div>
        <div class="pui-step__line pui-step__line--done"></div>
        <div class="pui-step pui-step--active">
            <div class="pui-step__num">3</div>
            <span><?php echo Lang::t('_IMPORT_STEP_RESULTS', 'admin_directory', null, false, 'Risultati'); ?></span>
        </div>
    </div>

    <?php
        // $first_row_header is not passed by the controller for step 3, read it from the
        // hidden field posted from step 2 instead
        $first_row_header_flag = (isset($_POST['import_first_row_header']) && $_POST['import_first_row_header'] === 'true');
        $import_total   = $first_row_header_flag ? $results[0] - 1 : $results[0];
        $import_errors  = count($results) - 1;
        $import_success = $import_total - $import_errors;

        // remove the redundant "Errori: N" line already counted in the stats above
        $errors_prefix = Lang::t('_ERRORS', 'admin_directory') . ': <b>' . $import_errors . '</b><br/>';
        $table_only = str_replace($errors_prefix, '', $table);
    ?>
    <div class="pui-card">
        <div class="pui-back-link"><?php echo $backUi; ?></div>
        <div class="pui-stats-row">
            <div class="pui-stat-box">
                <div class="pui-stat-box__value"><?php echo (int)$import_total; ?></div>
                <div class="pui-stat-box__label"><?php echo Lang::t('_TOTAL_TO_IMPORT', 'admin_directory', null, false, 'Da importare'); ?></div>
            </div>
            <div class="pui-stat-box pui-stat-box--success">
                <div class="pui-stat-box__value"><?php echo (int)$import_success; ?></div>
                <div class="pui-stat-box__label"><?php echo Lang::t('_IMPORTED', 'admin_directory', null, false, 'Importati'); ?></div>
            </div>
            <div class="pui-stat-box pui-stat-box--error">
                <div class="pui-stat-box__value"><?php echo (int)$import_errors; ?></div>
                <div class="pui-stat-box__label"><?php echo Lang::t('_NOT_IMPORTED', 'admin_directory', null, false, 'Non importati'); ?></div>
            </div>
        </div>
        <?php echo $table_only; ?>
        <div class="pui-btn-row" style="margin-top:24px">
            <?php echo str_replace(
                '>' . Lang::t('_BACK', 'standard') . '<',
                '>' . Lang::t('_CLOSE', 'standard', null, false, 'Chiudi') . '<',
                $backUi
            ); ?>
        </div>
    </div>
</div>
<?php break; } ?>

<script type="text/javascript">
    var D = YAHOO.util.Dom, E = YAHOO.util.Event;

    E.onDOMReady(function() {
        var cr = D.get("action_on_users_1");
        var fl = D.get("set_password_0"), al = D.get("send_alert");
        var ia = D.get("set_password_1");
        var pa = D.get("password_to_insert_0"), pm = D.get("password_to_insert_1"), tx = D.get("manual_password");
        var frm = D.get("directory_importgroupuser");

        var errPf = "<?php echo Lang::t('_MISSING_PWD_FIELD', 'user_managment'); ?>";
        var errPm = "<?php echo Lang::t('_PASSWORD_TOO_SHORT', 'register'); ?>";

        if (!frm) return;

        ia.checked = true;
        pa.checked = true;

        E.addListener(ia, "click", function(e) {
            pa.disabled = false;
            pm.disabled = false;
            tx.disabled = false;
        });
        E.addListener(fl, "click", function(e) {
            pa.disabled = true;
            pm.disabled = true;
            tx.disabled = true;
        });
        E.addListener(frm, "submit", function(e) {
            if (cr && cr.checked && fl && fl.checked) {
                var slist = YAHOO.util.Selector.query('select[id^=import_map_]');
                var i;
                for (i = 0; i < slist.length; i++) {
                    if (slist[i].value == "pass") return;
                }
                YAHOO.util.Event.preventDefault(e);
                alert(errPf);
            } else if (ia && ia.checked && pm && pm.checked && tx && tx.value.trim() == "") {
                YAHOO.util.Event.preventDefault(e);
                alert(errPm);
            }
        });
    });
</script>
