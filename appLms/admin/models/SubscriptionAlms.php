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
 * Mod. ABR	
 */

defined('IN_FORMA') or exit('Direct access is forbidden');

class SubscriptionAlms extends Model
{
    protected $db;
    protected $acl_man;

    public $id_course;
    public $id_edition;
    public $id_date;

    public $data;
    public $js_user;
    public $level;
    public $status;
    public $user_data;
    public $course_data;

    public function __construct($id_course = false, $id_edition = false, $id_date = false)
    {
        $this->db = DbConn::getInstance();
        $this->acl_man = Docebo::user()->getAclManager();
        $this->setCourseData($id_course, $id_edition, $id_date);
        parent::__construct();
    }

    public function setCourseData($id_course, $id_edition = false, $id_date = false)
    {
        $this->setIdCourse($id_course);
        $this->setIdEdition($id_edition);
        $this->setIdDate($id_date);
    }

    public function setIdCourse($id_course)
    {
        $this->id_course = (int) $id_course;
    }

    public function setIdEdition($id_edition)
    {
        $this->id_edition = (int) $id_edition;
    }

    public function setIdDate($id_date)
    {
        $this->id_date = (int) $id_date;
    }

    public function loadUser($start_index = false, $results = false, $sort = false, $dir = false, $filter = false, $adminFilter = true)
    {
        if ($this->id_edition != 0) {
            require_once Forma::include(_lms_ . '/lib/', 'lib.edition.php');
            $edition_man = new EditionManager();

            return $edition_man->getCourseEditionSubscription($this->id_course, $this->id_edition, $start_index, $results, $sort, $dir, $filter, $adminFilter);
        } elseif ($this->id_date != 0) {
            require_once Forma::include(_lms_ . '/lib/', 'lib.date.php');
            $date_man = new DateManager();

            return $date_man->getCourseEditionSubscription($this->id_course, $this->id_date, $start_index, $results, $sort, $dir, $filter, $adminFilter);
        } else {
            require_once Forma::include(_lms_ . '/lib/', 'lib.subscribe.php');
            $subscribe_man = new CourseSubscribe_Manager();

            return $subscribe_man->getCourseSubscription($this->id_course, $start_index, $results, $sort, $dir, $filter, $adminFilter);
        }
    }

    public function totalUser($filter = false, $level = false)
    {
        if ($this->id_edition != 0) {
            require_once _lms_ . '/lib/lib.edition.php';
            $edition_man = new EditionManager();

            return $edition_man->getTotalUserSubscribed($this->id_course, $this->id_edition, $filter);
        } elseif ($this->id_date != 0) {
            require_once _lms_ . '/lib/lib.date.php';

            $date_man = new DateManager();

            return $date_man->getTotalUserSubscribed($this->id_course, $this->id_date, $filter);
        } else {
            require_once _lms_ . '/lib/lib.subscribe.php';

            $subscribe_man = new CourseSubscribe_Manager();

            return $subscribe_man->getTotalUserSubscribed($this->id_course, $filter, $level);
        }
    }

    public function getIdCourse()
    {
        return $this->id_course;
    }

    public function getIdEdition()
    {
        return $this->id_edition;
    }

    public function getIdDate()
    {
        return $this->id_date;
    }

    public function loadUserSelectorSelection()
    {
        if ($this->id_edition != 0) {
            require_once _lms_ . '/lib/lib.edition.php';
            $edition_man = new EditionManager();

            return $edition_man->getEditionSubscribed($this->id_edition);
        } elseif ($this->id_date != 0) {
            require_once _lms_ . '/lib/lib.date.php';

            $date_man = new DateManager();

            return $date_man->getDateSubscribed($this->id_date);
        } else {
            require_once _lms_ . '/lib/lib.subscribe.php';

            $subscribe_man = new CourseSubscribe_Manager();

            return $subscribe_man->getCourseSubscribedUserIdst($this->id_course);
        }
    }

    public function loadSelectedUser($user_selected)
    {
        require_once _lms_ . '/lib/lib.subscribe.php';

        $subscribe_man = new CourseSubscribe_Manager();

        $this->js_user = 'var elementi = new Array(';
        $i = 0;

        foreach ($user_selected as $id_user) {
            if ($i != 0) {
                $this->js_user .= ',';
            }
            ++$i;

            $this->js_user .= "'" . $id_user . "'";
        }

        $this->data = $this->acl_man->getUsers($user_selected);

        $this->level = $subscribe_man->getUserLevel();
    }

    public function getSubscriptionsList($filter = false)
    {
        $output = false;
        if ($this->id_edition != 0) {
            require_once _lms_ . '/lib/lib.edition.php';
            $edition_man = new EditionManager();
            $output = $edition_man->getEditionSubscribed($this->id_edition, false, $filter);
        } elseif ($this->id_date != 0) {
            require_once _lms_ . '/lib/lib.date.php';
            $date_man = new DateManager();
            $output = $date_man->getDateSubscribed($this->id_date, $filter);
        } else {
            require_once _lms_ . '/lib/lib.subscribe.php';
            $subscribe_man = new CourseSubscribe_Manager();
            $output = $subscribe_man->getCourseSubscribedUserIdst($this->id_course, false, $filter);
        }

        return $output;
    }

    public function getCourseInfoForSubscription()
    {
        if ($this->id_edition != 0) {
            require_once _lms_ . '/lib/lib.edition.php';
            $edition_man = new EditionManager();

            $edition_info = $edition_man->getEditionInfo($this->id_edition);

            require_once _lms_ . '/lib/lib.course.php';

            $course_info = Man_Course::getCourseInfo($this->id_course);

			//ABR: aggiunto 'id_course', 'status' e 'idCategory' all'array di output
            $res = [
				'id_course' => $course_info['idCourse'],
                'max_num_subscribe' => $edition_info['max_num_subscribe'],
                'subscribe_method' => $course_info['subscribe_method'],
                'code' => $edition_info['code'],
                'name' => $edition_info['name'],
                'medium_time' => $course_info['mediumTime'],
                'course_type' => $course_info['course_type'],
                'date_begin' => $edition_info['date_begin'],
                'date_end' => $edition_info['date_end'],
                'sendCalendar' => $course_info['sendCalendar'],
                'status' => $edition_info['status'],
                'idCategory' => $course_info['idCategory']
            ];
        } elseif ($this->id_date != 0) {
            require_once _lms_ . '/lib/lib.date.php';

            $date_man = new DateManager();

            $date_info = $date_man->getDateInfo($this->id_date);

            require_once _lms_ . '/lib/lib.course.php';

            $course_info = Man_Course::getCourseInfo($this->id_course);
            
            
           //ABR: aggiunto 'id_course', 'status', 'idCategory' e 'course_virtual' all'array di output

            $res = [
				'id_course' => $course_info['idCourse'],
                'max_num_subscribe' => $course_info['max_num_subscribe'],
                'subscribe_method' => $course_info['subscribe_method'],
                'code' => $date_info['code'],
                'name' => $date_info['name'],
                'medium_time' => $course_info['mediumTime'],
                'course_type' => $course_info['course_type'],
                'date_begin' => $date_info['date_begin'],
                'date_end' => $date_info['date_end'],
                'sendCalendar' => $course_info['sendCalendar'],
                'status' => $date_info['status'],
                'idCategory' => $course_info['idCategory'],
                'course_virtual' => $course_info['course_virtual']
            ];
        } else {
            require_once _lms_ . '/lib/lib.course.php';

            $course_info = Man_Course::getCourseInfo($this->id_course);

            $res = [
                'max_num_subscribe' => $course_info['max_num_subscribe'],
                'subscribe_method' => $course_info['subscribe_method'],
                'code' => $course_info['code'],
                'name' => $course_info['name'],
                'medium_time' => $course_info['mediumTime'],
                'course_type' => $course_info['course_type'],
                'date_begin' => $course_info['date_begin'],
                'date_end' => $course_info['date_end'],
                'sendCalendar' => $course_info['sendCalendar'],
            ];
        }

        return $res;
    }

    public function getCoursepathNameForSubscription($id_path)
    {
        $query = 'SELECT path_code, path_name'
                    . ' FROM %lms_coursepath'
                    . ' WHERE id_path = ' . (int) $id_path;

        list($code, $name) = sql_fetch_row(sql_query($query));

        $res = ($code !== '' ? '[' . $code . '] ' : '') . $name;

        return $res;
    }

    public function subscribeUser($id_user, $level, $waiting, $date_begin_validity = false, $date_expire_validity = false, $overbooking = 0)
    {
		//Mod. ABR
		
		require_once(_lms_.'/lib/lib.courseassn.php'); //ABR
		$courseassn_man = new CourseassnManager();
		$res = false;
		
        if ($this->id_edition != 0) {
            require_once _lms_ . '/lib/lib.edition.php';
            $edition_man = new EditionManager();

            $res = $edition_man->subscribeUserToEdition($id_user, $this->id_course, $this->id_edition, $level, $waiting, $date_begin_validity, $date_expire_validity);
 			
 			if( $res ) {
				//ABR: aggiorno assegnazione se c'è coincidenza corso-utente e l'assegnazione è aperta
				$res = $courseassn_man->updAssnEditionSubs($id_user, $this->id_course, $this->id_edition);
			}      
        
        } elseif ($this->id_date != 0) { // classroom enrollment
            require_once _lms_ . '/lib/lib.date.php';
            $date_man = new DateManager();

            $res = $date_man->subscribeUserToDate($id_user, $this->id_course, $this->id_date, $level, $waiting, $date_begin_validity, $date_expire_validity);
            
			if( $res ) {
				//ABR: aggiorno assegnazione se c'è coincidenza corso-utente e l'assegnazione è aperta
				$res = $courseassn_man->updAssnEditionSubs($id_user, $this->id_course, $this->id_date);
			}
				
        } else {
            require_once _lms_ . '/lib/lib.subscribe.php'; // elearning enrollment
            $subscribe_man = new CourseSubscribe_Manager();

            $res = $subscribe_man->subscribeUserToCourse($id_user, $this->id_course, $level, $waiting, $date_begin_validity, $date_expire_validity, $overbooking);
        }
        
        return $res;
    }

    public function delUser($id_user)
    {
		//Mod. ABR
		
		require_once(_lms_.'/lib/lib.courseassn.php'); 	//ABR
		$replacement_id = false; 						//ABR
		$courseassn_man = new CourseassnManager(); 		//ABR
        $cmodel = new CourseAlms($this->id_course, $this->id_date, $this->id_edition);
        
        
        if ($this->id_edition != 0) {
            require_once _lms_ . '/lib/lib.edition.php';
            $edition_man = new EditionManager();
            $ret = $edition_man->delUserFromEdition($id_user, $this->id_course, $this->id_edition);
            
            //ABR: Aggiorno l'assegnazione se l'utente è stato eliminato dall'edizione
			if ($ret) {
				//Controllo se ha altre iscrizioni aperte associabili all'assegnazione (stesso corso, altra edizione)
				
				$subs_compatible = $courseassn_man->getSubsCompatible($this->id_course, $id_user);
				if ($subs_compatible) {
					$replacement_id = reset($subs_compatible)['id_edition'];
				}
				
				// Aggiorno l'edizione
				$ret = $courseassn_man->updAssnEditionExistent($this->id_course, $this->id_edition, $replacement_id, $id_user);
			}
				
        } elseif ($this->id_date != 0) {
            require_once _lms_ . '/lib/lib.date.php';
            $date_man = new DateManager();
            // managing overbooked user on course_date_user here

            $ret = $date_man->delUserFromDate($id_user, $this->id_course, $this->id_date);
            
  			//ABR: Aggiorno l'assegnazione se l'utente è stato tolto dalla classe
			if ($ret) {
				//Controllo se ha altre iscrizioni aperte associabili all'assegnazione (stesso corso, altra edizione)
				
				$subs_compatible = $courseassn_man->getSubsCompatible($this->id_course, $id_user);
				if ($subs_compatible) {
					$replacement_id = reset($subs_compatible)['id_date'];
				}
				$ret = $courseassn_man->updAssnEditionExistent($this->id_course, $this->id_date, $replacement_id, $id_user);
			}          
            
            
        // Rimossa funzione inserita precedentemente perchè già presente nella delUserFromDate -> removeUserFromDate
        } else {
            require_once _lms_ . '/lib/lib.subscribe.php';
            $subscribe_man = new CourseSubscribe_Manager();
            $ret = $subscribe_man->delUserFromCourse($id_user, $this->id_course);
        }
        /* enrolling first overbooked user, if any */
        if ($ret) {
            // For classroom courses, be sure that is enabled overbooking in the course, not in the edition, or this method will return null
            $user_to_enroll = $cmodel->getFirstOverbooked();
            if ($user_to_enroll) {
                $course_info = $this->getCourseInfoForSubscription();
                $status = ($course_info['subscribe_method'] == 1 ? -2 : 0); // check for need approval

                $query = 'UPDATE %lms_courseuser  SET status = ' . $status
                . ($status == -2 ? ' ,waiting=1' : '')
                . ' WHERE idCourse = ' . $this->id_course . ' AND iduser = ' . $user_to_enroll;

                $sql_query_res = sql_query($query);

                if ($sql_query_res) {
                    require_once _lms_ . '/lib/lib.course.php';
                    require_once _lms_ . '/lib/lib.levels.php';
                    require_once _base_ . '/lib/lib.eventmanager.php';

                    $acl_man = Docebo::user()->getAclManager();

                    $event = $status < 0 ? 'UserCourseInsertModerate' : 'UserCourseInserted';
                    $isEventEnabled = getEnabledEvent($event);

                    if ($isEventEnabled) {
                        // message to user that is waiting

                        $msg_composer = new EventMessageComposer('subscribe', 'lms');
                        $description_event = 'User subscribed';
                        if ($status < 0) { // Waiting
                            $description_event .= ' with moderation';
                            $subject_key = '_NEW_USER_SUBS_WAITING_SUBJECT';
                            $body_key = '_NEW_USER_SUBS_WAITING_TEXT';
                        } else {
                            $subject_key = '_NEW_USER_SUBSCRIBED_SUBJECT';
                            $body_key = '_NEW_USER_SUBSCRIBED_TEXT_MODERATORS';
                        }
                        $array_subst = [
                            '[url]' => FormaLms\lib\Get::site_url(),
                            '[course]' => $course_info['name'],
                            '[firstname]' => $userinfo[ACL_INFO_FIRSTNAME], //istantiate user_info with the user to enroll if you want to enable this
                            '[lastname]' => $userinfo[ACL_INFO_LASTNAME],
                            '[userid]' => $acl_man->relativeId($userinfo[ACL_INFO_USERID]),
                        ];

                        $msg_composer->setSubjectLangText('email', $subject_key, false);
                        $msg_composer->setBodyLangText('email', $body_key, $array_subst);
                        // message to user that is waiting

                        $acl = &Docebo::user()->getAcl();
                        $acl_man = &$this->acl_man;

                        $recipients = [];

                        // get all superadmins
                        // no mail to superadmin
                        /*
                            $idst_group_god_admin = $acl->getGroupST(ADMIN_GROUP_GODADMIN);
                        $recipients = $acl_man->getGroupMembers($idst_group_god_admin);
                        */

                        // get all admins
                        $idst_group_admin = $acl->getGroupST(ADMIN_GROUP_ADMIN);
                        $idst_admin = $acl_man->getGroupMembers($idst_group_admin);

                        require_once _adm_ . '/lib/lib.adminmanager.php';

                        foreach ($idst_admin as $id_user) {
                            $adminManager = new AdminManager();
                            $acl_manager = &$acl_man;

                            // st = organization, get all orgs related to the user
                            $idst_associated = $adminManager->getAdminTree($id_user);

                            $array_user = &$acl_manager->getAllUsersFromIdst($idst_associated);
                            $array_user = array_unique($array_user);

                            $control_user = array_search(getLogUserId(), $array_user);
                            if ($control_user === 0) {
                                $control_user = true;
                            }

                            $query = 'SELECT COUNT(*)'
                                . ' FROM ' . FormaLms\lib\Get::cfg('prefix_fw') . '_admin_course'
                                . " WHERE idst_user = '" . $id_user . "'"
                                . " AND type_of_entry = 'course'"
                                . ' AND id_entry in (-1,0,' . $id_course . ')';

                            list($control_course) = sql_fetch_row(sql_query($query));

                            $query = 'SELECT COUNT(*)'
                                . ' FROM ' . FormaLms\lib\Get::cfg('prefix_fw') . '_admin_course'
                                . " WHERE idst_user = '" . $id_user . "'"
                                . " AND type_of_entry = 'coursepath'"
                                . ' AND id_entry IN'
                                . ' ('
                                . ' SELECT id_path'
                                . ' FROM ' . FormaLms\lib\Get::cfg('prefix_lms') . '_coursepath_courses'
                                . " WHERE id_item = '" . $id_course . "'"
                                . ' )';

                            list($control_coursepath) = sql_fetch_row(sql_query($query));

                            $query = 'SELECT COUNT(*)'
                                . ' FROM ' . FormaLms\lib\Get::cfg('prefix_fw') . '_admin_course'
                                . " WHERE idst_user = '" . $id_user . "'"
                                . " AND type_of_entry = 'catalogue'"
                                . ' AND id_entry IN'
                                . ' ('
                                . ' SELECT idCatalogue'
                                . ' FROM ' . FormaLms\lib\Get::cfg('prefix_lms') . '_catalogue_entry'
                                . " WHERE idEntry = '" . $id_course . "'"
                                . ' )';

                            list($control_catalogue) = sql_fetch_row(sql_query($query));

                            if ($control_user && ($control_course || $control_coursepath || $control_catalogue)) {
                                $recipients[] = $id_user;
                            }
                        }

                        $recipients = array_unique($recipients);

                        createNewAlert($event, 'subscribe', 'insert', '1', $description_event, $recipients, $msg_composer);
                    }

                    // Notify the user that is entering to the course

                    $user_info = $acl_man->getUser($user_to_enroll, false);
                    $array_subst = [
                        '[url]' => FormaLms\lib\Get::site_url(),
                        '[course]' => $course_info['name'],
                        '[firstname]' => $user_info[ACL_INFO_FIRSTNAME],
                        '[lastname]' => $user_info[ACL_INFO_LASTNAME],
                        '[userid]' => $acl_man->relativeId($user_info[ACL_INFO_USERID]),
                    ];

                    $recipients = [$user_info[ACL_INFO_EMAIL]];
                    require_once _adm_ . '/lib/lib.usernotifier.php';
                    $user_notification = new DoceboUserNotifier('');
                    $attachments = false;
                    $user_info_arr = [$user_info];
                    if ($status < 0) { // Waiting
                        $subject = Lang::t('NEXT_USER_IN_WAITING_SUBJECT', 'email');
                        $email_body = Lang::t('NEXT_USER_IN_WAITING_BODY', 'email', $array_subst);
                    } else {
                        $subject = Lang::t('NEXT_USER_ENTERED_SUBJECT', 'email');
                        $email_body = Lang::t('NEXT_USER_ENTERED_BODY', 'email', $array_subst);
                        $calendar = CalendarManager::getCalendarDataContainerForDateDays((int) $this->id_course, (int) $this->id_date, (int) $user_info[ACL_INFO_IDST]);
                        $attachments = [$calendar->getFile()];
                    }
                    
               
					$user_notification->_sendMail($subject, $email_body, $attachments, $recipients, $user_info_arr);

                    return true;
                }

                return $sql_query_res;
            }
        }

        return $ret;
    }

    public function getUserLevel($id_user)
    {
        require_once _lms_ . '/lib/lib.subscribe.php';
        $subscribe_man = new CourseSubscribe_Manager();

        return $subscribe_man->getUserLeveInCourse($id_user, $this->id_course);
    }

    public function getLevel()
    {
        require_once _lms_ . '/lib/lib.subscribe.php';

        $subscribe_man = new CourseSubscribe_Manager();

        return $subscribe_man->getUserLevel();
    }

    public function getStatus()
    {
        require_once _lms_ . '/lib/lib.subscribe.php';
        $subscribe_man = new CourseSubscribe_Manager();

        return $subscribe_man->getUserStatus();
    }

    public function updateUserLevel($id_user, $new_level)
    {
        require_once _lms_ . '/lib/lib.subscribe.php';
        $subscribe_man = new CourseSubscribe_Manager();

        return $subscribe_man->updateUserLeveInCourse($id_user, $this->id_course, $new_level);
    }

    public function updateUserDateBeginValidity($id_user, $new_date_begin)
    {
        require_once _lms_ . '/lib/lib.subscribe.php';
        $subscribe_man = new CourseSubscribe_Manager();

        return $subscribe_man->updateUserDateBeginValidityInCourse($id_user, $this->id_course, $new_date_begin);
    }

    public function updateUserDateExpireValidity($id_user, $new_date_expire)
    {
        require_once _lms_ . '/lib/lib.subscribe.php';
        $subscribe_man = new CourseSubscribe_Manager();

        return $subscribe_man->updateUserDateExpireValidityInCourse($id_user, $this->id_course, $new_date_expire);
    }

    public function updateUserStatus($id_user, $new_status)
    {
		//Mod. ABR
		require_once(_lms_.'/lib/lib.courseassn.php');	//ABR
		$courseassn_man = new CourseassnManager(); 
		
		
        if ($this->id_edition != 0) {
            require_once _lms_ . '/lib/lib.subscribe.php';

            $subscribe_man = new CourseSubscribe_Manager();

            if ($new_status == _CUS_END) {
                require_once _lms_ . '/lib/lib.edition.php';
                $edition_man = new EditionManager();

                $edition_man->setEditionFinished($this->id_edition, $id_user);
            }

            $res = $subscribe_man->updateUserStatusInCourse($id_user, $this->id_course, $new_status);
 			
 			//ABR: Aggiornamento stato assegnazioni
			$info_edition = $this->getCourseInfoForSubscription();
			$res = $courseassn_man->updAssnStatus($this->id_course, $this->id_edition, $info_edition['status'], 'elearning', $id_user);

			return $res;
			
        } elseif ($this->id_date != 0) {
            require_once _lms_ . '/lib/lib.subscribe.php';

            $subscribe_man = new CourseSubscribe_Manager();
            require_once _lms_ . '/lib/lib.date.php';

            $date_man = new DateManager();
            if ($new_status == _CUS_END) {
                $date_man->setDateFinished($this->id_date, $id_user);
            }

            if ($new_status == _CUS_OVERBOOKING) {
                $date_man->setDateOverbooking($this->id_date, $id_user, 1);
            } else {
                $date_man->setDateOverbooking($this->id_date, $id_user, 0);
            }

            $res = $subscribe_man->updateUserStatusInCourse($id_user, $this->id_course, $new_status);
            
			//ABR: Aggiornamento stato assegnazioni
			$info_edition = $this->getCourseInfoForSubscription();
			$res = $courseassn_man->updAssnStatus($this->id_course, $this->id_date, $info_edition['status'], 'classroom', $id_user);

			return $res;
			
        } else {
            require_once _lms_ . '/lib/lib.subscribe.php';

            $subscribe_man = new CourseSubscribe_Manager();
            $courseModel = new CourseLms($this->id_course);		// ABR
            
            if ($new_status == _CUS_END) {
                $courseModel->setCourseDateCompleted($id_user);
            } else {
				$courseModel->eraseCourseDateCompleted($id_user); //ABR
            }

            return $subscribe_man->updateUserStatusInCourse($id_user, $this->id_course, $new_status);
        }
    }

    public function getFastSubscribeList($filter, $limit = false)
    {
        if (!$limit || !is_numeric($limit)) {
            $limit = FormaLms\lib\Get::sett('visuItem', 25);
        }
        $already_subscribed = $this->loadUserSelectorSelection();

        $is_admin = false;

        if (Docebo::user()->getUserLevelId() != ADMIN_GROUP_GODADMIN) {
            require_once _base_ . '/lib/lib.preference.php';
            $adminManager = new AdminPreference();
            $admin_query = $adminManager->getAdminUsersQuery(Docebo::user()->getIdST(), 'idst');
            $is_admin = true;
        }

        $query = 'SELECT idst, userid, firstname, lastname '
                    . ' FROM %adm_user'
                    . " WHERE (userid LIKE '%" . $filter . "%'"
                    . " OR firstname LIKE '%" . $filter . "%'"
                    . " OR lastname LIKE '%" . $filter . "%')"
                    . ($is_admin ? ' AND ' . $admin_query : '')
                    . (!empty($already_subscribed) ? ' AND idst NOT IN (' . implode(',', $already_subscribed) . ') ' : '')
                    . " AND userid<>'/Anonymous' "
                    . ' ORDER BY userid '
                    . ' LIMIT 0, ' . $limit;

        $res = $this->db->query($query);
        $output = [];
        if ($res && $this->db->num_rows($res) > 0) {
            while (list($idst, $userid, $firstname, $lastname) = $this->db->fetch_row($res)) {
                $output[] = [
                    'idst' => $idst,
                    'userid' => $userid,
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                ];
            }
        }

        return $output;
    }

    public function getUserLevelList()
    {
        require_once _lms_ . '/lib/lib.subscribe.php';
        $subscribe_man = new CourseSubscribe_Manager();

        return $subscribe_man->getUserLevel();
    }

    public function getUserStatusList()
    {
        require_once _lms_ . '/lib/lib.subscribe.php';
        $subscribe_man = new CourseSubscribe_Manager();

        return $subscribe_man->getUserStatus();
    }

    public function setUserData($user_data)
    {
        $this->user_data = $user_data;
    }

    public function getUserData()
    {
        return $this->user_data;
    }

    public function setCoursesData($course_data)
    {
        $this->course_data = $course_data;
    }

    public function getCoursesData()
    {
        return $this->course_data;
    }

    public function isUserSubscribed($id_user, $id_course = false)
    {
        if ($id_course == false) {
            $id_course = $this->id_course;
        }
        $subscribe_man = new CourseSubscribe_Manager();

        return $subscribe_man->controlSubscription($id_user, $id_course);
    }

    public function resetValidityDates($id_course, $id_edition, $id_user)
    {
        if ($id_course <= 0 || $id_user <= 0) {
            return false;
        }

        $query = 'UPDATE %lms_courseuser SET date_begin_validity = NULL, date_expire_validity = NULL '
            . ' WHERE idCourse = ' . (int) $id_course . ' AND idUser = ' . (int) $id_user . ' '
            ; //.((int)$id_edition > 0 ? " AND edition_id = ".(int)$id_edition : "");
        $res = sql_query($query);

        return $res ? true : false;
    }
    
    
    /** ABR **
     * Restituisce true se l'edizione è finita
     */
	public function checkEditionFinished($id_date = false, $id_edition = false){

		$res = null;

		// Recupero id edizione dal modello se non è passata in argomento
		$id_date = (!$id_date ? $this->id_date : $id_date);
		$id_edition = (!$id_edition ? $this->id_edition : $id_edition);

		// Controllo stato
		if ($id_date > 0){
				require_once(_lms_ . '/lib/lib.date.php');

				$dt_man = new DateManager;
				$row = $dt_man->getDateInfo($id_date);
				$res = ($row['status'] == _DATE_STATUS_FINISHED);

		}elseif ($id_edition > 0){
				require_once(_lms_ . '/lib/lib.edition.php');

				$ed_man = new EditionManager;
				$row = $ed_man->getEditionInfo($id_edition);
				$res = ($row['status'] == CST_CONCLUDED);
		}

		// Output
		return $res;
	}
	


	public function unsubscribeUser($id_user, $id_course, $id_edition = false, $id_date = false)
    {
        $this->id_course = $id_course;
        $this->id_edition = $id_edition;
        $this->id_date = $id_date;

        $this->unsetUnsubscribeRequest($id_user, $id_course, $id_edition, $id_date);

        return $this->delUser($id_user);

        /* require_once(_lms_ . '/lib/lib.course.php');
        $docebo_course = new DoceboCourse($id_course);

        $level_idst = & $docebo_course->getCourseLevel($id_course);
        //$level = $this->getUserLevel($id_user);

        require_once(_lms_.'/lib/lib.subscribe.php');
        $subscribe_man = new CourseSubscribe_Manager();
        $level = $subscribe_man->getUserLeveInCourse($id_user, $id_course);

        $res = FALSE;
        if ($subscribe_man->delUserFromCourse($id_user, $id_course)) {
            if ($id_edition == 0 && $id_date == 0)
                $this->acl_man->removeFromGroup($level_idst[$level], $id_user);
            $res = TRUE;
        } else {
            $res = FALSE;
        }

        return $res; */
    }	
	
	
	/*
    public function unsubscribeUser($id_user, $id_course, $id_edition = false, $id_date = false)
    {
		//Mod. ABR - non usata
		
		$res = false; //ABR
		
        $this->id_course = $id_course;
        $this->id_edition = $id_edition;
        $this->id_date = $id_date;
        
        //ABR: Recupero il tipo di corso
		
		require_once(_lms_. '/lib/lib.course.php'); //ABR
		
		$course_info = Man_Course::getCourseInfo($this->id_course);
			
		if ($course_info['course_type'] == 'classroome') {
			$course_type = 'classroom';
			
		} elseif ($course_info['course_edition'] == 1) {
			$course_type = 'elearning-edition';
			
		} else {
			$course_type = 'elearning';
		}
		
		
		// ABR: Controllo come operare
		if ( $course_type == 'elearning' ) {
			// Se il corso è elearning senza edizioni, elimino e basta
			        
			$this->unsetUnsubscribeRequest($id_user, $id_course, false, false);
			$res = $this->delUser($id_user);
			
		} else {
			// ABR: Se il corso ha edizioni, è necessario verificare se l'edizione è stata passata e appartiene al corso
			if ( $course_type == 'classroom' ) {
				
				require_once Forma::include(_lms_ . '/lib/', 'lib.date.php');
				$date_man = new DateManager();
				
				$dates = $date_man->getUserDateForCourse($id_user, $id_course, true);
				if (in_array($id_date, $dates)) {
					$this->unsetUnsubscribeRequest($id_user, $id_course, false, $id_date);
					$res = $this->delUser($id_user);
				}
		
			} elseif ($course_type == 'elearning-edition')  {
				
				require_once Forma::include(_lms_ . '/lib/', 'lib.edition.php');
				$edition_man = new EditionManager();
				
				$editions = $edition_man->getEditionAvailableForCourse($id_user, $id_course);
				if (in_array($id_edition, $editions)) {
					$this->unsetUnsubscribeRequest($id_user, $id_course, $id_edition, false);
					$res = $this->delUser($id_user);
				}
			} 
		}
		
		return $res; //Out
    }
    */
    
    
    

    public function isUserWaitingForSelfUnsubscribe($id_user, $id_course, $id_edition = false, $id_date = false)
    {
        $output = false;

        if ($id_date > 0) {
            $query = 'SELECT requesting_unsubscribe FROM %lms_course_date_user '
                . ' WHERE id_user = ' . (int) $id_user . ' AND id_date = ' . (int) $id_date;
        } elseif ($id_edition > 0) {
            $query = 'SELECT requesting_unsubscribe FROM %lms_course_editions_user '
                . ' WHERE id_user = ' . (int) $id_user . ' AND id_edition = ' . (int) $id_edition;
        } else {
            $query = 'SELECT requesting_unsubscribe FROM %lms_courseuser '
                . ' WHERE idUser = ' . (int) $id_user . ' AND idCourse = ' . (int) $id_course;
        }
        $res = $this->db->query($query);
        if ($res && $this->db->num_rows($res) > 0) {
            list($is_requesting) = $this->db->fetch_row($res);
            $output = $is_requesting > 0;
        }

        return $output;
    }

    /**
     * Calculate all pending request of unsubscription to be moderated.
     *
     * @return bool
     */
    public function countPendingUnsubscribeRequests()
    {
        $output = 0;
        $ulevel = Docebo::user()->getUserLevelId();
        $id_admin = Docebo::user()->getIdSt();

        $filter = false;
        $admin_query_course = '';
        $admin_query_editions = '';

        if ($ulevel != ADMIN_GROUP_GODADMIN) {
            require_once _base_ . '/lib/lib.preference.php';
            $preference = new AdminPreference();
            $view = $preference->getAdminCourse($id_admin);
            $all_courses = false;
            if (isset($view['course'][0])) {
                $all_courses = true;
            } elseif (isset($view['course'][-1])) {
                require_once _lms_ . '/lib/lib.catalogue.php';
                $cat_man = new Catalogue_Manager();

                $user_catalogue = $cat_man->getUserAllCatalogueId(Docebo::user()->getIdSt());
                if (count($user_catalogue) > 0) {
                    $courses = [0];

                    foreach ($user_catalogue as $id_cat) {
                        $catalogue_course = &$cat_man->getCatalogueCourse($id_cat, true);

                        $courses = array_merge($courses, $catalogue_course);
                    }

                    foreach ($courses as $id_course) {
                        if ($id_course != 0) {
                            $view['course'][$id_course] = $id_course;
                        }
                    }
                } elseif (FormaLms\lib\Get::sett('on_catalogue_empty', 'off') == 'on') {
                    $all_courses = true;
                }
            } else {
                $array_courses = [];
                $array_courses = array_merge($array_courses, $view['course']);

                if (!empty($view['coursepath'])) {
                    require_once _lms_ . '/lib/lib.coursepath.php';
                    $path_man = new CoursePath_Manager();
                    $coursepath_course = &$path_man->getAllCourses($view['coursepath']);
                    $array_courses = array_merge($array_courses, $coursepath_course);
                }
                if (!empty($view['catalogue'])) {
                    require_once _lms_ . '/lib/lib.catalogue.php';
                    $cat_man = new Catalogue_Manager();
                    foreach ($view['catalogue'] as $id_cat) {
                        $catalogue_course = &$cat_man->getCatalogueCourse($id_cat, true);
                        $array_courses = array_merge($array_courses, $catalogue_course);
                    }
                }
                $view['course'] = array_merge($view['course'], $array_courses);
            }

            $filter = $view['course'];

            $admin_query_course = $preference->getAdminUsersQuery($id_admin, 'idUser');
            $admin_query_editions = $preference->getAdminUsersQuery($id_admin, 't1.id_user');
        }
        
		// -- Count for normal courses: //ABR: fix query
		$query = "SELECT COUNT(*) FROM %lms_courseuser cu JOIN %lms_course c ON cu.idCourse = c.idCourse";
		$query.= " WHERE requesting_unsubscribe = 1 AND course_edition=0 AND course_type='elearning'";

        // -- Count for normal courses:
        // $query = 'SELECT COUNT(*) FROM %lms_courseuser WHERE requesting_unsubscribe = 1';
        // $query .= " AND course_edition=0 AND course_type='elearning'";
        
        
        if ($filter !== false) {
            if (empty($filter)) {
                return 0;
            } //no courses to check --> no requests for sure
            if (!$all_courses) { //we haven't assigned "all courses" to the admin
                $query .= ' AND idCourse IN (' . implode(',', $filter) . ')';
            }
        }
        if ($admin_query_course) {
            $query .= ' AND ' . $admin_query_course;
        }

        $res = $this->db->query($query);
        if ($res) {
            list($tot) = $this->db->fetch_row($res);
            $output = (int) $tot;
        }
        
		// -- Count for editions: //ABR fix query
		$query = "SELECT COUNT(*) FROM %lms_course_editions_user as t1,
			%lms_course_editions as t2 WHERE t1.requesting_unsubscribe = 1 AND
			t1.id_edition=t2.id_edition ";

        // -- Count for editions:
        // $query = 'SELECT COUNT(*) FROM %lms_course_editions_user as t1,
		//	%lms_course_edition as t2 WHERE t1.requesting_unsubscribe = 1 AND
		//	t1.id_edition=t2.idCourseEdition ';
		
        if ($filter !== false) {
            if (empty($filter)) {
                return 0;
            } //no courses to check --> no requests for sure
            if (!isset($filter[0]) || $filter[0] != 0) { //we haven't assigned "all courses" to the admin
                $query .= ' AND t2.idCourse IN (' . implode(',', $filter) . ')';
            }
        }
        if ($admin_query_editions) {
            $query .= ' AND ' . $admin_query_editions;
        }

        $res = $this->db->query($query);
        if ($res) {
            list($tot) = $this->db->fetch_row($res);
            $output += (int) $tot; //ABR +=
        }

        // -- Count for classrooms:
        $query = 'SELECT COUNT(*) FROM %lms_course_date_user as t1,
			%lms_course_date as t2 WHERE t1.requesting_unsubscribe = 1 AND
			t1.id_date=t2.id_date ';
        if ($filter !== false) {
            if (empty($filter)) {
                return 0;
            } //no courses to check --> no requests for sure
            if (!isset($filter[0]) || $filter[0] != 0) { //we haven't assigned "all courses" to the admin
                $query .= ' AND t2.id_course IN (' . implode(',', $filter) . ')';
            }
        }
        if ($admin_query_editions) {
            $query .= ' AND ' . $admin_query_editions;
        }

        $res = $this->db->query($query);
        if ($res) {
            list($tot) = $this->db->fetch_row($res);
            $output += (int) $tot; //ABR +=
        }

        return $output > 0 ? $output : false;
    }

    public function getUnsubscribeRequestsList_OLD($pagination, $filter, $req_tot = false, $all = false)
    {
        $startIndex = $results = $sort = $dir = false;

        if (isset($pagination['startIndex'])) {
            $startIndex = (int) $pagination['startIndex'];
        }
        if (isset($pagination['results'])) {
            $results = (int) $pagination['results'];
        }
        if (isset($pagination['dir'])) {
            switch (strtolower($pagination['dir'])) {
                case 'desc': $dir = 'DESC'; break;
                default: $dir = 'ASC'; break;
            }
        }
        if (isset($pagination['sort'])) {
            switch ($pagination['sort']) {
                case 'userid': $sort = 'u.userid'; break;
                case 'firstname': $sort = 'u.firstname ' . $dir . ', u.lastname ' . $dir . ', u.userid'; break;
                case 'lastname': $sort = 'u.lastname ' . $dir . ', u.firstname ' . $dir . ', u.userid'; break;
                case 'course_code': $sort = 'c.code'; break;
                case 'course_name': $sort = 'c.name'; break;
                case 'request_date': $sort = 'requesting_unsubscribe_date'; break;
            }
        }

        $query = 'SELECT cu.idUser, cu.idCourse, u.userid, u.lastname, u.firstname, u.email, '
            . ' c.code AS course_code, c.name AS course_name, c.course_type, cu.requesting_unsubscribe_date AS request_date '
            . ' FROM %lms_courseuser AS cu JOIN %lms_course AS c JOIN %adm_user AS u '
            . ' ON (cu.idUser = u.idst AND cu.idCourse = c.idCourse AND cu.requesting_unsubscribe = 1 '
            . ' AND (c.unsubscribe_date_limit >= NOW() OR c.unsubscribe_date_limit IS NULL '
            . " OR c.unsubscribe_date_limit = '0000-00-00 00:00:00')) "
            . ' WHERE 1';

        if (isset($filter['text']) && $filter['text'] != '') {
            $query .= " AND (u.userid LIKE '%" . $filter['text'] . "%' "
                . " OR u.firstname LIKE '%" . $filter['text'] . "%' "
                . " OR u.lastname LIKE '%" . $filter['text'] . "%') ";
        }

        if (isset($filter['course'])) {
            if (empty($filter['course'])) {
                return 0;
            }
            $query .= ' AND cu.idCourse IN (' . implode(',', $filter['course']) . ')';
        }

        if ($sort != false) {
            $query .= ' ORDER BY ' . $sort . ' ' . $dir . ' ';
        }

        if ($startIndex !== false && $results > 0) {
            $query .= ' LIMIT ' . (int) $startIndex . ', ' . (int) $results;
        }

        $output = false;
        $res = $this->db->query($query);
        if ($res) {
            $output = [];
            while ($obj = $this->db->fetch_obj($res)) {
                $output[] = $obj;
            }
        }

        return $output;
    }

    public function getUnsubscribeRequestsList($pagination, $filter, $req_tot = false, $all = false)
    {
        $first = true;
        $union_fields = [
            '%lms_courseuser' => ['id_user' => 'idUser', 'res_id' => 'idCourse', 'idCourse' => 'idCourse', 'parent' => '%lms_course', 'r_type' => 'course'],
            '%lms_course_editions_user' => ['id_user' => 'id_user', 'res_id' => 'id_edition', 'idCourse' => 'id_course', 'parent' => '%lms_course_editions', 'r_type' => 'edition'],
            '%lms_course_date_user' => ['id_user' => 'id_user', 'res_id' => 'id_date', 'idCourse' => 'id_course', 'parent' => '%lms_course_date', 'r_type' => 'classroom'],
        ];

        $p = 'p'; // $p = parent prefix. $p is set to "c" if the parent table is the same as the course table (%lms_course)

        $startIndex = $results = $sort = $dir = false;

        if (isset($pagination['startIndex'])) {
            $startIndex = (int) $pagination['startIndex'];
        }
        if (isset($pagination['results'])) {
            $results = (int) $pagination['results'];
        }
        if (isset($pagination['dir'])) {
            switch (strtolower($pagination['dir'])) {
                case 'desc': $dir = 'DESC'; break;
                default: $dir = 'ASC'; break;
            }
        }
        if (isset($pagination['sort'])) {
            switch ($pagination['sort']) {
                case 'userid': $sort = 'userid'; break;
                case 'firstname': $sort = 'firstname ' . $dir . ', lastname ' . $dir . ', userid'; break;
                case 'lastname': $sort = 'lastname ' . $dir . ', firstname ' . $dir . ', userid'; break;
                case 'course_code': $sort = 'course_code'; break;
                case 'course_name': $sort = 'course_name'; break;
                case 'request_date': $sort = 'request_date'; break;
            }
        }

        $query = '';
        foreach ($union_fields as $table => $f) { // -- build the query
            $query .= ($first ? '' : ' UNION ');

            $p = ($f['parent'] != '%lms_course' ? 'p' : 'c');

            if ($all) { // getUnsubscribeRequestsAll
                $query .= 'SELECT cu.' . $f['id_user'] . ' as user_id, cu.' . $f['res_id'] . ' as res_id  ';
            } elseif ($req_tot) { // getUnsubscribeRequestsTotal
                $query .= 'SELECT COUNT(*) ';
            } else {
                $query .= 'SELECT cu.' . $f['id_user'] . ' as user_id, cu.' . $f['res_id'] . ' as res_id,
					u.userid as userid, u.lastname, u.firstname, u.email,
					c.code AS course_code, c.course_type, c.idCourse,
					cu.requesting_unsubscribe_date AS request_date, ' .
                    $p . ".name AS course_name, '" . $f['r_type'] . "' as r_type ";
            }

            $query .= 'FROM ' . $table . ' AS cu ';

            if ($f['parent'] != '%lms_course') {
                $query .= '
					JOIN ' . $f['parent'] . ' AS p ON (p.' . $f['res_id'] . '=cu.' . $f['res_id'] . ')
					JOIN %lms_course AS c ON (c.idCourse=p.' . $f['idCourse'] . ') ';
            } else {
                $query .= 'JOIN %lms_course AS c ON (c.idCourse=cu.' . $f['res_id'] . ') ';
            }

            $query .= 'JOIN %adm_user AS u ON (cu.' . $f['id_user'] . ' = u.idst) ';

            $query .= 'WHERE requesting_unsubscribe = 1 ';

            if (!$all) {
                $query .= "AND (
					c.unsubscribe_date_limit >= NOW() OR c.unsubscribe_date_limit IS NULL
					OR c.unsubscribe_date_limit = '0000-00-00 00:00:00'
				) ";
            } else {
                $query .= 'AND c.unsubscribe_date_limit >= NOW() ';
            }

            if (isset($filter['text']) && $filter['text'] != '') {
                $query .= " AND (u.userid LIKE '%" . $filter['text'] . "%' "
                    . " OR u.firstname LIKE '%" . $filter['text'] . "%' "
                    . " OR u.lastname LIKE '%" . $filter['text'] . "%') ";
            }

            if (isset($filter['course'])) {
                if (empty($filter['course'])) {
                    return 0;
                }
                $query .= ' AND ' . $p . '.' . $f['idCourse'] . ' IN (' . implode(',', $filter['course']) . ')';
            }

            if (isset($filter['user_q']) && $filter['user_q'] != '') {
                $query .= ' AND ' . $filter['user_q'];
            }

            $first = false;
        }

        // echo $query; return true;

        if ($sort != false) {
            $query .= ' ORDER BY ' . $sort . ' ' . $dir . ' ';
        }

        if ($startIndex !== false && $results > 0) {
            $query .= ' LIMIT ' . (int) $startIndex . ', ' . (int) $results;
        }

        //if (!$req_tot && !$all) { echo $query; return true; }

        $output = false;
        $res = $this->db->query($query);
        if ($res) {
            if ($all) { // getUnsubscribeRequestsAll
                $output = [];
                while ($obj = $this->db->fetch_obj($res)) {
                    $output[] = $obj->idUser . '_' . $obj->idCourse;
                }
            } elseif ($req_tot) { // getUnsubscribeRequestsTotal
                while (list($tot) = $this->db->fetch_row($res)) {
                    $output = $output + (int) $tot;
                }
            } else { // Normal output
                $output = [];
                while ($obj = $this->db->fetch_obj($res)) {
                    $output[] = $obj;
                }
            }
        }

        return $output;
    }

    public function getUnsubscribeRequestsTotal($filter)
    {
        return $this->getUnsubscribeRequestsList([], $filter, true);
    }

    public function getUnsubscribeRequestsAll($filter)
    {
        return $this->getUnsubscribeRequestsList([], $filter, false, true);
    }

    public function setUnsubscribeRequest($id_user, $id_course, $id_edition = false, $id_date = false)
    {
        if ($id_user <= 0 || $id_course <= 0) {
            return false;
        }

        $cmodel = new CourseAlms();
        $cinfo = $cmodel->getCourseModDetails($id_course);

        switch ((int) $cinfo['auto_unsubscribe']) {
            case 0: return false; break;
            case 1:
            case 2:
                if ($cinfo['unsubscribe_date_limit'] != '' && $cinfo['unsubscribe_date_limit'] != '0000-00-00 00:00:00') {
                    if ($cinfo['unsubscribe_date_limit'] < date('Y-m-d H:i:s')) {
                        return false;
                    }
                }
             break;
        }

        if ($id_date > 0) {
            $query = 'UPDATE %lms_course_date_user SET requesting_unsubscribe = 1, requesting_unsubscribe_date = NOW() '
                . ' WHERE id_user = ' . (int) $id_user . ' AND id_date = ' . (int) $id_date;
        } elseif ($id_edition > 0) {
            $query = 'UPDATE %lms_course_editions_user SET requesting_unsubscribe = 1, requesting_unsubscribe_date = NOW() '
                . ' WHERE id_user = ' . (int) $id_user . ' AND id_edition = ' . (int) $id_edition;
        } else {
            $query = 'UPDATE %lms_courseuser SET requesting_unsubscribe = 1, requesting_unsubscribe_date = NOW() '
                . ' WHERE idUser = ' . (int) $id_user . ' AND idCourse = ' . (int) $id_course;
        }

        $res = $this->db->query($query);

        // check and send message for unsibscription moderated
        if (($res) && (int) $cinfo['auto_unsubscribe'] == 1) {
            //moderated self unsubscribe
            $userinfo = $this->acl_man->getUser($id_user, false);
            $array_subst = ['[url]' => FormaLms\lib\Get::site_url(),
                '[course]' => $cinfo['name'],
                '[firstname]' => $userinfo[ACL_INFO_FIRSTNAME],
                '[lastname]' => $userinfo[ACL_INFO_LASTNAME],
                '[userid]' => $this->acl_man->relativeId($userinfo[ACL_INFO_USERID]),
            ];

            // message to user that is waiting
            require_once _base_ . '/lib/lib.eventmanager.php';
            $msg_composer = new EventMessageComposer('subscribe', 'lms');

            $msg_composer->setSubjectLangText('email', '_NEW_USER_UNSUBS_WAITING_SUBJECT', false);
            $msg_composer->setBodyLangText('email', '_NEW_USER_UNSUBS_WAITING_TEXT', $array_subst);

            $msg_composer->setSubjectLangText('sms', '_NEW_USER_UNSUBS_WAITING_SUBJECT_SMS', false);
            $msg_composer->setBodyLangText('sms', '_NEW_USER_UNSUBS_WAITING_TEXT_SMS', $array_subst);

            $acl = &Docebo::user()->getAcl();
            $acl_man = &$this->acl_man;

            $recipients = [];

            $idst_group_god_admin = $acl->getGroupST(ADMIN_GROUP_GODADMIN);
            $recipients = $acl_man->getGroupMembers($idst_group_god_admin);
            $idst_group_admin = $acl->getGroupST(ADMIN_GROUP_ADMIN);
            $idst_admin = $acl_man->getGroupMembers($idst_group_admin);

            require_once _adm_ . '/lib/lib.adminmanager.php';
            foreach ($idst_admin as $id_user) {
                $adminManager = new AdminManager();
                $acl_manager = &$acl_man;

                $idst_associated = $adminManager->getAdminTree($id_user);

                $array_user = &$acl_manager->getAllUsersFromIdst($idst_associated);

                $array_user = array_unique($array_user);

                $array_user[] = $array_user[0];
                unset($array_user[0]);

                $control_user = array_search(getLogUserId(), $array_user);

                $query = 'SELECT COUNT(*)'
                            . ' FROM ' . FormaLms\lib\Get::cfg('prefix_fw') . '_admin_course'
                            . " WHERE idst_user = '" . $id_user . "'"
                            . " AND type_of_entry = 'course'"
                            . " AND id_entry = '" . $id_course . "'";

                list($control_course) = sql_fetch_row(sql_query($query));

                $query = 'SELECT COUNT(*)'
                            . ' FROM ' . FormaLms\lib\Get::cfg('prefix_fw') . '_admin_course'
                            . " WHERE idst_user = '" . $id_user . "'"
                            . " AND type_of_entry = 'coursepath'"
                            . ' AND id_entry IN'
                            . ' ('
                            . ' SELECT id_path'
                            . ' FROM ' . FormaLms\lib\Get::cfg('prefix_lms') . '_coursepath_courses'
                            . " WHERE id_item = '" . $id_course . "'"
                            . ' )';

                list($control_coursepath) = sql_fetch_row(sql_query($query));

                $query = 'SELECT COUNT(*)'
                            . ' FROM ' . FormaLms\lib\Get::cfg('prefix_fw') . '_admin_course'
                            . " WHERE idst_user = '" . $id_user . "'"
                            . " AND type_of_entry = 'catalogue'"
                            . ' AND id_entry IN'
                            . ' ('
                            . ' SELECT idCatalogue'
                            . ' FROM ' . FormaLms\lib\Get::cfg('prefix_lms') . '_catalogue_entry'
                            . " WHERE idEntry = '" . $id_course . "'"
                            . ' )';

                list($control_catalogue) = sql_fetch_row(sql_query($query));

                if ($control_user && ($control_course || $control_coursepath || $control_catalogue)) {
                    $recipients[] = $id_user;
                }
            }

            $recipients = array_unique($recipients);

            createNewAlert('UserCourseRemovedModerate', 'unsubscribe', 'insert', '1', 'User unsubscribed with moderation',
                        $recipients, $msg_composer);
        }

        return $res ? true : false;
    }

    public function unsetUnsubscribeRequest($id_user, $id_course, $id_edition = false, $id_date = false)
    {
        if ($id_user <= 0 || ($id_course <= 0 && $id_edition <= 0 && $id_date <= 0)) {
            return false;
        }

        if ($id_date > 0) { // classroom unsubscribe request
            $query = 'UPDATE %lms_course_date_user SET requesting_unsubscribe = 0, requesting_unsubscribe_date = NULL '
                . ' WHERE id_user = ' . (int) $id_user . ' AND id_date = ' . (int) $id_date;
        } elseif ($id_edition > 0) {  // edition unsubscribe request
            $query = 'UPDATE %lms_course_editions_user SET requesting_unsubscribe = 0, requesting_unsubscribe_date = NULL '
                . ' WHERE id_user = ' . (int) $id_user . ' AND id_edition = ' . (int) $id_edition;
        } else {  // course unsubscribe request
            $query = 'UPDATE %lms_courseuser SET requesting_unsubscribe = 0, requesting_unsubscribe_date = NULL '
                . ' WHERE idUser = ' . (int) $id_user . ' AND idCourse = ' . (int) $id_course;
        }
        $res = $this->db->query($query);

        return $res ? true : false;
    }

    public function controlCoursesWithEdition($courses)
    {
        $query = 'SELECT COUNT(*)'
                    . ' FROM %lms_course'
                    . ' WHERE idCourse IN (' . implode(',', $courses) . ')'
                    . " AND (course_type = 'classroom' OR course_edition = 1)";

        list($control) = sql_fetch_row(sql_query($query));

        if ($control == 0) {
            return false;
        }

        return true;
    }

    public function getEditionTableStyle()
    {
        return ['', '', 'image'];
    }

    public function getEditionTableHeader()
    {
        return [Lang::t('_CODE', 'course'),
                        Lang::t('_NAME', 'course'),
                        '', ];
    }

    public function getEditionTableContent($courses)
    {
        $res = [];
        foreach ($courses as $id_course) {
            if ($this->controlCoursesWithEdition([$id_course])) {
                $query = 'SELECT code, name, course_type'
                            . ' FROM %lms_course'
                            . ' WHERE idCourse = ' . (int) $id_course;

                list($code, $name, $course_type) = sql_fetch_row(sql_query($query));

                if ($course_type !== 'classroom') {
                    require_once _lms_ . '/admin/models/EditionAlms.php';
                    $edition_model = new EditionAlms($id_course);
                    $edition = $edition_model->loadEdition(false, false, 'date_begin', 'desc');
                } else {
                    require_once _lms_ . '/admin/models/ClassroomAlms.php';
                    $classroom_model = new ClassroomAlms($id_course);
                    $edition = $classroom_model->loadCourseEdition(false, false, 'date_begin', 'desc');
                }

                $all_value = [];
                foreach ($edition as $edition_info) {
                    $all_value[(isset($edition_info['id_date']) ? $edition_info['id_date'] : $edition_info['id_edition'])] = $edition_info['code'] . ' - '
                                                                                                                                . $edition_info['name']
                                                                                                                                . ' (' . (isset($edition_info['id_date']) ? $edition_info['date_begin'] : Format::date($edition_info['date_begin'], 'date')) . ' - ' . (isset($edition_info['id_date']) ? $edition_info['date_end'] : Format::date($edition_info['date_end'], 'date')) . ')';
                }
                $res[] = [$code,
                                $name,
                                Form::getInputDropdown('dropdown',
                                                        'edition_' . $id_course,
                                                        'edition_' . $id_course,
                                                        $all_value,
                                                        false,
                                                        ''), ];
            }
        }

        return $res;
    }
    
    
    /** ABR **
     * Esegue il reset dell'iscrizione cancellando le informazioni di fruizione
     * @param $id_user, int, id utente
     * @param $id_course, int, optional id corso (se non valorizzato prende quello di istanza)
     */
	public function resetCourseUserTrackInfo($id_user, $id_course = false) {

		require_once(_lms_. '/lib/lib.course.php');
		require_once(_lms_.'/models/CoursestatsLms.php');
		
		$courseuser_man = new Man_CourseUser();
		$stats_model = new CoursestatsLms();
		$res = true;
		
		// Recupero id corso di istanza se non è in argomento
		if ($id_course == false) $id_course = $this->id_course;
		
		// Recupero i corsi a cui è iscritto l'utente
		$user_courses = $courseuser_man->getUserSubscriptionsInfo($id_user);
		
		// Controllo
		if( !isset($user_courses[$id_course]) ) return false;
		
		// Recupero lo stato utente nel corso
		$status = $user_courses[$id_course]['status'];
			
		// Recupero i learning object del corso
		$arr_lo = $stats_model->getCourseLOs($id_course);
		
				
		// Elimino le statistiche di ogni lo
		foreach ($arr_lo as $lo) {
			$res &= $stats_model->resetTrack($lo->id, $id_user);		
		}
		
		// Elimino il tracking di dettaglio delle fruizioni corso
		if ($res) {
			$query = "DELETE FROM %lms_trackingeneral WHERE idUser=".$id_user." AND idCourse=".$id_course;
			$res &= sql_query($query);
		}
		
		// Elimino le sessioni di fruizione del corso
		if ($res) {
			$query = "DELETE FROM %lms_tracksession WHERE idUser=".$id_user." AND idCourse=".$id_course;
			$res &= sql_query($query);
		}
		
		// Riporto l'iscrizione allo stato iniziale, se necessario
		if ( $res && ($status == _CUS_BEGIN || $status == _CUS_END) ) {
			$res &= $this->updateUserStatus($id_user, _CUS_SUBSCRIBED);					
		}
			
		// Out	
		return $res;
		
	}
	
	/** ABR **
	 *  Controlla se il corso è chiuso o annullato
	 */
	public function checkCourseClosed($alsoCancelled = false) {
		
		require_once(_lms_. '/lib/lib.course.php');
		
		$course_info = Man_Course::getCourseInfo($this->id_course);
			
		if ( $course_info['status'] == CST_CONCLUDED || ($alsoCancelled && $course_info['status'] == CST_CANCELLED) )
			$res = true;
		else
			$res = false;
		
		// Out
		return $res;
	}
	

	/*
	public function getIcsText($id_date, $weblinks) {
		//>> ABR: Restituisce un array di stringhe per allegati ics (contenuto dei file)
		//>> Usata solo per iscrizioni Classroom
		
		require_once(_lms_.'/lib/lib.date.php');
		require_once(_base_.'/lib/lib.ics.php' );
	
		$arr_ics_contents = false;
		$prefix = preg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', Lang::t('_ICS_PREFIX_SUBS', 'subscription'));
		
		// Istanzio manager 
		$classroom_man = new DateManager();
		
		// Recupero info edizione classroom
		$edition = $classroom_man->getDateInfo($id_date);
		
		// Info giorni e sedi
		$days = $classroom_man->getDaysAndLocations($id_date, 'month_name_short');
		

		foreach ($days as $d => $info) {
			$id_day = $info['id_day'];
			
			//Date UTC in formato stringa (es. 2019-12-28 9:00:00)
			$dt_start	= gmdate('Y-m-d H:i:s', strtotime($d.' '.$info['b_hours'].':'.$info['b_minutes']));
			$dt_end		= gmdate('Y-m-d H:i:s', strtotime($d.' '.$info['e_hours'].':'.$info['e_minutes']));
			
			// Preparo la descrizione dell'appuntamento
			$description = $edition['name'].', '.$info['location'].' '.$info['class'];
			
			// Recupero eventuale link webex della lezione
			if ( isset($weblinks[ $id_day ]) ){
				// Se il corso è virtuale ed è gestito con webex, presento link e password
				$description .= PHP_EOL."Link: ".$weblinks[$id_day]['webLink']." Password: ".$weblinks[$id_day]['password'];	
			}
			
			// Proprietà ICS
			$properties = array(
			  'location' 	=> $info['location'].', '.$info['class'],
			  'description' => $description,
			  'dtstart' 	=> $dt_start,	
			  'dtend' 		=> $dt_end,
			  'summary' 	=> $edition['name'],
			  'url' 		=>  FormaLms\lib\Get::sett('url')
			);
			
			// Istanzio oggetto ICS
			$ics = new ICS($properties);
			
			// Preparo nome contenuto (usato come nome file)
			$name = $prefix." ".substr($info['format_info_day'], 0 , 6).".ics";
			
			// Preparo array di output
			$arr_ics_contents[] = array('name' => $name, 'data' => $ics->to_string());
		}

		// Out
		return $arr_ics_contents;
		
	}
	*/

    //-- coursepaths -------------------------------------------------------------

    public function getCoursePathUsersList($id_path, $start_index, $results, $sort, $dir, $filter)
    {
        $_dir = 'ASC';
        switch (strtolower($dir)) {
            case 'desc': $_dir = 'DESC'; break;
        }

        $_sort = 'u.userid';
        switch ($sort) {
            case 'firstname': $_sort = 'u.firstname ' . $_dir . ', u.lastname ' . $_dir . ', u.userid'; break;
            case 'lastname': $_sort = 'u.lastname ' . $_dir . ', u.firstname ' . $_dir . ', u.userid'; break;
            case 'date_begin': $_sort = 's.date_begin_validity'; break;
            case 'date_expire': $_sort = 's.date_expire_validity'; break;
        }

        require_once _lms_ . '/lib/lib.coursepath.php';
        $cman = new CoursePath_Manager();

        $courses = $cman->getAllCourses([$id_path]);
        if (empty($courses)) {
            //...
        }

        $query = 'SELECT u.idst, u.userid, u.firstname, u.lastname, u.email, '
            . ' MIN(s.date_expire_validity) as date_expire_validity, '
            . ' MAX(s.date_begin_validity) as date_begin_validity, s.idCourse '
            . ' FROM (%lms_courseuser as s JOIN %lms_coursepath_user as p '
            . ' ON (s.idUser = p.idUser)) '
            . ' JOIN %adm_user as u '
            . ' ON (s.idUser = u.idst) '
            . ' WHERE p.id_path = ' . (int) $id_path . ' AND s.idCourse IN (' . implode(',', array_values($courses)) . ') ';

        if (is_array($filter)) {
            if (isset($filter['text']) && $filter['text'] != '') {
                $query .= " AND (u.userid LIKE '%" . $filter['text'] . "%' OR u.firstname LIKE '%" . $filter['text'] . "%' OR u.lastname LIKE '%" . $filter['text'] . "%') ";
            }

            $arr_idst = [];
            if (isset($filter['orgchart']) && $filter['orgchart'] > 0) {
                $umodel = new UsermanagementAdm();
                $use_desc = (isset($filter['descendants']) && $filter['descendants']);
                $ulist = $umodel->getFolderUsers($filter['orgchart'], $use_desc);
                if (!empty($ulist)) {
                    $arr_idst = $ulist;
                }
                unset($ulist);
            }
            if (!empty($arr_idst)) {
                $conditions[] = ' AND u.idst IN (' . implode(',', $arr_idst) . ') ';
            }

            if (isset($filter['date_valid']) && strlen($filter['date_valid']) >= 10) {
                $query .= " AND (s.date_begin_validity <= '" . $filter['date_valid'] . "' OR s.date_begin_validity IS NULL OR s.date_begin_validity='0000-00-00 00:00:00') ";
                $query .= " AND (s.date_expire_validity >= '" . $filter['date_valid'] . "' OR s.date_expire_validity IS NULL OR s.date_expire_validity='0000-00-00 00:00:00') ";
            }

            if (isset($filter['show'])) {
                //validate values
                switch ($filter['show']) {
                    case 0:  //all
                        //no condition to check ...
                     break;

                    case 1:  //expired
                        $query .= ' AND (s.date_expire_validity IS NOT NULL AND s.date_expire_validity < NOW())';
                     break;

                    case 2:  //not expired with expiring date
                        $query .= ' AND (s.date_expire_validity IS NOT NULL AND s.date_expire_validity > NOW())';
                     break;

                    case 3:  //not expired without expiring date
                        $query .= " AND (s.date_expire IS NULL OR s.date_expire='' OR s.date_expire='0000-00-00 00:00:00') ";
                     break;

                    default:
                        //all ...
                     break;
                }
            }
        }

        if (Docebo::user()->getUserLevelId() != ADMIN_GROUP_GODADMIN) {
            require_once _base_ . '/lib/lib.preference.php';
            $adminManager = new AdminPreference();
            $admin_tree = $adminManager->getAdminTree(Docebo::user()->getIdST());
            $admin_users = $this->acl_man->getAllUsersFromSelection($admin_tree);

            $query .= ' AND s.idUser IN (' . implode(',', $admin_users) . ')';
        }

        $query .= ' GROUP BY s.idUser ';
        $query .= ' ORDER BY ' . $_sort . ' ' . $_dir;
        ($start_index === false ? '' : $query .= ' LIMIT ' . $start_index . ', ' . $results);

        $result = sql_query($query);
        $acl_man = Docebo::user()->getACLManager();
        $res = [];
        while ($obj = sql_fetch_object($result)) {
            $res[] = $obj;
        }

        return $res;
    }

    public function getCoursePathUsersTotal($id_path, $filter = false)
    {
        require_once _lms_ . '/lib/lib.coursepath.php';
        $cman = new CoursePath_Manager();

        $courses = $cman->getAllCourses([$id_path]);
        if (empty($courses)) {
            //...
        }

        $query = 'SELECT COUNT(DISTINCT s.idUser) '
            . ' FROM (%lms_courseuser as s JOIN %lms_coursepath_user as p '
            . ' ON (s.idUser = p.idUser)) '
            . ' JOIN %adm_user as u '
            . ' ON (s.idUser = u.idst) '
            . ' WHERE p.id_path = ' . (int) $id_path . ' AND s.idCourse IN (' . implode(',', array_values($courses)) . ') '
            ; //." GROUP BY s.idUser";

        if (is_array($filter)) {
            if (isset($filter['text']) && $filter['text'] != '') {
                $query .= " AND (u.userid LIKE '%" . $filter['text'] . "%' OR u.firstname LIKE '%" . $filter['text'] . "%' OR u.lastname LIKE '%" . $filter['text'] . "%') ";
            }

            $arr_idst = [];
            if (isset($filter['orgchart']) && $filter['orgchart'] > 0) {
                $umodel = new UsermanagementAdm();
                $use_desc = (isset($filter['descendants']) && $filter['descendants']);
                $ulist = $umodel->getFolderUsers($filter['orgchart'], $use_desc);
                if (!empty($ulist)) {
                    $arr_idst = $ulist;
                }
                unset($ulist);
            }
            if (!empty($arr_idst)) {
                $conditions[] = ' AND u.idst IN (' . implode(',', $arr_idst) . ') ';
            }

            if (isset($filter['date_valid']) && strlen($filter['date_valid']) >= 10) {
                $query .= " AND (s.date_begin_validity <= '" . $filter['date_valid'] . "' OR s.date_begin_validity IS NULL OR s.date_begin_validity='0000-00-00 00:00:00') ";
                $query .= " AND (s.date_expire_validity >= '" . $filter['date_valid'] . "' OR s.date_expire_validity IS NULL OR s.date_expire_validity='0000-00-00 00:00:00') ";
            }

            if (isset($filter['show'])) {
                //validate values
                switch ($filter['show']) {
                    case 0:  //all
                        //no condition to check ...
                     break;

                    case 1:  //expired
                        $query .= ' AND (s.date_expire_validity IS NOT NULL AND s.date_expire_validity < NOW())';
                     break;

                    case 2:  //not expired with expiring date
                        $query .= ' AND (s.date_expire_validity IS NOT NULL AND s.date_expire_validity > NOW())';
                     break;

                    case 3:  //not expired without expiring date
                        $query .= " AND (s.date_expire IS NULL OR s.date_expire='' OR s.date_expire='0000-00-00 00:00:00') ";
                     break;

                    default:
                        //all ...
                     break;
                }
            }
        }

        if (Docebo::user()->getUserLevelId() != ADMIN_GROUP_GODADMIN) {
            require_once _base_ . '/lib/lib.preference.php';
            $acl_man = new DoceboACLManager();
            $adminManager = new AdminPreference();
            $admin_tree = $adminManager->getAdminTree(getLogUserId());
            $admin_users = $acl_man->getAllUsersFromSelection($admin_tree);

            $query .= ' AND s.idUser IN (' . implode(',', $admin_users) . ')';
        }

        list($res) = sql_fetch_row(sql_query($query));

        return $res;
    }

    public function getCoursePathSubscriptionsList($id_path, $filter = false)
    {
        require_once _lms_ . '/lib/lib.coursepath.php';
        $cman = new CoursePath_Manager();

        $courses = $cman->getAllCourses([$id_path]);
        if (empty($courses)) {
            //...
        }

        $query = 'SELECT DISTINCT s.idUser '
            . ' FROM (%lms_courseuser as s JOIN %lms_coursepath_user as p '
            . ' ON (s.idUser = p.idUser)) '
            . ' JOIN %adm_user as u '
            . ' ON (s.idUser = u.idst) '
            . ' WHERE p.id_path = ' . (int) $id_path . ' AND s.idCourse IN (' . implode(',', array_values($courses)) . ') '
            ; //." GROUP BY s.idUser";

        if (is_array($filter)) {
            if (isset($filter['text']) && $filter['text'] != '') {
                $query .= " AND (u.userid LIKE '%" . $filter['text'] . "%' OR u.firstname LIKE '%" . $filter['text'] . "%' OR u.lastname LIKE '%" . $filter['text'] . "%') ";
            }

            $arr_idst = [];
            if (isset($filter['orgchart']) && $filter['orgchart'] > 0) {
                $umodel = new UsermanagementAdm();
                $use_desc = (isset($filter['descendants']) && $filter['descendants']);
                $ulist = $umodel->getFolderUsers($filter['orgchart'], $use_desc);
                if (!empty($ulist)) {
                    $arr_idst = $ulist;
                }
                unset($ulist);
            }
            if (!empty($arr_idst)) {
                $conditions[] = ' AND u.idst IN (' . implode(',', $arr_idst) . ') ';
            }

            if (isset($filter['date_valid']) && strlen($filter['date_valid']) >= 10) {
                $query .= " AND (s.date_begin_validity <= '" . $filter['date_valid'] . "' OR s.date_begin_validity IS NULL OR s.date_begin_validity='0000-00-00 00:00:00') ";
                $query .= " AND (s.date_expire_validity >= '" . $filter['date_valid'] . "' OR s.date_expire_validity IS NULL OR s.date_expire_validity='0000-00-00 00:00:00') ";
            }

            if (isset($filter['show'])) {
                //validate values
                switch ($filter['show']) {
                    case 0:  //all
                        //no condition to check ...
                     break;

                    case 1:  //expired
                        $query .= ' AND (s.date_expire_validity IS NOT NULL AND s.date_expire_validity < NOW())';
                     break;

                    case 2:  //not expired with expiring date
                        $query .= ' AND (s.date_expire_validity IS NOT NULL AND s.date_expire_validity > NOW())';
                     break;

                    case 3:  //not expired without expiring date
                        $query .= " AND (s.date_expire IS NULL OR s.date_expire='' OR s.date_expire='0000-00-00 00:00:00') ";
                     break;

                    default:
                        //all ...
                     break;
                }
            }
        }

        if (Docebo::user()->getUserLevelId() != ADMIN_GROUP_GODADMIN) {
            require_once _base_ . '/lib/lib.preference.php';
            $acl_man = new DoceboACLManager();
            $adminManager = new AdminPreference();
            $admin_tree = $adminManager->getAdminTree(getLogUserId());
            $admin_users = $acl_man->getAllUsersFromSelection($admin_tree);
            $query .= ' AND s.idUser IN (' . implode(',', $admin_users) . ')';
        }

        $output = [];
        $res = sql_query($query);
        if ($res) {
            while (list($idst) = sql_fetch_row($res)) {
                $output[] = $idst;
            }
        }

        return $output;
    }

    public function unsubscribeFromCoursepath($id_path, $users)
    {
        if ($id_path <= 0) {
            return false;
        }
        if (is_numeric($users)) {
            $users = [(int) $users];
        }
        if (!is_array($users)) {
            return false;
        }
        if (count($users) <= 0) {
            return true;
        }
        $query = 'DELETE FROM %lms_coursepath_user WHERE id_path=' . (int) $id_path . ' '
            . ' AND idUser IN (' . implode(',', $users) . ')';
        $res = sql_query($query);

        return $res ? true : false;
    }

    public function resetCoursepathValidityDates($id_path, $id_user)
    {
        if ($id_path <= 0 || $id_user <= 0) {
            return false;
        }

        require_once _lms_ . '/lib/lib.coursepath.php';
        $cman = new CoursePath_Manager();
        $courses = $cman->getAllCourses([$id_path]);
        if (empty($courses)) {
            return true;
        }

        $query = 'UPDATE %lms_courseuser SET date_begin_validity = NULL, date_expire_validity = NULL '
            . ' WHERE idCourse IN (' . implode(',', $courses) . ') AND idUser=' . (int) $id_user . '';
        $res = sql_query($query);

        return $res ? true : false;
    }

    public function subscribeUsersToCoursepath($id_path, $users)
    {
        if ($id_path <= 0) {
            return false;
        }
        if (is_numeric($users)) {
            $users = [(int) $users];
        }
        if (!is_array($users)) {
            return false;
        }
        if (count($users) <= 0) {
            return true;
        }
    }

    //--- end coursepaths --------------------------------------------------------
}
