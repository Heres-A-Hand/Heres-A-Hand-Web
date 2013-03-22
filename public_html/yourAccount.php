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
mustBeLoggedIn();

$s = getSmarty();


if (isset($_POST['OldPassword']) && $_POST['OldPassword'] && $_POST['CSFRToken'] == $_SESSION['CSFRToken']) {  
	if ($CURRENT_USER->checkPassword($_POST['OldPassword'])) {
		if (strlen($_POST['NewPassword1']) > 3) {
			if ($_POST['NewPassword1'] == $_POST['NewPassword2']) {
				$CURRENT_USER->setPassword($_POST['NewPassword2']);
				$s->assign('flashOK', 'Your password has been changed!');
			} else {
				$s->assign('flashError', 'New passwords dont match');
			}
		} else {
			$s->assign('flashError', 'New password is to short');
		}
	} else {
		$s->assign('flashError', 'Old password is wrong!');
		logWarning("Wrong Password when trying to change password for User:".$CURRENT_USER->getId());
	}
}

if (isset($_FILES['newAvatar']) && $_FILES['newAvatar']['name'] && is_uploaded_file($_FILES['newAvatar']['tmp_name']) && $_POST['CSFRToken'] == $_SESSION['CSFRToken']) {
	$CURRENT_USER->setAvatar($_FILES['newAvatar']['tmp_name']);
}

$s->assign('PAGETITLE','Your Account');
$s->assign('helpPage','yourAccount');
$s->display('yourAccount.htm');
