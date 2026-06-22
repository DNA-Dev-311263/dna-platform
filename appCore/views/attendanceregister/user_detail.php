<div class="ar-detail__head">
    <div class="ar-detail__title"><?php echo htmlspecialchars($fullname); ?></div>
    <div class="ar-detail__sub"><?php echo htmlspecialchars($username); ?></div>
</div>

<div class="ar-detail__totals">
    <div><div class="ar-tot__v"><?php echo (int) $data['session_count']; ?></div><div class="ar-tot__l"><?php echo Lang::t('_NUMBER_OF_ACCESS', 'statistic'); ?></div></div>
    <div><div class="ar-tot__v"><?php echo (int) $data['day_count']; ?></div><div class="ar-tot__l"><?php echo Lang::t('_DAY_COUNT', 'statistic'); ?></div></div>
    <div><div class="ar-tot__v"><?php echo htmlspecialchars($data['total_duration']); ?></div><div class="ar-tot__l"><?php echo Lang::t('_HOW_MUCH_TIME', 'statistic'); ?></div></div>
</div>

<div class="ar-detail__body">
    <table>
        <tr>
            <th><?php echo Lang::t('_DATE', 'standard'); ?></th>
            <th><?php echo Lang::t('_FIRST_ENTRY', 'statistic'); ?></th>
            <th><?php echo Lang::t('_LAST_EXIT', 'statistic'); ?></th>
            <th><?php echo Lang::t('_HOW_MUCH_TIME', 'statistic'); ?></th>
            <th><?php echo Lang::t('_NUMBER_OF_OP', 'statistic'); ?></th>
        </tr>
        <?php foreach ($data['rows'] as $row) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['date']); ?></td>
                <td><?php echo htmlspecialchars($row['first_entry']); ?></td>
                <td><?php echo htmlspecialchars($row['last_exit']); ?></td>
                <td><?php echo htmlspecialchars($row['duration']); ?></td>
                <td><?php echo (int) $row['num_op']; ?></td>
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
</div>
