<?php namespace Surikat\Tool\Auth;
/*
	API
	
	Auth::RIGHT_ADMIN
	Auth::lock($right)		COOKIE OR 403
	Auth::lockHTTP($right)	COOKIE OR CHECK-HTTP OR 401
	
	$auth->register($email, $username, $password, $repeatpassword)
	$auth->activate($key)
	$auth->resendActivation($email)
	$auth->login($username, $password)
	$auth->requestReset($email)
	$auth->resetPass($key, $password, $repeatpassword)
	$auth->changePassword($uid, $currpass, $newpass, $repeatnewpass)
	$auth->changeEmail($uid, $email, $password)
	$auth->deleteUser($uid, $password)
	$auth->logout()
*/
use Surikat\Core\Config;
use Surikat\Core\Session;
use Surikat\Core\FS;
use Surikat\Model\R;
use Surikat\I18n\Lang;
use Exception;

if (version_compare(phpversion(), '5.5.0', '<')) {
	require_once SURIKAT_SPATH.'php/Tool/Crypto/password-compat.inc.php';
}
Lang::initialize;
class Auth{
	static function lock(){
		
	}
	static function lockHTTP(){
		
	}
	
	private $db;
	public $config;
	protected $tableRequests = 'requests';
	protected $tableUsers = 'users';
	protected $messages;
	public function __construct(){
		$this->db = R::getDatabase();
		$this->config = (object)Config::auth();
		if($this->config->tableRequests)
			$this->tableRequests = $this->config->tableRequests;
		if($this->config->tableUsers)
			$this->tableUsers = $this->config->tableUsers;
		$this->messages = (object)[
			'user_blocked' => __("You are currently locked out of the system.",null,'auth'),

			'username_short' => __("Username is too short.",null,'auth'),
			'username_long' => __("Username is too long.",null,'auth'),
			'username_incorrect' => __("Username is incorrect.",null,'auth'),
			'username_invalid' => __("Username is invalid.",null,'auth'),
			'username_banned' => __("This username is not allowed.",null,'auth'),

			'password_short' => __("Password is too short.",null,'auth'),
			'password_long' => __("Password is too long.",null,'auth'),
			'password_invalid' => __("Password must contain at least one uppercase and lowercase character, and at least one digit.",null,'auth'),
			'password_nomatch' => __("Passwords do not match.",null,'auth'),
			'password_changed' => __("Password changed successfully.",null,'auth'),
			'password_incorrect' => __("Current password is incorrect.",null,'auth'),
			'password_notvalid' => __("Password is invalid.",null,'auth'),

			'newpassword_short' => __("New password is too short.",null,'auth'),
			'newpassword_long' => __("New password is too long.",null,'auth'),
			'newpassword_invalid' => __("New password must contain at least one uppercase and lowercase character, and at least one digit.",null,'auth'),
			'newpassword_nomatch' => __("New passwords do not match.",null,'auth'),
			'newpassword_match' => __("New password matches previous password.",null,'auth'),

			'username_password_invalid' => __("Username / Password are invalid.",null,'auth'),
			'username_password_incorrect' => __("Username / Password are incorrect.",null,'auth'),
			'remember_me_invalid' => __("The remember me field is invalid.",null,'auth'),

			'email_short' => __("Email address is too short.",null,'auth'),
			'email_long' => __("Email address is too long.",null,'auth'),
			'email_invalid' => __("Email address is invalid.",null,'auth'),
			'email_incorrect' => __("Email address is incorrect.",null,'auth'),
			'email_banned' => __("This email address is not allowed.",null,'auth'),
			'email_changed' => __("Email address changed successfully.",null,'auth'),

			'newemail_match' => __("New email matches previous email.",null,'auth'),

			'account_inactive' => __("Account has not yet been activated.",null,'auth'),
			'account_activated' => __("Account activated.",null,'auth'),

			'logged_in' => __("You are now logged in.",null,'auth'),
			'logged_out' => __("You are now logged out.",null,'auth'),

			'system_error' => __("A system error has been encountered. Please try again.",null,'auth'),

			'register_success' => __("Account created. Activation email sent to email.",null,'auth'),
			'username_taken' => __("The username is already taken.",null,'auth'),
			'email_taken' => __("The email address is already in use.",null,'auth'),

			'authentication_required' => __("Authentication required.",null,'auth'),
			'already_authenticated' => __("You are already authenticated.",null,'auth'),

			'resetkey_invalid' => __("Reset key is invalid.",null,'auth'),
			'resetkey_incorrect' => __("Reset key is incorrect.",null,'auth'),
			'resetkey_expired' => __("Reset key has expired.",null,'auth'),
			'password_reset' => __("Password reset successfully.",null,'auth'),

			'activekey_invalid' => __("Activation key is invalid.",null,'auth'),
			'activekey_incorrect' => __("Activation key is incorrect.",null,'auth'),
			'activekey_expired' => __("Activation key has expired.",null,'auth'),
			'account_activated' => __("Account has been activated. You can now log in.",null,'auth'),

			'reset_requested' => __("Password reset request sent to email address.",null,'auth'),
			'reset_exists' => __("A reset request already exists.",null,'auth'),

			'already_activated' => __("Account is already activated.",null,'auth'),
			'activation_sent' => __("Activation email has been sent.",null,'auth'),
			'activation_exists' => __("An activation email has already been sent.",null,'auth'),'user_blocked' => __("You are currently locked out of the system.",null,'auth'),

			'username_short' => __("Username is too short.",null,'auth'),
			'username_long' => __("Username is too long.",null,'auth'),
			'username_incorrect' => __("Username is incorrect.",null,'auth'),
			'username_invalid' => __("Username is invalid.",null,'auth'),
			'username_banned' => __("This username is not allowed.",null,'auth'),

			'password_short' => __("Password is too short.",null,'auth'),
			'password_long' => __("Password is too long.",null,'auth'),
			'password_invalid' => __("Password must contain at least one uppercase and lowercase character, and at least one digit.",null,'auth'),
			'password_nomatch' => __("Passwords do not match.",null,'auth'),
			'password_changed' => __("Password changed successfully.",null,'auth'),
			'password_incorrect' => __("Current password is incorrect.",null,'auth'),
			'password_notvalid' => __("Password is invalid.",null,'auth'),

			'newpassword_short' => __("New password is too short.",null,'auth'),
			'newpassword_long' => __("New password is too long.",null,'auth'),
			'newpassword_invalid' => __("New password must contain at least one uppercase and lowercase character, and at least one digit.",null,'auth'),
			'newpassword_nomatch' => __("New passwords do not match.",null,'auth'),
			'newpassword_match' => __("New password matches previous password.",null,'auth'),

			'username_password_invalid' => __("Username / Password are invalid.",null,'auth'),
			'username_password_incorrect' => __("Username / Password are incorrect.",null,'auth'),
			'remember_me_invalid' => __("The remember me field is invalid.",null,'auth'),

			'email_short' => __("Email address is too short.",null,'auth'),
			'email_long' => __("Email address is too long.",null,'auth'),
			'email_invalid' => __("Email address is invalid.",null,'auth'),
			'email_incorrect' => __("Email address is incorrect.",null,'auth'),
			'email_banned' => __("This email address is not allowed.",null,'auth'),
			'email_changed' => __("Email address changed successfully.",null,'auth'),

			'newemail_match' => __("New email matches previous email.",null,'auth'),

			'account_inactive' => __("Account has not yet been activated.",null,'auth'),
			'account_activated' => __("Account activated.",null,'auth'),

			'logged_in' => __("You are now logged in.",null,'auth'),
			'logged_out' => __("You are now logged out.",null,'auth'),

			'system_error' => __("A system error has been encountered. Please try again.",null,'auth'),

			'register_success' => __("Account created. Activation email sent to email.",null,'auth'),
			'username_taken' => __("The username is already taken.",null,'auth'),
			'email_taken' => __("The email address is already in use.",null,'auth'),

			'authentication_required' => __("Authentication required.",null,'auth'),
			'already_authenticated' => __("You are already authenticated.",null,'auth'),

			'resetkey_invalid' => __("Reset key is invalid.",null,'auth'),
			'resetkey_incorrect' => __("Reset key is incorrect.",null,'auth'),
			'resetkey_expired' => __("Reset key has expired.",null,'auth'),
			'password_reset' => __("Password reset successfully.",null,'auth'),

			'activekey_invalid' => __("Activation key is invalid.",null,'auth'),
			'activekey_incorrect' => __("Activation key is incorrect.",null,'auth'),
			'activekey_expired' => __("Activation key has expired.",null,'auth'),
			'account_activated' => __("Account has been activated. You can now log in.",null,'auth'),

			'reset_requested' => __("Password reset request sent to email address.",null,'auth'),
			'reset_exists' => __("A reset request already exists.",null,'auth'),

			'already_activated' => __("Account is already activated.",null,'auth'),
			'activation_sent' => __("Activation email has been sent.",null,'auth'),
			'activation_exists' => __("An activation email has already been sent.",null,'auth'),
		];
	}
	
	public function login($name, $password){
		$return['error'] = 1;
		if ($this->isBlocked()) {
			$return['message'] = $this->messages->user_blocked;
			return $return;
		}
		$validateUsername = $this->validateUsername($name);
		$validatePassword = $this->validatePassword($password);
		if ($validateUsername['error'] == 1) {
			$this->addAttempt();
			$return['message'] = $this->messages->name_password_invalid;
			return $return;
		} elseif($validatePassword['error'] == 1) {
			$this->addAttempt();
			$return['message'] = $this->messages->name_password_invalid;
			return $return;
		}
		$uid = $this->getUID(strtolower($name));
		if(!$uid) {
			$this->addAttempt();
			$return['message'] = $this->messages->name_password_incorrect;
			return $return;
		}
		$user = $this->getUser($uid);
		if (!password_verify($password, $user['password'])) {
			$this->addAttempt();
			$return['message'] = $this->messages->name_password_incorrect;
			return $return;
		}
		else{
			$options = ['salt' => $user['salt'], 'cost' => $this->config->bcrypt_cost];
			if (password_needs_rehash($user['password'], PASSWORD_BCRYPT, $options)){
				$password = password_hash($password, PASSWORD_BCRYPT, $options);
				$row = $this->db->load($this->tableUsers,(int)$user['id']);
				$row->password = $password;
				if(!$row->store()){
					$return['message'] = $this->messages->system_error;
					return $return;
				}
			}
		}
		if (!isset($user['isactive'])||$user['isactive'] != 1) {
			$this->addAttempt();
			$return['message'] = $this->messages->account_inactive;
			return $return;
		}
		$sessiondata = $this->addSession($user['id']);
		if($sessiondata == false) {
			$return['message'] = $this->messages->system_error;
			return $return;
		}
		$return['error'] = 0;
		$return['message'] = $this->messages->logged_in;
		Session::setKey($user['id']);
		return $return;
	}

	public function register($email, $name, $password, $repeatpassword){
		$return['error'] = 1;
		if ($this->isBlocked()) {
			$return['message'] = $this->messages->user_blocked;
			return $return;
		}
		$validateEmail = $this->validateEmail($email);
		$validateUsername = $this->validateUsername($name);
		$validatePassword = $this->validatePassword($password);
		if ($validateEmail['error'] == 1) {
			$return['message'] = $validateEmail['message'];
			return $return;
		} elseif ($validateUsername['error'] == 1) {
			$return['message'] = $validateUsername['message'];
			return $return;
		} elseif ($validatePassword['error'] == 1) {
			$return['message'] = $validatePassword['message'];
			return $return;
		} elseif($password !== $repeatpassword) {
			$return['message'] = $this->messages->password_nomatch;
			return $return;
		}
		if ($this->isEmailTaken($email)) {
			$this->addAttempt();
			$return['message'] = $this->messages->email_taken;
			return $return;
		}
		if ($this->isUsernameTaken($name)) {
			$this->addAttempt();
			$return['message'] = $this->messages->name_taken;
			return $return;
		}
		$addUser = $this->addUser($email, $name, $password);
		if($addUser['error'] != 0) {
			$return['message'] = $addUser['message'];
			return $return;
		}
		$return['error'] = 0;
		$return['message'] = $this->messages->register_success;
		return $return;
	}
	public function activate($key){
		$return['error'] = 1;
		if($this->isBlocked()) {
			$return['message'] = $this->messages->user_blocked;
			return $return;
		}
		if(strlen($key) !== 20) {
			$this->addAttempt();
			$return['message'] = $this->messages->key_invalid;
			return $return;
		}
		$getRequest = $this->getRequest($key, "activation");
		if($getRequest['error'] == 1) {
			$return['message'] = $getRequest['message'];
			return $return;
		}
		$user = $this->getUser($getRequest[$this->tableUsers.'_id']);
		if(isset($user['isactive'])&&$user['isactive']==1) {
			$this->addAttempt();
			$this->deleteRequest($getRequest['id']);
			$return['message'] = $this->messages->system_error;
			return $return;
		}
		$row = $this->db->load($this->tableUsers,(int)$getRequest[$this->tableUsers.'_id']);
		$row->isactive = 1;
		$row->store();
		$this->deleteRequest($getRequest['id']);
		$return['error'] = 0;
		$return['message'] = $this->messages->account_activated;
		return $return;
	}
	public function requestReset($email){
		$return['error'] = 1;
		if ($this->isBlocked()) {
			$return['message'] = $this->messages->user_blocked;
			return $return;
		}
		$validateEmail = $this->validateEmail($email);
		if ($validateEmail['error'] == 1) {
			$return['message'] = $this->messages->email_invalid;
			return $return;
		}
		$id = $this->db->getCell("SELECT id FROM {$this->tableUsers} WHERE email = ?",[$email]);
		if(!$id){
			$this->addAttempt();
			$return['message'] = $this->messages->email_incorrect;
			return $return;
		}
		if($this->addRequest($id, $email, "reset")['error'] == 1){
			$this->addAttempt();
			$return['message'] = $addRequest['message'];
			return $return;
		}
		$return['error'] = 0;
		$return['message'] = $this->messages->reset_requested;
		return $return;
	}
	public function logout(){
		return Session::destroy();
	}
	public function getHash($string, $salt){
		return password_hash($string, PASSWORD_BCRYPT, ['salt' => $salt, 'cost' => $this->config->bcrypt_cost]);
	}
	public function getUID($name){
		return $this->db->getCell("SELECT id FROM {$this->tableUsers} WHERE name = ?",[$name]);
	}
	private function addSession($uid){
		$ip = $this->getIp();
		$user = $this->getUser($uid);
		if(!$user) {
			return false;
		}
		Session::destroyKey($uid);
		if(!Session::start()){
			return false;
		}
		$data = [
			'id'=>$uid,
			'email'=>$user['email'],
			'name'=>$user['name'],
		];
		Session::set('_AUTH_',$data);
		return $data;
	}
	private function isEmailTaken($email){
		return !!$this->db->getCell("SELECT id FROM {$this->tableUsers} WHERE email = ?",[$email]);
	}
	private function isUsernameTaken($name){
		return !!$this->getUID($name);
	}
	private function addUser($email, $name, $password){
		$return['error'] = 1;
		$row = $this->db->create($this->tableUsers);
		if(!$row->store()){
			$return['message'] = $this->messages->system_error;
			return $return;
		}
		$uid = $row->id;
		$email = htmlentities($email);
		$addRequest = $this->addRequest($uid, $email, "activation");
		if($addRequest['error'] == 1) {
			$row->trash();
			$return['message'] = $addRequest['message'];
			return $return;
		}
		$salt = substr(strtr(base64_encode(\mcrypt_create_iv(22, MCRYPT_DEV_URANDOM)), '+', '.'), 0, 22);
		$name = htmlentities(strtolower($name));
		$password = $this->getHash($password, $salt);
		$row->name = $name;
		$row->password = $password;
		$row->email = $email;
		$row->salt = $salt;
		if(!$row->store()){
			$row->trash();
			$return['message'] = $this->messages->system_error;
			return $return;
		}

		$return['error'] = 0;
		return $return;
	}

	public function getUser($uid){
		$row = $this->db->load($this->tableUsers,(int)$uid);
		if(!$row)
			return false;
		return $row->getProperties();
	}

	public function deleteUser($uid, $password) {
		$return['error'] = 1;
		if ($this->isBlocked()) {
			$return['message'] = $this->messages->user_blocked;
			return $return;
		}
		$validatePassword = $this->validatePassword($password);
		if($validatePassword['error'] == 1) {
			$this->addAttempt();
			$return['message'] = $validatePassword['message'];
			return $return;
		}
		$getUser = $this->getUser($uid);
		if(!password_verify($password, $getUser['password'])) {
			$this->addAttempt();
			$return['message'] = $this->messages->password_incorrect;
			return $return;
		}
		$row = $this->db->load($this->tableUsers,(int)$uid);
		if(!$row->trash()){
			$return['message'] = $this->messages->system_error;
			return $return;
		}
		Session::destroyKey($uid);
		foreach($row->own($this->tableRequests) as $request){
			if(!$request->trash()) {
				$return['message'] = $this->messages->system_error;
				return $return;
			}
		}		
		$return['error'] = 0;
		$return['message'] = $this->messages->account_deleted;
		return $return;
	}
	private function addRequest($uid, $email, $type){
		$return['error'] = 1;
		if($type != "activation" && $type != "reset") {
			$return['message'] = $this->messages->system_error;
			return $return;
		}
		$row = $this->db->findOne($this->tableRequests," WHERE {$this->tableUsers}_id = ? AND type = ?",[$uid, $type]);
		if(!$row){
			$expiredate = strtotime($row['expire']);
			$currentdate = strtotime(date("Y-m-d H:i:s"));
			if ($currentdate < $expiredate) {
				$return['message'] = $this->messages->request_exists;
				return $return;
			}
			$this->deleteRequest($row['id']);
		}
		$user = $this->getUser($uid);
		if($type == "activation" && isset($user['isactive']) && $user['isactive'] == 1) {
			$return['message'] = $this->messages->already_activated;
			return $return;
		}
		$key = $this->getRandomKey(20);
		$expire = date("Y-m-d H:i:s", strtotime("+1 day"));
		$request = $this->db->create($this->tableRequests,[$this->tableUsers.'_id'=>$uid, 'rkey'=>$key, 'expire'=>$expire, 'type'=>$type]);
		if(!$request->store()) {
			$return['message'] = $this->messages->system_error;
			return $return;
		}
		if($type == "activation") {
			$message = "Account activation required : <strong><a href=\"{$this->config->site_url}/activate/{$key}\">Activate my account</a></strong>";
			$subject = "{$this->config->site_name} - Account Activation";
		} else {
			$message = "Password reset request : <strong><a href=\"{$this->config->site_url}/reset/{$key}\">Reset my password</a></strong>";		
			$subject = "{$this->config->site_name} - Password reset request";
		}
		//$headers  = 'MIME-Version: 1.0' . "\r\n";
		//$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		//$headers .= "From: {$this->config->site_email}" . "\r\n";
		//if(!mail($email, $subject, $message, $headers)) {
			//$return['message'] = $this->messages->system_error;
			//throw new Exception();
			//return $return;
		//}
		$return['error'] = 0;
		return $return;
	}
	private function getRequest($key, $type){
		$return['error'] = 1;
		$row = $this->db->findOne($this->tableRequests,' WHERE rkey = ? AND type = ?',[$key, $type]);
		if(!$row) {
			$this->addAttempt();
			$return['message'] = $this->messages->key_incorrect;
			return $return;
		}
		$expiredate = strtotime($row['expire']);
		$currentdate = strtotime(date("Y-m-d H:i:s"));
		if ($currentdate > $expiredate) {
			$this->addAttempt();
			$this->deleteRequest($row['id']);
			$return['message'] = $this->messages->key_expired;
			return $return;
		}
		$return['error'] = 0;
		$return['id'] = $row['id'];
		$return[$this->tableUsers.'_id'] = $row[$this->tableUsers]['id'];
		return $return;
	}
	private function deleteRequest($id){
		return $this->db->exec("DELETE FROM {$this->tableRequests} WHERE id = ?",[$id]);
	}
	public function validateUsername($name) {
		$return['error'] = 1;
		if (strlen($name) < 3) {
			$return['message'] = $this->messages->name_short;
			return $return;
		} elseif (strlen($name) > 30) {
			$return['message'] = $this->messages->name_long;
			return $return;
		} elseif (!ctype_alnum($name)) {
			$return['message'] = $this->messages->name_invalid;
			return $return;
		}
		$return['error'] = 0;
		return $return;
	}
	private function validatePassword($password) {
		$return['error'] = 1;
		if (strlen($password) < 6) {
			$return['message'] = $this->messages->password_short;
			return $return;
		} elseif (strlen($password) > 72) {
			$return['message'] = $this->messages->password_long;
			return $return;
		} elseif ((!preg_match('@[A-Z]@', $password) && !preg_match('@[a-z]@', $password)) || !preg_match('@[0-9]@', $password)) {
			$return['message'] = $this->messages->password_invalid;
			return $return;
		}
		$return['error'] = 0;
		return $return;
	}
	private function validateEmail($email) {
		$return['error'] = 1;
		if (strlen($email) < 5) {
			$return['message'] = $this->messages->email_short;
			return $return;
		} elseif (strlen($email) > 100) {
			$return['message'] = $this->messages->email_long;
			return $return;
		} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$return['message'] = $this->messages->email_invalid;
			return $return;
		}
		if($this->isBannedEmail($email)){
			$return['message'] = $this->messages->email_banned;
			return $return;
		}
		$return['error'] = 0;
		return $return;
	}
	function isBannedEmail($email){
		return false;
	}
	public function resetPass($key, $password, $repeatpassword){
		$return['error'] = 1;
		if ($this->isBlocked()) {
			$return['message'] = $this->messages->user_blocked;
			return $return;
		}
		if(strlen($key) != 20) {
			$return['message'] = $this->messages->key_invalid;
			return $return;
		}
		$validatePassword = $this->validatePassword($password);
		if($validatePassword['error'] == 1) {
			$return['message'] = $validatePassword['message'];
			return $return;
		}
		if($password !== $repeatpassword) { // Passwords don't match
			$return['message'] = $this->messages->newpassword_nomatch;
			return $return;
		}
		$data = $this->getRequest($key, "reset");
		if($data['error'] == 1) {
			$return['message'] = $data['message'];
			return $return;
		}
		$user = $this->getUser($data[$this->tableUsers.'_id']);
		if(!$user) {
			$this->addAttempt();
			$this->deleteRequest($data['id']);
			$return['message'] = $this->messages->system_error;
			return $return;
		}
		if(password_verify($password, $user['password'])) {
			$this->addAttempt();
			$this->deleteRequest($data['id']);
			$return['message'] = $this->messages->newpassword_match;
			return $return;
		}
		$password = $this->getHash($password, $user['salt']);
		$row = $this->db->load($this->tableUsers,$data[$this->tableUsers.'_id']);
		$row->password = $password;
		if (!$row->store()) {
			$return['message'] = $this->messages->system_error;
			return $return;
		}
		$this->deleteRequest($data['id']);
		$return['error'] = 0;
		$return['message'] = $this->messages->password_reset;
		return $return;
	}
	public function resendActivation($email){
		$return['error'] = 1;
		if ($this->isBlocked()) {
			$return['message'] = $this->messages->user_blocked;
			return $return;
		}
		$validateEmail = $this->validateEmail($email);
		if($validateEmail['error'] == 1) {
			$return['message'] = $validateEmail['message'];
			return $return;
		}
		$row = $this->db->findOne($this->tableUsers,' WHERE email = ?',[$email]);
		if(!$row){
			$this->addAttempt();
			$return['message'] = $this->messages->email_incorrect;
			return $return;
		}
		if(isset($row['isactive'])&&$row['isactive'] == 1){
			$this->addAttempt();
			$return['message'] = $this->messages->already_activated;
			return $return;
		}
		$addRequest = $this->addRequest($row['id'], $email, "activation");
		if ($addRequest['error'] == 1) {
			$this->addAttempt();
			$return['message'] = $addRequest['message'];
			return $return;
		}
		$return['error'] = 0;
		$return['message'] = $this->messages->activation_sent;
		return $return;
	}
	public function changePassword($uid, $currpass, $newpass, $repeatnewpass){
		$return['error'] = 1;
		if ($this->isBlocked()) {
			$return['message'] = $this->messages->user_blocked;
			return $return;
		}
		$validatePassword = $this->validatePassword($currpass);
		if($validatePassword['error'] == 1) {
			$this->addAttempt();
			$return['message'] = $validatePassword['message'];
			return $return;
		}
		$validatePassword = $this->validatePassword($newpass);
		if($validatePassword['error'] == 1) {
			$return['message'] = $validatePassword['message'];
			return $return;
		} elseif($newpass !== $repeatnewpass) {
			$return['message'] = $this->messages->newpassword_nomatch;
			return $return;
		}
		$user = $this->getUser($uid);
		if(!$user) {
			$this->addAttempt();
			$return['message'] = $this->messages->system_error;
			return $return;
		}
		$newpass = $this->getHash($newpass, $user['salt']);
		if($currpass == $newpass) {
			$return['message'] = $this->messages->newpassword_match;
			return $return;
		}
		if(!password_verify($currpass, $user['password'])) {
			$this->addAttempt();
			$return['message'] = $this->messages->password_incorrect;
			return $return;
		}
		$row = $this->db->load($this->tableUsers,(int)$uid);
		$row->password = $newpass;
		$row->store();
		$return['error'] = 0;
		$return['message'] = $this->messages->password_changed;
		return $return;
	}
	public function getEmail($uid){
		$row = $this->db->load($this->tableUsers,(int)$uid);
		if (!$row->id){
			return false;
		}
		return $row['email'];
	}
	public function changeEmail($uid, $email, $password){
		$return['error'] = 1;
		if ($this->isBlocked()) {
			$return['message'] = $this->messages->user_blocked;
			return $return;
		}
		$validateEmail = $this->validateEmail($email);
		if($validateEmail['error'] == 1){
			$return['message'] = $validateEmail['message'];
			return $return;
		}
		$validatePassword = $this->validatePassword($password);
		if ($validatePassword['error'] == 1) {
			$return['message'] = $this->messages->password_notvalid;
			return $return;
		}
		$user = $this->getUser($uid);
		if(!$user) {
			$this->addAttempt();
			$return['message'] = $this->messages->system_error;
			return $return;
		}
		if(!password_verify($password, $user['password'])) {
			$this->addAttempt();
			$return['message'] = $this->messages->password_incorrect;
			return $return;
		}
		if ($email == $user['email']) {
			$this->addAttempt();
			$return['message'] = $this->messages->newemail_match;
			return $return;
		}
		$row = $this->db->load($this->tableUsers,(int)$uid);
		$row->email = $email;
		if(!$row->store()){
			$return['message'] = $this->messages->system_error;
			return $return;
		}
		$return['error'] = 0;
		$return['message'] = $this->messages->email_changed;
		return $return;
	}
	private function isBlocked(){
		$ip = $this->getIp();
		if(is_file(self::$attemptsPath.$ip))
			$count = (int)file_get_contents(self::$attemptsPath.$ip);
		else
			return false;
		$expiredate = filemtime(self::$attemptsPath.$ip)+1800;
		$currentdate = time();
		if($count==5){
			if($currentdate<$expiredate)
				return true;
			$this->deleteAttempts();
			return false;
		}
		if($currentdate>$expiredate)
			$this->deleteAttempts();
		return false;
	}
	private function addAttempt(){
		$ip = $this->getIp();
		FS::mkdir(self::$attemptsPath);
		if(is_file(self::$attemptsPath.$ip))
			$attempt_count = ((int)file_get_contents(self::$attemptsPath.$ip))+1;
		else
			$attempt_count = 1;
		return file_put_contents(self::$attemptsPath.$ip,$attempt_count,LOCK_EX);
	}
	private function deleteAttempts(){
		$ip = $this->getIp();
		return is_file(self::$attemptsPath.$ip)&&unlink(self::$attemptsPath.$ip);
	}
	public function getRandomKey($length = 20){
		$chars = "A1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6Q7R8S9T0U1V2W3X4Y5Z6a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6";
		$key = "";
		for ($i = 0; $i < $length; $i++)
			$key .= $chars{mt_rand(0, strlen($chars) - 1)};
		return $key;
	}
	private function getIp(){
		return $_SERVER['REMOTE_ADDR'];
	}
	static $attemptsPath;
	static function initialize(){
		self::$attemptsPath = SURIKAT_PATH.'.tmp/attempts/';	
	}
}
Auth::initialize();