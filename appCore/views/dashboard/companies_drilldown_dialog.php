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

$html = '<div class="dash-dialog-body">';
$html .= '<p style="font-size:13px;color:#5a6a85;margin-bottom:10px;">' . htmlspecialchars($current_name) . '</p>';
$html .= '<table class="dash-table-preview"><tr><th>Nodo</th><th></th></tr>';
foreach ($children as $c) {
    $html .= '<tr><td>' . htmlspecialchars($c['name']) . '</td><td>';
    if ($c['has_children']) {
        $html .= '<a href="javascript:void(0)" onclick="dashOpenCompanyDrilldown(' . (int) $c['idOrg'] . ')">&#128269; esplora</a>';
    }
    $html .= '</td></tr>';
}
if (empty($children)) {
    $html .= '<tr><td colspan="2">' . Lang::t('_NONE', 'standard') . '</td></tr>';
}
$html .= '</table></div>';

if (isset($json)) {
    echo $json->encode([
        'success' => true,
        'header' => 'Aziende — dettaglio',
        'body' => $html,
    ]);
} else {
    echo getTitleArea('Aziende — dettaglio');
    echo '<div class="std_block">' . $html . '</div>';
}
