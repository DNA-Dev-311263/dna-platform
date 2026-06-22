document.addEventListener('DOMContentLoaded', function () {
	var courseSelect = document.getElementById('ar_course_select');
	var companyField = document.getElementById('ar_company_field');
	var companySelect = document.getElementById('ar_company_select');
	var split = document.getElementById('ar_split');
	var usersContainer = document.getElementById('ar_users_container');
	var detail = document.getElementById('ar_detail');
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
		detail.innerHTML = '<div class="ar-detail__empty"></div>';

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
});

/**
 * Espande/richiude la riga di dettaglio (sessioni singole) sotto una riga
 * giorno, nel pannello dettaglio. Definita qui (non inline nel frammento
 * AJAX) perche' i <script> iniettati via innerHTML non vengono eseguiti dal
 * browser: serve una funzione globale gia' caricata sulla pagina.
 */
function arToggleDay(rowEl) {
	var detailRow = rowEl.nextElementSibling;
	var caret = rowEl.querySelector('.ar-caret');
	if (!detailRow) {
		return;
	}
	var isOpen = detailRow.style.display !== 'none';
	detailRow.style.display = isOpen ? 'none' : '';
	if (caret) {
		caret.innerHTML = isOpen ? '&#9656;' : '&#9662;';
	}
}

var arPendingDetailed = false;

/**
 * Apre il popup "Stampa / Excel / Word" dopo aver scelto Riepilogo o
 * Dettaglio. La scelta si applica agli utenti selezionati (checkbox o
 * username cliccata), o a tutti se nessuno e' selezionato.
 */
function arOpenFormatModal(detailed) {
	arPendingDetailed = detailed;
	document.getElementById('ar_format_modal').style.display = 'flex';
}

function arCloseFormatModal() {
	document.getElementById('ar_format_modal').style.display = 'none';
}

function arRunAction(action) {
	arCloseFormatModal();

	var form = document.getElementById('ar_export_form');
	document.getElementById('ar_export_detailed').value = arPendingDetailed ? '1' : '0';

	if (action === 'excel' || action === 'word') {
		document.getElementById('ar_export_r').value = 'adm/attendanceregister/export_' + action;
		form.submit();
		return;
	}

	arPrintSelected();
}

/**
 * Stampa (Riepilogo o Dettaglio) degli utenti selezionati, o di tutti se
 * nessuno e' selezionato: carica via AJAX la sezione di ciascuno nell'area
 * dedicata #ar_print_multi, poi richiama la stampa del browser. Al termine
 * l'area viene svuotata, cosi' non resta nulla "nascosto" in pagina.
 */
function arPrintSelected() {
	var form = document.getElementById('ar_export_form');
	var params = new URLSearchParams(new FormData(form));
	params.set('r', 'adm/attendanceregister/print_selected');

	var printArea = document.getElementById('ar_print_multi');
	printArea.innerHTML = '<div class="ar-empty">...</div>';

	var xhr = new XMLHttpRequest();
	xhr.open('GET', 'ajax.adm_server.php?' + params.toString(), true);
	xhr.onload = function () {
		if (xhr.status !== 200) {
			return;
		}
		printArea.innerHTML = xhr.responseText;
		document.body.classList.add('ar-printing-multi');
		window.print();
	};
	xhr.send();
}

window.addEventListener('afterprint', function () {
	document.body.classList.remove('ar-printing-multi');
	var printArea = document.getElementById('ar_print_multi');
	if (printArea) {
		printArea.innerHTML = '';
	}
});
