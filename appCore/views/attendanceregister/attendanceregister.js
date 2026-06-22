document.addEventListener('DOMContentLoaded', function () {
	var courseSelect = document.getElementById('ar_course_select');
	var split = document.getElementById('ar_split');
	var usersContainer = document.getElementById('ar_users_container');
	var detail = document.getElementById('ar_detail');
	var exportIdCourse = document.getElementById('ar_export_idcourse');

	if (!courseSelect) {
		return;
	}

	courseSelect.addEventListener('change', function () {
		var idCourse = courseSelect.value;
		exportIdCourse.value = idCourse;

		if (!idCourse) {
			split.style.display = 'none';
			return;
		}

		split.style.display = '';
		usersContainer.innerHTML = '<div class="ar-empty">...</div>';
		detail.innerHTML = '<div class="ar-detail__empty"></div>';

		var xhr = new XMLHttpRequest();
		xhr.open('GET', 'ajax.adm_server.php?r=adm/attendanceregister/course_users&idCourse=' + encodeURIComponent(idCourse) + '&authentic_request=' + encodeURIComponent(AR_SIGNATURE), true);
		xhr.onload = function () {
			if (xhr.status !== 200) {
				return;
			}
			usersContainer.innerHTML = xhr.responseText;
		};
		xhr.send();
	});
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

/**
 * Stampa il pannello dettaglio: forza tutte le righe giorno
 * espanse (true) o richiuse (false), stampa, poi ripristina lo stato
 * com'era prima (cosi' la stampa non altera quello che l'utente vedeva).
 */
function arPrint(showDetail) {
	var dayDetailRows = document.querySelectorAll('#ar_print_area tr.ar-day-detail');
	var carets = document.querySelectorAll('#ar_print_area .ar-caret');
	var previousDisplay = [];
	var previousCaret = [];

	dayDetailRows.forEach(function (row, i) {
		previousDisplay[i] = row.style.display;
		row.style.display = showDetail ? '' : 'none';
	});
	carets.forEach(function (caret, i) {
		previousCaret[i] = caret.innerHTML;
		caret.innerHTML = showDetail ? '&#9662;' : '&#9656;';
	});

	function restore() {
		dayDetailRows.forEach(function (row, i) { row.style.display = previousDisplay[i]; });
		carets.forEach(function (caret, i) { caret.innerHTML = previousCaret[i]; });
		window.removeEventListener('afterprint', restore);
	}
	window.addEventListener('afterprint', restore);

	window.print();
}
