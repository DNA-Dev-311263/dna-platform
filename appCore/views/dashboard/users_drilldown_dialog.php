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

$show_date = ($kind === 'access' || $kind === 'active');
$date_label = $kind === 'active' ? 'Data accesso contenuto' : 'Data accesso';
$colspan = $show_date ? 4 : 3;

$html = '<div class="dash-dialog-body"><table class="dash-table-preview">';
$html .= '<tr><th>' . Lang::t('_USERNAME', 'standard') . '</th><th>' . Lang::t('_FULLNAME', 'standard') . '</th><th>Azienda</th>' . ($show_date ? '<th>' . $date_label . '</th>' : '') . '</tr>';
foreach ($rows as $r) {
    $html .= '<tr><td>' . htmlspecialchars($r['userid']) . '</td><td>' . htmlspecialchars($r['name']) . '</td><td>' . htmlspecialchars($r['company']) . '</td>'
        . ($show_date ? '<td>' . htmlspecialchars($r['last_date']) . '</td>' : '') . '</tr>';
}
if (empty($rows)) {
    $html .= '<tr><td colspan="' . $colspan . '">' . Lang::t('_NONE', 'standard') . '</td></tr>';
}
$html .= '</table></div>';

if (isset($json)) {
    echo $json->encode([
        'success' => true,
        'header' => $title,
        'body' => $html,
    ]);
} else {
    echo getTitleArea($title);
    echo '<div class="std_block">' . $html . '</div>';
}
