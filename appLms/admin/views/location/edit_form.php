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
  
echo Form::openForm('upd_location', 'ajax.adm_server.php?r=alms/location/updatelocation')
	//ABR: Aggiunto virtual
	.Form::gethidden('location_id','location_id',$location->location_id)
	
	.Form::getTextfield(
		Lang::t('_NAME', 'location'),
		'location_new',
		'location_new',
		255,
		$location->location
	)
	.Form::getCheckbox(
		Lang::t('_VIRTUAL', 'location'),
		'location_virtual',
		'location_virtual',
		'1',
		(bool)$location->location_virtual
	)
	.Form::closeForm();
