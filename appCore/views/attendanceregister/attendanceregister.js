var AttendanceRegister = {
	oDialogCaller: {}
};

document.addEventListener('DOMContentLoaded', function () {
	var courseSelect = document.getElementById('ar_course_select');
	var usersContainer = document.getElementById('ar_users_container');
	var exportIdCourse = document.getElementById('ar_export_idcourse');

	if (!courseSelect) {
		return;
	}

	courseSelect.addEventListener('change', function () {
		var idCourse = courseSelect.value;
		exportIdCourse.value = idCourse;

		if (!idCourse) {
			usersContainer.innerHTML = '';
			return;
		}

		usersContainer.innerHTML = '<div class="ar-loading">...</div>';

		var xhr = new XMLHttpRequest();
		xhr.open('GET', 'ajax.adm_server.php?r=adm/attendanceregister/course_users&idCourse=' + encodeURIComponent(idCourse), true);
		xhr.onload = function () {
			if (xhr.status !== 200) {
				return;
			}
			usersContainer.innerHTML = xhr.responseText;
		};
		xhr.send();
	});
});
