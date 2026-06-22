<?php if (empty($sections)) { ?>
    <div class="ar-empty"><?php echo Lang::t('_NO_DATA', 'standard'); ?></div>
<?php } ?>
<?php foreach ($sections as $section) { ?>
    <div class="ar-print-section">
        <h2 class="ar-print-section__title"><?php echo htmlspecialchars($section['displayName']); ?> (<?php echo htmlspecialchars($section['username']); ?>)</h2>
        <table class="ar-print-table">
            <tr>
                <th><?php echo Lang::t('_DATE', 'standard'); ?></th>
                <th><?php echo Lang::t('_FIRST_ENTRY', 'statistic'); ?></th>
                <th><?php echo Lang::t('_LAST_EXIT', 'statistic'); ?></th>
                <th><?php echo Lang::t('_HOW_MUCH_TIME', 'statistic'); ?></th>
                <th><?php echo Lang::t('_NUMBER_OF_OP', 'statistic'); ?></th>
            </tr>
            <?php foreach ($section['data']['rows'] as $row) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                    <td><?php echo htmlspecialchars($row['first_entry']); ?></td>
                    <td><?php echo htmlspecialchars($row['last_exit']); ?></td>
                    <td><?php echo htmlspecialchars($row['duration']); ?></td>
                    <td><?php echo (int) $row['num_op']; ?></td>
                </tr>
                <?php if ($detailed) { foreach ($row['sessions'] as $s) { ?>
                    <tr class="ar-print-subrow">
                        <td></td>
                        <td><?php echo htmlspecialchars($s['enter']); ?></td>
                        <td><?php echo htmlspecialchars($s['exit']); ?></td>
                        <td><?php echo htmlspecialchars($s['duration']); ?></td>
                        <td><?php echo (int) $s['num_op']; ?></td>
                    </tr>
                <?php } } ?>
            <?php } ?>
            <?php if (empty($section['data']['rows'])) { ?>
                <tr><td colspan="5"><?php echo Lang::t('_NO_DATA', 'standard'); ?></td></tr>
            <?php } ?>
            <tr class="ar-print-total">
                <td><b><?php echo Lang::t('_TOTAL', 'standard'); ?></b></td>
                <td><b><?php echo Lang::t('_NUMBER_OF_ACCESS', 'statistic'); ?>: <?php echo (int) $section['data']['session_count']; ?></b></td>
                <td></td>
                <td><b><?php echo htmlspecialchars($section['data']['total_duration']); ?></b></td>
                <td></td>
            </tr>
        </table>
    </div>
<?php } ?>
