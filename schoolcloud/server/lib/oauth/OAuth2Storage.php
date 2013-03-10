<?php

/**
 * @file
 * Sample OAuth2 Library PDO DB Implementation.
 * 
 * Simply pass in a configured PDO class, eg:
 * new OAuth2StoragePDO( new PDO('mysql:dbname=mydb;host=localhost', 'user', 'pass') );
 *
 *注意：没有采用PDO
 *语句prepare改用LazyPHP3中db.sae.function.php所实现的
 *
 */

require_once AROOT . 'lib/oauth/lib/OAuth2.php';
require_once AROOT . 'lib/oauth/lib/IOAuth2Storage.php';
require_once AROOT . 'lib/oauth/lib/IOAuth2GrantCode.php';
require_once AROOT . 'lib/oauth/lib/IOAuth2RefreshTokens.php';

//20121230增加
require_once AROOT . 'lib/oauth/lib/IOAuth2GrantClient.php';

// 数据库连接Set these values to your database access info.
/*
define("PDO_DSN", "mysql:dbname=oauth;host=localhost");
define("PDO_USER", "ddonng");
define("PDO_PASS", "12290419");
*/


define( 'LR_OAUTH_ADD_CLIENT_ERROR' , 20001 );
define( 'LR_OAUTH_CHECK_CLIENT_CREDENTIALS_ERROR' , 20002 );
define( 'LR_OAUTH_GET_CLIENT_DETAILS_ERROR' , 20003 );
define( 'LR_OAUTH_UNSET_REFRESH_TOKEN_ERROR' , 20004 );
define( 'LR_OAUTH_GET_AUTH_CODE_ERROR' , 20005 );
define( 'LR_OAUTH_SET_AUTH_CODE_ERROR' , 20006 );
define( 'LR_OAUTH_SET_TOKEN_ERROR' , 20007 );
define( 'LR_OAUTH_GET_TOKEN_ERROR' , 20008 );

/**
 * PDO storage engine for the OAuth2 Library.
 * 
 * IMPORTANT: This is provided as an example only. In production you may implement
 * a client-specific salt in the OAuth2StoragePDO::hash() and possibly other goodies.
 * 
 *** The point is, use this as an EXAMPLE ONLY. ***
 */
class OAuth2Storage implements IOAuth2GrantCode, IOAuth2RefreshTokens, IOAuth2GrantClient {
	
	/**
	 * Change this to something unique for your system
	 * @var string
	 */
	const SALT = 'CHANGE_ME!';
	
	/**@#+
	 * Centralized table names
	 * 
	 * @var string
	 */
	const TABLE_CLIENTS = 'oauth_clients';
	const TABLE_CODES = 'oauth_auth_codes';
	const TABLE_TOKENS = 'oauth_access_tokens';
	const TABLE_REFRESH = 'oauth_refresh_tokens';
	/**@#-*/
	
	/**
	 * @var PDO
	 */
	private $db;

	/**
	 * Implements OAuth2::__construct().
	 */
	public function __construct( $db ) {

		$this->db = $db;
		//$this->db = new PDO(PDO_DSN, PDO_USER, PDO_PASS);

	}

	/**
	 * Release DB connection during destruct.
	 */
	function __destruct() {
		$this->db = NULL; // Release db connection
	}

	/**
	 * Handle exceptional cases.
	 */
	private function handleException( $number, $err ) {
		if(c("display_mysql_error"))
		{
			echo $number . ', Database error: ' . $err;
			exit();
		}else{
			echo $number . ', Database error';
			exit();
		}
	}

	/**
	 * Little helper function to add a new client to the database.
	 *
	 * Do NOT use this in production! This sample code stores the secret
	 * in plaintext!
	 *
	 * @param $client_id
	 * Client identifier to be stored.
	 * @param $client_secret
	 * Client secret to be stored.
	 * @param $redirect_uri
	 * Redirect URI to be stored.
	 */
	public function addClient($client_id, $client_secret, $redirect_uri) {

			$client_secret = $this->dohash($client_secret, $client_id);

			$array = Array( $client_id, $client_secret, $redirect_uri );
			$stmt = 'INSERT INTO ' . self::TABLE_CLIENTS . ' (client_id, client_secret, redirect_uri) VALUES (?s, ?s, ?s)';
			
			//db.sae.function.php中
			$sql=prepare($stmt,$array);
			
			run_sql( $sql );

			if ( mysql_errno() != 0  ) $this->handleException( LR_OAUTH_ADD_CLIENT_ERROR , mysql_error() );
			return true;


	}

	/**
	 * Implements IOAuth2Storage::checkClientCredentials().
	 *
	 */
	public function checkClientCredentials($client_id, $client_secret = NULL) {

			$sql = 'SELECT client_secret FROM ' . self::TABLE_CLIENTS . ' WHERE client_id = ?s';
			$array = Array( $client_id );
			$stmt = prepare($sql ,$array);
			
			$result = get_var($stmt);
			if ( mysql_errno() != 0  ) $this->handleException( LR_OAUTH_CHECK_CLIENT_CREDENTIALS_ERROR , mysql_error() );

			if ($client_secret === NULL) {
				return $result !== FALSE;
			}
			//下面两个变量位置
			return $this->checkPassword( $result,$client_secret, $client_id);

	}

	/**
	 * Implements IOAuth2Storage::getRedirectUri().
	 */
	public function getClientDetails($client_id) {

			$sql = 'SELECT redirect_uri FROM ' . self::TABLE_CLIENTS . ' WHERE client_id = ?s';
			$array = Array( $client_id );
			$stmt = prepare($sql,$array);
			
			$result = get_var($stmt);
			if ( mysql_errno() != 0  ) $this->handleException( LR_OAUTH_GET_CLIENT_DETAILS_ERROR , mysql_error() );

			if ($result === FALSE) {
				return FALSE;
			}

			return isset($result) && $result ? Array('redirect_uri'=>$result) : NULL;

	}

	/**
	 * Implements IOAuth2Storage::getAccessToken().
	 */
	public function getAccessToken($oauth_token) {
		return $this->getToken($oauth_token, FALSE);
	}

	/**
	 * Implements IOAuth2Storage::setAccessToken().
	 */
	public function setAccessToken($oauth_token, $client_id, $user_id, $expires, $scope = NULL) {
		$this->setToken($oauth_token, $client_id, $user_id, $expires, $scope, FALSE);
	}

	/**
	 * @see IOAuth2Storage::getRefreshToken()
	 */
	public function getRefreshToken($refresh_token) {
		return $this->getToken($refresh_token, TRUE);
	}

	/**
	 * @see IOAuth2Storage::setRefreshToken()
	 */
	public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = NULL) {
		return $this->setToken($refresh_token, $client_id, $user_id, $expires, $scope, TRUE);
	}

	/**
	 * @see IOAuth2Storage::unsetRefreshToken()
	 */
	public function unsetRefreshToken($refresh_token) {

			$sql = 'DELETE FROM ' . self::TABLE_REFRESH . ' WHERE refresh_token = ?s';
			$array = Array($refresh_token);
			$stmt = prepare( $sql ,$array );
			run_sql($stmt);
			if ( mysql_errno() != 0  ) $this->handleException( LR_OAUTH_UNSET_REFRESH_TOKEN_ERROR , mysql_error() );
	}

	/**
	 * Implements IOAuth2Storage::getAuthCode().
	 */
	public function getAuthCode($code) {

			$sql = 'SELECT code, client_id, user_id, redirect_uri, expires, scope FROM ' . self::TABLE_CODES . ' auth_codes WHERE code = ?s';
			$array = Array($code);
			$stmt = prepare($sql,$array);
			$result = get_var($stmt);
			if ( mysql_errno() != 0  ) $this->handleException( LR_OAUTH_GET_AUTH_CODE_ERROR , mysql_error() );

			return $result !== FALSE ? $result : NULL;

	}

	/**
	 * Implements IOAuth2Storage::setAuthCode().
	 */
	public function setAuthCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = NULL) {

			$sql = 'INSERT INTO ' . self::TABLE_CODES . ' (code, client_id, user_id, redirect_uri, expires, scope) VALUES (?s, ?s, ?i, ?s, ?i, ?s)';
			$array = Array($code, $client_id, $user_id, $redirect_uri, $expires, $scope);
			$stmt = prepare($sql,$array);
			
			run_sql($stmt);
			if ( mysql_errno() != 0  ) $this->handleException( LR_OAUTH_SET_AUTH_CODE_ERROR , mysql_error() );
	}

	/**
	 * @see IOAuth2Storage::checkRestrictedGrantType()
	 */
	public function checkRestrictedGrantType($client_id, $grant_type) {
		return TRUE; // Not implemented
	}

	/**
	 * Creates a refresh or access token
	 * 
	 * @param string $token - Access or refresh token id
	 * @param string $client_id
	 * @param mixed $user_id
	 * @param int $expires
	 * @param string $scope
	 * @param bool $isRefresh
	 */
	protected function setToken($token, $client_id, $user_id, $expires, $scope, $isRefresh = TRUE) {

			$tableName = $isRefresh ? self::TABLE_REFRESH : self::TABLE_TOKENS;
			//表中为auth_token字段，这里写成了token,注意这里refresh表无法更新了，因为它的字段是refresh_token。增加一行
			$tokenName = $isRefresh ? 'refresh_token' : 'oauth_token';
			$sql = "INSERT INTO `". s($tableName)."`(`$tokenName`, `client_id`, `user_id`, `expires`, `scope`) VALUES(?s, ?s, ?i, ?i, ?s)";
			$array = Array($token, $client_id, $user_id, $expires, $scope, $isRefresh);
			$stmt = prepare($sql, $array);

			run_sql($stmt);
			if ( mysql_errno() != 0  ) $this->handleException( LR_OAUTH_SET_TOKEN_ERROR , mysql_error() );
	}

	/**
	 * Retrieves an access or refresh token.
	 * 
	 * @param string $token
	 * @param bool $refresh
	 */
	protected function getToken($token, $isRefresh = true) {

			$tableName = $isRefresh ? self::TABLE_REFRESH : self::TABLE_TOKENS;
			$tokenName = $isRefresh ? 'refresh_token' : 'oauth_token';
			//下面WHERE条件后面需要修改字段token为$tokenName
			$sql = "SELECT `$tokenName`, `client_id`, `expires`, `scope`, `user_id` FROM `$tableName` WHERE `$tokenName` = ?s";

			$array = Array($token);
			$stmt = prepare($sql,$array);

			$result = get_line($stmt);
			if ( mysql_errno() != 0  ) $this->handleException( LR_OAUTH_GET_TOKEN_ERROR , mysql_error() );
			return $result !== FALSE ? $result : NULL;
	}

	/**
	 * Change/override this to whatever your own password hashing method is.
	 * 
	 * In production you might want to a client-specific salt to this function. 
	 * 
	 * @param string $secret
	 * @return string
	 */
	protected function dohash($client_secret, $client_id) {
		return hash('sha256', $client_id . $client_secret);
	}

	/**
	 * Checks the password.
	 * Override this if you need to
	 * 
	 * @param string $client_id
	 * @param string $client_secret
	 * @param string $actualPassword
	 */
	protected function checkPassword($try, $client_secret, $client_id) {
		return $try == $this->dohash($client_secret, $client_id);
	}

	/**
	*hxd
	*实现接口
	*这里只是测试，直接return true
	*/
	public function checkClientCredentialsGrant($client_id, $client_secret){
		$sql = 'SELECT * FROM `' . self::TABLE_CLIENTS . '` WHERE `client_id` = ?s AND `client_secret`=?s';
		$array = Array( $client_id,$client_secret);
		$stmt = prepare($sql ,$array);
		
		$result = get_var($stmt);
		if ( mysql_errno() != 0  ) $this->handleException( LR_OAUTH_CHECK_CLIENT_CREDENTIALS_ERROR , mysql_error() );

		return $result!==NULL?$result:FALSE;
	}
}
