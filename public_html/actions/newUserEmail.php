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


if (isset($_POST['newEmail']) && trim($_POST['newEmail']) && filter_var($_POST['newEmail'], FILTER_VALIDATE_EMAIL) && $_POST['CSFRToken'] == $_SESSION['CSFRToken']) {
	$email = $_POST['newEmail'];
	try {
		$CURRENT_USER->addEmail($email,$email);
		$_SESSION['flashOK'] = "Email added. Please check your email for instructions.";
	} catch (UserEmailAlreadyExistsException $e) {
		$_SESSION['flashError'] = "Error; email already entered! Please contact support.";
	}
} else  {
	$_SESSION['flashError'] = "Error; email not entered or not recognised as correct";
}

header("Location: /myCommunicationMethods.php");
