<?php

/*
 * FORMA - The E-Learning Suite
 *
 * Copyright (c) 2013-2023 (Forma)
 * https://www.formalms.org
 * License https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 *
 * from docebo 4.0.5 CE 2008-2012 (c) docebo
 * License https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 */

$html = '<div class="dash-dialog-body"><table class="dash-table-preview">';
$html .= '<tr><th>' . Lang::t('_USERNAME', 'standard') . '</th><th>' . Lang::t('_FULLNAME', 'standard') . '</th><th>Azienda</th><th>Stato</th></tr>';
foreach ($rows as $r) {
    $badge_class = $r['status_done'] ? 'pui-badge--success' : 'pui-badge--neutral';
    $html .= '<tr><td>' . htmlspecialchars($r['userid']) . '</td><td>' . htmlspecialchars($r['name']) . '</td><td>' . htmlspecialchars($r['company']) . '</td>'
        . '<td><span class="pui-badge ' . $badge_class . '">' . htmlspecialchars($r['status_label']) . '</span></td></tr>';
}
if (empty($rows)) {
    $html .= '<tr><td colspan="4">' . Lang::t('_NONE', 'standard') . '</td></tr>';
}
$html .= '</table></div>';

if (isset($json)) {
    echo $json->encode([
        'success' => true,
        'header' => 'Iscritti al corso',
        'body' => $html,
    ]);
} else {
    echo getTitleArea('Iscritti al corso');
    echo '<div class="std_block">' . $html . '</div>';
}
