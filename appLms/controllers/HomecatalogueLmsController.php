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

defined('IN_FORMA') or exit('Direct access is forbidden.');

class HomecatalogueLmsController extends CatalogLmsController
{
    public function init()
    {
        if (!HomepageAdm::staticIsCatalogToShow()) {
            Util::jump_to('');
        }

        YuiLib::load('base,tabview');
        Lang::init('course');
        $this->path_course = $GLOBALS['where_files_relative'] . '/appLms/' . FormaLms\lib\Get::sett('pathcourse') . '/';
        $this->model = new HomecatalogueLms();
        $this->_mvc_name = 'catalog';
        $this->acl_man = &Docebo::user()->getAclManager();
    }

    public function isTabActive($tab_name)
    {
        return true;
    }

    protected function getBaseData()
    {
        $data = parent::getBaseData();
        $data['catalogueType'] = 'homecatalogue';
        $data['endpoint'] = 'lms/homecatalogue';

        return $data;
    }

    public function show()
    {
        $id_catalogue = FormaLms\lib\Get::req('id_catalogue', DOTY_INT, 0);

        $catalogue = $this->model->GetGlobalJsonTree($id_catalogue, [CatalogLms::SHOW_RULES_EVERYONE]);
        $total_category = count($catalogue);

        $data = $this->getBaseData();

        $data = array_merge($data, [
            'id_catalogue' => $id_catalogue,
            'user_catalogue' => [],
            'show_general_catalogue_tab' => true,
            'show_empty_catalogue_tab' => false,
            'show_user_catalogue_tab' => false,
            'tab_actived' => false,
            'total_category' => $total_category,
            'starting_catalogue' => $id_catalogue,
            'catalogue' => $catalogue,
        ]);

        $this->render('catalog', [
            'data' => $data,
        ]);
    }

    public function allCourseForma()
    {
        $id_category = FormaLms\lib\Get::req('id_category', DOTY_INT, 0);
        $typeCourse = FormaLms\lib\Get::req('type_course', DOTY_STRING, '');
        $id_catalogue = FormaLms\lib\Get::req('id_catalogue', DOTY_INT, 0);

        $courses = $this->model->getCatalogCourseList($typeCourse, 1, $id_catalogue, $id_category);

        foreach ($courses as $index => $course) {
            if ((int) $course['show_rules'] !== 0 || (int) $course['in_home_catalogue'] !== 1) {
                unset($courses[$index]);
            }
        }

        $data = $this->getBaseData();

        $data = array_merge($data, compact('courses', 'id_catalogue'));

        $this->render('courselist', ['data' => $data]);
    }

    public function makemockups_DISABLED()
    {
        $dir = '/var/www/pandp/files/mockups';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $files = [];
        foreach (['a','b','c'] as $k) {
            $files[$k] = $dir . '/design_' . $k . '.html';
        }
        // ---- DESIGN A: Wizard Card ----
        file_put_contents($files['a'], '<!DOCTYPE html>
<html lang="it"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Design A – Wizard Card</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#f0f2f5;font-family:"Segoe UI",Arial,sans-serif;font-size:14px;color:#333;padding:30px}
.wrap{max-width:780px;margin:0 auto}
h2{font-size:22px;font-weight:600;color:#1a2b4a;margin-bottom:24px}
/* Stepper */
.stepper{display:flex;align-items:center;margin-bottom:28px}
.step{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:500;color:#9aafca}
.step.active{color:#1a6ef7}
.step.done{color:#28a745}
.step-num{width:28px;height:28px;border-radius:50%;border:2px solid currentColor;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0}
.step.active .step-num{background:#1a6ef7;border-color:#1a6ef7;color:#fff}
.step.done .step-num{background:#28a745;border-color:#28a745;color:#fff}
.step-line{flex:1;height:2px;background:#d0dae8;margin:0 8px}
.step-line.done{background:#28a745}
/* Card */
.card{background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08);padding:32px}
/* Upload zone */
.upload-zone{border:2px dashed #b0c4de;border-radius:10px;padding:40px 20px;text-align:center;cursor:pointer;transition:.2s;background:#f8fbff;margin-bottom:24px}
.upload-zone:hover{border-color:#1a6ef7;background:#eef3ff}
.upload-zone .icon{font-size:40px;margin-bottom:12px;color:#6a8fc8}
.upload-zone p{color:#6a8fc8;font-size:13px}
.upload-zone strong{font-size:15px;color:#2c4a7a;display:block;margin-bottom:4px}
/* Sections */
.section-label{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8a9fc4;margin-bottom:10px}
.radio-group{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px}
.radio-pill{display:flex;align-items:center;gap:6px;padding:7px 14px;border:1.5px solid #d0dae8;border-radius:20px;cursor:pointer;font-size:13px;transition:.15s;background:#fff}
.radio-pill:hover{border-color:#1a6ef7;color:#1a6ef7}
.radio-pill input{accent-color:#1a6ef7}
.radio-pill.manual{align-items:center;gap:8px;flex-basis:100%;border-radius:8px;padding:8px 14px;background:#f8fbff}
.manual-input{border:1px solid #d0dae8;border-radius:6px;padding:4px 10px;font-size:13px;width:70px;margin-left:4px}
.check-row{display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:14px;cursor:pointer}
.check-row input{accent-color:#1a6ef7;width:16px;height:16px}
.inline-field{display:flex;align-items:center;gap:12px;margin-bottom:24px}
.inline-field label{font-size:13px;color:#555;white-space:nowrap}
.txt-input{border:1.5px solid #d0dae8;border-radius:8px;padding:7px 12px;font-size:13px;width:120px;transition:.2s}
.txt-input:focus{outline:none;border-color:#1a6ef7}
/* Buttons */
.btn-row{display:flex;justify-content:flex-end;gap:12px;margin-top:8px;padding-top:24px;border-top:1px solid #edf2f7}
.btn{padding:10px 24px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;border:none;transition:.15s}
.btn-ghost{background:#f0f2f5;color:#556;border:1.5px solid #d0dae8}
.btn-ghost:hover{background:#e0e5ee}
.btn-primary{background:#1a6ef7;color:#fff}
.btn-primary:hover{background:#1457cc}
</style></head><body>
<div class="wrap">
<h2>Importa utenti</h2>
<div class="stepper">
  <div class="step active"><div class="step-num">1</div><span>Carica file</span></div>
  <div class="step-line"></div>
  <div class="step"><div class="step-num">2</div><span>Configura</span></div>
  <div class="step-line"></div>
  <div class="step"><div class="step-num">3</div><span>Risultati</span></div>
</div>
<div class="card">
  <div class="upload-zone">
    <div class="icon">⬆</div>
    <strong>Trascina il file CSV qui</strong>
    <p>oppure clicca per selezionare &nbsp;•&nbsp; .csv, .txt</p>
    <div style="margin-top:14px"><input type="file" style="font-size:13px;color:#555"></div>
  </div>

  <div class="section-label">Separatore colonne</div>
  <div class="radio-group">
    <label class="radio-pill"><input type="radio" name="sep" checked> Rilevamento automatico</label>
    <label class="radio-pill"><input type="radio" name="sep"> <b>,</b>&nbsp;Virgola</label>
    <label class="radio-pill"><input type="radio" name="sep"> <b>;</b>&nbsp;Punto e virgola</label>
    <label class="radio-pill manual"><input type="radio" name="sep"> Manuale:<input class="manual-input" type="text" placeholder="|"></label>
  </div>

  <label class="check-row"><input type="checkbox" checked> Prima riga come intestazione colonne</label>

  <div class="inline-field">
    <label>Charset</label>
    <input class="txt-input" type="text" value="UTF-8">
  </div>

  <div class="btn-row">
    <button class="btn btn-ghost">Annulla</button>
    <button class="btn btn-primary">Avanti &rarr;</button>
  </div>
</div>
</div>
</body></html>');

        // ---- DESIGN B: Two-column split ----
        file_put_contents($files['b'], '<!DOCTYPE html>
<html lang="it"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Design B – Two Column</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#f4f6fb;font-family:"Segoe UI",Arial,sans-serif;font-size:14px;color:#333;padding:30px}
.wrap{max-width:1100px;margin:0 auto}
/* Top bar */
.topbar{display:flex;align-items:center;gap:0;margin-bottom:24px;background:#fff;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.07);overflow:hidden}
.tbar-step{flex:1;padding:14px 20px;font-size:13px;font-weight:600;color:#9aafca;display:flex;align-items:center;gap:8px;border-right:1px solid #edf2f7}
.tbar-step:last-child{border-right:none}
.tbar-step.active{background:#1a6ef7;color:#fff}
.tbar-step.done{background:#eaf6f0;color:#28a745}
.tbar-step .num{width:24px;height:24px;border-radius:50%;border:2px solid currentColor;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0}
.tbar-step.active .num{background:#fff;border-color:#fff;color:#1a6ef7}
/* Main layout */
.layout{display:grid;grid-template-columns:280px 1fr;gap:20px}
/* Left panel */
.sidebar{background:#fff;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.07);padding:24px;display:flex;flex-direction:column;gap:20px}
.sidebar h3{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8a9fc4;margin-bottom:4px}
/* Right panel */
.main-panel{background:#fff;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.07);padding:24px;overflow:auto}
/* Form elements */
.section-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8a9fc4;margin-bottom:8px}
.radio-stack{display:flex;flex-direction:column;gap:8px;margin-bottom:16px}
.radio-item{display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer}
.radio-item input{accent-color:#1a6ef7}
.check-row{display:flex;align-items:center;gap:8px;margin-bottom:16px;font-size:13px;cursor:pointer}
.check-row input{accent-color:#1a6ef7;width:15px;height:15px}
select.sel-field{width:100%;border:1.5px solid #d0dae8;border-radius:8px;padding:8px 10px;font-size:13px;background:#fff;margin-bottom:16px}
/* mapping table */
.map-table{width:100%;border-collapse:collapse;min-width:500px}
.map-table th{background:#f0f4fa;font-size:11px;font-weight:700;color:#6a7fa0;text-transform:uppercase;letter-spacing:.04em;padding:10px 12px;text-align:left;border-bottom:2px solid #e0e8f4}
.map-table td{padding:8px 12px;border-bottom:1px solid #f0f2f8;font-size:13px;vertical-align:middle}
.map-table tr:last-child td{border-bottom:none}
.map-table tr:hover td{background:#fafbff}
.col-map-sel{width:100%;border:1.5px solid #d0dae8;border-radius:6px;padding:5px 8px;font-size:12px;background:#fff}
.badge{display:inline-block;background:#eef2ff;color:#4a6cf7;font-size:11px;font-weight:700;padding:2px 8px;border-radius:4px}
/* Buttons */
.btn-row{display:flex;justify-content:space-between;align-items:center;margin-top:28px;padding-top:20px;border-top:1px solid #edf2f7}
.btn{padding:10px 24px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;border:none;transition:.15s}
.btn-ghost{background:#f0f2f5;color:#556;border:1.5px solid #d0dae8}
.btn-ghost:hover{background:#e0e5ee}
.btn-primary{background:#1a6ef7;color:#fff}
.btn-primary:hover{background:#1457cc}
/* Tags */
.info-badge{background:#fff8e6;border:1px solid #ffe08a;color:#996600;border-radius:6px;padding:6px 10px;font-size:12px;margin-bottom:16px}
</style></head><body>
<div class="wrap">
  <div class="topbar">
    <div class="tbar-step done"><div class="num">✓</div>Carica file</div>
    <div class="tbar-step active"><div class="num">2</div>Configura</div>
    <div class="tbar-step"><div class="num">3</div>Risultati</div>
  </div>
  <div class="layout">
    <!-- LEFT sidebar -->
    <div class="sidebar">
      <div>
        <h3>Notifiche</h3>
        <label class="check-row"><input type="checkbox"> Invia alert con nuove credenziali</label>
      </div>
      <div>
        <h3>Struttura organizzativa</h3>
        <select class="sel-field"><option>Radice</option><option>Divisione A</option></select>
      </div>
      <div>
        <h3>Azione sugli utenti</h3>
        <div class="radio-stack">
          <label class="radio-item"><input type="radio" name="action"> Crea e aggiorna</label>
          <label class="radio-item"><input type="radio" name="action" checked> Solo crea</label>
          <label class="radio-item"><input type="radio" name="action"> Solo aggiorna</label>
        </div>
      </div>
      <div>
        <h3>Forza cambio password</h3>
        <div class="radio-stack">
          <label class="radio-item"><input type="radio" name="pwdchg" checked> No</label>
          <label class="radio-item"><input type="radio" name="pwdchg"> Sì</label>
          <label class="radio-item"><input type="radio" name="pwdchg"> Da impostazione server</label>
        </div>
      </div>
      <div>
        <h3>Impostazione password</h3>
        <div class="radio-stack">
          <label class="radio-item"><input type="radio" name="pwdset"> Da file</label>
          <label class="radio-item"><input type="radio" name="pwdset" checked> Imposta per tutti</label>
        </div>
        <div style="margin-top:12px;padding:12px;background:#f8fbff;border-radius:8px;border:1px solid #d8e8f8">
          <div class="radio-stack">
            <label class="radio-item"><input type="radio" name="pwdtype" checked> Password automatica</label>
            <label class="radio-item"><input type="radio" name="pwdtype"> Manuale: <input type="text" style="border:1px solid #d0dae8;border-radius:5px;padding:3px 8px;font-size:12px;width:90px;margin-left:4px"></label>
          </div>
        </div>
      </div>
    </div>
    <!-- RIGHT main -->
    <div class="main-panel">
      <div class="section-label" style="margin-bottom:16px">Mappa colonne CSV &rarr; campi sistema</div>
      <div class="info-badge">⚠ Associa ogni colonna del tuo CSV al campo corrispondente. Imposta "Ignora" per le colonne non necessarie.</div>
      <div style="overflow-x:auto">
      <table class="map-table">
        <thead>
          <tr>
            <th>Colonna CSV</th>
            <th>Mappa su campo</th>
            <th>Valore esempio 1</th>
            <th>Valore esempio 2</th>
          </tr>
        </thead>
        <tbody>
          <tr><td><span class="badge">COL 1</span> firstname</td><td><select class="col-map-sel"><option>— Ignora —</option><option selected>Nome</option><option>Cognome</option><option>Email</option></select></td><td>Mario</td><td>Anna</td></tr>
          <tr><td><span class="badge">COL 2</span> lastname</td><td><select class="col-map-sel"><option>— Ignora —</option><option>Nome</option><option selected>Cognome</option><option>Email</option></select></td><td>Rossi</td><td>Verdi</td></tr>
          <tr><td><span class="badge">COL 3</span> email</td><td><select class="col-map-sel"><option>— Ignora —</option><option>Nome</option><option>Cognome</option><option selected>Email</option></select></td><td>m.rossi@co.it</td><td>a.verdi@co.it</td></tr>
          <tr><td><span class="badge">COL 4</span> username</td><td><select class="col-map-sel"><option>— Ignora —</option><option>Nome</option><option>Cognome</option><option>Email</option><option selected>Username</option></select></td><td>mrossi</td><td>averdi</td></tr>
          <tr><td><span class="badge">COL 5</span> dept</td><td><select class="col-map-sel"><option selected>— Ignora —</option><option>Nome</option><option>Cognome</option><option>Email</option></select></td><td>Marketing</td><td>HR</td></tr>
        </tbody>
      </table>
      </div>
      <div class="btn-row">
        <button class="btn btn-ghost">&larr; Indietro</button>
        <button class="btn btn-primary">Avanti &rarr;</button>
      </div>
    </div>
  </div>
</div>
</body></html>');

        // ---- DESIGN C: Modern minimal dark header ----
        file_put_contents($files['c'], '<!DOCTYPE html>
<html lang="it"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Design C – Minimal sections</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#f7f8fc;font-family:"Segoe UI",Arial,sans-serif;font-size:14px;color:#2d3748}
/* Header */
.page-header{background:linear-gradient(135deg,#1a2b4a 0%,#2b4a8c 100%);padding:24px 40px;display:flex;align-items:center;justify-content:space-between}
.page-header h1{color:#fff;font-size:20px;font-weight:600}
/* Progress */
.progress-bar{background:#ffffff22;height:4px;width:200px;border-radius:2px;overflow:hidden}
.progress-fill{background:#4af;height:100%;width:33%;border-radius:2px}
.progress-label{color:#a0b8e0;font-size:12px;margin-top:4px}
/* Content */
.content{padding:32px 40px;max-width:820px;margin:0 auto}
/* Section cards */
.sec-card{background:#fff;border-radius:10px;box-shadow:0 1px 6px rgba(0,0,0,.06);margin-bottom:16px;overflow:hidden}
.sec-card .sec-head{display:flex;align-items:center;gap:12px;padding:16px 20px;border-bottom:1px solid #f0f2f8;cursor:pointer}
.sec-card .sec-head:hover{background:#fafbff}
.sec-icon{width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.ic-blue{background:#e8f0fe;color:#1a6ef7}
.ic-green{background:#e6f9ed;color:#1fa057}
.ic-orange{background:#fff5e6;color:#e07a00}
.ic-purple{background:#f3eeff;color:#7c3aed}
.sec-title{font-size:14px;font-weight:700;color:#2d3748}
.sec-subtitle{font-size:12px;color:#8a9fc4;margin-top:1px}
.sec-card .sec-body{padding:20px}
/* Upload */
.upload-zone{border:2px dashed #c8d8f0;border-radius:10px;padding:32px;text-align:center;background:#f8fbff;cursor:pointer;transition:.2s}
.upload-zone:hover{border-color:#1a6ef7;background:#eef3ff}
.upload-zone .up-icon{font-size:36px;color:#6a8fc8;margin-bottom:10px}
.upload-zone .up-title{font-size:15px;font-weight:600;color:#2c4a7a;margin-bottom:4px}
.upload-zone .up-sub{font-size:12px;color:#8a9fc4}
.file-name{display:flex;align-items:center;gap:8px;margin-top:14px;padding:8px 14px;background:#edf2ff;border-radius:6px;font-size:13px;color:#2c4a7a;width:fit-content;margin-left:auto;margin-right:auto}
/* Radio chips */
.chip-row{display:flex;flex-wrap:wrap;gap:8px}
.chip{padding:7px 16px;border:1.5px solid #d0dae8;border-radius:20px;font-size:13px;cursor:pointer;display:flex;align-items:center;gap:6px;transition:.15s;background:#fff}
.chip:hover{border-color:#1a6ef7;color:#1a6ef7}
.chip input{accent-color:#1a6ef7}
.manual-chip{flex-basis:100%;border-radius:8px;padding:8px 14px;background:#f8fbff;border:1.5px dashed #c8d8f0}
.mini-input{border:1px solid #d0dae8;border-radius:5px;padding:4px 8px;font-size:12px;width:60px;margin-left:6px}
/* Checkbox toggle */
.toggle-row{display:flex;align-items:center;gap:12px}
.toggle-row label{font-size:13px;color:#4a5568;cursor:pointer}
.toggle{width:40px;height:22px;position:relative;cursor:pointer}
.toggle input{opacity:0;width:0;height:0}
.slider{position:absolute;inset:0;background:#d0dae8;border-radius:11px;transition:.2s}
.slider:before{content:"";position:absolute;height:16px;width:16px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
.toggle input:checked+.slider{background:#1a6ef7}
.toggle input:checked+.slider:before{transform:translateX(18px)}
/* text field */
.field-row{display:flex;align-items:center;gap:12px;margin-top:12px}
.field-row label{font-size:13px;color:#4a5568;min-width:70px}
.txt{border:1.5px solid #d0dae8;border-radius:8px;padding:7px 12px;font-size:13px;transition:.2s;width:130px}
.txt:focus{outline:none;border-color:#1a6ef7}
/* Buttons */
.bottom-bar{display:flex;justify-content:flex-end;gap:12px;margin-top:28px}
.btn{padding:10px 28px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;border:none;transition:.15s}
.btn-ghost{background:#fff;color:#4a5568;border:1.5px solid #d0dae8}
.btn-ghost:hover{background:#f0f2f8}
.btn-primary{background:linear-gradient(135deg,#1a6ef7,#2b4af7);color:#fff;box-shadow:0 3px 10px rgba(26,110,247,.35)}
.btn-primary:hover{background:linear-gradient(135deg,#1457cc,#2337cc)}
</style></head><body>
<div class="page-header">
  <h1>Importa utenti</h1>
  <div>
    <div class="progress-bar"><div class="progress-fill"></div></div>
    <div class="progress-label">Passo 1 di 3</div>
  </div>
</div>
<div class="content">

  <div class="sec-card">
    <div class="sec-head">
      <div class="sec-icon ic-blue">📁</div>
      <div><div class="sec-title">File CSV</div><div class="sec-subtitle">Seleziona il file da importare</div></div>
    </div>
    <div class="sec-body">
      <div class="upload-zone">
        <div class="up-icon">⬆</div>
        <div class="up-title">Trascina il file qui oppure clicca per selezionare</div>
        <div class="up-sub">.csv &nbsp;•&nbsp; .txt &nbsp;•&nbsp; max 50 MB</div>
        <div class="file-name">📄 utenti_import.csv</div>
      </div>
    </div>
  </div>

  <div class="sec-card">
    <div class="sec-head">
      <div class="sec-icon ic-orange">⚙</div>
      <div><div class="sec-title">Separatore colonne</div><div class="sec-subtitle">Come sono separate le celle nel CSV?</div></div>
    </div>
    <div class="sec-body">
      <div class="chip-row">
        <label class="chip"><input type="radio" name="sep" checked> Rilevamento automatico</label>
        <label class="chip"><input type="radio" name="sep"> <b>,</b>&nbsp; Virgola</label>
        <label class="chip"><input type="radio" name="sep"> <b>;</b>&nbsp; Punto e virgola</label>
        <label class="chip manual-chip"><input type="radio" name="sep"> Manuale: <input class="mini-input" type="text" placeholder="|"></label>
      </div>
    </div>
  </div>

  <div class="sec-card">
    <div class="sec-head">
      <div class="sec-icon ic-green">📋</div>
      <div><div class="sec-title">Opzioni file</div><div class="sec-subtitle">Intestazione e codifica caratteri</div></div>
    </div>
    <div class="sec-body">
      <div class="toggle-row" style="margin-bottom:14px">
        <label class="toggle"><input type="checkbox" checked><span class="slider"></span></label>
        <label>Prima riga come intestazione colonne</label>
      </div>
      <div class="field-row">
        <label>Charset</label>
        <input class="txt" type="text" value="UTF-8">
      </div>
    </div>
  </div>

  <div class="bottom-bar">
    <button class="btn btn-ghost">Annulla</button>
    <button class="btn btn-primary">Avanti &rarr;</button>
  </div>
</div>
</body></html>');

        $urls = [];
        foreach (['a','b','c'] as $k) {
            $urls[$k] = 'http://192.168.188.88/files/mockups/design_' . $k . '.html';
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'urls' => $urls]);
        exit;
    }

    public function contactrequest()
    {
        $name        = trim(FormaLms\lib\Get::req('name',        DOTY_STRING, ''));
        $company     = trim(FormaLms\lib\Get::req('company',     DOTY_STRING, ''));
        $email       = trim(FormaLms\lib\Get::req('email',       DOTY_STRING, ''));
        $phone       = trim(FormaLms\lib\Get::req('phone',       DOTY_STRING, ''));
        $message     = trim(FormaLms\lib\Get::req('message',     DOTY_STRING, ''));
        $id_course   = (int) FormaLms\lib\Get::req('id_course',  DOTY_INT,    0);
        $course_name = trim(FormaLms\lib\Get::req('course_name', DOTY_STRING, ''));

        if (!$name || !$company || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Dati obbligatori mancanti o non validi.']);
            return;
        }

        require_once _base_ . '/lib/lib.mailer.php';

        $db = DbConn::getInstance();
        $row = $db->fetch_row($db->query(
            "SELECT param_value FROM %adm_setting WHERE param_name = 'mail_sender' LIMIT 1"
        ));
        $recipient = $row ? trim($row[0]) : '';
        if (!$recipient) {
            echo json_encode(['success' => false, 'error' => 'Configurazione email non disponibile.']);
            return;
        }

        $subject  = 'Richiesta informazioni corso';
        $subject .= $id_course ? ' [#' . $id_course . '] ' . str_replace(["\r", "\n"], ' ', strip_tags($course_name)) : '';

        $body  = '<p><strong>Nome:</strong> '    . htmlspecialchars($name)    . '</p>';
        $body .= '<p><strong>Azienda:</strong> ' . htmlspecialchars($company) . '</p>';
        $body .= '<p><strong>Email:</strong> '   . htmlspecialchars($email)   . '</p>';
        if ($phone !== '') {
            $body .= '<p><strong>Telefono:</strong> ' . htmlspecialchars($phone) . '</p>';
        }
        if ($message !== '') {
            $body .= '<p><strong>Messaggio:</strong><br>' . nl2br(htmlspecialchars($message)) . '</p>';
        }
        if ($id_course) {
            $body .= '<p><strong>Corso:</strong> #' . $id_course . ' — ' . htmlspecialchars($course_name) . '</p>';
        }

        $mailer = new FormaMailer();
        $sent = $mailer->SendFormaMail($recipient, [$recipient], $subject, $body);

        if ($sent) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invio email fallito. Riprova.']);
        }
    }

}