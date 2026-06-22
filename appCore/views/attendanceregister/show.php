<div class="pui-page ar-page">
    <?php echo getTitleArea(Lang::t('_ATTENDANCE_REGISTER', 'standard')); ?>

    <div class="pui-card">
        <div class="pui-section-label"><?php echo Lang::t('_COURSE', 'standard'); ?></div>
        <div class="pui-field">
            <select class="pui-select" id="ar_course_select" style="max-width:420px">
                <option value="">-- <?php echo Lang::t('_SELECT', 'standard'); ?> --</option>
                <?php foreach ($courses as $c) { ?>
                    <option value="<?php echo (int) $c['idCourse']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                <?php } ?>
            </select>
        </div>
    </div>

    <div class="ar-split" id="ar_split" style="display:none;">
        <form id="ar_export_form" method="get" action="ajax.adm_server.php">
            <input type="hidden" name="r" value="adm/attendanceregister/export_selected" />
            <input type="hidden" name="idCourse" id="ar_export_idcourse" value="" />
            <input type="hidden" name="authentic_request" value="<?php echo Util::getSignature(); ?>" />
            <div id="ar_users_container"></div>
        </form>

        <div class="ar-detail" id="ar_detail">
            <div class="ar-detail__empty"><?php echo Lang::t('_SELECT', 'standard'); ?></div>
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
