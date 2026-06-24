<?php if (empty($users)) { ?>
    <div class="ar-empty"><?php echo Lang::t('_NO_DATA', 'standard'); ?></div>
<?php } else { ?>
    <div class="ar-table-wrap">
        <table class="ar-table">
            <tr>
                <th class="ar-col-check"></th>
                <th class="ar-col-username"><?php echo Lang::t('_USERNAME', 'standard'); ?></th>
                <th class="ar-col-fullname"><?php echo Lang::t('_FULLNAME', 'standard'); ?></th>
            </tr>
            <?php foreach ($users as $u) { ?>
                <tr>
                    <td><input type="checkbox" name="selected_users[]" value="<?php echo (int) $u['idst']; ?>" /></td>
                    <td class="ar-link" title="<?php echo htmlspecialchars($u['userid']); ?>" onclick="arSelectUser(this.parentNode)"><?php echo htmlspecialchars($u['userid']); ?></td>
                    <td title="<?php echo htmlspecialchars($u['name']); ?>"><?php echo htmlspecialchars($u['name']); ?></td>
                </tr>
            <?php } ?>
        </table>
    </div>
    <div class="ar-actions__note"><?php echo Lang::t('_EXPORT_SELECTION_NOTE', 'statistic'); ?></div>
<?php } ?>
