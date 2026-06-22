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

        <div class="ar-preview" id="ar_preview">
            <div class="ar-preview__head">
                <div class="ar-preview__toggle">
                    <button type="button" class="ar-preview__toggle-btn ar-preview__toggle-btn--active" id="ar_mode_summary" onclick="arSetMode(false)"><?php echo Lang::t('_SUMMARY_VIEW', 'statistic'); ?></button>
                    <button type="button" class="ar-preview__toggle-btn" id="ar_mode_detailed" onclick="arSetMode(true)"><?php echo Lang::t('_DETAILS', 'standard'); ?></button>
                </div>
            </div>
            <div class="ar-preview__body" id="ar_preview_container">
                <div class="ar-empty"><?php echo Lang::t('_SELECT', 'standard'); ?></div>
            </div>
            <div class="ar-preview__foot">
                <button type="button" class="pui-btn pui-btn--primary" onclick="arDoPrint()"><?php echo Lang::t('_PRINT', 'statistic'); ?></button>
                <button type="button" class="pui-btn pui-btn--ghost" onclick="arDoExport('excel')"><?php echo Lang::t('_EXPORT_ALL_USERS_XLS', 'statistic'); ?></button>
                <button type="button" class="pui-btn pui-btn--ghost" onclick="arDoExport('word')"><?php echo Lang::t('_EXPORT_ALL_USERS_WORD', 'statistic'); ?></button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    // Ogni richiesta verso ajax.adm_server.php (anche GET) richiede questa
    // firma anti-CSRF (Util::checkSignature()), altrimenti il server risponde
    // con un errore "Security issue".
    var AR_SIGNATURE = '<?php echo Util::getSignature(); ?>';
</script>
