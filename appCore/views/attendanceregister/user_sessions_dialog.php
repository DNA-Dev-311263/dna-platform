<?php

$html = '<div class="dash-dialog-body">';
$html .= '<table class="dash-table-preview">';
$html .= '<tr><th>' . Lang::t('_SESSION_STARTED', 'statistic') . '</th>'
    . '<th>' . Lang::t('_LAST_ACTION_AT', 'statistic') . '</th>'
    . '<th>' . Lang::t('_HOW_MUCH_TIME', 'statistic') . '</th>'
    . '<th>' . Lang::t('_NUMBER_OF_OP', 'statistic') . '</th></tr>';

foreach ($data['rows'] as $row) {
    $html .= '<tr><td>' . htmlspecialchars($row['start']) . '</td>'
        . '<td>' . htmlspecialchars($row['end']) . '</td>'
        . '<td>' . htmlspecialchars($row['duration']) . '</td>'
        . '<td>' . (int) $row['num_op'] . '</td></tr>';
}

if (empty($data['rows'])) {
    $html .= '<tr><td colspan="4">' . Lang::t('_NO_DATA', 'standard') . '</td></tr>';
}

$html .= '<tr style="font-weight:700;"><td>' . Lang::t('_TOTAL', 'standard') . '</td>'
    . '<td>' . Lang::t('_NUMBER_OF_ACCESS', 'statistic') . ': ' . (int) $data['session_count'] . '</td>'
    . '<td>' . htmlspecialchars($data['total_duration']) . '</td>'
    . '<td></td></tr>';
$html .= '</table>';

$html .= '<div style="margin-top:14px;">'
    . '<a class="pui-btn pui-btn--ghost" href="ajax.adm_server.php?r=adm/attendanceregister/export_user&idCourse=' . (int) FormaLms\lib\Get::req('idCourse', DOTY_INT, 0) . '&idUser=' . (int) FormaLms\lib\Get::req('idUser', DOTY_INT, 0) . '">'
    . Lang::t('_EXPORT_XLS', 'standard') . '</a>'
    . '</div>';
$html .= '</div>';

if (isset($json)) {
    echo $json->encode([
        'success' => true,
        'header' => htmlspecialchars($fullname),
        'body' => $html,
    ]);
} else {
    echo getTitleArea(htmlspecialchars($fullname));
    echo '<div class="std_block">' . $html . '</div>';
}
