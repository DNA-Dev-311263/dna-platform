<?php

defined('IN_FORMA') or exit('Direct access is forbidden.');

echo Util::get_css(FormaLms\lib\Get::rel_path('base') . '/css/pandp-ui.css', true, true);
echo getTitleArea('Statistiche');
?>
<div class="std_block pui-page">
<?php if (!$stats) : ?>
    <p>Nessun dato di fruizione disponibile per questo corso.</p>
<?php else : ?>

    <div class="pui-stats-row">
        <div class="pui-stat-box">
            <div class="pui-stat-box__value"><?php echo $stats->date_inscr ? htmlspecialchars(date('d/m/Y', strtotime($stats->date_inscr))) : '-'; ?></div>
            <div class="pui-stat-box__label">Iscritto il</div>
        </div>
        <div class="pui-stat-box">
            <div class="pui-stat-box__value"><?php echo htmlspecialchars($stats->medium_time_formatted); ?></div>
            <div class="pui-stat-box__label">Tempo medio corso</div>
        </div>
        <div class="pui-stat-box <?php echo $stats->gap_seconds < 0 ? 'pui-stat-box--error' : 'pui-stat-box--success'; ?>">
            <div class="pui-stat-box__value"><?php echo htmlspecialchars($stats->total_time_formatted); ?></div>
            <div class="pui-stat-box__label">Tempo fruito (totale)</div>
            <div style="font-size:11px; margin-top:4px;">
                <?php echo htmlspecialchars($stats->gap_formatted); ?> dal tempo medio
            </div>
        </div>
    </div>

    <div class="pui-stats-row">
        <div class="pui-stat-box">
            <div class="pui-stat-box__value"><?php echo (int) $stats->materials_total; ?></div>
            <div class="pui-stat-box__label">Materiali</div>
        </div>
        <div class="pui-stat-box pui-stat-box--success">
            <div class="pui-stat-box__value"><?php echo (int) $stats->materials_completed; ?></div>
            <div class="pui-stat-box__label">Completati</div>
        </div>
        <div class="pui-stat-box <?php echo $stats->materials_remaining > 0 ? 'pui-stat-box--error' : 'pui-stat-box--success'; ?>">
            <div class="pui-stat-box__value"><?php echo (int) $stats->materials_remaining; ?></div>
            <div class="pui-stat-box__label">Da completare</div>
        </div>
    </div>

    <table class="table table-bordered" style="width:100%;">
        <thead>
            <tr>
                <th>Modulo</th>
                <th>Stato</th>
                <th>Progresso</th>
                <th>Tempo</th>
                <th>Sessioni</th>
                <th>Punteggio</th>
                <th>Tentativi</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($stats->lo_list as $row) : ?>
            <tr>
                <td><?php echo htmlspecialchars($row->title); ?></td>
                <td style="text-align:center;">
                    <span class="pui-badge pui-badge--<?php echo htmlspecialchars($row->badge_class); ?>"><?php echo htmlspecialchars($row->status_label); ?></span>
                </td>
                <td style="text-align:center;"><?php echo (int) $row->percent; ?>%</td>
                <td style="text-align:center;"><?php echo htmlspecialchars($row->time_formatted); ?></td>
                <td style="text-align:center;"><?php echo (int) $row->sessions; ?></td>
                <td style="text-align:center;"><?php echo htmlspecialchars($row->score); ?></td>
                <td style="text-align:center;"><?php echo htmlspecialchars($row->attempts_label); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

<?php endif; ?>
</div>
