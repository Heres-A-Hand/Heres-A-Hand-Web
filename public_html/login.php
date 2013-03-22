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

require '../src/global.php';
checkUserSession();

if ($CURRENT_USER) {
	header("Location: /");
	die();
}


$s = getSmarty();
$s->assign('loginError',false);

if (isset($_POST['userNameThing']) && $_POST['userNameThing'] && isset($_POST['password']) && $_POST['password']) {

	if (filter_var($_POST['userNameThing'], FILTER_VALIDATE_EMAIL)) {


		$user = UserAccount::findByEmail($_POST['userNameThing']);
		if ($user) {
			if ($user->checkPassword($_POST['password'])) {
				logIn($user, (isset($_POST['rememberMe']) && $_POST['rememberMe'] == 'yes'));
				header("Location: ".(isset($_SESSION['afterLoginGoTo'])?$_SESSION['afterLoginGoTo']:'/'));
				unset($_SESSION['afterLoginGoTo']);
				die();
			} else {
				logWarning("Wrong Password when trying to log into User:".$user->getId()." using email ".$_POST['userNameThing']);
				$s->assign('loginError',true);
			}
		} else {
			logWarning("Email not Known when trying to log in. ".$_POST['userNameThing']);
			$s->assign('loginError',true);
		}
	} else {
		$phone = UserTelephone::findByCountryIDandTelphone(1,$_POST['userNameThing']);
		if ($phone) {
			if ($phone->isConfirmed()) {
				$user = $phone->getUserAccount();
				if ($user->checkPassword($_POST['password'])) {
					logIn($user, (isset($_POST['rememberMe']) && $_POST['rememberMe'] == 'yes'));
					header("Location: ".(isset($_SESSION['afterLoginGoTo'])?$_SESSION['afterLoginGoTo']:'/'));
					unset($_SESSION['afterLoginGoTo']);
					die();
				} else {
					logWarning("Wrong Password when trying to log into User:".$user->getId()." using UserTelephone:".$phone->getId(). " (Phone confirmed)");
					$s->assign('loginError',true);
				}
			} else {
				if ($phone->checkConfirmCode($_POST['password'])) {
					$_SESSION['confirmTelephoneID'] = $phone->getId();
					header("Location: /confirmTelephone.php");
					die();
				} else {
					logWarning("Wrong Password when trying to log into UserTelephone:".$phone->getId(). " (Phone unconfirmed)");
					$s->assign('loginError',true);
				}
			}
		} else {
			logWarning("Phone not Known when trying to log in. ".$_POST['userNameThing']);
			$s->assign('loginError',true);
		}
	}
}


$s->assign('PAGETITLE','Login to');
$s->assign('CANONICALURL','/login.php');
$s->assign('userNameThing', isset($_POST['userNameThing']) && $_POST['userNameThing'] ? $_POST['userNameThing'] : (isset($_GET['email']) && $_GET['email'] ? $_GET['email'] : ''));

/**
$db = getDB();
$stat = $db->query("SELECT * FROM country");
$countries = array();
while($d = $stat->fetch()) $countries[] = $d;
$s->assign('countries',$countries);
**/

$s->display('login.htm');
