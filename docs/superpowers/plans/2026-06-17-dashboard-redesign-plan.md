# Dashboard Backoffice Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the current backoffice welcome page (`appCore/views/dashboard/show.php`) with a renamed, redesigned "Dashboard" showing Utenti / Aziende / Corsi KPIs in a single-screen 3-column layout, with drill-down dialogs, scoped correctly for Superadmin vs Admin.

**Architecture:** Same route/controller (`DashboardAdmController` + `DashboardAdm.php`). New model methods follow the existing `users_filter`/`courses_filter` scoping pattern already used by `getUsersStats()`/`getCoursesStats()`. New view markup reuses the `pandp-ui.css` design system, extended with a `dash-*` class block. Drill-downs reuse the existing YUI `dialog` widget + `xxxTask()` AJAX controller pattern already used for "Stato utente" / "Certificati".

**Tech Stack:** PHP 7.4 (procedural-style MVC, no Twig for this view), MySQL (raw SQL via `$this->db->query()` / `%table_prefix%` placeholders), YUI widgets (dialog), Chartist.js already loaded by the controller. No automated test framework is exercised anywhere in this codebase for this kind of fix — verification is manual: deploy the changed file with `sudo install`, then check the live page in the browser, exactly like every other change made on this project this far. Every task below ends with a manual verification step instead of an automated test run.

**Spec:** `docs/superpowers/specs/2026-06-17-dashboard-redesign-design.md`

**Known limitation accepted in this plan:** `core_org_chart_tree` (the org-chart table) has no creation-date column, so "nuove aziende per mese" cannot be computed from history that already exists. Task 8 adds a `date_created` column via `ALTER TABLE`, backfilled to `NOW()` for the rows that exist today (there are only 3 org nodes in the current dataset). The 6-month trend will therefore show all of today's companies bucketed in the current month until new ones are created going forward — this is called out in the UI copy, not hidden.

---

## File Map

| File | Change |
|---|---|
| `core_menu` (DB) | Disable "Impostazioni Dashboard" row |
| `appCore/views/dashboard/show.php` | Rewritten: page renamed to "Dashboard", "Link veloci" removed, new 3-column layout |
| `css/pandp-ui.css` | Append `dash-*` class block at end of file |
| `appCore/models/DashboardAdm.php` | New methods for Utenti time-windowed stats, Aziende stats/tree, Corsi completed/certificates/trend/top-viewed |
| `appCore/controllers/DashboardAdmController.php` | `show()` wired to pass new data; new `xxxTask()` methods for drill-down dialogs |
| `appCore/views/dashboard/*.php` (new files) | New dialog view partials for drill-down content |
| `core_org_chart_tree` (DB) | New `date_created` column (migration) |

---

## Phase 1 — Cleanup

### Task 1: Disable the old "Impostazioni Dashboard" menu entry

**Files:**
- DB only: `core_menu` table

- [ ] **Step 1: Run the disable command**

```bash
mysql -h localhost -u pandpuser -p'P@ndp_Dev_2016!' pandp -e "UPDATE core_menu SET is_active = 'false' WHERE idMenu = 601 AND name = '_DASHBOARD_CONFIGURATION';"
```

- [ ] **Step 2: Verify**

```bash
mysql -h localhost -u pandpuser -p'P@ndp_Dev_2016!' pandp -e "SELECT idMenu, name, is_active FROM core_menu WHERE idMenu = 601;"
```

Expected: `is_active` = `false`. No code was touched — `DashboardsettingsAdmController` and its views stay on disk untouched, simply unreachable from the menu.

- [ ] **Step 3: Manual check**

Log in to the backoffice as GodAdmin, open **Configurazione**: the "Impostazioni Dashboard" entry must no longer appear.

---

### Task 2: Remove "Link veloci" block and add page title

**Files:**
- Modify: `appCore/views/dashboard/show.php:1-315` (delete), top of file (add header)
- Modify: `appCore/controllers/DashboardAdmController.php:62-67` (add CSS link)

- [ ] **Step 1: Add the pandp-ui.css link in the controller**

In `appCore/controllers/DashboardAdmController.php`, inside `show()`, right after the existing `Util::get_css(...)` calls (after line 67, before `$charts_num_days = 7;`):

```php
        Util::get_css(FormaLms\lib\Get::rel_path('base') . '/css/pandp-ui.css', true, true);
```

- [ ] **Step 2: Delete the "Link veloci" column from the view**

In `appCore/views/dashboard/show.php`, delete lines 1 through 315 (the entire `<div class="yui-g"><div class="yui-u first">...Link veloci...Certificate...Report...Support Site...</div></div>` block — everything up to and including the line that closes the outer `yui-g` wrapper, right before `<div class="yui-u">` that starts the Users stats column).

After deletion, the file must start directly with what was line 316:
```php
<div class="yui-u">
    <div class="inline_block_big">
        <h2 class="heading"><?php echo Lang::t('_USERS', 'dashboard'); ?></h2>
```

- [ ] **Step 3: Add the page title at the very top of the file**

Insert this as the new first line of `appCore/views/dashboard/show.php` (before the `<div class="yui-u">` that now starts the file):

```php
<div class="pui-page">
    <h1 style="font-size:22px;font-weight:700;color:#1a2b4a;margin:0 0 16px;">Dashboard</h1>
</div>
```

(This wrapper is temporary scaffolding — Task 5 replaces the whole file body, including this line, with the final 3-column layout. It exists so this task is independently testable.)

- [ ] **Step 4: Deploy**

```bash
sudo install -o www-data -g www-data -m 644 /tmp/show.php /var/www/pandp/appCore/views/dashboard/show.php
sudo install -o www-data -g www-data -m 644 /tmp/DashboardAdmController.php /var/www/pandp/appCore/controllers/DashboardAdmController.php
```

- [ ] **Step 5: Manual check**

Reload `index.php?r=adm/dashboard/show`. Confirm: a "Dashboard" heading appears at the top, the "Link veloci" box is gone, the Utenti/Corsi stat blocks (old style, unchanged) still render below it without PHP errors.

- [ ] **Step 6: Commit**

```bash
cd /var/www/pandp && sudo -u www-data git add appCore/views/dashboard/show.php appCore/controllers/DashboardAdmController.php
sudo -u www-data git commit -m "dashboard: rimuovi Link veloci, rinomina pagina in Dashboard"
sudo -u www-data git push
```

---

## Phase 2 — Layout base + Sezione Utenti

### Task 3: Append the `dash-*` CSS block to pandp-ui.css

**Files:**
- Modify: `css/pandp-ui.css` (append at end of file)

- [ ] **Step 1: Append this block at the end of `css/pandp-ui.css`**

```css

/* =============================================================
   Dashboard — 3-column KPI board
   ============================================================= */
.dash-board { display:grid; grid-template-columns: 1fr 1fr 1.3fr; gap: 14px; align-items:start; }

.dash-col { background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06); padding:16px 16px 14px; }
.dash-col__title { font-size:12px; font-weight:700; color:#1a2b4a; text-transform:uppercase; letter-spacing:.04em; display:flex; align-items:center; gap:7px; margin-bottom:12px; }
.dash-col__title .dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
.dot--users { background:#1a6ef7; }
.dot--companies { background:#28a745; }
.dot--courses { background:#f0a93e; }

.dash-kpi-grid { display:grid; grid-template-columns: 1fr 1fr; gap:8px; margin-bottom:12px; }
.dash-kpi-grid--3 { grid-template-columns: 1fr 1fr 1fr; }
.dash-kpi { background:#f8fbff; border:1px solid #e7eef8; border-radius:9px; padding:9px 6px; text-align:center; cursor:pointer; transition: box-shadow .12s, transform .12s; }
.dash-kpi:hover { box-shadow:0 4px 12px rgba(26,110,247,.18); transform: translateY(-1px); background:#fff; }
.dash-kpi__value { font-size:19px; font-weight:700; color:#1a2b4a; line-height:1.05; }
.dash-kpi__label { font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.01em; color:#8a9fc4; margin-top:3px; line-height:1.25; }

.dash-tw { border-top: 1px solid #eef1f8; padding-top: 10px; margin-top: 4px; }
.dash-tw__head { display:flex; align-items:center; justify-content:space-between; margin-bottom:7px; }
.dash-tw__title { font-size:11px; font-weight:700; color:#2c4a7a; }
.dash-tw__info { font-size:9.5px; color:#aebcd8; cursor:help; }
.dash-tw-row { display:flex; gap:6px; }
.dash-tw-num { flex:1; text-align:center; background:#f8fbff; border-radius:7px; padding:6px 2px; border:1px solid #eef1f8; cursor:pointer; }
.dash-tw-num:hover { background:#eef3ff; }
.dash-tw-num__v { font-size:15px; font-weight:700; color:#1a2b4a; }
.dash-tw-num__l { font-size:8.5px; color:#9aafca; text-transform:uppercase; font-weight:600; margin-top:1px; }

.dash-spark { display:flex; align-items:flex-end; gap:3px; height:34px; margin-top:8px; }
.dash-spark .b { flex:1; background:#cfe0fb; border-radius:2px 2px 0 0; }
.dash-spark .b.now { background:#1a6ef7; }
.dash-spark.companies .b { background:#cdebd9; }
.dash-spark.companies .b.now { background:#28a745; }

.dash-spark-dual { display:flex; align-items:flex-end; gap:6px; height:48px; margin-top:8px; }
.dash-spark-dual .grp { flex:1; display:flex; align-items:flex-end; gap:2px; height:100%; }
.dash-spark-dual .grp .b { flex:1; border-radius:2px 2px 0 0; }
.dash-spark-dual .grp .b--sub { background:#fde3b8; }
.dash-spark-dual .grp .b--comp { background:#f0a93e; }
.dash-spark-dual .grp.now .b--sub { background:#fcd49a; }
.dash-spark-dual .grp.now .b--comp { background:#d98a1f; }
.dash-legend { display:flex; gap:14px; font-size:9.5px; color:#8a9fc4; margin-top:6px; }
.dash-legend span { display:inline-flex; align-items:center; gap:4px; }
.dash-legend i { width:8px; height:8px; border-radius:2px; display:inline-block; }

.dash-drill-note { font-size:9.5px; color:#aebcd8; margin-top:8px; }

.dash-table-preview { width:100%; border-collapse:collapse; font-size:11.5px; margin-top:6px; }
.dash-table-preview th { text-align:left; color:#aebcd8; font-size:9.5px; text-transform:uppercase; padding:5px 6px; border-bottom:1.5px solid #edf2f7; }
.dash-table-preview td { padding:6px 6px; border-bottom:1px solid #f3f5fa; color:#2d3748; }
.dash-table-preview tr td:first-child.dash-link { cursor:pointer; color:#1a6ef7; }

@media (max-width: 1100px) {
    .dash-board { grid-template-columns: 1fr; }
}
```

- [ ] **Step 2: Deploy**

```bash
sudo install -o www-data -g www-data -m 644 /tmp/pandp-ui.css /var/www/pandp/css/pandp-ui.css
```

- [ ] **Step 3: Manual check**

Open `https://test.pandp.it/css/pandp-ui.css` directly in the browser, confirm the new block is present at the end and the file is valid (no truncation).

- [ ] **Step 4: Commit**

```bash
cd /var/www/pandp && sudo -u www-data git add css/pandp-ui.css
sudo -u www-data git commit -m "dashboard: aggiungi classi CSS dash-* per il nuovo layout a 3 colonne"
sudo -u www-data git push
```

---

### Task 4: Model methods for Utenti time-windowed stats (Accessi / Utenti attivi)

**Files:**
- Modify: `appCore/models/DashboardAdm.php` (add methods after `getDashBoardCertList()`, before the closing `}` of the class)

These methods compute, for a given period (`'month'`, `'3months'`, `'6months'`), the count of:
- **Accessi**: users with a `learning_tracksession` row in the period who do **not** also have a `learning_commontrack` row in the same period (logged in, viewed nothing)
- **Utenti attivi**: users with at least one `learning_commontrack` row in the period (viewed at least one content), filtered to the current year

- [ ] **Step 1: Add the period-to-date-range helper and the two count methods**

Add inside `class DashboardAdm` in `appCore/models/DashboardAdm.php`, right before the final closing `}`:

```php
    /**
     * Convert a period key ('month','3months','6months') into a [from, to] date range.
     *
     * @param string $period
     * @return array
     */
    private function periodToRange($period)
    {
        $to = date('Y-m-d 23:59:59');
        switch ($period) {
            case '3months':
                $from = date('Y-m-01 00:00:00', strtotime('-2 months'));
                break;
            case '6months':
                $from = date('Y-m-01 00:00:00', strtotime('-5 months'));
                break;
            case 'month':
            default:
                $from = date('Y-m-01 00:00:00');
                break;
        }

        return [$from, $to];
    }

    private function scopeFilterSql($users_column, $courses_column)
    {
        $sql = '';
        if ($this->user_level != ADMIN_GROUP_GODADMIN) {
            if ($this->users_filter !== false) {
                $sql .= empty($this->users_filter)
                    ? ' AND 0 '
                    : ' AND ' . $users_column . ' IN (' . implode(',', $this->users_filter) . ') ';
            }
            if ($this->courses_filter !== false) {
                $sql .= empty($this->courses_filter)
                    ? ' AND 0 '
                    : ' AND ' . $courses_column . ' IN (' . implode(',', $this->courses_filter) . ') ';
            }
        }

        return $sql;
    }

    /**
     * Utenti che hanno aperto una sessione nel periodo ma non hanno visionato
     * nessun contenuto formativo nello stesso periodo.
     */
    public function getUsersAccessCount($period)
    {
        list($from, $to) = $this->periodToRange($period);

        $query = 'SELECT COUNT(DISTINCT ts.idUser) FROM %lms_tracksession ts '
            . " WHERE ts.enterTime BETWEEN '" . $from . "' AND '" . $to . "' "
            . $this->scopeFilterSql('ts.idUser', 'ts.idCourse')
            . ' AND ts.idUser NOT IN ('
            . "   SELECT DISTINCT ct.idUser FROM %lms_commontrack ct"
            . "   WHERE ct.dateAttempt BETWEEN '" . $from . "' AND '" . $to . "'"
            . ' )';

        list($count) = $this->db->fetch_row($this->db->query($query));

        return (int) $count;
    }

    /**
     * Utenti che hanno visionato almeno un contenuto formativo nel periodo
     * (sempre filtrato sull'anno corrente, come richiesto dalla spec).
     */
    public function getUsersActiveCount($period)
    {
        list($from, $to) = $this->periodToRange($period);
        $year_start = date('Y-01-01 00:00:00');
        if ($from < $year_start) {
            $from = $year_start;
        }

        $query = 'SELECT COUNT(DISTINCT ct.idUser) FROM %lms_commontrack ct '
            . " WHERE ct.dateAttempt BETWEEN '" . $from . "' AND '" . $to . "' "
            . $this->scopeFilterSql('ct.idUser', 'ct.idReference');

        list($count) = $this->db->fetch_row($this->db->query($query));

        return (int) $count;
    }

    /**
     * Andamento mensile (ultimi $how_many_months mesi) per Accessi o Utenti attivi.
     *
     * @param string $type 'access' o 'active'
     * @param int $how_many_months
     * @return array lista di ['label' => 'Gen', 'count' => N], dal piu' vecchio al piu' recente
     */
    public function getUsersMonthlyTrend($type, $how_many_months = 6)
    {
        $output = [];
        for ($i = $how_many_months - 1; $i >= 0; --$i) {
            $month_start = date('Y-m-01 00:00:00', strtotime('-' . $i . ' months'));
            $month_end = date('Y-m-t 23:59:59', strtotime('-' . $i . ' months'));

            if ($type === 'active') {
                $query = 'SELECT COUNT(DISTINCT ct.idUser) FROM %lms_commontrack ct '
                    . " WHERE ct.dateAttempt BETWEEN '" . $month_start . "' AND '" . $month_end . "' "
                    . $this->scopeFilterSql('ct.idUser', 'ct.idReference');
            } else {
                $query = 'SELECT COUNT(DISTINCT ts.idUser) FROM %lms_tracksession ts '
                    . " WHERE ts.enterTime BETWEEN '" . $month_start . "' AND '" . $month_end . "' "
                    . $this->scopeFilterSql('ts.idUser', 'ts.idCourse')
                    . ' AND ts.idUser NOT IN ('
                    . "   SELECT DISTINCT ct.idUser FROM %lms_commontrack ct"
                    . "   WHERE ct.dateAttempt BETWEEN '" . $month_start . "' AND '" . $month_end . "'"
                    . ' )';
            }

            list($count) = $this->db->fetch_row($this->db->query($query));
            $output[] = ['label' => date('M', strtotime($month_start)), 'count' => (int) $count];
        }

        return $output;
    }
```

- [ ] **Step 2: Deploy**

```bash
sudo install -o www-data -g www-data -m 644 /tmp/DashboardAdm.php /var/www/pandp/appCore/models/DashboardAdm.php
```

- [ ] **Step 3: Manual check via a throwaway debug script**

```bash
php -r '
define("IN_FORMA", true);
chdir("/var/www/pandp");
require "base.php";
require "lib/lib.bootstrap.php";
Boot::init(BOOT_DB);
$m = new DashboardAdm();
var_dump($m->getUsersAccessCount("month"));
var_dump($m->getUsersActiveCount("month"));
var_dump($m->getUsersMonthlyTrend("active", 6));
'
```

Expected: three non-error outputs — two integers and an array of 6 `['label'=>..., 'count'=>...]` entries, oldest month first. If this throws a fatal error about missing `Docebo::user()`, run it instead through a real page request (Step 4) rather than CLI, since `DashboardAdm`'s constructor calls `Docebo::user()`, which needs a real session — the CLI snippet above is only useful if it doesn't error; if it does, skip straight to Step 4.

- [ ] **Step 4: Commit**

```bash
cd /var/www/pandp && sudo -u www-data git add appCore/models/DashboardAdm.php
sudo -u www-data git commit -m "dashboard: metodi per Accessi e Utenti attivi (mese/3 mesi/6 mesi)"
sudo -u www-data git push
```

---

### Task 5: Rewrite the view with the 3-column layout, Utenti column fully wired

**Files:**
- Modify: `appCore/views/dashboard/show.php` (full rewrite of body)
- Modify: `appCore/controllers/DashboardAdmController.php:show()` (pass new data)

- [ ] **Step 1: Update `DashboardAdmController::show()`**

Replace the `$this->render('show', [...])` call block (lines 90-119 from the original file) with:

```php
        $this->render('show', [
            'diagnostic_problem' => $problem,
            'lang_dir' => Lang::direction(),

            'can_approve' => checkPerm('approve_waiting_user', true, 'directory', 'framework'),
            'version' => $this->model->getVersionExternalInfo(),
            'is_godadmin' => Docebo::user()->getUserLevelId() == ADMIN_GROUP_GODADMIN,

            'user_stats' => $this->model->getUsersStats(),
            'users_access' => [
                'month' => $this->model->getUsersAccessCount('month'),
                '3months' => $this->model->getUsersAccessCount('3months'),
                '6months' => $this->model->getUsersAccessCount('6months'),
            ],
            'users_active' => [
                'month' => $this->model->getUsersActiveCount('month'),
                '3months' => $this->model->getUsersActiveCount('3months'),
                '6months' => $this->model->getUsersActiveCount('6months'),
            ],
            'users_access_trend' => $this->model->getUsersMonthlyTrend('access', 6),
            'users_active_trend' => $this->model->getUsersMonthlyTrend('active', 6),
            'current_month_label' => Lang::t('_MONTH_' . date('m'), 'standard'),

            'course_stats' => $this->model->getCoursesStats(),
            'course_months_stats' => $this->model->getCoursesMonthsStats(),

            'permissions' => $this->permissions,
            'reports' => $arr_report,
        ]);
```

(This drops the old `userdata_*`/`coursedata_*` Chartist payloads and the `diagnostic_problem` Chartist scripts — they belonged to the old layout's full-width line charts, which Task 5's new view no longer renders. `Util::get_js`/`get_css` calls for `dashboard.js`/`show.js`/`show.css`/Chartist at the top of `show()` can stay; they are harmless if unused by the new view and Task 6/7 may still want the dialog JS callbacks defined in `dashboard.js`.)

- [ ] **Step 2: Replace the full contents of `appCore/views/dashboard/show.php`**

```php
<div class="pui-page">
    <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:14px;">
        <div>
            <h1 style="font-size:21px;font-weight:700;color:#1a2b4a;margin:0;">Dashboard</h1>
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

        <!-- COLONNA AZIENDE (placeholder, Task 9 la completa) -->
        <div class="dash-col">
            <div class="dash-col__title"><span class="dot dot--companies"></span> Aziende</div>
            <p style="font-size:12px;color:#aebcd8;">Sezione in arrivo.</p>
        </div>

        <!-- COLONNA CORSI (placeholder, Task 12 la completa) -->
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
```

- [ ] **Step 3: Deploy**

```bash
sudo install -o www-data -g www-data -m 644 /tmp/show.php /var/www/pandp/appCore/views/dashboard/show.php
sudo install -o www-data -g www-data -m 644 /tmp/DashboardAdmController.php /var/www/pandp/appCore/controllers/DashboardAdmController.php
```

- [ ] **Step 4: Manual check**

Reload `index.php?r=adm/dashboard/show` as GodAdmin: 3 columns side by side, Utenti column shows real numbers (Totale caricati, Connessi ora, Amministratori, Super admin), Accessi/Utenti attivi blocks show 3 numbers + sparkline matching the mockup. Log in as a regular Admin (or use the impersonation feature built earlier in this project) and confirm: "Super admin" KPI is hidden, numbers in Accessi/Utenti attivi are smaller (scoped), and the "Vista Amministratore" label shows under the title.

- [ ] **Step 5: Commit**

```bash
cd /var/www/pandp && sudo -u www-data git add appCore/views/dashboard/show.php appCore/controllers/DashboardAdmController.php
sudo -u www-data git commit -m "dashboard: layout a 3 colonne, sezione Utenti completa"
sudo -u www-data git push
```

---

### Task 6: Drill-down dialog for Utenti KPIs

**Files:**
- Create: `appCore/views/dashboard/users_drilldown_dialog.php`
- Modify: `appCore/controllers/DashboardAdmController.php` (add `users_drilldownTask()`)
- Modify: `appCore/views/dashboard/show.php` (wire dialog widget + click handlers)
- Modify: `appCore/models/DashboardAdm.php` (add `getUsersDrilldownList()`)

- [ ] **Step 1: Add the list query to the model**

Add to `DashboardAdm.php`:

```php
    /**
     * Elenco utenti per il drill-down della Dashboard.
     *
     * @param string $kind 'total','online','admin','superadmin','access','active'
     * @param string|false $period 'month'|'3months'|'6months', usato solo per 'access'/'active'
     */
    public function getUsersDrilldownList($kind, $period = false)
    {
        $aclManager = Docebo::user()->getACLManager();
        $rows = [];

        if ($kind === 'admin' || $kind === 'superadmin') {
            $idst_group = $aclManager->getGroupST($kind === 'superadmin' ? ADMIN_GROUP_GODADMIN : ADMIN_GROUP_ADMIN);
            $query = 'SELECT u.idst, u.userid, u.firstname, u.lastname, u.email FROM %adm_user u '
                . ' JOIN %adm_group_members gm ON gm.idstMember = u.idst '
                . " WHERE gm.idst = " . (int) $idst_group;
            $res = $this->db->query($query);
            while (list($idst, $userid, $firstname, $lastname, $email) = $this->db->fetch_row($res)) {
                $rows[] = ['idst' => $idst, 'userid' => $userid, 'name' => $firstname . ' ' . $lastname, 'email' => $email];
            }

            return $rows;
        }

        if ($kind === 'access' || $kind === 'active') {
            list($from, $to) = $this->periodToRange($period ?: 'month');
            if ($kind === 'active') {
                $query = 'SELECT DISTINCT u.idst, u.userid, u.firstname, u.lastname FROM %adm_user u '
                    . ' JOIN %lms_commontrack ct ON ct.idUser = u.idst '
                    . " WHERE ct.dateAttempt BETWEEN '" . $from . "' AND '" . $to . "' "
                    . $this->scopeFilterSql('ct.idUser', 'ct.idReference');
            } else {
                $query = 'SELECT DISTINCT u.idst, u.userid, u.firstname, u.lastname FROM %adm_user u '
                    . ' JOIN %lms_tracksession ts ON ts.idUser = u.idst '
                    . " WHERE ts.enterTime BETWEEN '" . $from . "' AND '" . $to . "' "
                    . $this->scopeFilterSql('ts.idUser', 'ts.idCourse')
                    . ' AND ts.idUser NOT IN ('
                    . "   SELECT DISTINCT ct2.idUser FROM %lms_commontrack ct2"
                    . "   WHERE ct2.dateAttempt BETWEEN '" . $from . "' AND '" . $to . "'"
                    . ' )';
            }
            $res = $this->db->query($query);
            while (list($idst, $userid, $firstname, $lastname) = $this->db->fetch_row($res)) {
                $rows[] = ['idst' => $idst, 'userid' => $userid, 'name' => $firstname . ' ' . $lastname];
            }

            return $rows;
        }

        // 'total' or 'online'
        $data = new PeopleDataRetriever($GLOBALS['dbConn'], $GLOBALS['prefix_fw']);
        if (!empty($this->users_filter)) {
            $data->setUserFilter($this->users_filter);
        }
        if ($kind === 'online') {
            $data->addFieldFilter('lastenter', date('Y-m-d H:i:s', time() - REFRESH_LAST_ENTER), '>');
        }
        // getRows() returns a raw DB resource (SELECT idst, userid, firstname, lastname, email, valid, signature),
        // not an array — fetch it row by row like the rest of this codebase does.
        $res = $data->getRows(0, 500);
        while ($row = sql_fetch_assoc($res)) {
            $rows[] = ['idst' => $row['idst'], 'userid' => $row['userid'], 'name' => $row['firstname'] . ' ' . $row['lastname']];
        }

        return $rows;
    }
```

- [ ] **Step 2: Create the dialog view**

This codebase's existing dialog views (e.g. `appCore/views/dashboard/diagnostic_dialog.php`) build an `$html` string and, when a `$json` object has been passed in by the controller, echo the `{success, header, body}` JSON themselves rather than relying on `render()` to wrap anything. Follow that exact convention.

Create `appCore/views/dashboard/users_drilldown_dialog.php`:

```php
<?php
$html = '<table class="dash-table-preview">';
$html .= '<tr><th>' . Lang::t('_USERNAME', 'standard') . '</th><th>' . Lang::t('_FULLNAME', 'standard') . '</th></tr>';
foreach ($rows as $r) {
    $html .= '<tr><td>' . htmlspecialchars($r['userid']) . '</td><td>' . htmlspecialchars($r['name']) . '</td></tr>';
}
if (empty($rows)) {
    $html .= '<tr><td colspan="2">' . Lang::t('_NONE', 'standard') . '</td></tr>';
}
$html .= '</table>';

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
```

- [ ] **Step 3: Add the controller AJAX action**

Add to `DashboardAdmController.php`, after `findcertificateTask()`:

```php
    public function users_drilldownTask()
    {
        $kind = FormaLms\lib\Get::req('kind', DOTY_ALPHANUM, 'total');
        $period = FormaLms\lib\Get::req('period', DOTY_ALPHANUM, 'month');

        $titles = [
            'total' => 'Totale utenti caricati',
            'online' => 'Utenti connessi ora',
            'admin' => 'Totale amministratori',
            'superadmin' => 'Totale super admin',
            'access' => 'Accessi',
            'active' => 'Utenti attivi',
        ];

        $this->render('users_drilldown_dialog', [
            'rows' => $this->model->getUsersDrilldownList($kind, $period),
            'title' => isset($titles[$kind]) ? $titles[$kind] : 'Dettaglio',
            'json' => $this->json,
        ]);
    }
```

- [ ] **Step 4: Expose a dialog trigger object in `dashboard.js`**

The dialog widget's `callObjectFunc` parameter is the real, existing mechanism in this codebase for triggering a dynamic-content dialog on demand from arbitrary JS (proven usage: `appLms/views/coursestats/show.php:86` + `coursestats.js:6,159`, `CourseStats.oDialogCaller`). It needs an empty object to attach to, declared on the existing `Dashboard` namespace.

In `appCore/views/dashboard/dashboard.js`, change:

```js
var Dashboard = {

	createUserRenderEvent: function() {
```

to:

```js
var Dashboard = {

	oDialogCaller: {},

	createUserRenderEvent: function() {
```

- [ ] **Step 5: Wire the dialog widget and click handlers in the view**

In `appCore/views/dashboard/show.php`, right after the closing `</div>` of `.dash-board` (before the final closing `</div>` of `.pui-page`), add:

```php
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
```

`callObjectFunc` makes the widget emit `Dashboard.oDialogCaller["users_drilldown_dialog"] = dialogEvent;` inside the page's `onDOMReady` block (see `widget/dialog/views/dynamic.php:38` + `widget/dialog/lib.dialog.php:168-172`) — that's the same closure-scoped trigger function that `callEvents` would otherwise bind to a fixed element's click, except here it's reachable by name from `dashOpenDrilldown()` so the same dialog can be reused for every KPI with a different dynamic URL set just before triggering it. Because `CreateDialog` builds a brand new `YAHOO.widget.Dialog` (and re-fires the AJAX request) on every call, no separate "refresh" call is needed — calling the trigger function again is enough.

Then add `onclick` attributes to the KPI/number elements added in Task 5, replacing the bare `id="..."` divs with clickable ones:

- `dash_kpi_users_total` → `onclick="dashOpenDrilldown('total')"`
- `dash_kpi_users_online` → `onclick="dashOpenDrilldown('online')"`
- `dash_kpi_users_admin` → `onclick="dashOpenDrilldown('admin')"`
- `dash_kpi_users_superadmin` → `onclick="dashOpenDrilldown('superadmin')"`
- `dash_access_month` → `onclick="dashOpenDrilldown('access','month')"`, `dash_access_3m` → `'3months'`, `dash_access_6m` → `'6months'`
- `dash_active_month` → `onclick="dashOpenDrilldown('active','month')"`, `dash_active_3m` → `'3months'`, `dash_active_6m` → `'6months'`

(Edit each `<div class="dash-kpi" id="...">` / `<div class="dash-tw-num" id="...">` opening tag from Task 5 to add the matching `onclick`.)

- [ ] **Step 6: Deploy**

```bash
sudo install -o www-data -g www-data -m 644 /tmp/DashboardAdm.php /var/www/pandp/appCore/models/DashboardAdm.php
sudo install -o www-data -g www-data -m 644 /tmp/users_drilldown_dialog.php /var/www/pandp/appCore/views/dashboard/users_drilldown_dialog.php
sudo install -o www-data -g www-data -m 644 /tmp/DashboardAdmController.php /var/www/pandp/appCore/controllers/DashboardAdmController.php
sudo install -o www-data -g www-data -m 644 /tmp/show.php /var/www/pandp/appCore/views/dashboard/show.php
sudo install -o www-data -g www-data -m 644 /tmp/dashboard.js /var/www/pandp/appCore/views/dashboard/dashboard.js
```

- [ ] **Step 7: Manual check**

Click each Utenti KPI and each Accessi/Utenti attivi number: a modal opens with a table of matching users. Click "Totale amministratori" — table lists exactly the admins counted in the KPI.

- [ ] **Step 8: Commit**

```bash
cd /var/www/pandp && sudo -u www-data git add appCore/models/DashboardAdm.php appCore/views/dashboard/users_drilldown_dialog.php appCore/controllers/DashboardAdmController.php appCore/views/dashboard/show.php appCore/views/dashboard/dashboard.js
sudo -u www-data git commit -m "dashboard: drill-down per i KPI della sezione Utenti"
sudo -u www-data git push
```

---

## Phase 3 — Sezione Aziende

### Task 7: Migration — add `date_created` to `core_org_chart_tree`

**Files:**
- DB only: `core_org_chart_tree`

- [ ] **Step 1: Run the migration**

```bash
mysql -h localhost -u pandpuser -p'P@ndp_Dev_2016!' pandp -e "ALTER TABLE core_org_chart_tree ADD COLUMN date_created DATETIME NULL DEFAULT NULL AFTER associated_template;"
mysql -h localhost -u pandpuser -p'P@ndp_Dev_2016!' pandp -e "UPDATE core_org_chart_tree SET date_created = NOW() WHERE date_created IS NULL;"
```

- [ ] **Step 2: Verify**

```bash
mysql -h localhost -u pandpuser -p'P@ndp_Dev_2016!' pandp -e "SELECT idOrg, idParent, lev, code, date_created FROM core_org_chart_tree;"
```

Expected: all existing rows now show today's date/time in `date_created`.

- [ ] **Step 3: Find where new org-chart nodes get inserted, and add `date_created` there too**

```bash
grep -rln "INSERT INTO %adm_org_chart_tree\|INSERT INTO.*core_org_chart_tree" /var/www/pandp/appCore /var/www/pandp/lib --include="*.php"
```

For each match found, add `date_created = NOW()` to the `SET`/column list of the `INSERT` so future companies get a real creation date instead of relying on this migration's one-time backfill. (The exact line numbers depend on what the grep above returns — read each matched file, find the `INSERT`, add the column.)

- [ ] **Step 4: Commit**

```bash
cd /var/www/pandp && sudo -u www-data git add -A
sudo -u www-data git commit -m "dashboard: aggiungi date_created a core_org_chart_tree per il trend Aziende"
sudo -u www-data git push
```

---

### Task 8: Model methods for Aziende (count, list, tree drill-down, monthly trend)

**Files:**
- Modify: `appCore/models/DashboardAdm.php`

- [ ] **Step 1: Add the company-scoped methods**

Add to `DashboardAdm.php`:

```php
    /**
     * Risale dal nodo di organigramma dell'admin corrente fino al nodo di
     * primo livello (l'"azienda" che gestisce). Ritorna false per il GodAdmin
     * (che non e' scoped su una singola azienda) o se l'admin non e' legato
     * a nessun nodo.
     */
    public function getAdminCompanyNode()
    {
        if ($this->user_level == ADMIN_GROUP_GODADMIN) {
            return false;
        }

        $query = 'SELECT oct.idOrg, oct.idParent FROM core_org_chart_tree oct '
            . ' JOIN core_group_members gm ON (gm.idst = oct.idst_oc OR gm.idst = oct.idst_ocd) '
            . ' WHERE gm.idstMember = ' . (int) Docebo::user()->getIdSt()
            . ' LIMIT 1';
        $res = $this->db->query($query);
        if (!$res || $this->db->num_rows($res) <= 0) {
            return false;
        }
        list($idOrg, $idParent) = $this->db->fetch_row($res);

        // risale fino al nodo con idParent = 0
        while ((int) $idParent !== 0) {
            $query = 'SELECT idOrg, idParent FROM core_org_chart_tree WHERE idOrg = ' . (int) $idParent;
            $res = $this->db->query($query);
            if (!$res || $this->db->num_rows($res) <= 0) {
                break;
            }
            list($idOrg, $idParent) = $this->db->fetch_row($res);
        }

        return (int) $idOrg;
    }

    /**
     * Numero di "aziende" (nodi di primo livello dell'organigramma).
     * Per un Admin scoped su una singola azienda, ritorna sempre 1 (o 0 se
     * non e' legato a nessun nodo).
     */
    public function getCompaniesCount()
    {
        if ($this->user_level != ADMIN_GROUP_GODADMIN) {
            return $this->getAdminCompanyNode() ? 1 : 0;
        }

        list($count) = $this->db->fetch_row(
            $this->db->query('SELECT COUNT(*) FROM core_org_chart_tree WHERE idParent = 0')
        );

        return (int) $count;
    }

    /**
     * Elenco delle aziende (nodi di primo livello) con il numero di utenti
     * totali nel loro sottoalbero. Per un Admin, elenco con la sola azienda
     * gestita.
     */
    public function getCompaniesList()
    {
        $where = 'oct.idParent = 0';
        if ($this->user_level != ADMIN_GROUP_GODADMIN) {
            $node = $this->getAdminCompanyNode();
            $where = $node ? 'oct.idOrg = ' . (int) $node : '0';
        }

        $query = 'SELECT oct.idOrg, oct.iLeft, oct.iRight, c.translation '
            . ' FROM core_org_chart_tree oct '
            . ' JOIN core_org_chart c ON c.id_dir = oct.idOrg AND c.lang_code = "' . getLanguage() . '" '
            . ' WHERE ' . $where
            . ' ORDER BY c.translation ASC';
        $res = $this->db->query($query);

        $rows = [];
        while (list($idOrg, $iLeft, $iRight, $name) = $this->db->fetch_row($res)) {
            $users_query = 'SELECT COUNT(*) FROM %adm_group_members gm '
                . ' JOIN core_org_chart_tree d ON (d.idst_oc = gm.idst OR d.idst_ocd = gm.idst) '
                . ' WHERE d.iLeft >= ' . (int) $iLeft . ' AND d.iRight <= ' . (int) $iRight;
            list($users_count) = $this->db->fetch_row($this->db->query($users_query));

            $rows[] = ['idOrg' => $idOrg, 'name' => $name, 'users_count' => (int) $users_count];
        }

        return $rows;
    }

    /**
     * Figli diretti di un nodo di organigramma (per il drill-down ricorsivo).
     */
    public function getCompanyChildren($idOrg)
    {
        $query = 'SELECT oct.idOrg, c.translation '
            . ' FROM core_org_chart_tree oct '
            . ' JOIN core_org_chart c ON c.id_dir = oct.idOrg AND c.lang_code = "' . getLanguage() . '" '
            . ' WHERE oct.idParent = ' . (int) $idOrg
            . ' ORDER BY c.translation ASC';
        $res = $this->db->query($query);

        $rows = [];
        while (list($childId, $name) = $this->db->fetch_row($res)) {
            $has_children_query = 'SELECT COUNT(*) FROM core_org_chart_tree WHERE idParent = ' . (int) $childId;
            list($has_children) = $this->db->fetch_row($this->db->query($has_children_query));
            $rows[] = ['idOrg' => $childId, 'name' => $name, 'has_children' => (int) $has_children > 0];
        }

        return $rows;
    }

    /**
     * Andamento mensile (ultimi $how_many_months mesi) di nuove aziende
     * (nodi di primo livello) create, basato su date_created.
     */
    public function getCompaniesMonthlyTrend($how_many_months = 6)
    {
        $output = [];
        for ($i = $how_many_months - 1; $i >= 0; --$i) {
            $month_start = date('Y-m-01 00:00:00', strtotime('-' . $i . ' months'));
            $month_end = date('Y-m-t 23:59:59', strtotime('-' . $i . ' months'));

            $query = 'SELECT COUNT(*) FROM core_org_chart_tree '
                . " WHERE idParent = 0 AND date_created BETWEEN '" . $month_start . "' AND '" . $month_end . "'";
            list($count) = $this->db->fetch_row($this->db->query($query));
            $output[] = ['label' => date('M', strtotime($month_start)), 'count' => (int) $count];
        }

        return $output;
    }
```

- [ ] **Step 2: Deploy**

```bash
sudo install -o www-data -g www-data -m 644 /tmp/DashboardAdm.php /var/www/pandp/appCore/models/DashboardAdm.php
```

- [ ] **Step 3: Manual check**

Reuse the debug snippet pattern from Task 4 Step 3 (or test directly through the page once Task 9 wires it in) to confirm `getCompaniesCount()` returns `2` for GodAdmin (matches the 2 existing top-level nodes: P&P Technology, Test) and `getCompaniesList()` returns both with their user counts.

- [ ] **Step 4: Commit**

```bash
cd /var/www/pandp && sudo -u www-data git add appCore/models/DashboardAdm.php
sudo -u www-data git commit -m "dashboard: metodi per la sezione Aziende (conteggio, elenco, drill-down, trend)"
sudo -u www-data git push
```

---

### Task 9: Populate the Aziende column + drill-down dialog

**Files:**
- Modify: `appCore/controllers/DashboardAdmController.php` (pass company data, add `companies_drilldownTask()`)
- Modify: `appCore/views/dashboard/show.php` (replace Aziende placeholder column)
- Create: `appCore/views/dashboard/companies_drilldown_dialog.php`

- [ ] **Step 1: Add company data to `show()` in the controller**

In `DashboardAdmController::show()`, add to the `render()` data array (after `'users_active_trend'`):

```php
            'companies_count' => $this->model->getCompaniesCount(),
            'companies_list' => $this->model->getCompaniesList(),
            'companies_trend' => $this->model->getCompaniesMonthlyTrend(6),
```

- [ ] **Step 2: Replace the Aziende placeholder column in `show.php`**

Replace:
```php
        <!-- COLONNA AZIENDE (placeholder, Task 9 la completa) -->
        <div class="dash-col">
            <div class="dash-col__title"><span class="dot dot--companies"></span> Aziende</div>
            <p style="font-size:12px;color:#aebcd8;">Sezione in arrivo.</p>
        </div>
```

with:
```php
        <!-- COLONNA AZIENDE -->
        <div class="dash-col">
            <div class="dash-col__title"><span class="dot dot--companies"></span> Aziende</div>

            <div class="dash-kpi-grid">
                <div class="dash-kpi" onclick="dashOpenCompanyDrilldown(0)">
                    <div class="dash-kpi__value"><?php echo (int) $companies_count; ?></div>
                    <div class="dash-kpi__label">Aziende caricate</div>
                </div>
            </div>

            <div class="dash-tw">
                <div class="dash-tw__head"><div class="dash-tw__title">Nuove aziende — ultimi 6 mesi</div></div>
                <div class="dash-spark companies">
                    <?php
                    $max_comp = max(1, max(array_column($companies_trend, 'count')));
                    foreach ($companies_trend as $idx => $pt) {
                        $h = max(8, round(($pt['count'] / $max_comp) * 100));
                        $cls = ($idx === count($companies_trend) - 1) ? 'b now' : 'b';
                        echo '<div class="' . $cls . '" style="height:' . $h . '%" title="' . htmlspecialchars($pt['label']) . ': ' . (int) $pt['count'] . '"></div>';
                    }
                    ?>
                </div>
                <div class="dash-drill-note">Conteggio storico approssimato: il tracciamento delle date e' iniziato il <?php echo date('d/m/Y'); ?></div>
            </div>

            <div class="dash-tw">
                <div class="dash-tw__head"><div class="dash-tw__title">Aziende — elenco rapido</div></div>
                <table class="dash-table-preview">
                    <tr><th>Azienda</th><th>Utenti</th><th></th></tr>
                    <?php foreach ($companies_list as $c) { ?>
                        <tr>
                            <td class="dash-link" onclick="dashOpenCompanyDrilldown(<?php echo (int) $c['idOrg']; ?>)"><?php echo htmlspecialchars($c['name']); ?></td>
                            <td><?php echo (int) $c['users_count']; ?></td>
                            <td>&#128269;</td>
                        </tr>
                    <?php } ?>
                </table>
                <div class="dash-drill-note">&#8627; click su un'azienda per esplorare i nodi figli e i sotto-nodi</div>
            </div>
        </div>
```

- [ ] **Step 3: Add the dialog view**

Create `appCore/views/dashboard/companies_drilldown_dialog.php`, following the same `$json`-aware convention as `users_drilldown_dialog.php` (Task 6 Step 2):

```php
<?php
$html = '<p style="font-size:12px;color:#8a9fc4;margin-bottom:10px;">' . htmlspecialchars($current_name) . '</p>';
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
$html .= '</table>';

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
```

- [ ] **Step 4: Add the controller AJAX action**

Add to `DashboardAdmController.php`:

```php
    public function companies_drilldownTask()
    {
        $idOrg = FormaLms\lib\Get::req('idOrg', DOTY_INT, 0);

        if ($idOrg <= 0) {
            // root: per GodAdmin, lista delle aziende; per Admin, la sua azienda
            $companies = $this->model->getCompaniesList();
            $children = array_map(function ($c) {
                return ['idOrg' => $c['idOrg'], 'name' => $c['name'], 'has_children' => true];
            }, $companies);
            $current_name = 'Tutte le aziende';
        } else {
            $children = $this->model->getCompanyChildren($idOrg);
            $current_name = 'Sotto-nodi';
        }

        $this->render('companies_drilldown_dialog', [
            'children' => $children,
            'current_name' => $current_name,
            'json' => $this->json,
        ]);
    }
```

- [ ] **Step 5: Add the dialog widget + JS helper in `show.php`**

Right after the `users_drilldown_dialog` widget block added in Task 6, add:

```php
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
```

Same `callObjectFunc` mechanism as Task 6 Step 4/5 — `Dashboard.oDialogCaller` already exists on the page (added once in Task 6, shared by every dialog declared after it; no further changes needed to `dashboard.js` for this dialog).

- [ ] **Step 6: Deploy**

```bash
sudo install -o www-data -g www-data -m 644 /tmp/DashboardAdmController.php /var/www/pandp/appCore/controllers/DashboardAdmController.php
sudo install -o www-data -g www-data -m 644 /tmp/show.php /var/www/pandp/appCore/views/dashboard/show.php
sudo install -o www-data -g www-data -m 644 /tmp/companies_drilldown_dialog.php /var/www/pandp/appCore/views/dashboard/companies_drilldown_dialog.php
```

- [ ] **Step 7: Manual check**

As GodAdmin: Aziende column shows "2" (P&P Technology + Test), elenco rapido lists both with user counts, sparkline shows a single bar in the current month. Click an azienda name: dialog opens showing its direct children (e.g. clicking "P&P Technology" shows "Bergamo"). Click "esplora" on a child with further children: dialog updates to show the next level down.

As Admin (impersonate a non-godadmin admin user): Aziende column shows "1" and the elenco rapido lists only their own company.

- [ ] **Step 8: Commit**

```bash
cd /var/www/pandp && sudo -u www-data git add appCore/controllers/DashboardAdmController.php appCore/views/dashboard/show.php appCore/views/dashboard/companies_drilldown_dialog.php
sudo -u www-data git commit -m "dashboard: sezione Aziende completa con drill-down ricorsivo"
sudo -u www-data git push
```

---

## Phase 4 — Sezione Corsi

### Task 10: Model methods for Corsi (completati, certificati, trend doppio, top visti)

**Files:**
- Modify: `appCore/models/DashboardAdm.php`

- [ ] **Step 1: Add the new course methods**

Add to `DashboardAdm.php`:

```php
    /**
     * Numero di iscrizioni completate (_CUS_END = 2), opzionalmente filtrate
     * su un periodo (false = nessun filtro periodo, conteggio totale).
     */
    public function getCoursesCompletedCount($period = false)
    {
        $query = 'SELECT COUNT(*) FROM %lms_courseuser WHERE status = 2 ';
        if ($period) {
            list($from, $to) = $this->periodToRange($period);
            $query .= " AND date_complete BETWEEN '" . $from . "' AND '" . $to . "' ";
        }
        $query .= $this->scopeFilterSql('idUser', 'idCourse');

        list($count) = $this->db->fetch_row($this->db->query($query));

        return (int) $count;
    }

    /**
     * Numero di certificati rilasciati (learning_certificate_assign),
     * opzionalmente filtrati su un periodo.
     */
    public function getCertificatesIssuedCount($period = false)
    {
        $query = 'SELECT COUNT(*) FROM %lms_certificate_assign WHERE 1=1 ';
        if ($period) {
            list($from, $to) = $this->periodToRange($period);
            $query .= " AND on_date BETWEEN '" . $from . "' AND '" . $to . "' ";
        }
        $query .= $this->scopeFilterSql('id_user', 'id_course');

        list($count) = $this->db->fetch_row($this->db->query($query));

        return (int) $count;
    }

    /**
     * Andamento mensile (ultimi $how_many_months mesi) di Iscrizioni vs
     * Completamenti, per il grafico a doppia serie della sezione Corsi.
     */
    public function getCoursesEnrollmentCompletionTrend($how_many_months = 6)
    {
        $output = [];
        for ($i = $how_many_months - 1; $i >= 0; --$i) {
            $month_start = date('Y-m-01 00:00:00', strtotime('-' . $i . ' months'));
            $month_end = date('Y-m-t 23:59:59', strtotime('-' . $i . ' months'));

            $sub_query = 'SELECT COUNT(*) FROM %lms_courseuser '
                . " WHERE date_inscr BETWEEN '" . $month_start . "' AND '" . $month_end . "' "
                . $this->scopeFilterSql('idUser', 'idCourse');
            list($subs) = $this->db->fetch_row($this->db->query($sub_query));

            $comp_query = 'SELECT COUNT(*) FROM %lms_courseuser '
                . " WHERE status = 2 AND date_complete BETWEEN '" . $month_start . "' AND '" . $month_end . "' "
                . $this->scopeFilterSql('idUser', 'idCourse');
            list($comp) = $this->db->fetch_row($this->db->query($comp_query));

            $output[] = ['label' => date('M', strtotime($month_start)), 'subscriptions' => (int) $subs, 'completions' => (int) $comp];
        }

        return $output;
    }

    /**
     * Top corsi per numero di iscritti.
     */
    public function getTopViewedCourses($limit = 5)
    {
        $query = 'SELECT c.idCourse, c.name, '
            . ' (SELECT COUNT(*) FROM %lms_courseuser WHERE idCourse = c.idCourse) AS enrolled, '
            . ' (SELECT COUNT(*) FROM %lms_courseuser WHERE idCourse = c.idCourse AND status = 2) AS completed, '
            . ' c.status '
            . ' FROM %lms_course c WHERE 1=1 ';
        if ($this->courses_filter !== false) {
            $query .= empty($this->courses_filter)
                ? ' AND 0 '
                : ' AND c.idCourse IN (' . implode(',', $this->courses_filter) . ') ';
        }
        $query .= ' ORDER BY enrolled DESC LIMIT ' . (int) $limit;

        $res = $this->db->query($query);
        $rows = [];
        while (list($idCourse, $name, $enrolled, $completed, $status) = $this->db->fetch_row($res)) {
            $rows[] = [
                'idCourse' => $idCourse,
                'name' => $name,
                'enrolled' => (int) $enrolled,
                'completed' => (int) $completed,
                'active' => (int) $status === 1,
            ];
        }

        return $rows;
    }
```

- [ ] **Step 2: Deploy**

```bash
sudo install -o www-data -g www-data -m 644 /tmp/DashboardAdm.php /var/www/pandp/appCore/models/DashboardAdm.php
```

- [ ] **Step 3: Manual check**

Same throwaway-snippet approach as Task 4 Step 3 (or wait for Task 11's page wiring): confirm `getCoursesCompletedCount(false)`, `getCertificatesIssuedCount(false)`, `getCoursesEnrollmentCompletionTrend(6)` and `getTopViewedCourses(5)` all return without SQL errors and with plausible shapes.

- [ ] **Step 4: Commit**

```bash
cd /var/www/pandp && sudo -u www-data git add appCore/models/DashboardAdm.php
sudo -u www-data git commit -m "dashboard: metodi per Corsi completati, certificati, trend e top corsi"
sudo -u www-data git push
```

---

### Task 11: Populate the Corsi column with the new KPIs, chart and table

**Files:**
- Modify: `appCore/controllers/DashboardAdmController.php` (pass course data)
- Modify: `appCore/views/dashboard/show.php` (replace Corsi placeholder content)

- [ ] **Step 1: Add the new course data to `show()`**

In `DashboardAdmController::show()`, add to the `render()` data array:

```php
            'courses_completed' => $this->model->getCoursesCompletedCount(),
            'certificates_issued' => $this->model->getCertificatesIssuedCount(),
            'courses_trend' => $this->model->getCoursesEnrollmentCompletionTrend(6),
            'top_courses' => $this->model->getTopViewedCourses(5),
```

- [ ] **Step 2: Replace the Corsi placeholder column in `show.php`**

Replace the whole "COLONNA CORSI" block from Task 5 with:

```php
        <!-- COLONNA CORSI -->
        <div class="dash-col">
            <div class="dash-col__title"><span class="dot dot--courses"></span> Corsi</div>

            <div class="dash-kpi-grid dash-kpi-grid--3">
                <div class="dash-kpi">
                    <div class="dash-kpi__value"><?php echo (int) $course_stats['active']; ?></div>
                    <div class="dash-kpi__label">Attivi</div>
                </div>
                <div class="dash-kpi">
                    <div class="dash-kpi__value"><?php echo (int) $courses_completed; ?></div>
                    <div class="dash-kpi__label">Completati</div>
                </div>
                <div class="dash-kpi">
                    <div class="dash-kpi__value"><?php echo (int) $certificates_issued; ?></div>
                    <div class="dash-kpi__label">Certificati</div>
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

            <div class="dash-tw">
                <div class="dash-tw__head"><div class="dash-tw__title">Iscrizioni vs Completamenti — ultimi 6 mesi</div></div>
                <div class="dash-spark-dual">
                    <?php
                    $max_course = 1;
                    foreach ($courses_trend as $pt) {
                        $max_course = max($max_course, $pt['subscriptions'], $pt['completions']);
                    }
                    foreach ($courses_trend as $idx => $pt) {
                        $h_sub = max(6, round(($pt['subscriptions'] / $max_course) * 100));
                        $h_comp = max(6, round(($pt['completions'] / $max_course) * 100));
                        $cls = ($idx === count($courses_trend) - 1) ? 'grp now' : 'grp';
                        echo '<div class="' . $cls . '" title="' . htmlspecialchars($pt['label']) . '">'
                            . '<div class="b b--sub" style="height:' . $h_sub . '%"></div>'
                            . '<div class="b b--comp" style="height:' . $h_comp . '%"></div>'
                            . '</div>';
                    }
                    ?>
                </div>
                <div class="dash-legend">
                    <span><i style="background:#fde3b8"></i> Iscrizioni</span>
                    <span><i style="background:#f0a93e"></i> Completamenti</span>
                </div>
            </div>

            <div class="dash-tw">
                <div class="dash-tw__head"><div class="dash-tw__title">Corsi più visti</div></div>
                <table class="dash-table-preview">
                    <tr><th>Corso</th><th>Iscritti</th><th>Compl.</th><th></th></tr>
                    <?php foreach ($top_courses as $tc) { ?>
                        <tr>
                            <td><a href="index.php?r=alms/course/show&id_course=<?php echo (int) $tc['idCourse']; ?>"><?php echo htmlspecialchars($tc['name']); ?></a></td>
                            <td><?php echo (int) $tc['enrolled']; ?></td>
                            <td><?php echo (int) $tc['completed']; ?></td>
                            <td><span class="pui-badge <?php echo $tc['active'] ? 'pui-badge--success' : 'pui-badge--neutral'; ?>"><?php echo $tc['active'] ? 'Attivo' : 'Non attivo'; ?></span></td>
                        </tr>
                    <?php } ?>
                    <?php if (empty($top_courses)) { ?>
                        <tr><td colspan="4"><?php echo Lang::t('_NONE', 'standard'); ?></td></tr>
                    <?php } ?>
                </table>
            </div>
        </div>
```

- [ ] **Step 3: Deploy**

```bash
sudo install -o www-data -g www-data -m 644 /tmp/DashboardAdmController.php /var/www/pandp/appCore/controllers/DashboardAdmController.php
sudo install -o www-data -g www-data -m 644 /tmp/show.php /var/www/pandp/appCore/views/dashboard/show.php
```

- [ ] **Step 4: Manual check**

Reload the Dashboard: Corsi column shows 5 KPIs with real numbers, the dual-series sparkline renders with two visibly different bar colors per month, "Corsi più visti" lists real courses sorted by enrollment with working links to the course page.

- [ ] **Step 5: Commit**

```bash
cd /var/www/pandp && sudo -u www-data git add appCore/controllers/DashboardAdmController.php appCore/views/dashboard/show.php
sudo -u www-data git commit -m "dashboard: sezione Corsi completa (completati, certificati, trend, top corsi)"
sudo -u www-data git push
```

---

## Spec coverage check

| Requisito spec | Task |
|---|---|
| Rimuovi Link veloci | Task 2 |
| Disattiva Impostazioni Dashboard | Task 1 |
| Rinomina pagina in Dashboard | Task 2 |
| Layout 3 colonne single-screen | Task 3, 5 |
| Utenti: totale, connessi, admin, superadmin | Task 5 |
| Utenti: Accessi mese/3m/6m + grafico | Task 4, 5 |
| Utenti: Attivi mese/3m/6m + grafico | Task 4, 5 |
| Aziende: conteggio nodi primo livello | Task 8, 9 |
| Aziende: scoping admin (1 sola azienda) | Task 8 (`getAdminCompanyNode`) |
| Aziende: trend 6 mesi nuove aziende | Task 7, 8, 9 |
| Aziende: drill-down ricorsivo figli/sotto-figli | Task 8, 9 |
| Corsi: attivi, completati, certificati, attivazione 7gg, iscrizioni | Task 5 (parziale), 10, 11 |
| Corsi: grafico iscrizioni vs completamenti 6 mesi | Task 10, 11 |
| Corsi: tabella "Corsi più visti" | Task 10, 11 |
| Drill-down modale su tutti i KPI | Task 6 (Utenti), 9 (Aziende). Corsi: i KPI numerici di Task 11 non hanno drill-down dedicato in questo piano — vedi nota sotto. |

**Nota aperta:** la spec richiede drill-down su "tutti i KPI" ma questo piano implementa il drill-down dialog completo solo per Utenti (Task 6) e Aziende (Task 9, via click sul nome/elenco). Per Corsi, l'unico drill-down concreto è il link diretto alla scheda corso nella tabella "Corsi più visti" (Task 11) — i 5 KPI numerici (Attivi, Completati, Certificati, In attivazione 7gg, Iscrizioni) non aprono un dialog dedicato. Se serve anche per questi, è un'estensione naturale del pattern Task 6 (stesso `xxxTask()` + dialog), da aggiungere come Task 12 in un secondo passaggio.
