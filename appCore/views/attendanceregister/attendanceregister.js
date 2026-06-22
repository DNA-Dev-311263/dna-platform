var arDetailedMode = false;

document.addEventListener('DOMContentLoaded', function () {
	var courseSelect = document.getElementById('ar_course_select');
	var companyField = document.getElementById('ar_company_field');
	var companySelect = document.getElementById('ar_company_select');
	var split = document.getElementById('ar_split');
	var usersContainer = document.getElementById('ar_users_container');
	var exportIdCourse = document.getElementById('ar_export_idcourse');
	var exportIdOrg = document.getElementById('ar_export_idorg');

	if (!courseSelect) {
		return;
	}

	function loadUsers() {
		var idCourse = courseSelect.value;
		var idOrg = companySelect.value;
		exportIdCourse.value = idCourse;
		exportIdOrg.value = idOrg;

		usersContainer.innerHTML = '<div class="ar-empty">...</div>';
		arRefreshPreview();

		var xhr = new XMLHttpRequest();
		xhr.open('GET', 'ajax.adm_server.php?r=adm/attendanceregister/course_users&idCourse=' + encodeURIComponent(idCourse) + '&idOrg=' + encodeURIComponent(idOrg) + '&authentic_request=' + encodeURIComponent(AR_SIGNATURE), true);
		xhr.onload = function () {
			if (xhr.status !== 200) {
				return;
			}
			usersContainer.innerHTML = xhr.responseText;
		};
		xhr.send();
	}

	function loadCompanies(idCourse) {
		while (companySelect.options.length > 1) {
			companySelect.remove(1);
		}
		companySelect.value = '';
		companyField.style.display = 'none';

		var xhr = new XMLHttpRequest();
		xhr.open('GET', 'ajax.adm_server.php?r=adm/attendanceregister/companies&idCourse=' + encodeURIComponent(idCourse) + '&authentic_request=' + encodeURIComponent(AR_SIGNATURE), true);
		xhr.onload = function () {
			if (xhr.status !== 200) {
				return;
			}
			var companies = JSON.parse(xhr.responseText);
			if (!companies.length) {
				return;
			}
			companies.forEach(function (c) {
				var opt = document.createElement('option');
				opt.value = c.idOrg;
				opt.textContent = c.name;
				companySelect.appendChild(opt);
			});
			companyField.style.display = '';
		};
		xhr.send();
	}

	courseSelect.addEventListener('change', function () {
		var idCourse = courseSelect.value;

		if (!idCourse) {
			split.style.display = 'none';
			companyField.style.display = 'none';
			return;
		}

		split.style.display = '';
		loadCompanies(idCourse);
		loadUsers();
	});

	companySelect.addEventListener('change', loadUsers);

	// Le checkbox vengono ricreate ad ogni caricamento della lista utenti
	// (AJAX): la delega sul contenitore stabile evita di doverle riagganciare
	// una per una ogni volta.
	usersContainer.addEventListener('change', function (evt) {
		if (evt.target && evt.target.matches('input[type="checkbox"]')) {
			arRefreshPreview();
		}
	});
});

/**
 * Selezionare un allievo si puo' fare sia dalla sua checkbox sia cliccando
 * la username: in questo secondo caso la spunta viene messa in automatico.
 */
function arSelectUser(rowEl) {
	var rows = document.querySelectorAll('#ar_users_container tr.ar-row-active');
	for (var i = 0; i < rows.length; i++) {
		rows[i].classList.remove('ar-row-active');
	}
	rowEl.classList.add('ar-row-active');

	var checkbox = rowEl.querySelector('input[type="checkbox"]');
	if (checkbox) {
		checkbox.checked = true;
	}

	arRefreshPreview();
}

/**
 * Passa tra Riepilogo e Dettaglio: l'anteprima si aggiorna subito, niente
 * popup. Si puo' cambiare idea quante volte si vuole prima di stampare o
 * esportare.
 */
function arSetMode(detailed) {
	arDetailedMode = detailed;
	document.getElementById('ar_mode_summary').classList.toggle('ar-preview__toggle-btn--active', !detailed);
	document.getElementById('ar_mode_detailed').classList.toggle('ar-preview__toggle-btn--active', detailed);
	document.getElementById('ar_export_detailed').value = detailed ? '1' : '0';
	arRefreshPreview();
}

/**
 * Ricarica l'anteprima (Riepilogo o Dettaglio) per gli utenti attualmente
 * selezionati (o tutti, se nessuno e' selezionato): stessa regola usata da
 * stampa ed export, cosi' quello che si vede e' esattamente quello che si
 * otterrebbe scegliendo di stampare o esportare in quel momento.
 */
function arRefreshPreview() {
	var form = document.getElementById('ar_export_form');
	var container = document.getElementById('ar_preview_container');
	var idCourse = document.getElementById('ar_export_idcourse').value;

	if (!idCourse) {
		return;
	}

	var params = new URLSearchParams(new FormData(form));
	params.set('r', 'adm/attendanceregister/preview');

	container.innerHTML = '<div class="ar-empty">...</div>';

	var xhr = new XMLHttpRequest();
	xhr.open('GET', 'ajax.adm_server.php?' + params.toString(), true);
	xhr.onload = function () {
		if (xhr.status !== 200) {
			return;
		}
		container.innerHTML = xhr.responseText;
	};
	xhr.send();
}

/**
 * Stampa quello che e' gia' visibile nell'anteprima (vedi @media print in
 * pandp-ui.css): nessuna richiesta separata, e' lo stesso contenuto che si
 * sta guardando in quel momento.
 */
function arDoPrint() {
	window.print();
}

/**
 * Esporta (Excel o Word) gli utenti attualmente selezionati (o tutti se
 * nessuno e' selezionato), nella modalita' (Riepilogo/Dettaglio) corrente.
 */
function arDoExport(format) {
	var form = document.getElementById('ar_export_form');
	document.getElementById('ar_export_r').value = 'adm/attendanceregister/export_' + format;
	form.submit();
}
