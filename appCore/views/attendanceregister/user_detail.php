<div class="ar-detail__head">
    <div class="ar-detail__title"><?php echo htmlspecialchars($fullname); ?></div>
    <div class="ar-detail__sub"><?php echo htmlspecialchars($username); ?></div>
</div>

<div class="ar-detail__totals">
    <div><div class="ar-tot__v"><?php echo (int) $data['session_count']; ?></div><div class="ar-tot__l"><?php echo Lang::t('_NUMBER_OF_ACCESS', 'statistic'); ?></div></div>
    <div><div class="ar-tot__v"><?php echo (int) $data['day_count']; ?></div><div class="ar-tot__l"><?php echo Lang::t('_DAY_COUNT', 'statistic'); ?></div></div>
    <div><div class="ar-tot__v"><?php echo htmlspecialchars($data['total_duration']); ?></div><div class="ar-tot__l"><?php echo Lang::t('_HOW_MUCH_TIME', 'statistic'); ?></div></div>
</div>

<div class="ar-detail__body" id="ar_print_area">
    <table>
        <tr>
            <th><?php echo Lang::t('_DATE', 'standard'); ?></th>
            <th><?php echo Lang::t('_FIRST_ENTRY', 'statistic'); ?></th>
            <th><?php echo Lang::t('_LAST_EXIT', 'statistic'); ?></th>
            <th><?php echo Lang::t('_HOW_MUCH_TIME', 'statistic'); ?></th>
            <th><?php echo Lang::t('_NUMBER_OF_OP', 'statistic'); ?></th>
        </tr>
        <?php foreach ($data['rows'] as $row) { ?>
            <tr class="ar-day-row" onclick="arToggleDay(this)">
                <td><span class="ar-caret">&#9656;</span> <?php echo htmlspecialchars($row['date']); ?></td>
                <td><?php echo htmlspecialchars($row['first_entry']); ?></td>
                <td><?php echo htmlspecialchars($row['last_exit']); ?></td>
                <td><?php echo htmlspecialchars($row['duration']); ?></td>
                <td><?php echo (int) $row['num_op']; ?></td>
            </tr>
            <tr class="ar-day-detail" style="display:none;">
                <td colspan="5">
                    <table class="ar-day-detail__table">
                        <tr>
                            <th><?php echo Lang::t('_ENTRY', 'statistic'); ?></th>
                            <th><?php echo Lang::t('_EXIT', 'statistic'); ?></th>
                            <th><?php echo Lang::t('_HOW_MUCH_TIME', 'statistic'); ?></th>
                            <th><?php echo Lang::t('_NUMBER_OF_OP', 'statistic'); ?></th>
                        </tr>
                        <?php foreach ($row['sessions'] as $s) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['enter']); ?></td>
                                <td><?php echo htmlspecialchars($s['exit']); ?></td>
                                <td><?php echo htmlspecialchars($s['duration']); ?></td>
                                <td><?php echo (int) $s['num_op']; ?></td>
                            </tr>
                        <?php } ?>
                    </table>
                </td>
            </tr>
        <?php } ?>
        <?php if (empty($data['rows'])) { ?>
            <tr><td colspan="5"><?php echo Lang::t('_NO_DATA', 'standard'); ?></td></tr>
        <?php } ?>
    </table>
</div>

<div class="ar-detail__foot">
    <a class="pui-btn pui-btn--ghost" href="ajax.adm_server.php?r=adm/attendanceregister/export_user&idCourse=<?php echo (int) $idCourse; ?>&idUser=<?php echo (int) $idUser; ?>&authentic_request=<?php echo urlencode(Util::getSignature()); ?>">
        <?php echo Lang::t('_EXPORT_XLS', 'standard'); ?>
    </a>
    <button type="button" class="pui-btn pui-btn--ghost" onclick="arPrint(false)"><?php echo Lang::t('_PRINT_SUMMARY', 'statistic'); ?></button>
    <button type="button" class="pui-btn pui-btn--ghost" onclick="arPrint(true)"><?php echo Lang::t('_PRINT_DETAILED', 'statistic'); ?></button>
</div>
