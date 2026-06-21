var counter = 1;

function addFile()
{
	var hidden = YAHOO.util.Dom.get('file_number');
	var div = YAHOO.util.Dom.get('file');

	counter++;

	hidden.value = counter;

	var new_div = document.createElement('div');
	new_div.id = 'div_file_' + hidden.value;
	new_div.innerHTML = '<div class="form_line_l"><p>' +
						'<label class="floating" for="file_1">' + _FILE_TO_SEND + '</label></p>' +
						'<input type="file" class="fileupload" id="file_' + counter + '" name="file_' + counter + '" value="" alt="' + _FILE_TO_SEND + '" /> ' +
						_MAX + ' <a href="#" onclick="delFile(\'' + counter + '\'); return false;"><span id="rem_span">' + _DEL + '</span><a></div>';

	div.appendChild(new_div);
}

function delFile(id_file)
{
	var field_to_rem = YAHOO.util.Dom.get('div_file_' + id_file);
	field_to_rem.parentNode.removeChild(field_to_rem);
}

document.addEventListener('DOMContentLoaded', function () {
	// Shared recipients accumulator: union of prefill (resend) + course-based +
	// additional Users/Groups/Org chart selections, Section B
	var prefillField = document.getElementById('prefill_idst');
	var courseField = document.getElementById('course_recipients_idst');
	var selectorField = document.getElementById('selector_recipients_idst');
	var recipientsField = document.getElementById('recipients_idst');
	var courseInfoDiv = document.getElementById('nl_course_recipients_info');
	var selectorInfoDiv = document.getElementById('nl_selector_recipients_info');

	function splitIdst(field) {
		return (field && field.value) ? field.value.split(',').filter(function (v) { return v !== ''; }) : [];
	}

	function recomputeRecipients() {
		var all = splitIdst(prefillField).concat(splitIdst(courseField)).concat(splitIdst(selectorField));
		var unique = all.filter(function (v, idx, arr) { return arr.indexOf(v) === idx; });

		if (recipientsField) {
			recipientsField.value = unique.join(',');
		}
		if (courseInfoDiv) {
			courseInfoDiv.textContent = _NL_COURSE_RECIPIENTS_COUNT + ': ' + splitIdst(courseField).length;
		}
		if (selectorInfoDiv) {
			selectorInfoDiv.textContent = _NL_SELECTOR_RECIPIENTS_COUNT + ': ' + splitIdst(selectorField).length;
		}
	}

	function mergeIntoField(field, idstList) {
		if (!field) {
			return;
		}
		var merged = splitIdst(field).concat(idstList.map(String));
		var unique = merged.filter(function (v, idx, arr) { return arr.indexOf(v) === idx; });
		field.value = unique.join(',');
		recomputeRecipients();
	}

	recomputeRecipients();

	// History delete (must run before the "courseSelect" early-return below:
	// this page-level link exists on the Storico tab, where #nl_course_select
	// does not).
	var deleteLinks = document.querySelectorAll('.nl_delete_history');
	for (var di = 0; di < deleteLinks.length; di++) {
		deleteLinks[di].addEventListener('click', function (e) {
			e.preventDefault();
			var link = this;
			if (!confirm(link.getAttribute('data-confirm'))) {
				return;
			}
			var id = link.getAttribute('data-id');
			var xhr = new XMLHttpRequest();
			xhr.open('GET', 'index.php?modname=newsletter&op=ajax_delete_history&id_send=' + encodeURIComponent(id), true);
			xhr.onload = function () {
				if (xhr.status !== 200) {
					return;
				}
				var data = JSON.parse(xhr.responseText);
				if (data.success) {
					link.closest('tr').remove();
				}
			};
			xhr.send();
		});
	}

	// Course/edition recipients block
	var courseSelect = document.getElementById('nl_course_select');
	var editionSelect = document.getElementById('nl_edition_select');
	var addBtn = document.getElementById('nl_add_course_recipients');
	var removeBtn = document.getElementById('nl_remove_course_recipients');

	if (removeBtn) {
		removeBtn.addEventListener('click', function () {
			if (courseField) {
				courseField.value = '';
			}
			recomputeRecipients();
		});
	}

	// Additional recipients block (Users / Groups / Org chart selector)
	var addSelectorBtn = document.getElementById('nl_add_selector_recipients');
	if (addSelectorBtn) {
		addSelectorBtn.addEventListener('click', function () {
			// The widget keeps each tab's selection in its own JS object
			// (not in the userselector_input_main_selector hidden field, which
			// is only filled on the form's "submit" event). Read the current
			// selection directly from each tab's selector instance.
			var parts = [];

			if (typeof UserSelector_main_selector !== 'undefined' && UserSelector_main_selector.oTable) {
				var userSel = UserSelector_main_selector.oTable.innerSelector.toString();
				if (userSel) {
					parts.push(userSel);
				}
			}
			if (typeof GroupSelector_main_selector !== 'undefined' && GroupSelector_main_selector.oTable) {
				var groupSel = GroupSelector_main_selector.oTable.innerSelector.toString();
				if (groupSel) {
					parts.push(groupSel);
				}
			}
			if (typeof TreeView_orgchart_selector_tree_main_selector !== 'undefined') {
				var orgSel = TreeView_orgchart_selector_tree_main_selector.oSelector.toString();
				if (orgSel) {
					parts.push(orgSel);
				}
			}

			var selection = parts.join(',');

			if (!selection) {
				return;
			}

			// POST requests are required to carry the anti-CSRF "authentic_request"
			// signature (see Util::checkSignature()), otherwise the framework
			// redirects the request to the logout page. The signature is rendered
			// as a hidden field by Form::openForm() for the newsletter_form.
			var signatureField = document.getElementById('authentic_request_newsletter_form');
			var signature = signatureField ? signatureField.value : '';

			var xhr = new XMLHttpRequest();
			xhr.open('POST', 'index.php?modname=newsletter&op=ajax_resolve_recipients', true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.onload = function () {
				if (xhr.status !== 200) {
					return;
				}
				var data = JSON.parse(xhr.responseText);
				if (data.idst) {
					mergeIntoField(selectorField, data.idst);
				}
			};
			xhr.send('userselector_input%5Bmain_selector%5D=' + encodeURIComponent(selection) + '&authentic_request=' + encodeURIComponent(signature));
		});
	}

	if (!courseSelect) {
		return;
	}

	// Load courses
	var xhrCourses = new XMLHttpRequest();
	xhrCourses.open('GET', 'index.php?modname=newsletter&op=ajax_get_courses', true);
	xhrCourses.onload = function () {
		if (xhrCourses.status !== 200) {
			return;
		}
		var courses = JSON.parse(xhrCourses.responseText);
		for (var i = 0; i < courses.length; i++) {
			var opt = document.createElement('option');
			opt.value = courses[i].id;
			opt.textContent = courses[i].name;
			courseSelect.appendChild(opt);
		}
	};
	xhrCourses.send();

	courseSelect.addEventListener('change', function () {
		editionSelect.innerHTML = '';
		editionSelect.style.display = 'none';

		var idCourse = courseSelect.value;
		if (!idCourse) {
			return;
		}

		var xhrEditions = new XMLHttpRequest();
		xhrEditions.open('GET', 'index.php?modname=newsletter&op=ajax_get_course_editions&id_course=' + encodeURIComponent(idCourse), true);
		xhrEditions.onload = function () {
			if (xhrEditions.status !== 200) {
				return;
			}
			var editions = JSON.parse(xhrEditions.responseText);
			if (editions.length === 0) {
				return;
			}

			var allOpt = document.createElement('option');
			allOpt.value = '';
			allOpt.textContent = _NL_ALL_EDITIONS;
			editionSelect.appendChild(allOpt);

			for (var i = 0; i < editions.length; i++) {
				var opt = document.createElement('option');
				opt.value = editions[i].id;
				opt.textContent = editions[i].name;
				editionSelect.appendChild(opt);
			}
			editionSelect.style.display = 'inline';
		};
		xhrEditions.send();
	});

	addBtn.addEventListener('click', function () {
		var idCourse = courseSelect.value;
		if (!idCourse) {
			return;
		}
		var idDate = (editionSelect.style.display !== 'none') ? editionSelect.value : '';

		var listUrl = 'index.php?modname=newsletter&op=ajax_get_course_recipients_count&id_course=' + encodeURIComponent(idCourse) + (idDate ? '&id_date=' + encodeURIComponent(idDate) : '') + '&list=1';

		var xhr = new XMLHttpRequest();
		xhr.open('GET', listUrl, true);
		xhr.onload = function () {
			if (xhr.status !== 200) {
				return;
			}
			var data = JSON.parse(xhr.responseText);

			if (data.idst) {
				mergeIntoField(courseField, data.idst);
			}
		};
		xhr.send();
	});
});
