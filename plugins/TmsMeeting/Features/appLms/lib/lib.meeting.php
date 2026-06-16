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
	protected $lang;
	protected $acl_man;
	
	protected $site_url;
	protected $scope_url;
	protected $tenant_id;
	protected $client_id;
	protected $client_secret;
	protected $access_token;
	protected $token_expiration;
	protected $extension_name = "com.dnateams.integration";
	

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
	 * Controlla se è una data e la restituisce in formato iso
	 */
	private function isoDateTime($datetimeString)
	{
		$dt = DateTime::createFromFormat('Y-m-d H:i:s', $datetimeString);

		if (!$dt) {
			return null; // o gestisci errore
		}

		return $dt->format('Y-m-d\TH:i:s');
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
	 * Imposta il token nella variabile di istanza
	 */
	private function setAccessToken($token, $expiresIn) {
		
		if (!$token) {
			$this->access_token = '';
			$this->token_expiration = 0;
		} else {
			$this->access_token = $token;
			$this->token_expiration = time() +  $expiresIn;
		}		
		
	}
	
	
	/**
	 * Mapping dei campi restituiti dai record dei report Teams
	 */
	private function mapAttendance(array $records): array
	{
		$out = [];
		foreach ($records as $r) {
					
			$displayName  = $r['identity']['displayName'] ?? null;
			$emailAddress  = $r['emailAddress'] ?? null;
			$role  = $r['role'] ?? null;
			$totalAttendanceInSeconds  = $r['totalAttendanceInSeconds'] ?? null;
			
			
			foreach ($r['attendanceIntervals'] as $interval) {
				
				$out[] = [
					'displayName'     		=> $displayName,
					'emailAddress'    		=> $emailAddress,
					'role'     				=> $role,
					'joinDateTime' 				=> $interval['joinDateTime']         ?? null,
					'leaveDateTime'				=> $interval['leaveDateTime']        ?? null,
					'durationInSeconds'			=> $interval['durationInSeconds']    ?? null,
					'totalAttendanceInSeconds' 	=> $totalAttendanceInSeconds
				];
			}
		}
		
		return $out;
	}



	/***
	 * Controlla la validità della chiave segreta (può scadere, massimo dura due anni)
	 * Se non è valida restituisce l'errore   "error": "invalid_client", "error_description": "AADSTS7000215: Invalid client secret is provided."
	 */
	public function isClientSecretValid(&$errorMsg = null)
	{
		// Forza la rigenerazione del token ignorando cache/expiration
		$this->setAccessToken(null, null);

		$data = $this->getAccessToken($errorMsg);

		if ($data !== false && !empty($data)) {
			
			$this->setAccessToken;
			$this->setAccessToken( $data['access_token'], $data['expires_in'] );
			
			return true;    // Secret valido
		}

		return false;       // Secret scaduto, errato o revocato
	}

	
	/**
	 * Restituisce il token di autenticazione
	 * Su errore return false e $errorMsg restituisce la descrizione dell'errore"
	 */
	protected function getAccessToken(&$errorMsg = null) {

		// Restituisco il tokeno valido (utile per chiamate multiple nella stessa operazione)
		if (time() < $this->token_expiration) return $this->access_token;
		
		// Procedo se occorre rigenerare il token
		$res = false;
		
		$url = $this->site_url . $this->tenant_id.'/oauth2/v2.0/token';
		$scope = $this->scope_url . '.default';
	
		$params = array(
			'client_id'		=>	$this->client_id,
			'client_secret'	=>	$this->client_secret,
			'scope'			=>	$scope,
			'grant_type'	=>	'client_credentials'
		);
		
		if ($this->callRestApi("POST", $url, null, $params, null, $response, $errorMsg, 'form')) {
			
			// Recupero la risposta della chiamata
			$data = json_decode($response, true);
			
			if (!isset($data['access_token'])) {
				// JSON potrebbe essere malformato o mancare dei campi
				$errorMsg = "Token non presente nella risposta.";
			} else {
				// Tutto OK
				
				// Imposto il token della variabile di istanza
				$this->setAccessToken( $data['access_token'], $data['expires_in'] );
				
				// Lo recupero per l'output
				$res = $this->access_token;
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
	 * @param string|null $errorMsg     Output: messaggio d'errore in caso di fallimento
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
		&$errorMsg = null,
		$content_type = 'json'
	) {
		$success = true;
		$errorMsg = null;

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
		curl_setopt($curl, CURLOPT_FAILONERROR, false); // NON bloccare errori HTTP per leggere JSON di errore

		// In caso di metodi con body (POST, PUT, PATCH...)
		if ($method !== 'GET' && $body_string !== '') {
	
			// Mi assicuro che il carattere "&" non diventi "amp;"
			$body_string = html_entity_decode($body_string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
				
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
			$errorMsg = curl_error($curl);
		} else {
			// Errori HTTP (400, 401, 500...)
			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

			if ($http_code >= 400) {
				$success = false;
				$errorMsg = "HTTP $http_code: " . $response;
			}
		}

		curl_close($curl);
		return $success;
	}
	
	
	/**
	 * Crea un meeting Teams tramite evento Outlook (Graph API).
	 * @param array      $params    Parametri dell’evento (start, end, title, organizerEmail, timezone, ecc.)
	 * @param array|null $invitees  Lista invitati in formato Graph: 
	 *                              [ ['email' => 'x@y.com', 'type' => 'required'] ]
	 * @param array|null $integrationTags  array chiavi di integrazione
	 *
	 * @return array [ 'success' => bool, 'response' => mixed ]
	 */
	 
	public function addEventMeeting(array $params, ?array $invitees = null, ?array $integrationTags = null)
	{
		$result = ['success' => false, 'response' => ''];
		$admitTags = ['key_day', 'id_date', 'id_course', 'edition_code'];
		
		
		// Preparo la queryString per recuperare le eventuali estensioni inserite nel response
		$expand = "extensions(\$filter=id eq '".$this->extension_name."')";
		$queryString = http_build_query(['$expand' => $expand]);
		
		// Endpoint reale Graph
		$url = $this->scope_url .'v1.0/users/' . $params['organizerEmail'] . '/events?' . $queryString;

		// Token
		$accessToken = $this->getAccessToken();

		// Validazione parametri
		if (!$this->validateMeetingParams($params, $err)) {
			$result['response'] = $err;
			return $result;
		}

		// Default timezone
		$params['timezone'] = $params['timezone'] ?? 'Europe/Rome';

		// Normalizzazione evento
		$graphEvent = [
			"subject" => $params["subject"],

			"start" => [
				"dateTime" => $this->isoDateTime($params["start"]),
				"timeZone" => $params['timezone']
			],

			"end" => [
				"dateTime" => $this->isoDateTime($params["end"]),
				"timeZone" => $params['timezone']
			],
			
			// Body personalizzato (HTML o text)
			"body" => [
				"contentType" => isset($params['body_type']) ? $params['body_type'] : "HTML",
				"content" => isset($params['body']) ? $params['body'] : ""
			],
			
			"isOnlineMeeting" => true,
			"onlineMeetingProvider" => "teamsForBusiness",

			"attendees" => []
		];

		// Invitati
		if (is_array($invitees)) {
			foreach ($invitees as $user) {
				if (!isset($user['email'])) continue;

				$graphEvent["attendees"][] = [
					"emailAddress" => [
						"address" => $user['email']
					],
					"type" => $user['attendee_type'] ?? "required"
				];
			}
		}

		// Open Extension (inserisco i tag di integrazione)
		if (is_array($integrationTags)) {

			$extensionData = [];

			foreach ($integrationTags as $key => $value) {
				if (!in_array($key, $admitTags)) continue;
				$extensionData[$key] = (string)$value;
			}

			if (!empty($extensionData)) {

				$graphEvent["extensions"] = [
					array_merge(
						[
							"@odata.type"   => "microsoft.graph.openTypeExtension",
							"extensionName" => $this->extension_name
						],
						$extensionData
					)
				];
			}
		}

		// Chiamata Graph
		if ($this->callRestApi("POST", $url, null, $graphEvent, $accessToken, $response, $errorMsg, 'json')) {

			$result["success"]  = true;
			$result["response"] = json_decode($response, true);

		} else {
			$result["response"] = $errorMsg;
		}

		return $result;
	}
	
	
	/**
	 * Recupera i report di uno specifico meeting.
	 * Un meeting può avere più report in base al numero di sessioni avviate (es. meeting riaperto).
	 * Un report ha un id, una data-ora di inizio, una data-ora di fine
	 */
	public function getMeetingReports($userId, $meetingId, &$errorMsg = null)
	{
		$url = $this->scope_url . "v1.0/users/{$userId}/onlineMeetings/{$meetingId}/attendanceReports";
		if (!$this->callRestApi("GET", $url, null, null, $this->getAccessToken(),
								$response, $errorMsg, 'json')) {
			return null;
		}
		$data = json_decode($response, true);
		return $data['value'] ?? [];
	}

	
	
	/**
	 * Recupera i record di uno specifico report di meeting.
	 * Un meeting può avere più report in base al numero di sessioni avviate (es. meeting riaperto).
	 */
	public function getAttendanceRecords($userId, $meetingId, $reportId, &$errorMsg = null)
	{
		$url = $this->scope_url .
			"v1.0/users/{$userId}/onlineMeetings/{$meetingId}/attendanceReports/{$reportId}/attendanceRecords";

		if (!$this->callRestApi("GET", $url, null, null, $this->getAccessToken(),
								$response, $errorMsg, 'json')) {
			return null;
		}
		$data = json_decode($response, true);
		return $data['value'] ?? [];
	}

		 
	 
	 /*
	public function addEventMeeting(array $params, ?array $invitees = null, ?array $integrationTags = null)
	{
		
		$result = ['success' => false, 'response' => ''];
		$admitTags = ['key_day', 'id_date', 'id_course', 'edition_code'];

		// Endpoint
		$url = $this->scope_url . "v1.0/users/{$params['organizerEmail']}/events";
		
		// Token
		$accessToken = $this->getAccessToken();

		// Validazione parametri
		if (!$this->validateMeetingParams($params, $err)) {
			$result['response'] = $err;
			return $result;
		}

		// Default timezone
		$params['timezone'] = $params['timezone'] ?? 'Europe/Rome';

		// Normalizzazione parametri evento
		$graphEvent = [
			"subject" => $params["subject"],

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
		if (is_array($invitees)) {
			foreach ($invitees as $inv) {
				if (!isset($inv['email'])) continue;

				$graphEvent["attendees"][] = [
					"emailAddress" => [
						"address" => $inv['email']
					],
					"type" => $inv['type'] ?? "required"
				];
			}
		}

		// Preparo le proprietà da aggiungere all'evento
		if (is_array($integrationTags)) {

			// GUID standard MAPI
			$guid = "{00020329-0000-0000-C000-000000000046}";

			$graphEvent["singleValueExtendedProperties"] = [];

			foreach ($integrationTags as $key => $value) {
				
				// Salto se la chiave non è ammessa
				if ( !in_array($key, $admitTags) ) continue;

				// Nome property unico
				$propertyId = "String {$guid} Name {$key}";

				$graphEvent["singleValueExtendedProperties"][] = [
					"id"    => $propertyId,
					"value" => $value
				];
			}
		}
				$url = "https://httpbin.org/post";
		
				

		// Chiamata
		if ($this->callRestApi("POST", $url, null, $graphEvent, $accessToken, $response, $errorMsg, 'json')) {

			$result["success"]  = true;
			$result["response"] = json_decode($response, true);

		} else {
			$result["response"] = $errorMsg;
		}
		
		var_export($result["response"]);exit;
	
		// Out
		return $result;
	}
	*/
	
	/*
	public function _addEventMeeting3($params, $invitees = null)
	{
		$result = array('success'  => false, 'response' => '');
		$url = $this->scope_url . "v1.0/users/{$params['organizerEmail']}/events";
		
		// Recupero il token
		$accessToken = $this->getAccessToken();
		
	
		// Validazione
		if (!$this->validateMeetingParams($params, $err)) {
			$result['response'] = $err;
			return $result;
		}

		// Valori di Default
		$params['timezone'] = $params['timezone'] ?? 'Europe/Rome';

		 // Normalizzazione parametri
		$graphEvent = [
			"subject" => $params["subject"],

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
		if (is_array($invitees)) {
			foreach ($invitees as $inv) {
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
		
		if ($this->callRestApi("POST", $url, null, $graphEvent, $accessToken, $response, $errorMsg, 'json')) {

			$result["success"]  = true;
			$result["response"] = json_decode($response, true);

		} else {
			$result["response"] = $errorMsg;
		}

		// Out
		return $result;
	}
	*/
	
	/**
	 * Crea un meeting Teams + aggiunge i tag di integrazione in modo atomico.
	 *
	 * @param array      $params       Parametri dell’evento (start, end, title, organizerEmail…)
	 * @param array|null $invitees     Lista invitati
	 * @param array|null $integrationTags  Tag di integrazione personalizzato
	 *
	 * @return array ['success' => bool, 'response' => mixed]
	 */
	 /*
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

			$add = $this->_addEventIntegrationTags($organizerEmail, $eventId, $integrationTags);
			

			if ($add['success']) {
				$tagsAdded = true;
				break;
			}

			$lastError = $add['response'];
			usleep(500000 * $attempt); // backoff: 0.5s, 1s, 1.5s
		}
		
		//exit( '{"success":false,"message":"x'.$test.'"}' );

		// Se non sono riuscito ad aggiungere i tag → rollback
		if (!$tagsAdded) {
			
			// Cancello l'evento creato
			$this->deleteEventMeeting($eventId, $organizerEmail);
			
			// Segnalo il problema
			return [ 'success'  => false, 'response' => "Evento rimosso perché impossibile aggiungere il tag.", 'error' => $lastError ];
		}
		
		exit( '{"success":false,"message":"x'.$test.'"}' );

		// Tutto OK
		return [ 'success'  => true, 'response' => $event, 'error' => null ];
	}
	*/
	
	
	/**
	 * Restituisce un tag di integrazione associato a un evento tramite open extension
	 */
	public function getEventIntegrationTag($organizerEmail, $eventId, $tagName)
	{
		$result = ['success' => false, 'response' => null];

		// Nome dell'extension
		$extensionName = $this->extension_name;

		// Endpoint per leggere l'extension specifica
		$url = $this->scope_url . "v1.0/users/{$organizerEmail}/events/{$eventId}/extensions/{$extensionName}";

		// Recupero token
		$accessToken = $this->getAccessToken();

		// Chiamata GET
		if ($this->callRestApi("GET", $url, null, null, $accessToken, $response, $errorMsg, 'json')) {

			$data = json_decode($response, true);

			// Se il tag richiesto esiste nell'extension
			if (isset($data[$tagName])) {
				$result['success']  = true;
				$result['response'] = $data[$tagName];
			} else {
				// Extension esiste ma il tag specifico non è presente
				$result['success']  = true;
				$result['response'] = null;
			}

		} else {
			// Se l'extension non esiste, Graph restituisce 404
			if (strpos($errorMsg, 'ResourceNotFound') !== false) {
				$result['success']  = true;
				$result['response'] = null;
			} else {
				$result['success']  = false;
				$result['response'] = $errorMsg;
			}
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
			"subject" => $params["subject"],
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
		if ($this->callRestApi("PATCH", $url, null, $payload, $this->getAccessToken(), $response, $errorMsg, 'json')) {

			$result["success"]  = true;
			$result["response"] = json_decode($response, true);

		} else {
			$result["response"] = $errorMsg;
		}

		return $result;
	}
	
	

	/**
	 * Valida i parametri minimi per la creazione/aggiornamento meeting.
	 *
	 * @param array  $params    Parametri passati dal chiamante
	 * @param string $errorMsg Output contenente l'errore, se presente
	 * @return bool  TRUE se validi, FALSE se errore
	 */
	protected function validateMeetingParams($params, &$errorMsg)
	{
		$required = [
			'start'     => 'missing start date',
			'end'       => 'missing end date',
			'subject'     => 'missing title',
			'organizerEmail' => 'missing host email'
		];

		foreach ($required as $field => $msg) {
			if (
				!isset($params[$field]) || 
				!is_string($params[$field]) || 
				$params[$field] === '' || 
				(isset($params['organizerEmail']) && !filter_var($params['organizerEmail'], FILTER_VALIDATE_EMAIL))
			){
				$errorMsg = $msg;
				return false;
			}
		}

		return true;
	}
	
	
	/**
	 * Annulla un meeting Teams (evento Outlook con onlineMeeting)
	 * utilizzando la chiamata Graph /cancel invece di DELETE.
	 *
	 * @param string      $eventId        ID dell'evento da annullare
	 * @param string      $organizerEmail Email dell'organizzatore
	 * @param string|null $errorMsg       Output messaggio errore
	 *
	 * @return bool TRUE se ok, FALSE se errore
	 */
	public function deleteEventMeeting($eventId, $organizerEmail, &$errorMsg = null)
	{
		// Controlli minimi
		if (!$eventId || !$organizerEmail) {
			$errorMsg = "Missing eventId or organizerEmail";
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
			$errorMsg,
			'json'
		);
		
		return $success;
	}

	/** VER 2
	public function deleteEventMeeting($eventId, $organizerEmail, &$errorMsg = null)
	{
		// Controlli minimi
		if (!$eventId || !$organizerEmail) {
			$errorMsg = "Missing eventId or organizerEmail";
			return false;
		}

		// Endpoint Graph corretto per l’annullamento
		$url = $this->scope_url . "v1.0/users/{$organizerEmail}/events/{$eventId}/cancel";

		// Corpo della richiesta: comment è obbligatorio secondo Graph.
		// Puoi lasciarlo vuoto per non inserire testo nella notifica.
		$body = [
			"Comment" => ""  // Oppure "La riunione è stata annullata."
		];

		// Chiamata POST
		$success = $this->callRestApi(
			"POST",
			$url,
			null,                       // query params
			json_encode($body),         // body JSON
			$this->getAccessToken(),    // token
			$response,
			$errorMsg,
			'json'
		);

		return $success;
	}	 
	 
	*/
	
	
	/**
	 * Recupera un meeting Teams (evento Outlook con onlineMeeting).
	 *
	 * @param string      $eventId     ID dell’evento (meeting)
	 * @param string      $organizerEmail   Email dell'organizzatore
	 * @param string|null $errorMsg   Output messaggio errore
	 * @param bool|true	  $withTags	  Indica se l'evento deve includere i tag di integrazione
	 * @return array|string  Risposta JSON Graph o stringa vuota se errore
	 */
	public function getEventMeeting(string $eventId, string $organizerEmail, string &$errorMsg = null, bool $withTags = true)
	{

	   $result = [];

		// Controlli minimi
		if (!$eventId || !$organizerEmail) {
			$errorMsg = "Missing eventId or organizerEmail";
			return $result;
		}

		// Endpoint base
		$url = $this->scope_url . "v1.0/users/{$organizerEmail}/events/" . rawurlencode($eventId);

		// Se voglio prendere anche le proprietà custom
		if ($withTags) {
			// NESSUN filtro → prendo TUTTE le singleValueExtendedProperties
			$url .= '?$expand=singleValueExtendedProperties';
		}

		// Chiamata
		if ($this->callRestApi(
			"GET", $url, null, null,
			$this->getAccessToken(),
			$response, $errorMsg, 'json'
		)) {
			$result = json_decode($response, true);
		}
		
		// Out
		return $result;
	}
	
	
	/**
	 * Per un dato meeting restituisce quanti sessioni ci sono state.
	 * Per ogni sessione c'è un report diverso
	 */
	public function getMeetingSessionReports($userId, $meetingId, &$errorMsg = null)
	{
		$url = $this->scope_url .
			   "v1.0/users/{$userId}/onlineMeetings/{$meetingId}/attendanceReports";

		if (!$this->callRestApi("GET", $url, null, null,
								$this->getAccessToken(), $response, $errorMsg, 'json')) {
			return null;
		}

		$data = json_decode($response, true);
		return $data['value'] ?? [];
	}
	
	
	/**
	 * Restituisce i partecipanti (attendanceRecords) di un evento Outlook con meeting Teams associato.
	 */
	public function getAttendanceReports($joinUrl, $organizerEmail, &$errorMsg = null)
	{
		if (!$joinUrl || !$organizerEmail) {
			$errorMsg = "Missing joinUrl or organizerEmail";
			return null;
		}

		// Ricavo lo userId (GUID) dell’organizzatore
		$userId = $this->getAccountUserIdByEmail($organizerEmail, $errorMsg);
		if (!$userId) {
			return null;
		}

		// Risolvo l’onlineMeeting usando userId + joinUrl
		$meeting = $this->getMeetingByJoinUrl($userId, $joinUrl, $errorMsg);
		if (!$meeting || empty($meeting['id'])) {
			return [];
		}
		

		$meetingId = $meeting['id'];

		// Recupero i meetingAttendanceReport
		$reports = $this->getMeetingSessionReports($userId, $meetingId, $errorMsg);

		if ($reports === null) {
			return null;    // errore HTTP
		}
		if (empty($reports)) {
			// Riunione non ancora terminata → nessun attendance report
			return [];
		}

		// Per ogni report recupero attendanceRecords
		$result = [];

		foreach ($reports as $rep) {

			$reportId = $rep['id'];
			
			// Recupero records
			$records = $this->getAttendanceRecords($userId, $meetingId, $reportId, $errorMsg);
			if ($records === null) {
				return null;
			}

			// Mappo i record
			$mapped = $this->mapAttendance($records);

			// Struttura come Graph
			$result[] = [
				"reportId"              => $reportId,
				"totalParticipantCount" => $rep['totalParticipantCount'] ?? null,
				"meetingStartDateTime"  => $rep['meetingStartDateTime'] ?? null,
				"meetingEndDateTime"    => $rep['meetingEndDateTime'] ?? null,
				"attendanceRecords"     => $mapped
			];
		}

		return $result;
	}
	
	/*
	public function getEventParticipants($meetingId, $userId, &$errorMsg = null)
	{
		if (!$meetingId || !$userId) {
			$errorMsg = "Missing meetingId or userId";
			return null;
		}

		// Endpoint per meeting-evento (Teams meeting legato a un evento Outlook)
		$url = $this->scope_url . "v1.0/users/{$userId}/onlineMeetings/{$meetingId}/attendanceReports/cd8e616e-7155-4ce0-acba-588a21049381?".'$expand=attendanceRecords';
		
		//$url = $this->scope_url . "v1.0/users/{$userId}/onlineMeetings/{$meetingId}/attendanceReports";
		
		// https://graph.microsoft.com/v1.0/me/onlineMeetings/MSpkYzE3Njc0Yy04MWQ5LTRhZGItYmZ/attendanceReports/c9b6db1c-d5eb-427d-a5c0-20088d9b22d7?$expand=attendanceRecords

		// Chiamata Graph, se fallisce null
		if (!$this->callRestApi("GET", $url, null, null, $this->getAccessToken(), $response, $errorMsg, 'json')) {
			return null;
		}

		$data = json_decode($response, true);
		
		return $data;

		// Nessun report, evento senza partecipanti
		if (!isset($data['value']) || empty($data['value'])) {
			return [];
		}

		$attendees = [];

		foreach ($data['value'] as $report) {

			$reportId = $report['id'] ?? null;

			if (!isset($report['attendanceRecords'])) {
				continue;
			}

			foreach ($report['attendanceRecords'] as $rec) {

				$attendees[] = [
					// ID del report (una sessione: utile se la riunione viene chiusa/riaperta)
					'reportId'  => $reportId,

					// Campi reali dal JSON ufficiale Microsoft Graph
					'displayName'   => $rec['identity']['displayName'] ?? null,
					'email'     	=> $rec['identity']['user']['email'] ?? null,
					'role'      	=> $rec['role'] ?? null,

					'joinDateTime'  => $rec['joinDateTime'] ?? null,
					'leaveDateTime' => $rec['leaveDateTime'] ?? null,
					
					'totalAttendanceInSeconds'  => $rec['totalAttendanceInSeconds'] ?? 0,
				];
			}
		}

		return $attendees;
	}
	* 
	* */
	
	
	/**
	 * Restituisce i partecipanti di un meeting associato a evento dopo il suo termine
	 * @param string      $eventId     ID dell’evento (meeting)
	 * @param string      $organizerEmail   Email dell'organizzatore
	 * @return array|null   event meeting oppure null se non trovato

	public function getEventParticipants($eventId, $userId, &$errorMsg = null)
	{
		if (!$eventId || !$userId) {
			$errorMsg = "Missing eventId or userId";
			return null;
		}

		// Endpoint per meeting-evento (Teams meeting legato a un evento Outlook)
		$url = $this->scope_url . "v1.0/users/{$userId}/events/{$eventId}/onlineMeeting/attendanceReports";
		
		

		// Chiamata Graph, se fallisce null
		if (!$this->callRestApi("GET", $url, null, null, $this->getAccessToken(), $response, $errorMsg, 'json')) {
			return null;
		}

		$data = json_decode($response, true);

		// Nessun report, evento senza partecipanti
		if (!isset($data['value']) || empty($data['value'])) {
			return [];
		}

		$attendees = [];

		foreach ($data['value'] as $report) {

			$reportId = $report['id'] ?? null;

			if (!isset($report['attendanceRecords'])) {
				continue;
			}

			foreach ($report['attendanceRecords'] as $rec) {

				$attendees[] = [
					// ID del report (una sessione: utile se la riunione viene chiusa/riaperta)
					'reportId'  => $reportId,

					// Campi reali dal JSON ufficiale Microsoft Graph
					'displayName'   => $rec['identity']['displayName'] ?? null,
					'email'     	=> $rec['identity']['user']['email'] ?? null,
					'role'      	=> $rec['role'] ?? null,

					'joinDateTime'  => $rec['joinDateTime'] ?? null,
					'leaveDateTime' => $rec['leaveDateTime'] ?? null,
					
					'totalAttendanceInSeconds'  => $rec['totalAttendanceInSeconds'] ?? 0,
				];
			}
		}

		return $attendees;
	}
	*/
	
	public function isMeetingTeamsTerminated($eventId, $organizerEmail, &$errorMsg = null)
	{
		if (!$eventId || !$organizerEmail) {
			$errorMsg = "Missing eventId or organizerEmail";
			return null;
		}

		// 1) Recupero l'evento per ottenere il joinUrl
		$urlEvent = $this->scope_url . 
			"v1.0/users/{$organizerEmail}/events/{$eventId}?$select=onlineMeeting";

		if (!$this->callRestApi("GET", $urlEvent, null, null, $this->getAccessToken(), $respEvent, $errorMsg, 'json')) {
			return null;
		}

		$event = json_decode($respEvent, true);

		if (empty($event['onlineMeeting']['joinUrl'])) {
			return null; // Non è un Teams meeting o manca joinUrl
		}

	  
	}
	
	
	/**
	 * Controlla se un evento Outlook è un vero Teams meeting (onlineMeeting associato)
	 */
	public function isEventTeamsMeeting($eventId, $organizerEmail, &$errorMsg = null)
	{
		if (!$eventId || !$organizerEmail) {
			$errorMsg = "Missing eventId or organizerEmail";
			return null;
		}

		// Recupera solo i campi utili
		$url = $this->scope_url .
			   "v1.0/users/{$organizerEmail}/events/{$eventId}?$select=isOnlineMeeting,onlineMeeting";

		if (!$this->callRestApi("GET", $url, null, null, $this->getAccessToken(), $response, $errorMsg, 'json')) {
			return null;
		}

		$event = json_decode($response, true);

		// Controlla se è un meeting Teams
		if (
			!empty($event['isOnlineMeeting']) &&
			!empty($event['onlineMeeting']) &&
			!empty($event['onlineMeeting']['joinUrl'])
		) {
			return true;
		}

		return false;
	}


	/**
	 * Controlla se un evento esiste in teams
	 */
	public function eventExistsInTeams( $eventId, $organizerEmail)
	{
		$result = ['success' => false, 'response' => null];

		// Codifica dell'eventId (OBBLIGATORIO per Graph)
		$encodedEventId = rawurlencode($eventId);

		// Costruzione URL
		$url = $this->scope_url . "v1.0/users/{$organizerEmail}/events/{$encodedEventId}";

		// Token
		$access_token = $this->getAccessToken();

		// Chiamata GET
		if ($this->callRestApi("GET", $url, null, null, $access_token, $response, $errorMsg, 'json')) {

			$result['success']  = true;
			$result['response'] = json_decode($response, true);

		} else {

			// Se la risorsa non esiste, Graph restituisce 404
			// Lo trasformiamo in success=true ma exists=false
			if (strpos($errorMsg, 'HTTP 404') !== false) {
				$result['success']  = true;
				$result['response'] = null;   // evento non esiste
			} else {
				$result['success']  = false;
				$result['response'] = $errorMsg; // errore reale
			}
		}

		return $result;
	}
	
	
	/**
	 * Recupera gli eventi futuri presenti in teams su una determinato account organizzatore
	 */
	public function getFutureEventMeetingIds($organizerEmail)
	{
		$result = ['success' => false, 'response' => []];

		// Time range
		$start = gmdate("Y-m-d\TH:i:s\Z");
		$end   = gmdate("Y-m-d\TH:i:s\Z", strtotime("+1 year"));

		// Query OData (attenzione: usare apici singoli per evitare che PHP interpreti $filter)
		$query = [
			'startDateTime' => $start,
			'endDateTime'   => $end,
			'$filter'       => 'isOnlineMeeting eq true',
			'$select'       => 'id'
		];

		// Costruzione URL
		// ATTENZIONE: nessun &amp;, nessun $filter interpretato come variabile PHP
		$url = $this->scope_url . "v1.0/users/{$organizerEmail}/calendarView";

		// Recupero token
		$access_token = $this->getAccessToken();

		// Chiamata GET
		if ($this->callRestApi("GET", $url, $query, null, $access_token, $response, $errorMsg, 'json')) {

			$data = json_decode($response, true);

			if (isset($data['value'])) {
				// Estraggo solo gli ID
				$meetingIds = array_column($data['value'], 'id');

				$result['success']  = true;
				$result['response'] = $meetingIds;
			} else {
				// Risposta valida ma senza risultati
				$result['success']  = true;
				$result['response'] = [];
			}

		} else {

			// Errore da Graph
			$result['success']  = false;
			$result['response'] = $errorMsg;
		}

		return $result;
	}
	
	
	/**
	 * Risolve lo userId (objectId GUID) a partire dall'UPN/email.
	 * Richiede: User.Read.All (delegated) oppure User.Read.All (application) con admin consent.
	 */
	public function getAccountUserIdByEmail($email, &$errorMsg = null)
	{
		if (!$email) { $errorMsg = "Missing email"; return null; }

		$url = $this->scope_url . "v1.0/users/". rawurlencode($email) ."?$select=id";

		if (!$this->callRestApi("GET", $url, null, null, $this->getAccessToken(),
								 $response, $errorMsg, 'json')) {
			return null;
		}

		$data = json_decode($response, true);
		return $data['id'] ?? null;
	}
	

	/**
	 * Recupera l'onlineMeeting (Teams) a partire dalla Join URL.
	 *
	 * @param string $userId  		  ID utente dell’organizzatore (lo user nel path /users/{...})
	 * @param string $joinUrl         La JoinWebUrl del meeting (es. https://teams.microsoft.com/l/meetup-join/...)
	 * @param string|null $errorMsg   Restituisce eventuale errore
	 * @return array|null             Restituisce l’oggetto onlineMeeting (array associativo) se trovato,
	 *                                array vuoto [] se non trovato, null in caso di errore di chiamata.
	 */
	public function getMeetingByJoinUrl($userId, $joinUrl, &$errorMsg = null)
	{
		if (!$userId || !$joinUrl) {
			$errorMsg = "Missing userId or joinUrl";
			return null;
		}

		// OData filter: occorre raddoppiare gli apici interni per OData
		$escapedJoinUrl = str_replace("'", "''", $joinUrl);
		$filter = "JoinWebUrl eq '{$escapedJoinUrl}'";

		// Costruzione URL: encodiamo solo il valore del $filter, non l'intero URL
		$url = $this->scope_url
			 . "v1.0/users/{$userId}/onlineMeetings"
			 . '?$filter=' . rawurlencode($filter);

		// Chiamata Graph
		if (!$this->callRestApi("GET", $url, null, null, $this->getAccessToken(), $response, $errorMsg, 'json')) {
			return null; // Errore HTTP/Graph; dettaglio in $errorMsg
		}

		$data = json_decode($response, true);

		// L’endpoint con $filter deve tornare al massimo 1 meeting (identificatore univoco)
		// ma gestiamo comunque l’array per sicurezza
		if (empty($data['value'])) {
			return []; // Nessun meeting trovato per quella joinUrl
		}

		// Restituiamo il primo (e di solito unico) risultato
		return $data['value'][0];
	}
	
	
	/**
	 * Aggiunge uno o più invitati a un meeting Teams tramite aggiornamento evento calendario.
	 *
	 * @param string $eventId
	 * @param string $organizerEmail
	 * @param array  $emailsToAdd
	 * @param string|null $errorMsg
	 *
	 * @return array
	 */
	public function insertInvitees(string $eventId, string $organizerEmail, array $emailsToAdd, ?string &$errorMsg = null) : array
	{
		$result = ['success' => false, 'response' => ''];	
		

		// Validazioni
		if (!$eventId || !$organizerEmail || !$emailsToAdd) {
			$errorMsg = "Missing eventId, organizerEmail or emailsToAdd";
			return $result;
		}

		// Recupero evento
		$event = $this->getEventMeeting($eventId, $organizerEmail, $errorMsg);

		if (!$event) {
			$result['response'] = "Event not found";
			return $result;
		}

		// Invitati già presenti
		$existing = $event['attendees'] ?? [];

		// Lista nuovi invitati
		$invitees = [];

		foreach ($emailsToAdd as $email) {
			$found = false;

			foreach ($existing as $ex) {
				if (($ex['emailAddress']['address'] ?? '') === $email) {
					$found = true;
					break;
				}
			}

			if (!$found) {
				$invitees[] = [
					"emailAddress" => ["address" => $email],
					"type" => "required"
				];
			}
		}

		// Nessun nuovo invitato
		if (empty($invitees)) {
			return [
				'success'  => true,
				'response' => "Everyone already invited"
			];
		}

		// Merge tra vecchi e nuovi
		$payload = [
			"attendees" => array_merge($existing, $invitees)
		];

		// URL PATCH
		$url = $this->scope_url . "v1.0/users/{$organizerEmail}/events/{$eventId}";
		
		// Chiamata API
		if ($this->callRestApi("PATCH", $url, null, $payload, $this->getAccessToken(), $response, $errorMsg, 'json')) {
			return [
				'success'  => true,
				'response' => json_decode($response, true)
			];
		}

		// Errore API
		return [
			'success'  => false,
			'response' => $errorMsg
		];
	}

	
	/**
	 * Rimuove un invitato da un meeting Teams (evento Outlook).
	 *
	 * @param string $eventId
	 * @param string $organizerEmail
	 * @param string $emailToRemove
	 * @param string|null $errorMsg
	 *
	 * @return array
	 */
	public function deleteInvitee($eventId, $organizerEmail, $emailToRemove, &$errorMsg = null)
	{
		return $this->deleteInvitees($eventId, $organizerEmail,[$emailToRemove], $errorMsg);
	}
	
	
	/**
	 * Rimuove un array di invitati da un meeting Teams (evento Outlook).
	 *
	 * @param string      $eventId
	 * @param string      $organizerEmail
	 * @param array       $emailsToRemove    array di email da rimuovere
	 * @param string|null $errorMsg
	 *
	 * @return array ['success'=>bool, 'response'=>mixed]
	 */
	public function deleteInvitees(string $eventId, string $organizerEmail, array $emailsToRemove, &$errorMsg = null)
	{
		$result = ['success' => false, 'response' => ''];

		// Validazioni
		if (!$eventId || !$organizerEmail || empty($emailsToRemove)) {
			$errorMsg = "Missing eventId, organizerEmail or emailsToRemove";
			return $result;
		}

		// Normalizza email per confronti case-insensitive
		$emailsToRemove = array_map('strtolower', $emailsToRemove);

		// Recupero l’evento
		$event = $this->getEventMeeting($eventId, $organizerEmail, $errorMsg);

		if (!$event) {
			$result['response'] = "Event not found";
			return $result;
		}

		// Attendees correnti (fallback [] se non esiste la proprietà)
		$invitees = $event['attendees'] ?? [];

		// Ricostruisco la lista mantenendo SOLO chi NON deve essere rimosso
		$newAttendees = [];
		$removed = false;

		foreach ($invitees as $attendee) {
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

		if ($this->callRestApi("PATCH", $url, null, $payload, $this->getAccessToken(), $response, $errorMsg, 'json')) {
			$result['success']  = true;
			$result['response'] = json_decode($response, true);
		} else {
			$result['response'] = $errorMsg;
		}

		// Out
		return $result;
	} 

	 
	
	/**
	 * Restituisce le informazioni degli utenti invitati inseriti a sistema
	 */
	public function getInfoInvitee(int $id_date, int $key_day, ?int $id_user = null) {
		
		$res = array();
		$whr = ($id_user ? ' AND i.id_user = '.$id_user : '');
		
		$query = "	SELECT * FROM teams_event_invitee i 
						JOIN  teams_event_meeting m ON i.eventId = m.eventId 
					WHERE m.id_date = ".$id_date.' AND m.key_day = '.$key_day. $whr;
					
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
		$query = "DELETE FROM teams_event_invitee WHERE inviteeId = " . $this->esInt($inviteeId);

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
	public function insInfoAttendanceRecord(array $info, string $reportId, string $eventId, $conn = false):bool
	{
		// Recupero l'id dell'invito presente a sistema
		$inviteeId = $this->getInviteeId($info['emailAddress'], $info['displayName'], $eventId);
	
		// Query
		$query = "
			INSERT INTO teams_attendance_record (reportId, inviteeId, emailAddress, displayName, role, joinDateTime, leaveDateTime, durationInSeconds, totalAttendanceInSeconds
			) VALUES (
				" . $this->esStr($reportId) . ",
				" . $this->esInt($inviteeId) . ",
				" . $this->esStr($info['emailAddress']) . ",
				" . $this->esStr($info['displayName']) . ",
				" . $this->esStr($info['role']) . ",
				" . $this->esDateTime($info['joinDateTime']) . ",
				" . $this->esDateTime($info['leaveDateTime']) . ",
				" . $this->esInt($info['durationInSeconds']) . ",
				" . $this->esInt($info['totalAttendanceInSeconds']) . "
			)";	
			

		// Lancio la insert
		$res = sql_query($query, $conn);
		
		// Output
		return $res;
	}

	/**
	 * Inserisce le informazioni sulle sessioni del meeting (attendance report)
	 */
	public function insInfoAttendanceReport($eventId, $info, $conn = false):bool {
	
		// Formo la query
		$query = "	INSERT INTO teams_attendance_report (reportId, eventId, totalParticipantCount, meetingStartDateTime, meetingEndDateTime)
					VALUES (
						".$this->esStr($info['reportId']).", 
						".$this->esStr($eventId).", 
						".$this->esInt($info['totalParticipantCount']).", 
						".$this->esDateTime($info['meetingStartDateTime']).", 
						".$this->esDateTime($info['meetingEndDateTime']).")";					

		// Lancio la insert
		$res = sql_query($query, $conn);
		
				
		// Output
		return $res;	
	}
	
	
	/**
	 * Inserisce le informazioni sull'invitato al meeting
	 */
	public function insInfoInvitee($info, $id_user) {
		
		$res = false;
		
		// Formo la query
		$query = "	INSERT INTO teams_event_invitee (emailAddress, displayName, eventId, type, id_user)
					VALUES (
						".$this->esStr($info['emailAddress']).", 
						".$this->esStr($info['displayName']).", 
						".$this->esStr($info['eventId']).", 
						".$this->esStr($info['type']).", 
						".$this->esInt($id_user).")";

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
		
		$idParam = $this->esStr($eventId);
		
		$query = "DELETE FROM teams_event_meeting WHERE eventId = " . $idParam;

		// Lancio la delete (i record delle tabelle correlate si eliminano a cascata per integrità referenziale)
		$res = sql_query($query);
					
		// Output
		return $res;	
	}
	
	
	/**
	 * Inserisce le informazioni del meeting nella tabella di sistema
	 */
	public function insInfoMeeting($event) {

		$res = false;
		
		// Controllo
		if (!$event ) return $res;
		
		// Recupero chiavi da array di ritorno da Teams
		$id_course = $event['extensions'][0]['id_course'] ?? null;
		$id_date = $event['extensions'][0]['id_date'] ?? null;
		$key_day = $event['extensions'][0]['key_day'] ?? null;

		// Formo la query
		$query = "	INSERT INTO teams_event_meeting (eventId, subject, organizerEmail, organizerTenantId, 
							joinUrl, startDateTime, endDateTime, timezone, id_course, id_date, key_day)
					VALUES (
						" . $this->esStr($event['id']) . ",
						" . $this->esStr($event['subject']) . ",
						" . $this->esStr($event['organizer']['emailAddress']['address']) . ",
						" . $this->esStr($this->tenant_id) . ",
						" . $this->esStr($event['onlineMeeting']['joinUrl']) . ",
						" . $this->esDateTime($event['start']['dateTime']) . ",
						" . $this->esDateTime($event['end']['dateTime']) . ",
						" . $this->esStr($event['start']['timeZone']) . ",
						" . $this->esInt($id_course) . ",
						" . $this->esInt($id_date) . ",
						" . $this->esInt($key_day) . "
					)";
					
				//exit( '{"success":false,"message":"uy'.$errorMsg.'"}' );		

		// Lancio la insert
		$res = sql_query($query);
				
		// Output
		return $res;	
	}
	
	
	/**
	 * Aggiorna le informazioni del meeting nella tabella di sistema
	 */
	public function updInfoMeeting($event) {

		$res = false;
		
		// Controllo
		if (!$event ) return $res;
		
		// Formo la query
		$query = "	UPDATE teams_event_meeting SET 
						subject = " 			. $this->esStr($event['subject']) . ",
						organizerEmail = " 		. $this->esStr($event['organizer']['emailAddress']['address']) . ",
						organizerTenantId = " 	. $this->esStr($this->tenant_id) . ",
						joinUrl = " 		. $this->esStr($event['onlineMeeting']['joinUrl']) . ",
						startDateTime = " 	. $this->esDateTime($event['start']['dateTime']) . ",
						endDateTime = " 	. $this->esDateTime($event['end']['dateTime']) . ",
						timeZone = " 		. $this->esStr($event['start']['timeZone']) . " 
					WHERE eventId = " 		. $this->esStr($event['id']);

		// Lancio la insert
		$res = sql_query($query);
				
		// Output
		return $res;	
	}
	
	
	/**
	 * Restituisce le informazioni del meeting registrate nella tabella di sistema
	 */
	public function getInfoMeeting($id_date, $key_day = null) {

		$res = array();
		$whr = ($id_day ? ' AND key_day = '.(int)$key_day : '');
		
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
	 * Restituisce le informazioni del meeting registrate nella tabella di sistema
	 */
	public function getInfoMeetingById($eventId) {
	
		$res = array();
			
		$query = "SELECT * FROM teams_event_meeting WHERE eventId = " . $this->esStr($eventId);

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
	public function getCourseEdition(int $id_course) {
		
		$res = array();
		
		$query = "SELECT DISTINCT id_date FROM teams_event_meeting WHERE id_course = ".$id_course;
		
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
	 * Restituisce l'id dell'invitato presente nei dati informativi caricati
	 * La ricerca avviene per email ed evento.
	 * return int participantId
	 */
	public function getInviteeId(?string $email, string $displayName, string $eventId):int {
		
		static $invitees = [];
		$inviteeId = 0;
		
		if ( empty($invitees[$eventId]) ) {
			
			$res = [];
			
			// Recupero invitati dell'evento/meeting
			$query = " SELECT i.inviteeId, i.emailAddress, cu.level, u.firstname, u.lastname"
						. "	FROM teams_event_invitee i"
						. " 	JOIN teams_event_meeting m ON i.eventId = m.eventId"
						. " 	JOIN %lms_courseuser cu ON i.id_user = cu.idUser AND m.id_course = cu.idCourse"
						. " 	JOIN %adm_user u ON i.id_user = u.idst"
						. " WHERE i.eventId = " . $this->esStr($eventId);			
			
			// Lancio la query
			$result = sql_query($query);
			
			// Recupero dati	
			while($row = sql_fetch_assoc($result))
			{
				//restituisco la riga alla variabile statica
				$invitees[$eventId][] = $row;
			}
		}
		
		
		// Ciclo di ricerca
		if ( !empty($invitees[$eventId]) ) {
			
			foreach($invitees[$eventId] as $inv) {
				
				if (!empty($email) && $email == $inv['emailAddress']) {
					// Il match deve essere fatto con l'email
					
					$inviteeId = $inv['inviteeId'];
					break;
					
				} elseif ($inv['level'] == 6 && stripos($displayName, $inv['lastname']) !== false && stripos($displayName, $inv['firstname']) !== false) {
					// Se è un docente, provo il match con il nome e cognome
					
					$inviteeId = $inv['inviteeId'];
					break;
				}
				
			}
		}
		
		// Out				
		return $inviteeId;
	}
	
	/**
	 * Conta quanti report del partecipante sono già stati salvati
	 * La ricerca avviene per email ed evento.
	 * Se il meeting viene chiuso e riaperto, Teams crea un nuovo report, quindi un nuovo reportId
	 */
	public function reportCount(string $email, string $eventId):bool {
		
		$res = false;
				
		// Controllo esistenza partecipante
		$query = " SELECT reportId"
					. "	FROM teams_event_participant"
					. " WHERE eventId = " . $this->esStr($eventId) . " AND email = " . $this->esStr($email) 
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
	public function getSubscribedUsers(int $id_date, ?array $in_list = null) {
	
		$res = array();
		
		$whr = is_array($in_list) ? " AND du.id_user IN (" . $this->esIntArr($in_list) . ")" : "";
		
		// Preparo la query
		$query = "	SELECT du.id_user, u.firstname, u.lastname, u.email, cu.level, cu.status 
					FROM %lms_course_date_user du
						JOIN %lms_course_date dt ON du.id_date = dt.id_date
						JOIN %adm_user u ON du.id_user = u.idst
						JOIN %lms_courseuser cu ON (du.id_user = cu.idUser AND dt.id_course = cu.idCourse)
					WHERE du.id_date = ".$id_date. $whr;
	
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
	public function getEditionList(string $date_begin, string $date_end, int $status, $id_org = false) {
	
		//Preparo stringa Where query
		$whrExp  = " AND DATE(dy.date_begin) >= ". $this->esDateTime($date_begin) . " AND DATE(dy.date_end) <= ". $this->esDateTime($date_end);
		
		if($status !== false)
			$whrExp  .= " AND  dt.status = ".$status;
			
		if($id_org){
			$courses = $this->getCourseCatalogOrg($id_org, false, false);
			$whrExp  .=  " AND dt.id_course IN (" . $this->esIntArr(array_keys($courses)) . ") ";
		}
		
		//Recupero la stringa SQL
		$query = "	SELECT dt.name, dt.code, MIN(dy.date_begin) AS date_begin, MAX(dy.date_end) AS date_end, dt.status, dt.id_date, fe.id_fund, COUNT(eventId) AS meeting_count 
					FROM %lms_course_date dt 
						JOIN %lms_course c ON dt.id_course = c.idCourse
						JOIN %lms_course_date_day dy ON dt.id_date = dy.id_date
						LEFT JOIN teams_event_meeting m ON (dy.id_date = m.id_date AND dy.id = m.key_day)
						LEFT JOIN %lms_fund_entry fe ON (fe.id_entry = dt.id_date AND fe.type_entry = 'date')
					WHERE dy.deleted = 0 AND c.course_virtual = 1".$whrExp."
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
	 * Restituisce le informazinoi essenziali del meeting in base a corso e date
	 */
	public function getMeetingsByCourse(int $id_course, ?string $date_begin = null, ?string $date_end = null) {
		
		// Sistemo date
		$date_begin = $date_begin ?? "1900-01-01";
		$date_end = $date_end ?? "2199-12-31";
		
		//Preparo stringa Where query
		$whr  = " AND DATE(startDateTime) >= ". $this->esDate($date_begin) ." AND DATE(endDateTime) <= ". $this->esDate($date_end);
		
		$query 	= "	SELECT * FROM teams_event_meeting"
				. " WHERE id_course = ".$id_course.$whr;
				
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
	 * Restituisce se l'id del report è presente nei dati informativi caricati
	 */
	public function reportExists($reportId) {
		
		$query = "	SELECT COUNT( reportId ) AS Exist "
				."	FROM teams_attendee_report"
				."	WHERE reportId = ".$this->esStr($reportId);
						
		// Lancio la query
		$result = sql_query($query);
		
		// Recupero dati
		while(list($exist) = sql_fetch_row($result))
			$res = (bool)$exist;
					
		// Out
		return $res;
	}
	
	
	/**
	 * Metodo per l'estrazione dei giorni di lezione con link e password dei meeting
	 */
	public function getMeetingList($date_begin, $date_end, $status, $id_org = false) {
				
		//Preparo stringa Where query
		$whrExp  = " AND DATE(dy.date_begin) >= ". $this->esDate($date_begin) ." AND DATE(dy.date_end) <= ". $this->esDate($date_end);
		
		if($status !== false)
			$whrExp  .= " AND  dt.status = ". $this->esInt($status);
			
		if($id_org){
			$courses = $this->getCourseCatalogOrg($id_org, false, false);
			$whrExp  .=  " AND dt.id_course IN (". $this->esIntArr(array_keys($courses)) .") ";
		}
		
		//Recupero la stringa SQL
		$query = "	SELECT dt.name, dt.code, dy.id_day, dy.date_begin, dt.status,
						dy.classroom, m.joinUrl,fe.id_fund, m.eventId, dt.id_date, dy.id
					FROM %lms_course_date dt 
						JOIN %lms_course c ON dt.id_course = c.idCourse
						JOIN %lms_course_date_day dy ON dt.id_date = dy.id_date
						LEFT JOIN teams_event_meeting m ON (dy.id_date = m.id_date AND dy.id = m.key_day)
						LEFT JOIN %lms_fund_entry fe ON (fe.id_entry = dt.id_date AND fe.type_entry = 'date')
					WHERE c.course_virtual = 1 AND dy.deleted = 0 ".$whrExp."
					ORDER BY dy.date_begin, dy.id, dt.code";
					
					
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
	public function getAttendeeList($id_date, $key_day = null, $id_org = null) {
		
		require_once(_adm_.'/lib/lib.field.php');
		$flist = new FieldList();
		
		$res = array();
		$users = array();
		$cf_name = 'USERIDSF';
		
		// Trovo l'id del campo utente custom in base al nome traduzione
		$id_common = $flist->getFieldIdCommonFromTranslation($cf_name); 
		
		// Preparo Where
		$whrExp = "";
		
		if ($key_day !== null)
			$whrExp .= " AND m.key_day = ".(int)$key_day;
		
		if ($id_org) {
			$query_users = $this->acl_man->getSqlUsersByOrg($id_org);
			$whrExp .= " AND (cu.level > 3 OR i.id_user IN (".$query_users."))";
		}
		
		// Preparo Query
		$query = "	SELECT i.id_user, u.email, u.userid, u.firstname, u.lastname, cu.level, rc.joinDateTime, rc.leaveDateTime, rc.durationInSeconds, m.eventId, dy.date_begin AS scheduled_day 
					FROM teams_attendance_record rc
						JOIN teams_attendance_report rp ON rc.reportId = rp.reportId
						JOIN teams_event_meeting m ON rp.eventId = m.eventId
						JOIN teams_event_invitee i ON rc.inviteeId = i.inviteeId
						JOIN %adm_user u ON i.id_user = u.idst
						JOIN %lms_courseuser cu ON m.id_course = cu.idCourse AND i.id_user = cu.idUser
						JOIN %lms_course_date_day dy ON (m.id_date = dy.id_date AND m.key_day = dy.id)
					WHERE m.id_date = " .(int)$id_date. " AND dy.deleted = 0" . $whrExp;
					
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
					$row['minutes'] = round( (int)$row["durationInSeconds"] / 60 );
					
					if ($id_common)
						$row[ $cf_name ] = isset($cf_vals[ $id_user ][ $id_common ]) ? $cf_vals[ $id_user ][ $id_common ] : '';
				}
			}	
		}
		
		// Out
		return $res;
	}
	
	

public function testOnlineMeetingAccess($organizerEmail, &$errorMsg = null)
{


    // --- TEST 1: accesso diretto a /onlineMeetings ---
    $url1 = $this->scope_url . "v1.0/users/{$organizerEmail}/onlineMeetings";

  
    if (!$this->callRestApi("GET", $url1, null, null, $this->getAccessToken(), $resp1, $errorMsg, 'json')) {

    }

 

    return $resp1;
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
		
		$query = "	SELECT joinUrl, organizerEmail, key_day 
					FROM teams_event_meeting 
					WHERE id_date = ".(int)$id_date;
					
		//Lancio la query
		$result = sql_query($query);
		
		// Recupero dati
		while( $row = sql_fetch_assoc($result) ) {	
			$res[ $row['key_day'] ] = array(	'joinUrl' => $row['joinUrl'], 
											'organizerEmail' => $row['organizerEmail'] );
		}
					
		// Out
		return $res;
	}
	
	/**
	 * Restituisce i meeting (eventId) che terminano in un dato periodo
	 */
	public function getEventMeetingEndBetween(string $date_from, string $date_to):array {

		$query = "	SELECT m.eventId, m.organizerEmail, m.joinUrl, dy.id_date, dy.id_day, dy.id AS key_day
					FROM %lms_course_date_day dy
					JOIN teams_event_meeting m ON (dy.id_date = m.id_date AND dy.id = m.key_day)
					WHERE dy.deleted = 0 AND dy.date_end >= ".$this->esDateTime($date_from)." AND dy.date_end <= ".$this->esDateTime($date_to)."
					ORDER BY id_date, id_day";
					
		//Lancio la query
		$result = sql_query($query);
		
		$res = array();

		// Recupero dati
		while($row = sql_fetch_assoc($result))			
			$res[] = $row;
					
		// Out
		return $res;
	}
	
	/**
	 * Restituisce lo userid relativo in base a quello assoluto (rimuove '/')
	 */
	public function getRelativeUserId($userid) {
	
		return $this->acl_man->relativeId($userid);
	}
	
	
	/** INIZIO FUNZIONI ESCAPE **/
	
	function esStr($value)
	{
		if ($value === null) {
			return "NULL";
		}

		return "'" . sql_escape_string($value) . "'";
	}
	
	function esStrNotNull($value)
	{
		return "'" . sql_escape_string((string)$value) . "'";
	}
	
	function esInt($value)
	{
		if ($value === null || $value === '') {
			return "NULL";
		}

		return (string) intval($value);
	}
	
	function esIntArr(array $values)
	{
		if (empty($values)) {
			return "NULL"; // oppure lancia eccezione
		}

		$clean = array_map('intval', $values);

		return implode(',', $clean);
	}
	
	function esStrArr(array $values)
	{
		if (empty($values)) {
			return "NULL";
		}

		$clean = array_map(function($v) {
			return "'" . sql_escape_string($v) . "'";
		}, $values);

		return implode(',', $clean);
	}
	
	function esEmail($email)
	{
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return "NULL"; // oppure throw
		}

		return esStr($email);
	}
	
	function esBool($value)
	{
		return $value ? "1" : "0";
	}
	
	function esLike($value, $before = true, $after = true)
	{
		// Es. WHERE title LIKE " . esLike($search)
		$value = sql_escape_string($value);

		if ($before) $value = '%' . $value;
		if ($after)  $value = $value . '%';

		return "'" . $value . "'";
	}
	
	function esDate($value)
	{
		if (!$value) return "NULL";

		return "'" . date('Y-m-d', strtotime($value)) . "'";
	}
	
	function esDateTime($value)
	{
		if (!$value) return "NULL";

		try {
			$dt = new DateTime($value);
		} catch (Exception $e) {
			return "NULL";
		}

		return "'" . $dt->format('Y-m-d H:i:s') . "'";
	}

	function esOrderBy($value, array $allowed)
	{
		//Es. ORDER BY " . esOrderBy($sort, ['name','date_begin','code'])
		return in_array($value, $allowed) ? $value : $allowed[0];
	}


	/** FINE FUNZIONI ESCAPE **/

	
	
	
	/**
	 * Routine per gestione eccezionale. Inserisce nuovamente gli invitati nella tabella di sistema in base alle iscrizioni
	 * Da utilizzare solo in caso di ricostruzione statistiche dopo eliminazioni accidentali
	 */
	 /** DA VALUTARE
	public function restoreInfoInvitee($arr_edition) {
	
		$organizerEmail = Get::sett('tmsMeeting.organizerEmail', '');
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


/**
 * Crea un Microsoft Teams onlineMeeting (non evento in calendario)
 * usando POST /v1.0/users/{organizerEmail}/onlineMeetings
 *
 * Requisiti:
 *  - Delegated: OnlineMeetings.ReadWrite
 *  - Application: OnlineMeetings.ReadWrite.All + Application Access Policy sullo user nel path
 *
 * @param string $organizerEmail UPN o Id utente nel path /users/{...}
 * @param string $startIso       Data/ora in ISO 8601 (es. "2026-02-25T14:00:00Z" o "2026-02-25T15:00:00+01:00")
 * @param string $endIso         Data/ora fine in ISO 8601
 * @param string $subject        Oggetto del meeting
 * @param string|null $body      (Opzionale) descrizione testuale
 * @param string|null $errorMsg  (out) eventuale messaggio di errore
 * @return array|null            vvvvvvvvvvvvvvvvvvvvOggetto onlineMeeting creato in caso di successo; null in caso di errore
 */
public function createOnlineMeetingAt(
    $organizerEmail,
     &$errorMsg = null,
    $startIso = "2026-02-28T14:00:00Z",
    $endIso = "2026-02-28T15:00:00+01:00",
    $subject = "pippo discute",
    $body = null
   
) {
    if (!$organizerEmail || !$startIso || !$endIso || !$subject) {
        $errorMsg = "Missing organizerEmail, startIso, endIso or subject";
        return null;
    }

    // Payload conforme all’API onlineMeetings
    // Riferimento: POST /users/{userId}/onlineMeetings (doc "Get onlineMeeting" + sezione HTTP requests) [1](https://github.com/Spence10873/GetAttendanceReports)
    $payload = [
        "startDateTime" => "2026-02-28T14:00:00Z",
        "endDateTime"   => "2026-02-28T15:00:00+01:00",
        "subject"       => "pippo discute",
    ];



    $url = $this->scope_url . "v1.0/users/{$organizerEmail}/onlineMeetings";

    $headers = ['Content-Type: application/json'];
    
    if (!$this->callRestApi("POST", $url, null, $payload, $this->getAccessToken(), $response, $errorMsg, 'json')) {

        // Se in app-only non hai Application Access Policy sullo user, questa chiamata può fallire
        // (tipicamente 404/UnknownError o 403). [3](https://heusser.pro/p/get-microsoft-teams-meeting-attendance-report-through-graph-api-lhpctbnzht7z/)
        return null;
    }

    //$meeting = json_decode($response, true);
    return $response;
}

}

?>
