<?php

defined('IN_FORMA') or exit('Direct access is forbidden.');

class ImpersonateAdmController extends AdmController
{
    public function init()
    {
        $session = \FormaLms\lib\Session\SessionManager::getInstance()->getSession();
        $parts   = explode('/', (string) FormaLms\lib\Get::req('r', DOTY_MIXED, ''));
        $action  = end($parts);
        // stop() viene chiamata con l'identità dell'utente impersonato (non-admin):
        // saltiamo checkPerm; la sicurezza è garantita dalla presenza di _su_original_idst
        if ($action === 'stop' && $session->has('_su_original_idst')) {
            return;
        }
        parent::init();
    }

    /**
     * Avvia l'impersonazione: sostituisce la sessione con quella dell'utente target
     * e redirige al front-office. Riservato esclusivamente ai GodAdmin.
     */
    protected function start()
    {
        if (Docebo::user()->getUserLevelId() != ADMIN_GROUP_GODADMIN) {
            Util::jump_to('index.php?r=adm/usermanagement/show');
            return;
        }

        $target_idst = (int) FormaLms\lib\Get::req('idst', DOTY_INT, 0);
        if ($target_idst <= 0) {
            Util::jump_to('index.php?r=adm/usermanagement/show');
            return;
        }

        $acl_man = Docebo::user()->getAclManager();

        $target_data = $acl_man->getUser($target_idst, false);
        if (!$target_data) {
            Util::jump_to('index.php?r=adm/usermanagement/show');
            return;
        }

        // Impedisce di impersonare altri GodAdmin
        if ($acl_man->getUserLevelId($target_idst) == ADMIN_GROUP_GODADMIN) {
            Util::jump_to('index.php?r=adm/usermanagement/show');
            return;
        }

        $session = \FormaLms\lib\Session\SessionManager::getInstance()->getSession();

        // Salva identità originale del GodAdmin
        $session->set('_su_original_idst', Docebo::user()->getIdSt());
        $session->set('_su_original_username', $session->get('public_area_username'));

        // Imposta identità del target (userid con prefisso completo, es. /pandp/mario.rossi)
        $target_username = $target_data[ACL_INFO_USERID];
        $session->set('public_area_idst', $target_idst);
        $session->set('public_area_username', $target_username);

        if ($session->has('user')) {
            $session->remove('user');
        }

        $session->save();

        Util::jump_to(FormaLms\lib\Get::rel_path('lms') . '/index.php');
    }

    /**
     * Termina l'impersonazione: ripristina la sessione del GodAdmin originale
     * e redirige alla lista utenti nel backoffice.
     */
    protected function stop()
    {
        $session = \FormaLms\lib\Session\SessionManager::getInstance()->getSession();

        $original_idst = (int) $session->get('_su_original_idst', 0);

        if ($original_idst <= 0) {
            Util::jump_to(FormaLms\lib\Get::rel_path('base') . '/appCore/index.php?r=adm/usermanagement/show');
            return;
        }

        $original_username = $session->get('_su_original_username', '');

        $session->set('public_area_idst', $original_idst);
        $session->set('public_area_username', $original_username);

        if ($session->has('user')) {
            $session->remove('user');
        }

        $session->remove('_su_original_idst');
        $session->remove('_su_original_username');

        $session->save();

        Util::jump_to(FormaLms\lib\Get::rel_path('base') . '/appCore/index.php?r=adm/usermanagement/show');
    }
}
