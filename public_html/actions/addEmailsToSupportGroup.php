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
require '../../src/global.php';
mustBeLoggedIn();

$group = getCurrentUserSupportGroupOrDie();

if ($group->isAdmin() && isset($_POST['addNewEmails']) && trim($_POST['addNewEmails']) && $_POST['CSFRToken'] == $_SESSION['CSFRToken']) {
	$count = 0;
	foreach(preg_split("/[\s,]+/", $_POST['addNewEmails']) as $email) {
		if (filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
			$userAccount = UserAccount::findByEmailOrCreate(trim($email));
			if ($group->addUser($userAccount, false, true, $CURRENT_USER)) {
				$count++;
			}
		}
	}
	if ($count == 1) {
		$_SESSION['flashOK'] = "Thanks, they have been added.";
	} else if ($count > 1) {
		$_SESSION['flashOK'] = "Thanks, ".$count." emails have been added.";
	}
}

header("Location: /team.php?supportGroup=".$group->getId());
