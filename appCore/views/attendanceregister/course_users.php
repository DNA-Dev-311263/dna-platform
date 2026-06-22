<?php if (empty($users)) { ?>
    <div class="ar-empty"><?php echo Lang::t('_NO_DATA', 'standard'); ?></div>
<?php } else { ?>
    <table class="ar-table">
        <tr>
            <th style="width:30px;"></th>
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
    <div class="ar-actions">
        <button type="submit" class="pui-btn pui-btn--primary"><?php echo Lang::t('_EXPORT_ALL_USERS_XLS', 'statistic'); ?></button>
    </div>
<?php } ?>
