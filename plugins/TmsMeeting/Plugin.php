<?php
namespace Plugin\TmsMeeting;
defined("IN_FORMA") or die('Direct access is forbidden.');

/* ======================================================================== \
|   FORMA - The E-Learning Suite                                            |
|                                                                           |
|   Copyright (c) 2013 (Forma)                                              |
|   http://www.formalms.org                                                 |
|   License  http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt           |
|                                                                           |
|   from docebo 4.0.5 CE 2008-2012 (c) docebo                               |
|   License http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt            |
\ ======================================================================== */


class Plugin extends \FormaPlugin {
    public function install(){
        parent::addSetting('tmsmeeting.site_url', 'string', 255, 'https://login.microsoftonline.com', 2);
        parent::addSetting('tmsmeeting.scope_url', 'string', 255, 'https://graph.microsoft.com', 4);
        parent::addSetting('tmsmeeting.auth_url', 'string', 255, 'https://entra.microsoft.com/', 6);
        parent::addSetting('tmsmeeting.tenant_id', 'string', 255, '', 8);
        parent::addSetting('tmsmeeting.client_id', 'string', 255, '', 10);
        parent::addSetting('tmsmeeting.client_secret', 'string', 255, '', 12);
        parent::addSetting('tmsmeeting.organizer_email', 'string', 255, 'trainer@mysite.com', 14);
        
        // addRequest is used to attach TmsMeetingAdmController to the request r=alms/tmsMeeting/XXX
        self::addRequest("alms", "tmsmeeting", "TmsmeetingAlmsController", "TmsMeetingAlms");
        
        // Nascondo setting aggiornati dal sistema
		// $query = "	UPDATE %adm_setting SET hide_in_modify = '1' WHERE param_name IN ('tmsMeeting.refresh_token', 'tmsMeeting.access_token', 'tmsMeeting.access_token_expiration') AND pack = 'TmsMeeting'";
		// return sql_query($query);
    }
    
    public function uninstall()
    {
        //code executed after uninstall
    }

    public function activate()
    {
		//code executed after activate
    }

    public function deactivate()
    {
        // Svuoto i setting per il reset di autenticazione
		//$query = "	UPDATE %adm_setting SET param_value = '' WHERE param_name IN ('tmsMeeting.access_token', 'tmsMeeting.access_token_expiration') AND pack = 'TmsMeeting'";
		//return sql_query($query);
    }
    

}
