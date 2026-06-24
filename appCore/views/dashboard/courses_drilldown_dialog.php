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

$titles = [
    'active' => 'Corsi attivi',
    'preparation' => 'Corsi in costruzione',
    'concluded' => 'Corsi conclusi',
    'cancelled' => 'Corsi cancellati',
    'activating' => 'Corsi in attivazione (prossimi 7 giorni)',
    'completed' => 'Completamenti',
    'certificates' => 'Certificati rilasciati',
    'subscriptions' => 'Iscrizioni',
    'category' => 'Corsi per categoria',
];
$title = isset($titles[$kind]) ? $titles[$kind] : 'Dettaglio';

$html = '<div class="dash-dialog-body"><table class="dash-table-preview">';

if ($kind === 'category') {
    $html .= '<tr><th>Corso</th><th>Iscritti</th><th>In attesa</th><th>Stato</th></tr>';
    foreach ($rows as $r) {
        $detail = '<span class="pui-badge ' . htmlspecialchars($r['detail_class']) . '">' . htmlspecialchars($r['detail']) . '</span>';
        $html .= '<tr><td>' . htmlspecialchars($r['name']) . '</td><td>' . (int) $r['enrolled'] . '</td><td>' . (int) $r['waiting'] . '</td><td>' . $detail . '</td></tr>';
    }
    $colspan = 4;
} elseif ($kind === 'active' || $kind === 'preparation' || $kind === 'concluded' || $kind === 'cancelled' || $kind === 'activating') {
    $html .= '<tr><th>Corso</th><th>' . ($kind === 'activating' ? 'Data inizio' : '') . '</th></tr>';
    foreach ($rows as $r) {
        $html .= '<tr><td>' . htmlspecialchars($r['name']) . '</td><td>' . htmlspecialchars($r['detail']) . '</td></tr>';
    }
    $colspan = 2;
} else {
    $detail_labels = [
        'completed' => 'Data completamento',
        'certificates' => 'Data rilascio',
        'subscriptions' => 'Data iscrizione',
    ];
    $html .= '<tr><th>' . Lang::t('_USERNAME', 'standard') . '</th><th>' . Lang::t('_FULLNAME', 'standard') . '</th><th>Corso</th><th>' . $detail_labels[$kind] . '</th></tr>';
    foreach ($rows as $r) {
        $html .= '<tr><td>' . htmlspecialchars($r['userid']) . '</td><td>' . htmlspecialchars($r['name']) . '</td><td>' . htmlspecialchars($r['course']) . '</td><td>' . htmlspecialchars($r['detail']) . '</td></tr>';
    }
    $colspan = 4;
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
