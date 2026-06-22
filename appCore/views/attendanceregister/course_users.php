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
                    <td class="ar-link" onclick="arOpenUserSessions(<?php echo (int) $idCourse; ?>, <?php echo (int) $u['idst']; ?>)"><?php echo htmlspecialchars($u['userid']); ?></td>
                    <td><?php echo htmlspecialchars($u['name']); ?></td>
                </tr>
            <?php } ?>
        </table>
    </div>
    <div class="ar-actions">
        <button type="submit" class="pui-btn pui-btn--primary"><?php echo Lang::t('_EXPORT_ALL_USERS_XLS', 'statistic'); ?></button>
        <a class="pui-btn pui-btn--ghost" href="ajax.adm_server.php?r=adm/attendanceregister/export_all_word&idCourse=<?php echo (int) $idCourse; ?>&authentic_request=<?php echo urlencode(Util::getSignature()); ?>">
            <?php echo Lang::t('_EXPORT_ALL_USERS_WORD', 'statistic'); ?>
        </a>
    </div>
<?php } ?>
