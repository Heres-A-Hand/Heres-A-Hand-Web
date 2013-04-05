<?php
/**
 *  Copyright 2011-2013 Here's A Hand Limited
 *
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 * 
 * @copyright 2011-2013 Here's A Hand Limited
 * @license Apache License, Version 2.0
**/


function my_autoload($class_name){
	$inc = dirname(__FILE__);	
	if ($class_name == 'TwitterOAuth') {
		require $inc.'/../libs/twitteroauth/twitteroauth.php';
	} else if ($class_name == 'Twilio' || $class_name == 'Services_Twilio') {
		require $inc.'/../libs/twilio/Twilio.php';	
	} else if(file_exists($inc."/".$class_name.'.class.php')){
		require_once($inc."/".$class_name.'.class.php');
	}
}
spl_autoload_register("my_autoload");

date_default_timezone_set('UTC');


$DB_CONNECTION = null;
/** @return PDO **/
function getDB() {
	global $DB_CONNECTION;
	if (!$DB_CONNECTION) {
		$DB_CONNECTION = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
		$DB_CONNECTION->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
		$DB_CONNECTION->exec("SET NAMES 'utf8'");
	}
	return $DB_CONNECTION;
}


/** @return Smarty **/
function getSmarty() {
	global $CURRENT_USER;
	require_once dirname(__FILE__).'/../libs/smarty/Smarty.class.php';
	$s = new Smarty();
	$s->template_dir = dirname(__FILE__) . '/../templates/';
	$s->compile_dir = dirname(__FILE__) . '/../smarty_c/';
	$s->assign('currentUser',$CURRENT_USER);
	$s->assign('supportGroups',getCurrentUserSupportGroups());
	$s->assign('supportGroup',getCurrentUserSupportGroup());
	$s->assign('httpHost',HTTP_HOST);
	$s->assign('httpsHost',HTTPS_HOST);
	$s->assign('theme',THEME);
	$s->assign('CSFRToken',isset($_SESSION['CSFRToken']) ? $_SESSION['CSFRToken'] : '');
	if (isset($_SESSION['flashError'])) {
		$s->assign('flashError',$_SESSION['flashError']);
		unset($_SESSION['flashError']);
	} else {
		$s->assign('flashError',null);
	}
	if (isset($_SESSION['flashOK'])) {
		$s->assign('flashOK',$_SESSION['flashOK']);
		unset($_SESSION['flashOK']);
	} else {
		$s->assign('flashOK',null);
	}	
	return $s;
}

/** @return Smarty **/
function getEmailSmarty() {
	require_once dirname(__FILE__).'/../libs/smarty/Smarty.class.php';
	$s = new Smarty();
	$s->template_dir = dirname(__FILE__) . '/../templates/';
	$s->compile_dir = dirname(__FILE__) . '/../smarty_c/';
	$s->assign('httpHost',HTTP_HOST);
	$s->assign('httpsHost',HTTPS_HOST);
	return $s;
}

function getSwiftMailer() {
	require_once dirname(__FILE__).'/../libs/swiftmailer/swift_required.php';
	$transport = Swift_MailTransport::newInstance();
	$mailer = Swift_Mailer::newInstance($transport);
	return array($mailer,  getSwiftMessage());
}

function getSwiftMessage() {
	return Swift_Message::newInstance()
			->setFrom(array(EMAILS_FROM_EMAIL => EMAILS_FROM_NAME));
}

/** @return Pheanstalk **/
function getPheanstalk() {
	if (defined('BEANSTALKD_HOST')) {
		$pheanstalkClassRoot = dirname(__FILE__) . '/../libs/pheanstalk/classes/';
		require_once($pheanstalkClassRoot . '/Pheanstalk/ClassLoader.php');
		Pheanstalk_ClassLoader::register($pheanstalkClassRoot);
		return new Pheanstalk(BEANSTALKD_HOST,BEANSTALKD_PORT);
	}
}

/** @var UserAccount **/
$CURRENT_USER = null;

function mustBeLoggedIn() {
	global $CURRENT_USER;
	checkUserSession();
	if (!$CURRENT_USER) {
		$_SESSION['afterLoginGoTo'] = $_SERVER['REQUEST_URI'];
		header('Location: /login.php');
		die();
	}
}

/**
 *
 * @global null $CURRENT_USER
 * @param boolean $checkPassword  On some pages (the password prompt) you want to not check password.
 */
function sysAdminMustBeLoggedIn($checkPassword = true) {
	global $CURRENT_USER;
	checkUserSession();
	if (!$CURRENT_USER) {
		$_SESSION['afterLoginGoTo'] = $_SERVER['REQUEST_URI'];
		header('Location: /login.php');
		die();
	}
	if (!$CURRENT_USER->getIsSystemAdmin()) {
		die('NO');
	}
	if ($checkPassword) {
		if (!isset($_SESSION['sysAdminPassword']) || $_SESSION['sysAdminPassword'] != SYS_ADMIN_PASSWORD) {
			header('Location: /sysadmin/login.php');
			die();
		}
	}
}

/**
 *
 * @global null $CURRENT_USER
 * @param boolean $checkPassword  On some pages (the password prompt) you want to not check password.
 */
function whiteLabelAdminMustBeLoggedIn($checkPassword = true) {
	global $CURRENT_USER;
	checkUserSession();
	if (!$CURRENT_USER) {
		$_SESSION['afterLoginGoTo'] = $_SERVER['REQUEST_URI'];
		header('Location: /login.php');
		die();
	}
	if ($checkPassword) {
		if (!isset($_SESSION['whiteLabelAdminPassword']) || $_SESSION['whiteLabelAdminPassword'] != 'DONE') {
			header('Location: /whitelabeladmin/login.php');
			die();
		}
	}
}


function checkUserSession() {
	global $CURRENT_USER;
	session_start();
	if (isset($_SESSION['userID']) && intval($_SESSION['userID']) > 0) {
		$CURRENT_USER = UserAccount::findByID($_SESSION['userID']);
		return;
	}
	if (isset($_COOKIE['HaHUserID']) && isset($_COOKIE['HaHSessionID']) && $_COOKIE['HaHUserID'] && $_COOKIE['HaHSessionID']) {
		$user = UserAccount::findByIDwithSessionID($_COOKIE['HaHUserID'], $_COOKIE['HaHSessionID']);
		if ($user) {
			logInfo("Logging in User:".$user->getId()." using UserSession:".$_COOKIE['HaHSessionID']);
			logIn ($user, false);
			return;
		} else {
			logWarning("Session not known when trying to log in User:".$_COOKIE['HaHUserID']." with key ".$_COOKIE['HaHSessionID']);
		}
	}
}


function getCurrentUserSupportGroups() {
	global $CURRENT_USER, $CURRENT_USER_GROUPS;
	if ($CURRENT_USER) {
		if (!isset($CURRENT_USER_GROUPS) || is_null($CURRENT_USER_GROUPS)) $CURRENT_USER_GROUPS = SupportGroup::findForUser($CURRENT_USER);
		return $CURRENT_USER_GROUPS;
	} else {
		return array();
	}
}


/** @return SupportGroup will always return a group. If it can't (user not in any?) will redirect user and die. **/
function getCurrentUserSupportGroupOrDie($id = null) {
	$group = getCurrentUserSupportGroup($id);
	if ($group) {
		return $group;
	}
	header("Location: /yourGroups.php");
	die();
}

/** @return SupportGroup or null if not possible. **/
function getCurrentUserSupportGroup($id = null) {
	global $CURRENT_USER, $CURRENT_USER_GROUPS;
	if ($CURRENT_USER) {
		$groups = getCurrentUserSupportGroups();
		// if id passed use that, and set in session so we stay there
		if ($id) {
			foreach($groups as $g) {
				if ($g->getId() == $id) {
					$_SESSION['currentSupportGroup'] = $id;
					return $g;
				}
			}
		}
		// if get variable passed use that. We need to always check this ...
		// otherwise all controllers would have to pass the GET var as an $id when calling getCurrentUserSupportGroup()
		// any controller that didn't would risk getting different returns from this function at different times 
		//    in it's execution which could introduce subtle bugs
		$id = isset($_GET['supportGroup']) ? $_GET['supportGroup'] : null;
		if ($id) {
			foreach($groups as $g) {
				if ($g->getId() == $id) {
					$_SESSION['currentSupportGroup'] = $id;
					return $g;
				}
			}
		}		
		// use session
		if (isset($_SESSION['currentSupportGroup'])) {
			foreach($groups as $g) {
				if ($g->getId() == $_SESSION['currentSupportGroup']) {
					return $g;
				}
			}
		}
		// use first one on the list
		if (count($groups) > 0) {
			$_SESSION['currentSupportGroup'] = $groups[0]->getId();
			return $groups[0];			
		}
	} else {
		return null;
	}
}


function getCurrentUserAdminWhiteLabels() {
	global $CURRENT_USER, $CURRENT_USER_ADMIN_WHITE_LABELS;
	if ($CURRENT_USER) {
		if (!isset($CURRENT_USER_ADMIN_WHITE_LABELS) || is_null($CURRENT_USER_ADMIN_WHITE_LABELS)) {
			$CURRENT_USER_ADMIN_WHITE_LABELS = array();
			$whiteLabelSearch = new WhiteLabelSearch();
			$whiteLabelSearch->userCanAdmin($CURRENT_USER);
			while($wl = $whiteLabelSearch->nextResult()) $CURRENT_USER_ADMIN_WHITE_LABELS[] = $wl;
		}
		return $CURRENT_USER_ADMIN_WHITE_LABELS;
	} else {
		return array();
	}
}


/** @return WhiteLabel will always return a group. If it can't (user not in any?) will redirect user and die. **/
function getCurrentUserAdminWhiteLabelOrDie($id = null) {
	$whiteLabel = getCurrentUserAdminWhiteLabel($id);
	if ($whiteLabel) {
		return $whiteLabel;
	}
	header("Location: /index.php");
	die();
}

/** @return WhiteLabel or null if not possible. **/
function getCurrentUserAdminWhiteLabel($id = null) {
	global $CURRENT_USER, $CURRENT_USER_WHITE_LABEL_ADMIN_GROUPS;
	if ($CURRENT_USER) {
		$whiteLabels = getCurrentUserAdminWhiteLabels();
		// if id passed use that, and set in session so we stay there
		if ($id) {
			foreach($whiteLabels as $g) {
				if ($g->getId() == $id) {
					$_SESSION['currentWhiteLabel'] = $id;
					return $g;
				}
			}
		}
		// if get variable passed use that. We need to always check this ...
		// otherwise all controllers would have to pass the GET var as an $id when calling getCurrentUserSupportGroup()
		// any controller that didn't would risk getting different returns from this function at different times 
		//    in it's execution which could introduce subtle bugs
		$id = isset($_GET['whiteLabel']) ? $_GET['whiteLabel'] : null;
		if ($id) {
			foreach($whiteLabels as $g) {
				if ($g->getId() == $id) {
					$_SESSION['currentWhiteLabel'] = $id;
					return $g;
				}
			}
		}		
		// use session
		if (isset($_SESSION['currentWhiteLabel'])) {
			foreach($whiteLabels as $g) {
				if ($g->getId() == $_SESSION['currentWhiteLabel']) {
					return $g;
				}
			}
		}
		// use first one on the list
		if (count($whiteLabels) > 0) {
			$_SESSION['currentWhiteLabel'] = $whiteLabels[0]->getId();
			return $whiteLabels[0];
		}
	} else {
		return null;
	}
}

function getRandomString($length=40) {
    $characters = '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';
    $string = '';
    for ($p = 0; $p < $length; $p++) $string .= $characters[mt_rand(0, strlen($characters)-1)];
    return $string;
}

function getRandomStringVarLength($minlength=10, $maxlength=50) {
    $characters = '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';
    $string = '';
	$length = mt_rand($minlength, $maxlength);
    for ($p = 0; $p < $length; $p++) $string .= $characters[mt_rand(0, strlen($characters)-1)];
    return $string;
}

function logIn(UserAccount $user, $remmemberMe = false) {
	global $CURRENT_USER;
	$_SESSION['userID'] = $user->getId();
	$_SESSION['CSFRToken'] = getRandomString(100);
	$CURRENT_USER = $user;
	if ($remmemberMe) {
		$id = $user->getNewSessionID();
		setcookie('HaHUserID', $user->getId(),time()+60*60*24*360);
		setcookie('HaHSessionID', $id,time()+60*60*24*360);
		logInfo("Logging in User:".$user->getId()." with UserSession:".$id);	
	} else {
		logInfo("Logging in User:".$user->getId());
	}
}

if (defined('LOG_TO_FACILITY') && LOG_TO_FACILITY) openlog(LOG_TO_IDENT,LOG_PID,LOG_TO_FACILITY);

/** warning conditions **/
function logWarning($msg) {
	global $CURRENT_USER;
	if (defined('LOG_TO_FACILITY') && LOG_TO_FACILITY) {
		if ($CURRENT_USER) $msg = "CurrentUser:".$CURRENT_USER->getId()." ".$msg;
		if (isset($_SERVER) && isset($_SERVER['REMOTE_ADDR'])) $msg = $_SERVER['REMOTE_ADDR']." ".$msg;
		syslog(LOG_WARNING, $msg);
	}
}
/** normal, but significant, condition **/
function logNotice($msg) {
	global $CURRENT_USER;
	if (defined('LOG_TO_FACILITY') && LOG_TO_FACILITY) {
		if ($CURRENT_USER) $msg = "CurrentUser:".$CURRENT_USER->getId()." ".$msg;
		if (isset($_SERVER) && isset($_SERVER['REMOTE_ADDR'])) $msg = $_SERVER['REMOTE_ADDR']." ".$msg;
		syslog(LOG_NOTICE, $msg);
	}
}
/** informational message **/
function logInfo($msg) {
	global $CURRENT_USER;
	if (defined('LOG_TO_FACILITY') && LOG_TO_FACILITY) {
		if ($CURRENT_USER) $msg = "CurrentUser:".$CURRENT_USER->getId()." ".$msg;
		if (isset($_SERVER) && isset($_SERVER['REMOTE_ADDR'])) $msg = $_SERVER['REMOTE_ADDR']." ".$msg;
		syslog(LOG_INFO, $msg);
	}
}
/** debug-level message **/
function logDebug($msg) {
	global $CURRENT_USER;
	if (defined('LOG_TO_FACILITY') && LOG_TO_FACILITY) {
		if ($CURRENT_USER) $msg = "CurrentUser:".$CURRENT_USER->getId()." ".$msg;
		if (isset($_SERVER) && isset($_SERVER['REMOTE_ADDR'])) $msg = $_SERVER['REMOTE_ADDR']." ".$msg;
		syslog(LOG_DEBUG, $msg);
	}
}

function setCalendarVariablesOnSmarty($smarty) {
	$from = new DateTime("now"); // new DateTimeZone($_SESSION['timeZone'])
	$to = new DateTime("now"); // ,new DateTimeZone($_SESSION['timeZone'])
	$to->add(new DateInterval('PT1H'));
	
	$smarty->assign('startDate',$from->format('j M Y'));
	$smarty->assign('startHour',$from->format('G'));
	$smarty->assign('startMin',$from->format('i'));

	$smarty->assign('endDate',$to->format('j M Y'));
	$smarty->assign('endYear',$to->format('Y'));
	$smarty->assign('endHour',$to->format('G'));
	$smarty->assign('endMin',$to->format('i'));
}

function parseCalendarFormInputs($data) {
	try {
		$from = new DateTime($_POST['from_date']); // new DateTimeZone($_SESSION['timeZone'])
	} catch (exception $e) {
		// parsing failed
		$from = null;
	}
	try {
		$to = new DateTime($_POST['to_date']); // ,new DateTimeZone($_SESSION['timeZone'])
	} catch (exception $e) {
		// parsing failed
		$from = null;
	}
	
	$error = null;
	if (!$from) {
		$error = 'Could not parse from date';
	} else if (!$from->setTime($_POST['from_hour'],$_POST['from_mins'])) {
		$error = 'Could not parse from time';
	} else if (!$to) {
		$error = 'Could not parse to date';
	} else if (!$to->setTime($_POST['to_hour'],$_POST['to_mins'])) {
		$error = 'Could not parse to time';
	} else if ($from->getTimestamp() < 1) {
		$error = 'Could not parse from date';
	} else if ($to->getTimestamp() < 1) {
		$error = 'Could not parse to date';
	} else if ($from->getTimestamp() > $to->getTimestamp()) {
		$error = 'From is after to!';
	} else if ($from->getTimestamp() == $to->getTimestamp()) {
		$error = 'From is the same as to!';
	} else if ($from->getTimestamp() < getCurrentTime()) {
		$error = 'From is in the past!';
	} else if ($to->getTimestamp() < getCurrentTime()) {
		$error = 'To is in the past!';
	}

	return array($from, $to, $error);
		
}

/** All our code should use this and not time()! So we can set a time for testing **/
function getCurrentTime() {
	global $MOCK_CURRENT_TIME;
	return (isset($MOCK_CURRENT_TIME) && $MOCK_CURRENT_TIME) ? $MOCK_CURRENT_TIME : time();
}

/** Does a users schedule rule apply to a request with these types at this time?
  * Split for easy testing
  * @var $rule ScheduleRule the rule in question
  * @var $requestTypes Array of RequestType objects that he schedule is in
  * @var $time  Timestamp  seperate for testing 
  * @returns Boolean
  **/
function doesScheduleRuleApply(ScheduleRule $rule, $requestTypes) {

	// time of day
	if ($rule->getFromHour() != $rule->getToHour()) {
		$hour = date("G",  getCurrentTime());
		if ($rule->getFromHour() < $rule->getToHour()) {
			// normal time range, eg 2pm => 4pm
			if ( $hour < $rule->getFromHour() || $rule->getToHour() < $hour) return false;
		} else {
			// time range loops around midnight, eg 10pm (from) => Midnight(Loop) => 7am (to)
			if ($rule->getToHour() < $hour && $hour <  $rule->getFromHour()) return false;
		}
	}

	// day of week
	if (!$rule->hasDay(strtolower(date("D",getCurrentTime())))) return false;

	// Request Type
	foreach($requestTypes as $type) {
		if (!$rule->hasRequestType($type))  return false;
	}

	return true;
}

/**
  * Takes a user and request types of a request and builds array of data indicating what, if any, methods should be used to contact them.
  * @var $member UserAccount the user in question
  * @var $types Array of RequestType objects that he schedule is in
  * @var $time  Timestamp  seperate for testing 
  **/
function buildNotifyData(UserAccount $member, $types) {
	
	$areTheyOnHoliday = UserOnHoliday::findByUserForDate($member, getCurrentTime());
	if (!$areTheyOnHoliday) {
		$data = array(
				'member'=>$member,
				'emails'=>array(),
				'sendToEmails'=>array(),
				'telephones'=>array(),
				'sendToTelephones'=>array(),
				'twitters'=>array(),
				'sendToTwitters'=>array(),
			);

		if (!$member->getUseAdvancedSchedule()) {
			if (count($types) > 0) {
				// Simple Schedule! Check request types. If no types match, then we don't send anything.
				$flag = false;
				foreach($types as $type) {
					if (!$flag && $type->getSimpleScheduleRuleForUser($member)) {
						$flag = true;
					}
				}
				if (!$flag) {
					// We don't send anything, so return now with no communications methods.
					return $data;
				}
			} else {
				// No types! What to do? Don't know! For now send anyway.
			}
		}		
		
		foreach($member->getEmails() as $email) {
			if ($email->isConfirmed() || (!$email->isConfirmed() && $email->getSendBeforeConfirmation())) {
				$data['emails'][$email->getId()] = $email;
				if ($member->getUseAdvancedSchedule()) {
					// we will check the times below, just set true for now.
					$data['sendToEmails'][$email->getId()] = true;
				} else {
					// simple schedule; just check if we are in right time and set true and false
					if ($email->doesSimpleScheduleRuleMatch(getCurrentTime())) {
						$data['sendToEmails'][$email->getId()] = true;
					} else {
						$data['sendToEmails'][$email->getId()] = false;
					}
				}
			}
		}
		foreach($member->getTelephones() as $telephone) {
			if ($telephone->isConfirmed()) {
				$data['telephones'][$telephone->getId()] = $telephone;
				if ($member->getUseAdvancedSchedule()) {
					// we will check the times below, just set true for now.
					$data['sendToTelephones'][$telephone->getId()] = true;
				} else {
					// simple schedule; just check if we are in right time and set true and false
					if ($telephone->doesSimpleScheduleRuleMatch(getCurrentTime())) {
						$data['sendToTelephones'][$telephone->getId()] = true;
					} else {
						$data['sendToTelephones'][$telephone->getId()] = false;
					}
				}
			}
		}
		foreach($member->getTwitters() as $twitter) {
			$data['twitters'][$twitter->getId()] = $twitter;
			if ($member->getUseAdvancedSchedule()) {
				// we will check the times below, just set true for now.
				$data['sendToTwitters'][$twitter->getId()] = true;
			} else {
				// simple schedule; just check if we are in right time and set true and false
				if ($twitter->doesSimpleScheduleRuleMatch(getCurrentTime())) {
					$data['sendToTwitters'][$twitter->getId()] = true;
				} else {
					$data['sendToTwitters'][$twitter->getId()] = false;
				}
			}
		}

		
		if ($member->getUseAdvancedSchedule()) {
			foreach($member->getScheduleRules() as $rule) {
				if (doesScheduleRuleApply($rule,$types)) {
					foreach($rule->getEmails() as $email) {
						$data['sendToEmails'][$email->getId()] = false;
					}
					foreach($rule->getTelephones() as $telephone) {
						$data['sendToTelephones'][$telephone->getId()] = false;
					}
					foreach($rule->getTwitters() as $twitter) {
						$data['sendToTwitters'][$twitter->getId()] = false;
					}
				}
			}
		}
		
		return $data;
	}
}

/**
  * Takes a user and builds array of data indicating what, if any, methods should be used to contact them.
  * @var $member UserAccount the user in question
  * @var $time  Timestamp  seperate for testing 
  **/
function buildNotifyDataForSupportGroupNewsArticle(UserAccount $member) {
	
	$data = array(
			'member'=>$member,
			'emails'=>array(),
			'sendToEmails'=>array(),
			'telephones'=>array(),
			'sendToTelephones'=>array(),
			'twitters'=>array(),
			'sendToTwitters'=>array(),
		);

	foreach($member->getEmails() as $email) {
		if ($email->isConfirmed() || (!$email->isConfirmed() && $email->getSendBeforeConfirmation())) {
			$data['emails'][$email->getId()] = $email;
			$data['sendToEmails'][$email->getId()] = true;
		}
	}
	return $data;
	
}

class UserTwitterAlreadyExistsException extends Exception {}
class UserEmailAlreadyExistsException extends Exception {}
class UserTelephoneAlreadyExistsException extends Exception {}
class UserAccountAlreadyCreatedException extends Exception {}


