<div class="pui-page">
    <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:14px;">
        <div>
            <h1 style="font-size:21px;font-weight:700;color:#1a2b4a;margin:0;"><?php echo Lang::t('_DASHBOARD', 'dashboard'); ?></h1>
            <div style="font-size:12px;color:#8a9fc4;margin-top:2px;">
                <?php echo $is_godadmin
                    ? 'Vista Superadmin — dati su tutta la piattaforma'
                    : 'Vista Amministratore — dati limitati alla tua azienda/organigramma'; ?>
            </div>
        </div>
    </div>

    <div class="dash-board">

        <!-- COLONNA UTENTI -->
        <div class="dash-col">
            <div class="dash-col__title"><span class="dot dot--users"></span> <?php echo Lang::t('_USERS', 'dashboard'); ?></div>

            <div class="dash-kpi-grid">
                <div class="dash-kpi" id="dash_kpi_users_total">
                    <div class="dash-kpi__value"><?php echo (int) $user_stats['all'] - 1; ?></div>
                    <div class="dash-kpi__label">Totale caricati</div>
                </div>
                <?php if ($permissions['view_user']) { ?>
                <div class="dash-kpi" id="dash_kpi_users_online">
                    <div class="dash-kpi__value"><?php echo (int) $user_stats['now_online']; ?></div>
                    <div class="dash-kpi__label">Connessi ora</div>
                </div>
                <?php } ?>
                <div class="dash-kpi" id="dash_kpi_users_admin">
                    <div class="dash-kpi__value"><?php echo (int) $user_stats['admin']; ?></div>
                    <div class="dash-kpi__label">Amministratori</div>
                </div>
                <?php if ($is_godadmin) { ?>
                <div class="dash-kpi" id="dash_kpi_users_superadmin">
                    <div class="dash-kpi__value"><?php echo (int) $user_stats['superadmin']; ?></div>
                    <div class="dash-kpi__label">Super admin</div>
                </div>
                <?php } ?>
            </div>

            <div class="dash-tw">
                <div class="dash-tw__head">
                    <div class="dash-tw__title">Accessi <span class="dash-tw__info" title="Utenti che entrano in piattaforma ma non visionano contenuti">&#9432;</span></div>
                </div>
                <div class="dash-tw-row">
                    <div class="dash-tw-num" id="dash_access_month"><div class="dash-tw-num__v"><?php echo (int) $users_access['month']; ?></div><div class="dash-tw-num__l"><?php echo htmlspecialchars($current_month_label); ?></div></div>
                    <div class="dash-tw-num" id="dash_access_3m"><div class="dash-tw-num__v"><?php echo (int) $users_access['3months']; ?></div><div class="dash-tw-num__l">3 mesi</div></div>
                    <div class="dash-tw-num" id="dash_access_6m"><div class="dash-tw-num__v"><?php echo (int) $users_access['6months']; ?></div><div class="dash-tw-num__l">6 mesi</div></div>
                </div>
                <div class="dash-spark">
                    <?php
                    $max_access = max(1, max(array_column($users_access_trend, 'count')));
                    foreach ($users_access_trend as $idx => $pt) {
                        $h = max(8, round(($pt['count'] / $max_access) * 100));
                        $cls = ($idx === count($users_access_trend) - 1) ? 'b now' : 'b';
                        echo '<div class="' . $cls . '" style="height:' . $h . '%" title="' . htmlspecialchars($pt['label']) . ': ' . (int) $pt['count'] . '"></div>';
                    }
                    ?>
                </div>
            </div>

            <div class="dash-tw">
                <div class="dash-tw__head">
                    <div class="dash-tw__title">Utenti attivi <?php echo date('Y'); ?> <span class="dash-tw__info" title="Hanno visionato almeno un contenuto formativo">&#9432;</span></div>
                </div>
                <div class="dash-tw-row">
                    <div class="dash-tw-num" id="dash_active_month"><div class="dash-tw-num__v"><?php echo (int) $users_active['month']; ?></div><div class="dash-tw-num__l"><?php echo htmlspecialchars($current_month_label); ?></div></div>
                    <div class="dash-tw-num" id="dash_active_3m"><div class="dash-tw-num__v"><?php echo (int) $users_active['3months']; ?></div><div class="dash-tw-num__l">3 mesi</div></div>
                    <div class="dash-tw-num" id="dash_active_6m"><div class="dash-tw-num__v"><?php echo (int) $users_active['6months']; ?></div><div class="dash-tw-num__l">6 mesi</div></div>
                </div>
                <div class="dash-spark">
                    <?php
                    $max_active = max(1, max(array_column($users_active_trend, 'count')));
                    foreach ($users_active_trend as $idx => $pt) {
                        $h = max(8, round(($pt['count'] / $max_active) * 100));
                        $cls = ($idx === count($users_active_trend) - 1) ? 'b now' : 'b';
                        echo '<div class="' . $cls . '" style="height:' . $h . '%" title="' . htmlspecialchars($pt['label']) . ': ' . (int) $pt['count'] . '"></div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- COLONNA AZIENDE (placeholder, future task completes it) -->
        <div class="dash-col">
            <div class="dash-col__title"><span class="dot dot--companies"></span> Aziende</div>
            <p style="font-size:12px;color:#aebcd8;">Sezione in arrivo.</p>
        </div>

        <!-- COLONNA CORSI (placeholder, future task completes it) -->
        <div class="dash-col">
            <div class="dash-col__title"><span class="dot dot--courses"></span> Corsi</div>
            <div class="dash-kpi-grid">
                <div class="dash-kpi">
                    <div class="dash-kpi__value"><?php echo (int) $course_stats['active']; ?></div>
                    <div class="dash-kpi__label">Attivi</div>
                </div>
                <div class="dash-kpi">
                    <div class="dash-kpi__value"><?php echo (int) $course_stats['active_seven']; ?></div>
                    <div class="dash-kpi__label">In attivaz. 7gg</div>
                </div>
                <div class="dash-kpi">
                    <div class="dash-kpi__value"><?php echo (int) $course_stats['user_subscription']; ?></div>
                    <div class="dash-kpi__label">Iscrizioni</div>
                </div>
            </div>
            <p style="font-size:12px;color:#aebcd8;">Completati, certificati e grafico in arrivo.</p>
        </div>

    </div>
</div>
