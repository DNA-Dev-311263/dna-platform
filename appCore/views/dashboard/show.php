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

    <div class="dash-canvas">
    <div class="dash-board">

        <!-- COLONNA UTENTI -->
        <div class="dash-col">
            <div class="dash-col__title"><span class="dot dot--users"></span> <?php echo Lang::t('_USERS', 'dashboard'); ?></div>

            <div class="dash-kpi-grid">
                <div class="dash-kpi dash-kpi--static">
                    <div class="dash-kpi__value"><?php echo (int) $user_stats['all'] - 1; ?></div>
                    <div class="dash-kpi__label">Totale caricati</div>
                </div>
                <?php if ($permissions['view_user']) { ?>
                <div class="dash-kpi" id="dash_kpi_users_online" onclick="dashOpenDrilldown('online')">
                    <div class="dash-kpi__value"><?php echo (int) $user_stats['now_online']; ?></div>
                    <div class="dash-kpi__label">Connessi ora</div>
                </div>
                <div class="dash-kpi dash-kpi--static">
                    <div class="dash-kpi__value"><?php echo (int) $user_stats['suspended']; ?></div>
                    <div class="dash-kpi__label">Sospesi</div>
                </div>
                <?php } ?>
                <div class="dash-kpi" id="dash_kpi_users_admin" onclick="dashOpenDrilldown('admin')">
                    <div class="dash-kpi__value"><?php echo (int) $user_stats['admin']; ?></div>
                    <div class="dash-kpi__label">Amministratori</div>
                </div>
                <?php if ($is_godadmin) { ?>
                <div class="dash-kpi" id="dash_kpi_users_superadmin" onclick="dashOpenDrilldown('superadmin')">
                    <div class="dash-kpi__value"><?php echo (int) $user_stats['superadmin']; ?></div>
                    <div class="dash-kpi__label">Super admin</div>
                </div>
                <?php } ?>
            </div>

            <div class="dash-tw dash-tw--grow">
                <div class="dash-tw__head">
                    <div class="dash-tw__title">Accessi <span class="dash-tw__info" title="Utenti che entrano in piattaforma ma non visionano contenuti">&#9432;</span></div>
                </div>
                <div class="dash-tw-row">
                    <div class="dash-tw-num" id="dash_access_month" onclick="dashOpenDrilldown('access','month')"><div class="dash-tw-num__v"><?php echo (int) $users_access['month']; ?></div><div class="dash-tw-num__l"><?php echo htmlspecialchars($current_month_label); ?></div></div>
                    <div class="dash-tw-num" id="dash_access_3m" onclick="dashOpenDrilldown('access','3months')"><div class="dash-tw-num__v"><?php echo (int) $users_access['3months']; ?></div><div class="dash-tw-num__l">3 mesi</div></div>
                    <div class="dash-tw-num" id="dash_access_6m" onclick="dashOpenDrilldown('access','6months')"><div class="dash-tw-num__v"><?php echo (int) $users_access['6months']; ?></div><div class="dash-tw-num__l">6 mesi</div></div>
                </div>
                <div class="dash-spark">
                    <?php
                    $max_access = max(1, max(array_column($users_access_trend, 'count')));
                    foreach ($users_access_trend as $idx => $pt) {
                        $h = round(($pt['count'] / $max_access) * 100);
                        $cls = ($idx === count($users_access_trend) - 1) ? 'b now' : 'b';
                        echo '<div class="' . $cls . '" style="height:' . $h . '%" title="' . htmlspecialchars($pt['label']) . ': ' . (int) $pt['count'] . '"></div>';
                    }
                    ?>
                </div>
                <div class="dash-spark-labels">
                    <?php foreach ($users_access_trend as $idx => $pt) {
                        $cls = ($idx === count($users_access_trend) - 1) ? 'now' : '';
                        echo '<span class="' . $cls . '">' . htmlspecialchars($pt['label']) . '</span>';
                    } ?>
                </div>
            </div>

            <div class="dash-tw dash-tw--grow">
                <div class="dash-tw__head">
                    <div class="dash-tw__title">Utenti attivi <?php echo date('Y'); ?> <span class="dash-tw__info" title="Hanno visionato almeno un contenuto formativo">&#9432;</span></div>
                </div>
                <div class="dash-tw-row">
                    <div class="dash-tw-num" id="dash_active_month" onclick="dashOpenDrilldown('active','month')"><div class="dash-tw-num__v"><?php echo (int) $users_active['month']; ?></div><div class="dash-tw-num__l"><?php echo htmlspecialchars($current_month_label); ?></div></div>
                    <div class="dash-tw-num" id="dash_active_3m" onclick="dashOpenDrilldown('active','3months')"><div class="dash-tw-num__v"><?php echo (int) $users_active['3months']; ?></div><div class="dash-tw-num__l">3 mesi</div></div>
                    <div class="dash-tw-num" id="dash_active_6m" onclick="dashOpenDrilldown('active','6months')"><div class="dash-tw-num__v"><?php echo (int) $users_active['6months']; ?></div><div class="dash-tw-num__l">6 mesi</div></div>
                </div>
                <div class="dash-spark">
                    <?php
                    $max_active = max(1, max(array_column($users_active_trend, 'count')));
                    foreach ($users_active_trend as $idx => $pt) {
                        $h = round(($pt['count'] / $max_active) * 100);
                        $cls = ($idx === count($users_active_trend) - 1) ? 'b now' : 'b';
                        echo '<div class="' . $cls . '" style="height:' . $h . '%" title="' . htmlspecialchars($pt['label']) . ': ' . (int) $pt['count'] . '"></div>';
                    }
                    ?>
                </div>
                <div class="dash-spark-labels">
                    <?php foreach ($users_active_trend as $idx => $pt) {
                        $cls = ($idx === count($users_active_trend) - 1) ? 'now' : '';
                        echo '<span class="' . $cls . '">' . htmlspecialchars($pt['label']) . '</span>';
                    } ?>
                </div>
            </div>
        </div>

        <!-- COLONNA AZIENDE -->
        <div class="dash-col">
            <div class="dash-col__title"><span class="dot dot--companies"></span> Aziende</div>

            <div class="dash-kpi-grid">
                <div class="dash-kpi" onclick="dashOpenCompanyDrilldown(0)">
                    <div class="dash-kpi__value"><?php echo (int) $companies_count; ?></div>
                    <div class="dash-kpi__label">Aziende caricate</div>
                </div>
            </div>

            <div class="dash-tw dash-tw--grow">
                <div class="dash-tw__head"><div class="dash-tw__title">Nuove aziende — ultimi 6 mesi</div></div>
                <div class="dash-spark companies">
                    <?php
                    $max_comp = max(1, max(array_column($companies_trend, 'count')));
                    foreach ($companies_trend as $idx => $pt) {
                        $h = round(($pt['count'] / $max_comp) * 100);
                        $cls = ($idx === count($companies_trend) - 1) ? 'b now' : 'b';
                        echo '<div class="' . $cls . '" style="height:' . $h . '%" title="' . htmlspecialchars($pt['label']) . ': ' . (int) $pt['count'] . '"></div>';
                    }
                    ?>
                </div>
                <div class="dash-spark-labels">
                    <?php foreach ($companies_trend as $idx => $pt) {
                        $cls = ($idx === count($companies_trend) - 1) ? 'now' : '';
                        echo '<span class="' . $cls . '">' . htmlspecialchars($pt['label']) . '</span>';
                    } ?>
                </div>
                <div class="dash-drill-note">Conteggio storico approssimato: il tracciamento delle date e' iniziato il 19/06/2026</div>
            </div>

            <div class="dash-tw">
                <div class="dash-tw__head"><div class="dash-tw__title">Aziende — elenco rapido</div></div>
                <table class="dash-table-preview">
                    <tr><th>Azienda</th><th>Utenti</th><th>Nodi</th></tr>
                    <?php foreach ($companies_list as $c) { ?>
                        <tr>
                            <td class="dash-link" onclick="dashOpenCompanyDrilldown(<?php echo (int) $c['idOrg']; ?>)"><?php echo htmlspecialchars($c['name']); ?></td>
                            <td><?php echo (int) $c['users_count']; ?></td>
                            <td><?php echo (int) $c['nodes_count']; ?></td>
                        </tr>
                    <?php } ?>
                </table>
                <div class="dash-drill-note">&#8627; click su un'azienda per esplorare i nodi figli e i sotto-nodi</div>
            </div>
        </div>

        <!-- COLONNA CORSI -->
        <div class="dash-col">
            <div class="dash-col__title"><span class="dot dot--courses"></span> Corsi</div>

            <div class="dash-kpi-grid">
                <div class="dash-kpi" onclick="dashOpenCoursesDrilldown('active')">
                    <div class="dash-kpi__value"><?php echo (int) $courses_status['active']; ?></div>
                    <div class="dash-kpi__label">Attivi</div>
                </div>
                <div class="dash-kpi" onclick="dashOpenCoursesDrilldown('preparation')">
                    <div class="dash-kpi__value"><?php echo (int) $courses_status['preparation']; ?></div>
                    <div class="dash-kpi__label">In costruzione</div>
                </div>
                <div class="dash-kpi" onclick="dashOpenCoursesDrilldown('concluded')">
                    <div class="dash-kpi__value"><?php echo (int) $courses_status['concluded']; ?></div>
                    <div class="dash-kpi__label">Conclusi</div>
                </div>
                <div class="dash-kpi" onclick="dashOpenCoursesDrilldown('cancelled')">
                    <div class="dash-kpi__value"><?php echo (int) $courses_status['cancelled']; ?></div>
                    <div class="dash-kpi__label">Cancellati</div>
                </div>
            </div>

            <div class="dash-kpi-grid">
                <div class="dash-kpi" onclick="dashOpenCoursesDrilldown('activating')">
                    <div class="dash-kpi__value"><?php echo (int) $course_stats['active_seven']; ?></div>
                    <div class="dash-kpi__label">In attivaz. 7gg</div>
                </div>
                <div class="dash-kpi" onclick="dashOpenCoursesDrilldown('subscriptions')">
                    <div class="dash-kpi__value"><?php echo (int) $course_stats['user_subscription']; ?></div>
                    <div class="dash-kpi__label">Iscrizioni</div>
                </div>
                <div class="dash-kpi" onclick="dashOpenCoursesDrilldown('certificates')">
                    <div class="dash-kpi__value"><?php echo (int) $certificates_issued; ?></div>
                    <div class="dash-kpi__label">Certificati emessi</div>
                </div>
            </div>

            <div class="dash-tw">
                <div class="dash-tw__head"><div class="dash-tw__title">Corsi più visti</div></div>
                <table class="dash-table-preview">
                    <tr><th>Corso</th><th>Iscritti</th><th>Stato</th></tr>
                    <?php foreach ($top_courses as $tc) { ?>
                        <tr>
                            <td class="dash-link" onclick="dashOpenCourseDrilldown(<?php echo (int) $tc['idCourse']; ?>)"><?php echo htmlspecialchars($tc['name']); ?></td>
                            <td><?php echo (int) $tc['enrolled']; ?></td>
                            <td><span class="pui-badge <?php echo htmlspecialchars($tc['status_class']); ?>"><?php echo htmlspecialchars($tc['status_label']); ?></span></td>
                        </tr>
                    <?php } ?>
                    <?php if (empty($top_courses)) { ?>
                        <tr><td colspan="3"><?php echo Lang::t('_NONE', 'standard'); ?></td></tr>
                    <?php } ?>
                </table>
            </div>

            <div class="dash-tw">
                <div class="dash-tw__head"><div class="dash-tw__title">Corsi per categoria</div></div>
                <table class="dash-table-preview">
                    <tr><th>Categoria</th><th>Corsi</th><th>Iscritti</th><th>In attesa</th></tr>
                    <?php foreach ($courses_by_category as $cc) { ?>
                        <tr>
                            <td class="dash-link" onclick="dashOpenCoursesDrilldown('category', <?php echo (int) $cc['idCategory']; ?>)"><?php echo htmlspecialchars($cc['name']); ?></td>
                            <td><?php echo (int) $cc['count']; ?></td>
                            <td><?php echo (int) $cc['total_enrolled']; ?></td>
                            <td><?php echo (int) $cc['total_waiting']; ?></td>
                        </tr>
                    <?php } ?>
                    <?php if (empty($courses_by_category)) { ?>
                        <tr><td colspan="4"><?php echo Lang::t('_NONE', 'standard'); ?></td></tr>
                    <?php } ?>
                </table>
            </div>
        </div>

    </div>
    </div>
    <?php
    $this->widget('dialog', [
        'id' => 'users_drilldown_dialog',
        'width' => '600px',
        'dynamicContent' => true,
        'ajaxUrl' => 'function(){ return YAHOO.util.Dom.get("dash_drilldown_url").value; }',
        'dynamicAjaxUrl' => true,
        'constrainToViewport' => false,
        'confirmOnly' => true,
        'fixedCenter' => true,
        'callObjectFunc' => 'Dashboard.oDialogCaller',
    ]);
    ?>
    <input type="hidden" id="dash_drilldown_url" value="" />
    <script type="text/javascript">
        function dashOpenDrilldown(kind, period) {
            var url = 'ajax.adm_server.php?r=adm/dashboard/users_drilldown&kind=' + kind + (period ? '&period=' + period : '');
            YAHOO.util.Dom.get('dash_drilldown_url').value = url;
            Dashboard.oDialogCaller['users_drilldown_dialog']();
        }
    </script>

    <?php
    $this->widget('dialog', [
        'id' => 'companies_drilldown_dialog',
        'width' => '600px',
        'dynamicContent' => true,
        'ajaxUrl' => 'function(){ return YAHOO.util.Dom.get("dash_company_drilldown_url").value; }',
        'dynamicAjaxUrl' => true,
        'constrainToViewport' => false,
        'confirmOnly' => true,
        'fixedCenter' => true,
        'callObjectFunc' => 'Dashboard.oDialogCaller',
    ]);
    ?>
    <input type="hidden" id="dash_company_drilldown_url" value="" />
    <script type="text/javascript">
        function dashOpenCompanyDrilldown(idOrg) {
            var url = 'ajax.adm_server.php?r=adm/dashboard/companies_drilldown&idOrg=' + idOrg;
            YAHOO.util.Dom.get('dash_company_drilldown_url').value = url;
            Dashboard.oDialogCaller['companies_drilldown_dialog']();
        }
    </script>

    <?php
    $this->widget('dialog', [
        'id' => 'course_drilldown_dialog',
        'width' => '650px',
        'dynamicContent' => true,
        'ajaxUrl' => 'function(){ return YAHOO.util.Dom.get("dash_course_drilldown_url").value; }',
        'dynamicAjaxUrl' => true,
        'constrainToViewport' => false,
        'confirmOnly' => true,
        'fixedCenter' => true,
        'callObjectFunc' => 'Dashboard.oDialogCaller',
    ]);
    ?>
    <input type="hidden" id="dash_course_drilldown_url" value="" />
    <script type="text/javascript">
        function dashOpenCourseDrilldown(idCourse) {
            var url = 'ajax.adm_server.php?r=adm/dashboard/course_drilldown&idCourse=' + idCourse;
            YAHOO.util.Dom.get('dash_course_drilldown_url').value = url;
            Dashboard.oDialogCaller['course_drilldown_dialog']();
        }
    </script>

    <?php
    $this->widget('dialog', [
        'id' => 'courses_drilldown_dialog',
        'width' => '650px',
        'dynamicContent' => true,
        'ajaxUrl' => 'function(){ return YAHOO.util.Dom.get("dash_courses_drilldown_url").value; }',
        'dynamicAjaxUrl' => true,
        'constrainToViewport' => false,
        'confirmOnly' => true,
        'fixedCenter' => true,
        'callObjectFunc' => 'Dashboard.oDialogCaller',
    ]);
    ?>
    <input type="hidden" id="dash_courses_drilldown_url" value="" />
    <script type="text/javascript">
        function dashOpenCoursesDrilldown(kind, idCategory) {
            var url = 'ajax.adm_server.php?r=adm/dashboard/courses_drilldown&kind=' + kind + (idCategory ? '&idCategory=' + idCategory : '');
            YAHOO.util.Dom.get('dash_courses_drilldown_url').value = url;
            Dashboard.oDialogCaller['courses_drilldown_dialog']();
        }
    </script>
</div>
