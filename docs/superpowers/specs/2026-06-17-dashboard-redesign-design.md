# Dashboard Backoffice — Redesign Design Spec

## Contesto

La pagina di benvenuto del backoffice (`appCore/index.php?r=adm/dashboard/show`, file `appCore/views/dashboard/show.php`) viene rinominata e ridisegnata in **Dashboard**: una panoramica sintetica e navigabile su Utenti, Aziende e Corsi, sostituendo l'attuale blocco "Link veloci" e la disposizione a sezioni impilate.

Il sistema esistente (`appCore/models/DashboardAdm.php`) ha già un meccanismo di scoping funzionante tra **Superadmin** (dati globali) e **Admin** (dati filtrati su `users_filter`/`courses_filter`, popolati in base all'organigramma gestito). Questo meccanismo viene riusato ed estenso, non ricostruito.

Esiste inoltre un secondo elemento di menu, **"Impostazioni Dashboard"** (`adm/dashboardsettings/show`, voce di menu `idMenu=601` sotto Configurazione), che appartiene a un vecchio sistema di dashboard a blocchi configurabili (`DashboardBlockWelcomeLms` e classi collegate), non funzionante e non più mantenuto. Va disattivato senza eliminare il codice.

Il nuovo look si basa sul design system **P&P UI** (`css/pandp-ui.css`), già usato nelle pagine di newsletter e di importazione utenti: card bianche con ombra leggera, accent blu `#1a6ef7`, badge colorati, tabelle compatte. Il mockup approvato è in `css/_mockup_dashboard_v3.html`.

## Cosa cambia rispetto ad oggi

| Elemento attuale | Nuovo comportamento |
|---|---|
| Blocco "Link veloci" (quick links utenti/corsi/contenuti/supporto) | **Rimosso** dalla pagina |
| Voce di menu "Impostazioni Dashboard" (Configurazione) | **Disattivata** (`is_active = false` su `core_menu.idMenu = 601`), codice non toccato |
| Sezioni Utenti/Corsi impilate verticalmente, con grafici larghi | **Layout a 3 colonne affiancate** (Utenti \| Aziende \| Corsi), pensato per restare in una sola schermata senza scroll su risoluzioni desktop standard |
| Nessuna sezione Aziende | **Nuova sezione Aziende** |
| Numeri statici, nessun drill-down | Ogni KPI è cliccabile e apre un **dialog modale** (pattern YUI già in uso nel backoffice) con il dettaglio |

## Architettura

- **Stessa route e stesso controller**: `DashboardAdmController` + `DashboardAdm.php` (model), nessuna nuova area applicativa.
- **View**: `appCore/views/dashboard/show.php` viene riscritta per il nuovo layout; il CSS specifico del nuovo layout (classi `dash-*`) viene aggiunto come blocco dedicato in coda a `css/pandp-ui.css`, riusando le classi esistenti (`pui-badge`, `pui-card` dove utile) per coerenza visiva.
- **Model**: `DashboardAdm.php` viene estesa con i nuovi metodi di calcolo (vedi sezioni seguenti). I metodi esistenti (`getUsersStats`, `getCoursesStats`, `getUsersChartAccessData`, ecc.) vengono riusati dove già coprono la metrica richiesta; il filtro `users_filter`/`courses_filter` esistente resta l'unico meccanismo di scoping admin/superadmin.
- **Drill-down**: ogni dialog è caricato via AJAX (`ajax.adm_server.php?r=adm/dashboard/...`), stesso pattern dei dialog "Stato utente" / "Certificati" già presenti nella pagina attuale (`$this->widget('dialog', [...])`).

## Sezione UTENTI

KPI principali (griglia 2×2):
- **Totale utenti caricati** — riusa `getUsersStats()['all']`
- **Utenti connessi ora** — riusa `getUsersStats()['now_online']` (solo se la voce era già visibile lato Admin; va verificato il permesso, altrimenti nascosto per Admin come oggi)
- **Totale amministratori** — riusa `getUsersStats()['admin']` (spostato qui dalla sezione Aziende)
- **Totale superadmin** — riusa `getUsersStats()['superadmin']` (visibile solo a Superadmin, come già gestito oggi da `if (Docebo::user()->getUserLevelId() == ADMIN_GROUP_GODADMIN)`)

Blocco **Accessi** (utenti che entrano in piattaforma ma non visionano nessun contenuto formativo):
- Tre numeri affiancati: mese in corso (etichettato col nome del mese), ultimi 3 mesi, ultimi 6 mesi
- Sorgente dati: `learning_tracksession` (tabella già usata da `getUsersChartAccessData`) per gli accessi, **escludendo** gli utenti che nello stesso periodo hanno anche almeno una riga in `learning_commontrack` (questi sono "attivi", non semplici "accessi")
- Mini-grafico a barre (sparkline) sotto i tre numeri, con l'andamento mensile

Blocco **Utenti attivi (anno in corso)** (hanno visionato almeno un contenuto formativo):
- Stessa struttura a tre numeri (mese/3 mesi/6 mesi) + sparkline
- Sorgente dati: utenti distinti con almeno una riga in `learning_commontrack` nel periodo, filtrati sull'anno corrente
- Lo scoping admin/superadmin riusa lo stesso filtro `users_filter`/`courses_filter` già applicato dai metodi esistenti

## Sezione AZIENDE

Definizione di "azienda": **nodo di primo livello dell'organigramma** (figlio diretto della radice, es. "P&P Technology", "Test"). I nodi interni a un'azienda (sotto-cartelle) non vengono contati come aziende separate, ma sono navigabili in drill-down.

KPI principale:
- **Totale aziende caricate** — conteggio dei nodi di primo livello sotto la radice dell'organigramma

Vista per ruolo:
- **Superadmin**: vede il totale globale; il drill-down elenca tutte le aziende, e da ciascuna si naviga ricorsivamente nei nodi figli e nei loro sotto-nodi (struttura ad albero)
- **Admin**: la sezione mostra solo l'azienda/nodo di organigramma che l'admin gestisce (un solo elemento, non un totale globale), con lo stesso drill-down ricorsivo sui suoi nodi interni

Grafico: **andamento nuove aziende create negli ultimi 6 mesi** (sparkline a barre), basato sulla data di creazione del nodo nell'organigramma.

Elenco rapido (mini-tabella sotto al grafico): nome azienda + numero utenti totali, con icona di accesso al drill-down. Per Admin la tabella mostra la singola azienda gestita.

## Sezione CORSI

KPI principali (griglia 3 colonne):
- **Corsi attivi** — riusa `getCoursesStats()` (campo `active`)
- **Corsi completati** — conteggio delle iscrizioni (`learning_courseuser`) con stato completato (`_CUS_END`). A livello di corso non esiste uno stato "completato" (il campo `status` di `learning_course` è solo preparazione/effettivo), quindi la metrica è sempre a livello di iscrizione utente↔corso, coerente con `_CUS_END` già usato altrove nel codice (es. `SubscriptionAlms`)
- **Certificati rilasciati** — nuovo conteggio da `learning_certificate_assign` (campo `on_date` per il filtro periodo), scoped su `users_filter`/`courses_filter`
- **In attivazione entro 7 giorni** — riusa `getCoursesStats()` (campo `active_seven`)
- **Totale iscrizioni** — riusa `getCoursesStats()` (campo `user_subscription`)

Grafico: **Iscrizioni vs Completamenti — ultimi 6 mesi**, doppia serie mensile affiancata (riusa/estende `getCoursesMonthsStats()` già presente, esteso da 3 a 6 mesi e aggiungendo la serie dei completamenti).

Tabella **"Corsi più visti"**: top corsi ordinati per numero di iscritti (`learning_courseuser`), colonne Corso / Iscritti / Completati / Stato (badge), con drill-down per riga verso la scheda corso esistente.

## Drill-down

Tutti i numeri KPI e le righe delle mini-tabelle sono cliccabili e aprono un **dialog modale** (stesso widget `dialog` YUI già usato per "Stato utente", "Cambia password", "Certificati" nella pagina attuale). Ogni dialog carica via AJAX una tabella di dettaglio filtrata in base al KPI cliccato (es. cliccando "Certificati rilasciati — Giugno 2026" si apre l'elenco dei certificati emessi nel mese).

## Layout visivo

Tre colonne affiancate su desktop (Utenti \| Aziende \| Corsi), che vanno a singola colonna sotto i ~1100px di larghezza (responsive di base, non priorità per mobile). Ogni colonna è una card bianca con KPI compatti in griglia, blocchi a sparkline per le metriche storiche, e una mini-tabella finale. Riferimento visivo: `css/_mockup_dashboard_v3.html` (approvato).

## Fuori scope (non in questa spec)

- Ottimizzazione mobile/tablet della Dashboard
- Esportazione dati Dashboard (PDF/Excel)
- Personalizzazione/drag&drop dei blocchi (il vecchio sistema "Impostazioni Dashboard" viene disattivato, non sostituito da un equivalente moderno)
- Modifica del significato di "Corsi completati" oltre a quanto chiarito nel piano di implementazione

## Fasi di implementazione previste

1. **Pulizia**: rimozione blocco "Link veloci" dalla view attuale, disattivazione voce menu "Impostazioni Dashboard" (DB), rinomina pagina in "Dashboard"
2. **Layout base + Sezione Utenti**: nuovo layout a 3 colonne, sezione Utenti completa con KPI, blocchi Accessi/Utenti attivi, sparkline, drill-down
3. **Sezione Aziende**: KPI, sparkline nuove aziende, elenco rapido, drill-down ricorsivo sull'organigramma, scoping Admin/Superadmin
4. **Sezione Corsi**: KPI (incluso nuovo conteggio certificati e corsi completati), grafico doppia serie, tabella "Corsi più visti", drill-down

Ogni fase è testabile autonomamente sulla pagina reale prima di passare alla successiva.
