<?php if (empty($users)) { ?>
    <div class="ar-empty"><?php echo Lang::t('_NO_DATA', 'standard'); ?></div>
<?php } else { ?>
    <div class="ar-table-wrap">
        <table class="ar-table">
            <tr>
                <th class="ar-col-check"></th>
                <th><?php echo Lang::t('_USERNAME', 'standard'); ?></th>
                <th><?php echo Lang::t('_FULLNAME', 'standard'); ?></th>
            </tr>
            <?php foreach ($users as $u) { ?>
                <tr>
                    <td><input type="checkbox" name="selected_users[]" value="<?php echo (int) $u['idst']; ?>" /></td>
                    <td class="ar-link" onclick="arOpenUserSessions(<?php echo (int) $idCourse; ?>, <?php echo (int) $u['idst']; ?>, this.parentNode)"><?php echo htmlspecialchars($u['userid']); ?></td>
                    <td><?php echo htmlspecialchars($u['name']); ?></td>
                </tr>
            <?php } ?>
        </table>
    </div>
    <div class="ar-actions">
        <button type="button" class="pui-btn pui-btn--primary" onclick="arOpenFormatModal(false)"><?php echo Lang::t('_SUMMARY_VIEW', 'statistic'); ?></button>
        <button type="button" class="pui-btn pui-btn--ghost" onclick="arOpenFormatModal(true)"><?php echo Lang::t('_DETAILS', 'standard'); ?></button>
    </div>
    <div class="ar-actions__note"><?php echo Lang::t('_EXPORT_SELECTION_NOTE', 'statistic'); ?></div>
<?php } ?>
