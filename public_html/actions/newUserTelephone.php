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


if ($CURRENT_USER->hasAnyPremiumGroups()) {
	if (isset($_POST['newTelephone']) && trim($_POST['newTelephone']) && $_POST['CSFRToken'] == $_SESSION['CSFRToken']) {
		$countryID =  $_POST['newTelephoneCountry'];
		$number = trim($_POST['newTelephone']);
		try {
			$CURRENT_USER->addTelephone($number,$countryID,$number);
			$_SESSION['flashOK'] = "Number added!";
		} catch (UserTelephoneAlreadyExistsException $e){
			$_SESSION['flashError'] = "Error; telephone already entered! Please contact support.";
		}
	} else  {
		$_SESSION['flashError'] = "Error; telephone not entered";
	}	
} else {
	$_SESSION['flashError'] = "Error; you are not in any premium groups";
}



header("Location: /myCommunicationMethods.php");
