<div class="pui-page ar-page">
    <?php echo getTitleArea(Lang::t('_ATTENDANCE_REGISTER', 'standard')); ?>

    <div class="pui-card">
        <div class="ar-filters">
            <div class="pui-field">
                <div class="pui-section-label"><?php echo Lang::t('_COURSE', 'standard'); ?></div>
                <select class="pui-select" id="ar_course_select" style="max-width:420px">
                    <option value="">-- <?php echo Lang::t('_SELECT', 'standard'); ?> --</option>
                    <?php foreach ($courses as $c) { ?>
                        <option value="<?php echo (int) $c['idCourse']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="pui-field" id="ar_company_field" style="display:none;">
                <div class="pui-section-label"><?php echo Lang::t('_COMPANY', 'statistic'); ?></div>
                <select class="pui-select" id="ar_company_select" style="max-width:420px">
                    <option value=""><?php echo Lang::t('_ALL_COMPANIES', 'statistic'); ?></option>
                </select>
            </div>
        </div>
    </div>

    <div class="ar-split" id="ar_split" style="display:none;">
        <form id="ar_export_form" method="get" action="ajax.adm_server.php">
            <input type="hidden" name="r" id="ar_export_r" value="" />
            <input type="hidden" name="idCourse" id="ar_export_idcourse" value="" />
            <input type="hidden" name="idOrg" id="ar_export_idorg" value="" />
            <input type="hidden" name="detailed" id="ar_export_detailed" value="0" />
            <input type="hidden" name="authentic_request" value="<?php echo Util::getSignature(); ?>" />
            <div id="ar_users_container"></div>
        </form>

        <div class="ar-detail" id="ar_detail">
            <div class="ar-detail__empty"><?php echo Lang::t('_SELECT', 'standard'); ?></div>
        </div>
    </div>

    <div id="ar_print_multi" class="ar-print-multi"></div>

    <div class="ar-modal" id="ar_format_modal" style="display:none;">
        <div class="ar-modal__box">
            <button type="button" class="ar-modal__close" onclick="arCloseFormatModal()">&times;</button>
            <div class="ar-modal__title"><?php echo Lang::t('_CHOOSE_EXPORT_FORMAT', 'statistic'); ?></div>
            <div class="ar-modal__actions">
                <button type="button" class="pui-btn pui-btn--primary" onclick="arRunAction('print')"><?php echo Lang::t('_PRINT', 'statistic'); ?></button>
                <button type="button" class="pui-btn pui-btn--ghost" onclick="arRunAction('excel')"><?php echo Lang::t('_EXPORT_ALL_USERS_XLS', 'statistic'); ?></button>
                <button type="button" class="pui-btn pui-btn--ghost" onclick="arRunAction('word')"><?php echo Lang::t('_EXPORT_ALL_USERS_WORD', 'statistic'); ?></button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    // Ogni richiesta verso ajax.adm_server.php (anche GET) richiede questa
    // firma anti-CSRF (Util::checkSignature()), altrimenti il server risponde
    // con un errore "Security issue".
    var AR_SIGNATURE = '<?php echo Util::getSignature(); ?>';

    function arOpenUserSessions(idCourse, idUser, rowEl) {
        var rows = document.querySelectorAll('#ar_users_container tr.ar-row-active');
        for (var i = 0; i < rows.length; i++) {
            rows[i].classList.remove('ar-row-active');
        }
        if (rowEl) {
            rowEl.classList.add('ar-row-active');
            // Aprire il dettaglio di un utente lo seleziona anche per
            // l'export/stampa: si puo' "scegliere l'allievo" sia dalla
            // checkbox sia cliccando la sua username.
            var checkbox = rowEl.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.checked = true;
            }
        }

        var detail = document.getElementById('ar_detail');
        detail.innerHTML = '<div class="ar-detail__empty">...</div>';

        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'ajax.adm_server.php?r=adm/attendanceregister/user_sessions&idCourse=' + idCourse + '&idUser=' + idUser + '&authentic_request=' + encodeURIComponent(AR_SIGNATURE), true);
        xhr.onload = function () {
            if (xhr.status !== 200) {
                return;
            }
            detail.innerHTML = xhr.responseText;
        };
        xhr.send();
    }
</script>
