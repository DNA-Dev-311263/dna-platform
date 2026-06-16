<?php

defined("IN_FORMA") or die('Direct access is forbidden.');

/* ======================================================================== \
|   FORMA - The E-Learning Suite                                            |
|                                                                           |
|                                                                           |
|                                                                           |
|   License  http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt           |
|                                                                           |
|   BKO libreria by ABR                                                     |
\ ======================================================================== */

use FormaLms\lib\Get;

class TmsMeetingManager
{
	public	  $extension_name = 'dna.teams.plugin';
	
	protected $lang;
	protected $acl_man;
	
	protected $site_url;
	protected $scope_url;
	protected $tenant_id;
	protected $client_id;
	protected $client_secret;
	protected $create_event;
	protected $access_token;
	protected $token_expiration;
	

	public function __construct(){
		
		$this->lang =& DoceboLanguage::CreateInstance('plugin_tmsmeeting', 'lms');
		$this->acl_man =& Docebo::user()->getAclManager();
		
		// Recupero indirizzi URL e controllo slash
		$this->site_url = rtrim(Get::sett('tmsmeeting.site_url', ''), '/') . '/';
		$this->scope_url = rtrim(Get::sett('tmsmeeting.scope_url', ''), '/') . '/';
		
		// Recupero chiavi app di integrazione
		$this->tenant_id = Get::sett('tmsmeeting.tenant_id', '');
		$this->client_id = Get::sett('tmsmeeting.client_id', '');
		$this->client_secret = Get::sett('tmsmeeting.client_secret', '');
		
		// Recupero se è abilitato lla creazione eventi (meeting nel calendario e email)
		$this->create_event = (Get::sett('tmsmeeting.create_event', 'on') == 'on');
		
		// L'access_token deve essere recuperato alla prima chiamata
		$this->access_token = '';
	}

	public function __destruct(){
		
	}
	
	
	/**
	 * Controlla se la data è in formato iso
	 */
	private function isTimestampIsoValid($timestamp){
		
		if (preg_match('/^'.
				'(\d{4})-(\d{2})-(\d{2})T'. // YYYY-MM-DDT ex: 2014-01-01T
				'(\d{2}):(\d{2}):(\d{2})'.  // HH-MM-SS  ex: 17:00:00
				'(Z|((-|\+)\d{2}:\d{2}))'.  // Z or +01:00 or -01:00
				'$/', $timestamp, $parts) == true)
		{
			try {
				new \DateTime($timestamp);
				return true;
			}
			catch ( \Exception $e)
			{
				return false;
			}
		} else {
			return false;
		}
	}
	
	
	/**
	 * Controlla se la data e la restituisce in formato iso
	 */
	private function isoDateTime($datetime_string) {

		$result = false;
		
		if (DateTime::createFromFormat('Y-m-d H:i:s', $datetime_string) !== false) {
			$result = date(DATE_ISO8601, strtotime($datetime_string));
		}
		
		return $result;
	}
	
	
	/**
	 * Controlla la data stringa passata in argomento. 
	 * Se il valore è nullo viene restituito stringa vuota, altrimenti viene restituita nel formato passato in argomento
	 */
	public function checkDateString($dateString, $format = 'Y-m-d'){
		
		$res = "";
		
		if(!empty($dateString)) $res = date($format, strtotime($dateString));
		
		return $res;
		
	}
	
	
	/**
	 * Restituisce il token di autenticazione
	 * Su errore return false e $error_msg restituisce la descrizione dell'errore"
	 */
	protected function getAccessToken(&$error_msg = null) {
		
		// Restituisco il tokeno valido (utile per chiamate multiple nella stessa operazione)
		if (time() < $this->token_expiration) return $this->access_token;
		
		// Procedo se occorre rigenerare il token
		$res = false;
		
		$url = $this->url . $this->tenant_id.'/oauth2/v2.0/token';
		$scope = $this->scope_url . '.default';
	
		$params = array(
			'client_id'		=>	$this->client_id,
			'client_secret'	=>	$this->client_secret,
			'scope'			=>	$scope,
			'grant_type'	=>	$this->client_credentials
		);
		
		if ($this->callRestApi("POST", $url, null, $params, null, $response, $error_msg, 'form')) {
			
			// Recupero la risposta della chiamata
			$data = json_decode($response, true);
			
			if (!isset($data['access_token'])) {
				// JSON potrebbe essere malformato o mancare dei campi
				$error_msg = "Token non presente nella risposta.";
			} else {
				// Tutto OK
				$res = $data['access_token'];
				$this->token_expiration = time() +  $data['expires_in'];
			}	
			
		}

		// Out
		return $res;
	}
	

	/**
	 * Esegue una chiamata REST verso un endpoint remoto.
	 *
	 * Funziona con Microsoft Graph (JSON) e Azure AD (x-www-form-urlencoded),
	 * grazie al parametro $content_type.
	 *
	 * @param string      $method        Metodo HTTP: GET, POST, PUT, DELETE, PATCH
	 * @param string      $url           URL dell'endpoint senza query string
	 * @param array|null  $query_params  Array associativo per generare la query string
	 * @param array|null  $body_params   Parametri del body (JSON o FORM, in base a $content_type)
	 * @param string|null $access_token  Token Bearer da aggiungere all'header (facoltativo)
	 * @param string|null $response      Output: corpo della risposta (raw JSON o testo)
	 * @param string|null $error_msg     Output: messaggio d'errore in caso di fallimento
	 * @param string      $content_type  'json' = invia JSON (default) — 'form' = x-www-form-urlencoded
	 *
	 * @return bool  TRUE se la chiamata restituisce un codice HTTP < 400, FALSE altrimenti.
	 */
	protected function callRestApi(
		$method,
		$url,
		$query_params = null,
		$body_params = null,
		$access_token = null,
		&$response = null,
		&$error_msg = null,
		$content_type = 'json'
	) {
		$success = true;
		$error_msg = null;

		 // 1. Costruzione QUERY STRING

		if ($query_params) {
			$qry_string = http_build_query($query_params, '', '&');
			$url .= '?' . $qry_string;
		}

		// 2. Preparo il body della richiesta e gli header in base al tipo di content-type richiesto

		switch ($content_type) {

			case 'form':   // Usato per Azure AD "POST /token"
				$body_string = $body_params ? http_build_query($body_params) : '';
				$headers = [
					'Accept: application/json',
					'Content-Type: application/x-www-form-urlencoded'
				];
				break;

			case 'json':   // Usato per Microsoft Graph
			default:
				$body_string = $body_params ? json_encode($body_params) : '';
				$headers = [
					'Accept: application/json',
					'Content-Type: application/json'
				];
				break;
		}


		// 3. Authorization bearer token (se specificato)

		if ($access_token) {
			$headers[] = 'Authorization: Bearer ' . $access_token;
		}


		// 4. Configurazione CURL

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		// NON bloccare errori HTTP per leggere JSON di errore
		curl_setopt($curl, CURLOPT_FAILONERROR, false);

		// In caso di metodi con body (POST, PUT, PATCH...)
		if ($method !== 'GET' && $body_string !== '') {
			curl_setopt($curl, CURLOPT_POSTFIELDS, $body_string);
		}

		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);


		// 5. Esecuzione chiamata

		$response = curl_exec($curl);
		$curl_errno = curl_errno($curl);


		// 6. Gestione errori

		if ($curl_errno !== 0) {
			// Errore cURL (timeout, DNS, rete...)
			$success = false;
			$error_msg = curl_error($curl);
		} else {
			// Errori HTTP (400, 401, 500...)
			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

			if ($http_code >= 400) {
				$success = false;
				$error_msg = "HTTP $http_code: " . $response;
			}
		}

		curl_close($curl);
		return $success;
	}

	
	/**
	 * Crea un meeting Teams tramite evento Outlook (Graph API).
	 * @param array      $params    Parametri dell’evento (start, end, title, organizerEmail, timezone, ecc.)
	 * @param array|null $attendees  Lista invitati in formato Graph: 
	 *                              [ ['email' => 'x@y.com', 'type' => 'required'] ]
	 *
	 * @return array [ 'success' => bool, 'response' => mixed ]
	 */
	public function _addEventMeeting($params, $invitees = null)
	{
		$result = array('success'  => false, 'response' => '');
		$url = $this->scope_url . "v1.0/users/{$params['organizerEmail']}/events";
		
		// Recupero il token
		$access_token = $this->getAccessToken();
	
		// Validazione
		if (!$this->validateMeetingParams($params, $err)) {
			$result['response'] = $err;
			return $result;
		}

		// Valori di Default
		$params['timezone'] = $params['timezone'] ?? 'Europe/Rome';

		 // Normalizzazione parametri
		$graphEvent = [
			"subject" => $params["title"],

			"start" => [
				"dateTime" => $this->isoDateTime($params["start"]),
				"timeZone" => $params['timezone']
			],

			"end" => [
				"dateTime" => $this->isoDateTime($params["end"]),
				"timeZone" => $params['timezone']
			],

			"isOnlineMeeting" => true,
			"onlineMeetingProvider" => "teamsForBusiness",

			"attendees" => []
		];

		// Mappo gli invitati
		if (is_array($attendees)) {
			foreach ($attendees as $inv) {
				if (!isset($inv['email'])) continue;

				$graphEvent["attendees"][] = [
					"emailAddress" => [
						"address" => $inv['email']
					],
					"type" => $inv['type'] ?? "required"
				];
			}
		}

		// Chiamata
		
		if ($this->callRestApi("POST", $url, null, $graphEvent, $access_token, $response, $error_msg, 'json')) {

			$result["success"]  = true;
			$result["response"] = json_decode($response, true);

		} else {
			$result["response"] = $error_msg;
		}

		// Out
		return $result;
	}
	
	
	/**
	 * Crea un meeting Teams + aggiunge i tag di integrazione in modo atomico.
	 *
	 * @param array      $params       Parametri dell’evento (start, end, title, organizerEmail…)
	 * @param array|null $invitees     Lista invitati
	 * @param array|null $integrationTags  Tag di integrazione personalizzati
	 *
	 * @return array ['success' => bool, 'response' => mixed]
	 */
	public function createEventMeeting(array $params, ?array $invitees = null, ?array $integrationTags = null)
	{
		// Creo l’evento
		$create = $this->_addEventMeeting($params, $invitees);

		if (!$create['success']) 
			return ['success'  => false, 'response' => "Errore nella creazione dell’evento.", 'error' => $create['response'] ];
		

		// ID evento Graph
		$event = $create['response'];
		$eventId = $event['id'];
		$organizerEmail = $params['organizerEmail'];

		// Se non sono previsti tag, ho finito
		if (empty($integrationTags)) 
			return [ 'success'  => true, 'response' => $event, 'error' => null ];
	

		// Aggiungo i tag (con retry semplice)
		$maxAttempts = 3;
		$attempt = 0;
		$tagsAdded = false;
		$lastError = null;

		while ($attempt < $maxAttempts && !$tagsAdded) {
			$attempt++;

			$add = $this->addEventIntegrationTags($organizerEmail, $eventId, $integrationTags);

			if ($add['success']) {
				$tagsAdded = true;
				break;
			}

			$lastError = $add['response'];
			usleep(500000 * $attempt); // backoff: 0.5s, 1s, 1.5s
		}

		// Se non sono riuscito ad aggiungere i tag → rollback
		if (!$tagsAdded) {
			
			// Cancello l'evento creato
			$this->deleteEventMeeting($eventId, $organizerEmail);
			
			// Segnalo il problema
			return [ 'success'  => false, 'response' => "Evento rimosso perché impossibile aggiungere i tag.", 'error' => $lastError ];
		}

		// Tutto OK
		return [ 'success'  => true, 'response' => $event, 'error' => null ];
	}
	
	
	
	/**
	 * Aggiunge dati di riferimento custom a un evento di calendario tramite
	 * una OpenTypeExtension Graph.
	 *
	 * @param string $organizerEmail   		Email dell'organizzatore (owner del calendario).
	 * @param string $eventId          		ID dell'evento creato in precedenza.
	 * @param array  $eventIntegrationTags  Array di chiavi utilizzate per il tracciamento interno.
	 *
	 * @return array ['success' => bool, 'response' => mixed]
	 */
	private function _addEventIntegrationTags($organizerEmail, $eventId, array $eventIntegrationTags)
	{
		$result = ['success' => false, 'response' => ''];

		// Endpoint Graph
		$url = $this->scope_url . "v1.0/users/{$organizerEmail}/events/{$eventId}/extensions";
		
		// Recupero il token
		$access_token = $this->getAccessToken();

		// Costruisco la open extension
		$extensionPayload = [
			"@odata.type"   => "microsoft.graph.openTypeExtension",
			"extensionName" => $this->extension_name,
			"eventIntegrationTags"  => $eventIntegrationTags,
			"meta"          => [
				"version" => 1
			]
		];

		// Chiamata
		if ($this->callRestApi("POST", $url, null, $extensionPayload, $access_token, $response, $error_msg, 'json')) {

			$result['success'] = true;
			$result['response'] = json_decode($response, true);

		} else {
			$result['response'] = $error_msg;
		}

		return $result;
	}

	
	
	/**
	 * Aggiorna un meeting Teams (evento Outlook con onlineMeeting).
	 *
	 * @param string $eventId     ID dell’evento da aggiornare
	 * @param array  $params      Parametri da modificare (start, end, title, organizerEmail, ecc.)
	 * @return array
	 */
	public function updateMeetingTeams($eventId, $params)
	{
		$result = ['success'  => false, 'response' => ''];
		$url = $this->scope_url . "v1.0/users/{$params['organizerEmail']}/events/{$eventId}";

		// Validazione parametri
		if (!$this->validateMeetingParams($params, $err)) {
			$result['response'] = $err;
			return $result;
		}
	
		// Preparo PayLoad Graph
		$timezone = $params['timezone'] ?? 'Europe/Rome';

		$payload = [
			"subject" => $params["title"],
			"start" => [
				"dateTime" => $this->isoDateTime($params["start"]),
				"timeZone" => $timezone
			],
			"end" => [
				"dateTime" => $this->isoDateTime($params["end"]),
				"timeZone" => $timezone
			]
		];

		// Gestione partecipanti se presenti
		if (isset($params['attendees']) && is_array($params['attendees'])) {
			$payload["attendees"] = [];

			foreach ($params['attendees'] as $a) {
				if (!isset($a['email'])) continue;

				$payload["attendees"][] = [
					"emailAddress" => [ "address" => $a['email'] ],
					"type" => $a['type'] ?? "required"
				];
			}
		}

		// Chiamata
		if ($this->callRestApi("PATCH", $url, null, $payload, $this->getAccessToken(), $response, $error_msg, 'json')) {

			$result["success"]  = true;
			$result["response"] = json_decode($response, true);

		} else {
			$result["response"] = $error_msg;
		}

		return $result;
	}
	
	

	/**
	 * Valida i parametri minimi per la creazione/aggiornamento meeting.
	 *
	 * @param array  $params    Parametri passati dal chiamante
	 * @param string $error_msg Output contenente l'errore, se presente
	 * @return bool  TRUE se validi, FALSE se errore
	 */
	protected function validateMeetingParams($params, &$error_msg)
	{
		$required = [
			'start'     => 'missing start date',
			'end'       => 'missing end date',
			'title'     => 'missing title',
			'organizerEmail' => 'missing host email'
		];

		foreach ($required as $field => $msg) {
			if (!isset($params[$field]) || !is_string($params[$field]) || $params[$field] === '') {
				$error_msg = $msg;
				return false;
			}
		}

		return true;
	}
	
	
	/**
	 * Elimina un meeting Teams (evento Outlook con onlineMeeting).
	 *
	 * @param string      $eventId     ID dell'evento da eliminare
	 * @param string      $organizerEmail   Email dell'organizzatore
	 * @param string|null $error_msg   Output messaggio errore
	 *
	 * @return bool  TRUE se ok, FALSE se errore
	 */
	public function deleteEventMeeting($eventId, $organizerEmail, &$error_msg = null)
	{
		// Controlli minimi
		if (!$eventId || !$organizerEmail) {
			$error_msg = "Missing eventId or organizerEmail";
			return false;
		}

		// Endpoint Graph
		$url = $this->scope_url . "v1.0/users/{$organizerEmail}/events/{$eventId}";

		// Chiamata DELETE
		$success = $this->callRestApi(
			"DELETE",
			$url,
			null,            // query params
			null,            // body
			$this->getAccessToken(),
			$response,
			$error_msg,
			'json'
		);

		return $success;
	}
	
	
	/**
	 * Recupera un meeting Teams (evento Outlook con onlineMeeting).
	 *
	 * @param string      $eventId     ID dell’evento (meeting)
	 * @param string      $organizerEmail   Email dell'organizzatore
	 * @param string|null $error_msg   Output messaggio errore
	 *
	 * @return array|string  Risposta JSON Graph o stringa vuota se errore
	 */
	public function getEventMeeting($eventId, $organizerEmail, &$error_msg = null)
	{
		$result = [];
		
		// Controlli minimi
		if (!$eventId || !$organizerEmail) {
			$error_msg = "Missing eventId or organizerEmail";
			return $result;
		}
		
		// Preparo URL
		$url = $this->scope_url . "v1.0/users/{$organizerEmail}/events/{$eventId}";

		// Chiamata
		if ($this->callRestApi("GET", $url, null, null, $this->getAccessToken(), $response, $error_msg, 'json')) {
			$result = json_decode($response, true);
		}

		// Out
		return $result;
	}
	
	
	/**
	 * Restituisce i partecipanti di un meeting associato a evento dopo il suo termine
	 * @param string      $eventId     ID dell’evento (meeting)
	 * @param string      $organizerEmail   Email dell'organizzatore
	 * @return array|null   event meeting oppure null se non trovato
	 */
	public function getEventParticipants($eventId, $organizerEmail, &$error_msg = null)
	{
		if (!$eventId || !$organizerEmail) {
			$error_msg = "Missing eventId or organizerEmail";
			return null;
		}

		// Endpoint per meeting-evento
		$url = $this->scope_url . "v1.0/users/{$organizerEmail}/events/{$eventId}/onlineMeeting/attendanceReports";

		if (!$this->callRestApi("GET", $url, null, null, $this->getAccessToken(), $response, $error_msg, 'json')) {
			return null;
		}

		$data = json_decode($response, true);

		if (!isset($data['value'][0]['attendanceRecords'])) {
			return [];
		}

		$participants = [];

		foreach ($data['value'][0]['attendanceRecords'] as $rec) {
			$participants[] = [
				'name'     => $rec['identity']['displayName'] ?? null,
				'email'    => $rec['identity']['user']['email'] ?? null,
				'role'     => $rec['role'] ?? null,
				'joinTime' => $rec['joinDateTime'] ?? null,
				'leaveTime'=> $rec['leaveDateTime'] ?? null,
				'duration' => $rec['totalAttendanceInSeconds'] ?? null,
			];
		}

		return $participants;
	}
	
	
	/**
	 * Recupera un meeting Teams usando la joinWebUrl.
	 *
	 * Nota: da usare solo per meeting creati senza evento
	 *
	 * @param string $joinUrl    URL di join della riunione Teams
	 * @param string|null $error_msg  output messaggio errore
	 *
	 * @return array|null   onlineMeeting oppure null se non trovato
	 */
	public function getOnlineMeetingByJoinUrl($joinUrl, &$error_msg = null)
	{
		// Validazione
		if (!$joinUrl || !is_string($joinUrl)) {
			$error_msg = "Invalid joinUrl";
			return null;
		}

		// Preparo URL
		$encoded = rawurlencode($joinUrl);
		$url = $this->scope_url . "v1.0/me/onlineMeetings?\$filter=joinWebUrl%20eq%20'{$encoded}'";
		
		// Chiamata API
		if ($this->callRestApi("GET", $url, null, null, $this->getAccessToken(), $response, $error_msg, 'json')) {

			$data = json_decode($response, true);

			// Se la query ritorna items
			if (isset($data['value']) && count($data['value']) > 0) {
				return $data['value'][0]; // primo meeting trovato
			}

			return null;
		}

		// Errore HTTP/cURL
		return null;
	}

	
	
	/**
	 * Recupera i partecipanti di un meeting Teams usando la join URL.
	 * Nota: Da usare per meeting creati senza evento
	 * Funziona solo per meeting conclusi (Teams attendance reports).
	 *
	 * @param string      $joinUrl
	 * @param string|null $error_msg
	 *
	 * @return array|null
	 */
	public function getParticipantsTeams($joinUrl, &$error_msg = null)
	{
		// Recupero l'onlineMeeting tramite join URL
		$meeting = $this->getOnlineMeetingByJoinUrl($joinUrl, $error_msg);

		if (!$meeting || !isset($meeting['id'])) {
			$error_msg = "Unable to retrieve onlineMeeting from join URL.";
			return null;
		}

		$onlineMeetingId = $meeting['id'];
		$organizerEmail = $meeting['organizer']['emailAddress']['address'] ?? null;

		if (!$organizerEmail) {
			$error_msg = "Missing organizer email.";
			return null;
		}
		
		// Preparo URL
		$url = $this->scope_url . "v1.0/users/{$organizerEmail}/onlineMeetings/{$onlineMeetingId}/attendanceReports";

		// Chiamo i Teams Attendance Reports
		if (!$this->callRestApi("GET", $url, null, null, $this->getAccessToken(), $response, $error_msg, 'json')) {
			return null;
		}

		$data = json_decode($response, true);

		if (!isset($data['value'][0]['attendanceRecords'])) {
			return [];
		}

		// Estraggo elenco partecipanti
		$participants = [];

		foreach ($data['value'][0]['attendanceRecords'] as $rec) {
			$participants[] = [
				'name'       => $rec['identity']['displayName'] ?? null,
				'email'      => $rec['identity']['user']['email'] ?? null,
				'role'       => $rec['role'] ?? null,
				'joinTime'   => $rec['joinDateTime'] ?? null,
				'leaveTime'  => $rec['leaveDateTime'] ?? null,
				'duration'   => $rec['totalAttendanceInSeconds'] ?? null,
			];
		}

		return $participants;
	}
	
	
	/**
	 * Aggiunge un invitato ad un meeting Teams tramite aggiornamento evento calendario.
	 *
	 * @param string $eventId
	 * @param string $organizerEmail
	 * @param string $emailToAdd
	 * @param string|null $error_msg
	 *
	 * @return array
	 */
	public function insertInviteeTeams($eventId, $organizerEmail, $emailToAdd, &$error_msg = null)
	{
		$result = ['success' => false, 'response' => ''];
		
		// Validazioni
		if (!$eventId || !$organizerEmail || !$emailToAdd) {
			$error_msg = "Missing eventId, organizerEmail or emailToAdd";
			return $result;
		}

		// Recupero l’evento esistente
		$event = $this->getEventMeeting($eventId, $organizerEmail, $error_msg);

		if (!$event) {
			$result['response'] = "Event not found";
			return $result;
		}

		// Prendo gli invitati esistenti (se presenti)
		$attendees = $event['attendees'] ?? [];

		// Controllo se l'invitato è già presente
		foreach ($attendees as $a) {
			if (($a['emailAddress']['address'] ?? '') === $emailToAdd) {
				$result['success'] = true;
				$result['response'] = "Invitee already added";
				return $result;
			}
		}

		// Aggiungo il nuovo invitato
		$attendees[] = [
			"emailAddress" => [ "address" => $emailToAdd ],
			"type"         => "required"
		];

		// Costruisco il payload PATCH
		$payload = [ "attendees" => $attendees ];

		// Preparo URL
		$url = $this->site_scope . "v1.0/users/{$organizerEmail}/events/{$eventId}";
		
		// Chiamata
		if ($this->callRestApi("PATCH", $url, null, $payload, $this->getAccessToken(), $response, $error_msg, 'json')) {
			$result['success'] = true;
			$result['response'] = json_decode($response, true);
		} else {
			$result['response'] = $error_msg;
		}

		return $result;
	}
	

	/**
	 * Rimuove un invitato da un meeting Teams (evento Outlook).
	 *
	 * @param string $eventId
	 * @param string $organizerEmail
	 * @param string $emailToRemove
	 * @param string|null $error_msg
	 *
	 * @return array
	 */
	public function deleteInvitee($eventId, $organizerEmail, $emailToRemove, &$error_msg = null)
	{
		return $this->deleteInvitees($eventId, $organizerEmail,[$emailsToRemove], $error_msg);
	}
	
	
	/**
	 * Rimuove un array di invitati da un meeting Teams (evento Outlook).
	 *
	 * @param string      $eventId
	 * @param string      $organizerEmail
	 * @param array       $emailsToRemove    array di email da rimuovere
	 * @param string|null $error_msg
	 *
	 * @return array ['success'=>bool, 'response'=>mixed]
	 */
	public function deleteInvitees($eventId, $organizerEmail, array $emailsToRemove, &$error_msg = null)
	{
		$result = ['success' => false, 'response' => ''];

		// Validazioni
		if (!$eventId || !$organizerEmail || empty($emailsToRemove)) {
			$error_msg = "Missing eventId, organizerEmail or emailsToRemove";
			return $result;
		}

		// Normalizza email per confronti case-insensitive
		$emailsToRemove = array_map('strtolower', $emailsToRemove);

		// Recupero l’evento
		$event = $this->getEventMeeting($eventId, $organizerEmail, $error_msg);

		if (!$event) {
			$result['response'] = "Event not found";
			return $result;
		}

		// Attendees correnti (fallback [] se non esiste la proprietà)
		$attendees = $event['attendees'] ?? [];

		// Ricostruisco la lista mantenendo SOLO chi NON deve essere rimosso
		$newAttendees = [];
		$removed = false;

		foreach ($attendees as $attendee) {
			$email = strtolower($attendee['emailAddress']['address'] ?? '');

			if (!in_array($email, $emailsToRemove)) {
				$newAttendees[] = $attendee;
			} else {
				$removed = true;
			}
		}

		// Nessun invitato corrispondente → nulla da aggiornare
		if (!$removed) {
			$result['success'] = true;
			$result['response'] = "No invitees matched for removal.";
			return $result;
		}

		// Preparo URL
		$url = $this->scope_url . "v1.0/users/{$organizerEmail}/events/{$eventId}";
				
		// Chiamata con la lista aggiornata
		$payload = [ "attendees" => $newAttendees ];

		if ($this->callRestApi("PATCH", $url, null, $payload, $this->getAccessToken(), $response, $error_msg, 'json')) {
			$result['success']  = true;
			$result['response'] = json_decode($response, true);
		} else {
			$result['response'] = $error_msg;
		}

		// Out
		return $result;
	}
	
	
	/**
	 * Restituisce le informazioni degli utenti invitati inseriti a sistema
	 */
	public function getInfoInvitee($id_date, $id_day, $id_user = null) {
		
		$res = array();
		$whr = ($id_user ? ' AND i.id_user = '.(int)$id_user : '');
		
		$query = "	SELECT * FROM teams_event_invitee i 
						JOIN  teams_event_meeting m ON i.eventId = m.eventId 
					WHERE m.id_date = ".(int)$id_date.' AND m.id_day = '.(int)$id_day. $whr;
					
		// Lancio la query
		$result = sql_query($query);
		
		// Recupero dati	
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[ $row['id_user'] ] = $row;
		}
			
		// Out
		return $res;
	}
	
	
	/**
	 * Elimina le informazioni sull'invitato al meeting
	 */
	public function delInfoInvitee($inviteeId) {
		
		// Formo la query
		$query = "DELETE FROM teams_event_invitee WHERE inviteeId = '".$inviteeId."'";
	
		// Lancio la insert
		$res = sql_query($query);
						
		// Output
		return $res;	
	}
	

	/**
	 * Inserisce nel sistema le informazioni di un partecipante ad un meeting Teams
	 * generato come EVENTO Outlook (non onlineMeeting puro).

	 * @param array   $info     Record attendance report del partecipante
	 * @param string  $eventId  EventId dell'evento Outlook/Teams (PK tabella teams_event_meeting)
	 * @param mixed   $conn     Connessione DB opzionale
	 *
	 * @return bool   TRUE se inserito o già presente, FALSE in caso di errore
	*/	
	public function insInfoParticipant($info, $eventId, $conn = false)
	{
		// $info è un record di attendanceReports Teams
		// $eventId è l'ID dell'evento Outlook (PK della tabella)
		
		// Estrazione dati
		$userId     = ( (int)$info['identity']['user']['id'] ) ?? null;
		$joins      = (int)$info['numberOfJoins'] ?? 1;
		$duration   = (int) $info['totalAttendanceInSeconds'];
		
		$email      	= sql_escape_string( $info['identity']['user']['email'] ?? null );
		$displayName	= sql_escape_string( $info['identity']['displayName'] ?? null );
		$tenantId   	= sql_escape_string( $info['identity']['user']['tenantId'] ?? null );    // non sempre presente
		$role       	= sql_escape_string( $info['role'] ?? null) ;
		$joinTime   	= sql_escape_string( $info['joinDateTime'] ?? null );
		$leaveTime  	= sql_escape_string( $info['leaveDateTime'] ?? null );
		$externalType 	= sql_escape_string( $info['identity']['user']['externalUserType'] ?? null);

		// Validazioni di base
		if (!$email || !$eventId) return false;  // il minimo per identificare un partecipante
	
		// Controllo esistenza partecipante, se già presente non inserisco
		if ( $this->participantExists($email, $eventId) ) return true;
	

		// Inserimento partecipante
		$query	= " INSERT INTO teams_event_participant (eventId, userId,tenantId, externalUserType, email, displayName, role,"
				. "		joinDateTime,leaveDateTime, durationSeconds, numberOfJoins)"
				. " VALUES (
				'".sql_escape_string($eventId)."',
				".($userId     ? "'".sql_escape_string($userId)."'" : "NULL").",
				".($tenantId   ? "'".sql_escape_string($tenantId)."'" : "NULL").",
				".($externalType ? "'".sql_escape_string($externalType)."'" : "NULL").",
				".($email      ? "'".sql_escape_string($email)."'" : "NULL").",
				".($displayName? "'".sql_escape_string($displayName)."'" : "NULL").",
				".($role       ? "'".sql_escape_string($role)."'" : "NULL").",
				".($joinTime   ? "'".sql_escape_string($joinTime)."'" : "NULL").",
				".($leaveTime  ? "'".sql_escape_string($leaveTime)."'" : "NULL").",
				".(int)$duration.",
				".(int)$joins."
			)
		";

		return sql_query($query, $conn);
	}
	
	/**
	 * Inserisce le informazioni sull'invitato al meeting
	 */
	public function insInfoInvitee($info, $id_user) {
		
		$res = false;
		
		// Formo la query
		$query = "	INSERT INTO teams_event_invitee (email, displayName, eventId, role, id_user)
					VALUES ('".$info['email']."', '".$info['displayName']."', '".$info['eventId']."', ".(int)$info['role'].", ".(int)$id_user.")";

		// Lancio la insert
		$result = sql_query($query);
		
		// Recupero id del record
		if ($result) $res = sql_insert_id();
						
		// Output
		return $res;	
	}
	
	
	/**
	 * Elimina le informazioni del meeting nelle tabelle di sistema (anche record utenti)
	 */
	public function delInfoMeeting($eventId) {
		
		// Formo le query
		$query1 = "	DELETE FROM teams_event_meeting WHERE eventId = '".$eventId."'";
		$query2 = "	DELETE FROM teams_event_invitee WHERE eventId = '".$eventId."'";

		// Lancio le delete
		$res = sql_query($query2);
		
		if ($res)
			$res = sql_query($query1);
						
		// Output
		return $res;	
	}
	
	
	/**
	 * Elimina le informazioni di reportistica di specifiche edizioni
	 */
	public function delInfoParticipant($dates) {

		$meetings = "SELECT * FROM teams_event_meeting WHERE id_date IN ('".implode("','", $dates)."')";
		
		foreach ($meetings as $meet) {
			$query = "DELETE FROM teams_event_participant WHERE eventId = '".$meet['eventId']."'";
			$res = sql_query($query);
		}
						
		// Output
		return $res;	
	}
	
	
	/**
	 * Inserisce le informazioni del meeting nella tabella di sistema
	 */
	public function insInfoMeeting($info, $eventIntegrationTags) {

		$tags = array();
		$title = sql_escape_string($info['title']);
		
		//Recupero le chiavi. L'ordine degli elementi dell'array non sembra garantito, quindi cerco in base a un prefisso
		foreach($eventIntegrationTags as $el) {
			if (substr($el, 0, 5) == 'keys,') {
				$tags = explode(",", $el);
			}
		}
		
		// Recupero chiavi da array di ritorno Webex
		$id_course = (int)$tags[1];
		$id_date = (int)$tags[2];
		$id_day = (int)$tags[3];
		
		// Formo la query
		$query = "	INSERT INTO teams_event_meeting (eventId, title, organizerId, organizerEmail, organizerTenantId, 
							attendanceReportId,	joinUrl, startDateTime, endDateTime, timezone, id_course, id_date, id_day)
					VALUES ('".$info['eventId']."', '".$title."', '".$info['organizerId']."', '".$info['organizerEmail']."', '".$info['organizerTenantId']."',
							'".$info['attendanceReportId']."', '".$info['joinUrl']."', '".$info['startDateTime']."', '".$info['endDateTime']."', '".$info['timezone']."',
							'".$id_course.", ".$id_date.", ".$id_day.")";

		// Lancio la insert
		$res = sql_query($query);
						
		// Output
		return $res;	
	}
	
	
	/**
	 * Restituisce le informazioni del meeting registrate nella tabella di sistema
	 */
	public function getInfoMeeting($id_date, $id_day = null) {

		$res = array();
		$whr = ($id_day ? ' AND id_day = '.(int)$id_day : '');
		
		$query = "	SELECT * FROM teams_event_meeting WHERE id_date = ".(int)$id_date . $whr;
		
		// Lancio la query
		$result = sql_query($query);
		
		// Recupero dati	
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[] = $row;
		}
			
		// Out
		return $res;
	}
	
	
	/**
	 * Recupera gli eventIntegrationTags da una OpenTypeExtension associata a un evento.
	 *
	 * @param string $organizerEmail   Email dell'organizzatore (owner del calendario)
	 * @param string $eventId          ID dell'evento Graph
	 *
	 * @return array ['success' => bool, 'response' => mixed, 'error' => string]
	 */
	public function getEventIntegrationTags($organizerEmail, $eventId)
	{
		$res = [
			'success'  => false,
			'response' => null,
			'error'    => ''
		];

		// Token
		$access_token = $this->getAccessToken();

		// URL della open extension
		// /users/{mail}/events/{id}/extensions/{extensionName}
		$url = $this->scope_url . "v1.0/users/{$organizerEmail}/events/{$eventId}/extensions/{$this->extension_name}";

		// Eseguo la chiamata
		if ($this->callRestApi("GET", $url, null, null, $access_token, $response, $error_msg, 'json')) {

			$json = json_decode($response, true);

			if (isset($json['eventIntegrationTags'])) {
				$res['success']  = true;
				$res['response'] = $json['eventIntegrationTags'];
			} else {
				$res['error'] = "L'evento esiste ma non contiene eventIntegrationTags.";
			}

		} else {
			$res['error'] = $error_msg;
		}

		return $res;
	}

	
	/**
	 * Restituisce le informazioni del meeting registrate nella tabella di sistema
	 */
	public function getInfoMeetingById($eventId) {
	
		$res = array();
			
		$query = "SELECT * FROM teams_event_meeting WHERE eventId = '".$eventId."'";
		
		// Lancio la query
		$result = sql_query($query);
		
		// Recupero dati riga
		$row = sql_fetch_assoc($result);
		
		// Assegno risultato
		if ($row) $res = $row;
		
		// Out
		return $res;
	}
	
	
	/**
	 * Restituisce le chiavi delle edizioni con lezioni/meeting
	 */
	public function getCourseEdition($id_course) {
		
		$res = array();
		
		$query = "SELECT DISTINCT id_date FROM teams_event_meeting WHERE id_course = ".(int)$id_course;
		
		// Lancio la query
		$result = sql_query($query);
		
		// Recupero dati
		while(list($id_date) = sql_fetch_row($result))
			$res[$id_date] = $id_date;
					
		// Out
		return $res;
	}
	
	
	/**
	 * Restituisce se il corso passato in argomento è stato pianificato con meeting
	 */
	public function isMeetingCourse($id_course, $check_integrity = true) {
		
		$res = false;
		
		$query = "	SELECT COUNT( DISTINCT(m.id_course) ) AS Exist FROM teams_event_meeting m "
				."		".($check_integrity ? 'INNER' : 'LEFT'). " JOIN %lms_course c ON m.id_course = c.idCourse "
				."	WHERE m.id_course = ".(int)$id_course;
						
		// Lancio la query
		$result = sql_query($query);
		
		// Recupero dati
		while(list($exist) = sql_fetch_row($result))
			$res = (bool)$exist;
					
		// Out
		return $res;
	}
	
	
	/**
	 * Restituisce se il partecipante è presente nei dati informativi caricati
	 * La ricerca avviene per email ed evento.
	 */
	public function participantExists($email, $eventId) {
		//>> Restituisce se l'id del partecipante è presente nei dati informativi caricati
		
		$res = false;
				
		// Controllo esistenza partecipante
		$query = " SELECT participantId"
					. "	FROM teams_event_participant"
					. " WHERE eventId = '" . $eventId . "' AND email = '" . $email . "'"
					. " LIMIT 1";

		$result = sql_query($query);
		
		if ($result && sql_num_rows($result) > 0) {
			// Già presente 
			$res = true;
		}
						
		return $res;
	}
	
	
	/**
	 * Restituisce le informazioni sugli iscritti a un'edizione
	 */
	public function getSubscribedUsers($id_date, $in_list = null) {
	
		$res = array();
		
		$whr = (is_array($in_list) ? " AND du.id_user IN ('".implode("','", $in_list)."')" : "");
		
		// Preparo la query
		$query = "	SELECT du.id_user, u.firstname, u.lastname, u.email, cu.level 
					FROM %lms_course_date_user du
						JOIN %lms_course_date dt ON du.id_date = dt.id_date
						JOIN %adm_user u ON du.id_user = u.idst
						JOIN %lms_courseuser cu ON (du.id_user = cu.idUser AND dt.id_course = cu.idCourse)
					WHERE du.id_date = ".(int)$id_date. $whr;
	
		// Lancio la query
		$result = sql_query($query);
		
		// Recupero dati	
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[ $row['id_user'] ] = $row;
		}
			
		// Out
		return $res;		
	}
	
	
	/**
	 * Restituisce le aule registrate a sistema
	 */
	public function getClassrooms() {
	
		$res = array();
		$query = "	SELECT idClassroom, room FROM %lms_classroom";
		
		//Lancio la query
		$result = sql_query($query);
		
		// Recupero i dati
		while($row = sql_fetch_assoc($result))
		{
			$res[ $row['idClassroom'] ] = $row['room'];
		}
		
		// Out
		return $res;
	}
	
	
	/**
	 * Metodo per l'estrazione delle edizioni di un dato periodo 
	 */
	public function getEditionList($date_begin, $date_end, $status, $id_org = false) {
	
		//Preparo stringa Where query
		$whrExp  = "c.course_virtual = 1 AND DATE(dy.date_begin) >= '". $date_begin . "' AND DATE(dy.date_end) <= '". $date_end . "'";
		
		if($status !== false)
			$whrExp  .= " AND  dt.status = ".(int)$status;
			
		if($id_org){
			$courses = $this->getCourseCatalogOrg($id_org, false, false);
			$whrExp  .=  " AND dt.id_course IN ('" .implode("','", array_keys($courses)). "') ";
		}
		
		//Recupero la stringa SQL
		$query = "	SELECT dt.name, dt.code, MIN(dy.date_begin) AS date_begin, MAX(dy.date_end) AS date_end, dt.status, dt.id_date, fe.id_fund, COUNT(meetingId) AS meeting_count 
					FROM %lms_course_date dt 
						JOIN %lms_course c ON dt.id_course = c.idCourse
						JOIN %lms_course_date_day dy ON dt.id_date = dy.id_date
						LEFT JOIN %teams_event_meeting m ON (dy.id_date = m.id_date AND dy.id_day = m.id_day)
						LEFT JOIN %lms_fund_entry fe ON (fe.id_entry = dt.id_date AND fe.type_entry = 'date')
					WHERE ".$whrExp."
					GROUP BY dt.id_date, dt.name, dt.code, dt.status, fe.id_fund
					ORDER BY dy.date_begin, dt.code";
					
		//Lancio la query
		$result = sql_query($query);
		
		$res = array();
		
		//Recupero il record
		while($row = sql_fetch_assoc($result))			
			$res[] = $row;
		
		//Out
		return $res;		
	}
	
	
	/**
	 * Metodo per l'estrazione dei giorni di lezione con link e password dei meeting
	 */
	public function getMeetingList($date_begin, $date_end, $status, $id_org = false) {
				
		//Preparo stringa Where query
		$whrExp  = "c.course_virtual = 1 AND DATE(dy.date_begin) >= '". $date_begin . "' AND DATE(dy.date_end) <= '". $date_end . "'";
		
		if($status !== false)
			$whrExp  .= " AND  dt.status = ".(int)$status;
			
		if($id_org){
			$courses = $this->getCourseCatalogOrg($id_org, false, false);
			$whrExp  .=  " AND dt.id_course IN ('" .implode("','", array_keys($courses)). "') ";
		}
		
	
		//Recupero la stringa SQL
		$query = "	SELECT dt.name, dt.code, dy.id_day, dy.date_begin, dt.status,
						dy.classroom, m.webLink, m.password, fe.id_fund, m.meetingId, dt.id_date 
					FROM %lms_course_date dt 
						JOIN %lms_course c ON dt.id_course = c.idCourse
						JOIN %lms_course_date_day dy ON dt.id_date = dy.id_date
						LEFT JOIN teams_event_meeting m ON (dy.id_date = m.id_date AND dy.id_day = m.id_day)
						LEFT JOIN %lms_fund_entry fe ON (fe.id_entry = dt.id_date AND fe.type_entry = 'date')
					WHERE ".$whrExp."
					ORDER BY dy.date_begin, dy.id_day, dt.code";
															
		//Lancio la query
		$result = sql_query($query);
		
		$res = array();
		
		//Recupero il record
		while($row = sql_fetch_assoc($result))			
			$res[] = $row;

		
		//Out
		return $res;
	}
	
	
	/**
	 * Restituisce i partecipanti ai meeting di un'edizione (da tabelle di forma)
	 */
	public function getParticipantList($id_date, $id_day = null, $id_org = null) {
		
		require_once(_adm_.'/lib/lib.field.php');
		$flist = new FieldList();
		
		$res = array();
		$users = array();
		$cf_name = 'USERIDSF';
		
		// Trovo l'id del campo utente custom in base al nome traduzione
		$id_common = $flist->getFieldIdCommonFromTranslation($cf_name); 
		
		// Preparo Where
		$whrExp = "m.id_date = ".(int)$id_date;
		
		if ($id_day !== null)
			$whrExp .= " AND m.id_day = ".(int)$id_day;
		
		if ($id_org) {
			$query_users = $this->acl_man->getSqlUsersByOrg($id_org);
			$whrExp .= " AND (cu.level > 3 OR i.id_user IN (".$query_users."))";
		}
		
		// Preparo Query
		$query = "	SELECT i.id_user, u.email, u.userid, u.firstname, u.lastname, cu.level, p.joinDateTime, p.leaveDateTime, p.durationSeconds, p.numberOfJoins, m.eventId, dy.date_begin AS scheduled_day 
					FROM teams_event_invitee i 
						JOIN teams_event_meeting m ON i.meetingId = m.meetingId
						JOIN teams_event_participant p ON (i.email = p.email AND i.meetingId = p.meetingId)
						JOIN %adm_user u ON i.id_user = u.idst
						JOIN %lms_courseuser cu ON m.id_course = cu.idCourse AND i.id_user = cu.idUser
						JOIN %lms_course_date_day dy ON (m.id_date = dy.id_date AND m.id_day = dy.id_day)
					WHERE ".$whrExp;
		
		// Lancio la query
		$result = sql_query($query);
		
		//Recupero i record
		if ( $result ) {
			while($row = sql_fetch_assoc($result))	{	
				$id_user = $row['id_user'];
				$res[$id_user][] = $row;
			}
			
			// Recupero utenti
			$users = array_keys($res);
			
			// Trovo i valori dei campi personalizzati per ogni utente
			$cf_vals = array();
				
			if ($id_common)
				$cf_vals = $flist->getUsersFieldEntryData($users, array($id_common));
				
				
			// Sistemazione dati
			foreach($res as $id_user => &$rows) {
				foreach ($rows as &$row) {
					$row['joinDateTime'] = $this->checkDateString($row['joinDateTime'], 'Y-m-d H:i:s');
					$row['leaveDateTime'] = $this->checkDateString($row['leaveDateTime'], 'Y-m-d H:i:s');
					$row['minutes'] = round( (int)$row["durationSeconds"] / 60 );
					
					if ($id_common)
						$row[ $cf_name ] = isset($cf_vals[ $id_user ][ $id_common ]) ? $cf_vals[ $id_user ][ $id_common ] : '';
				}
			}	
		}
		
		// Out
		return $res;
	}
	
	
	/**
	 * Restituisce i corsi del catalogo assegnati all'organizzazione
	 * L'argomento opzionale $status è un array con i valori di stato corso
	 */
	public function getCourseCatalogOrg($id_org, $status = false) {

		require_once(_lms_.'/lib/lib.course.php');
				
		$course_man = new Man_Course();
		
		return $course_man->getCourseCatalogOrg($id_org, $status, false);
	}
	
	
	/**
	 * Restituisce link e password dei meeting di una specifica edizione
	 */
	public function getMeetingLinks($id_date) {
	
		$res = array();
		
		$query = "	SELECT joinUrl, organizerEmail, id_day 
					FROM teams_meeting_meeting 
					WHERE id_date = ".(int)$id_date;
					
		//Lancio la query
		$result = sql_query($query);
		
		// Recupero dati
		while( $row = sql_fetch_assoc($result) ) {	
			$res[ $row['id_day'] ] = array(	'joinUrl' => $row['joinUrl'], 
											'organizerEmail' => $row['organizerEmail'] );
		}
					
		// Out
		return $res;
	}
	
	
	/**
	 * Restituisce lo userid relativo in base a quello assoluto (rimuove '/')
	 */
	public function getRelativeUserId($userid) {
	
		return $this->acl_man->relativeId($userid);
	}
	
	
	
	
	/**
	 * Routine per gestione eccezionale. Inserisce nuovamente gli invitati nella tabella di sistema in base alle iscrizioni
	 * Da utilizzare solo in caso di ricostruzione statistiche dopo eliminazioni accidentali
	 */
	 /** DA VALUTARE
	public function restoreInfoInvitee($arr_edition) {
	
		$organizer_email = Get::sett('tmsMeeting.organizer_email', '');
		$query = "
			INSERT INTO teams_event_invitee (`inviteeId`, `email`, `displayName`, `coHost`, `meetingId`, `id_user`)
			SELECT CONCAT(LEFT(m.meetingId, 32), '-', IFNULL(u.idst,0)), 
				CASE WHEN cu.level = 6 THEN '".$coHost."' ELSE u.email END as email,
				CONCAT(u.firstname, ' ', u.lastname),
				CASE WHEN cu.level = 6 THEN 1 ELSE 0 END as coHost,
				m.meetingId, u.idst
			FROM `learning_course_date` dt 
				JOIN learning_course_date_user du ON dt.id_date = du.id_date
				JOIN core_user u ON du.id_user = u.idst
				JOIN learning_course_date_day dy ON dt.id_date = dy.id_date
				JOIN learning_meeting m ON m.id_date = dt.id_date AND m.id_day = dy.id_day 
				JOIN learning_courseuser cu ON cu.idCourse = m.id_course AND cu.idUser = du.id_user
			WHERE dt.id_course IN ('".inplode("','", $arr_edition)."')  
			ORDER BY dt.code, dy.id_date,`dy`.`date_begin`  ASC;";
			
		//Lancio la query
		return sql_query($query);
		
	} */
	
	/*
		* Cosa significa "tokenlimit_reached" quando si tenta di generare token di accesso? 
		* Un utente Webex può avere fino a 750 token di accesso attivi in un determinato momento. 
		* Se un utente raggiunge tale limite e tenta di generare più token, verrà restituito un errore "tokenlimit_reached".
		* La soluzione sarebbe accedere a https://idbroker.webex.com/idb/profile#/ e terminare parte della sessione 
		* o inviare un ticket a devsupport@webex.com per revocare tutti i token di accesso per l'account. 
	 * */

}

?>
