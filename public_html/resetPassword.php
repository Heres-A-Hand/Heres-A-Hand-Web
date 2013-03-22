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
	$s = getSmarty();
	$s->assign('PAGETITLE','Problem: ');
	$s->display('resetPassword.alreadyLoggedIn.htm');
	die();
}


$s = getSmarty();
//----------------------------------- Load user, check code
$user = null;
$s->assign('userEmail',false);

if (isset($_GET['emailID'])) {
	$userEmail = UserEmail::findByID($_GET['emailID']);
	if (!$userEmail) die('Not Found!');
	$user = $userEmail->getUserAccount();
	$s->assign('userEmail',$userEmail);
}

// We'll be adding in phone number here later.

if (!$user) die('No User found');

if (!$user->checkForgottenPasswordCode($_GET['code'])) {
	logWarning("Code wrong when trying to reset password for User:".$user->getId());
	die("Code wrong");
}

//--------------------------------------- Reset password?
$s->assign('passwordError', false);


if (isset($_POST['NewPassword1']) && $_POST['NewPassword1']) {
	if (strlen($_POST['NewPassword1']) > 3) {
		if ($_POST['NewPassword1'] == $_POST['NewPassword2']) {
			$user->setPassword($_POST['NewPassword2']);
			logIn($user);
			header("Location: /");
			die();
		} else {
			$s->assign('passwordError', 'New passwords dont match');
		}
	} else {
		$s->assign('passwordError', 'New password is to short');
	}
}

//--------------------------------------- Display Form
$s->assign('PAGETITLE','Reset Password for');
$s->assign('user', $user);
$s->assign('code', $_GET['code']);
$s->display('resetPassword.htm');
