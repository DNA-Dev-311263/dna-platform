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

echo Form::openForm('add_location', 'ajax.adm_server.php?r=alms/location/insertLocation')
	//ABR: Aggiunto virtual
	.Form::getTextfield(
		Lang::t('_NAME', 'location'),
		'location',
		'location',
		255,
		$location->location
	)
	.Form::getCheckbox(
		Lang::t('_VIRTUAL', 'location'),
		'location_virtual',
		'location_virtual',
		'1',
		'0'
	)
	.Form::closeForm();
