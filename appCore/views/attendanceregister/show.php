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

    <form id="ar_export_form" method="get" action="ajax.adm_server.php">
        <input type="hidden" name="r" value="adm/attendanceregister/export_selected" />
        <input type="hidden" name="idCourse" id="ar_export_idcourse" value="" />
        <div id="ar_users_container"></div>
    </form>
</div>

<?php
    $this->widget('dialog', [
        'id' => 'ar_user_sessions_dialog',
        'width' => '650px',
        'dynamicContent' => true,
        'ajaxUrl' => 'function(){ return YAHOO.util.Dom.get("ar_sessions_url").value; }',
        'dynamicAjaxUrl' => true,
        'constrainToViewport' => false,
        'confirmOnly' => true,
        'fixedCenter' => true,
        'callObjectFunc' => 'AttendanceRegister.oDialogCaller',
    ]);
?>
<input type="hidden" id="ar_sessions_url" value="" />
<script type="text/javascript">
    function arOpenUserSessions(idCourse, idUser) {
        var url = 'ajax.adm_server.php?r=adm/attendanceregister/user_sessions&idCourse=' + idCourse + '&idUser=' + idUser;
        YAHOO.util.Dom.get('ar_sessions_url').value = url;
        AttendanceRegister.oDialogCaller['ar_user_sessions_dialog']();
    }
</script>
