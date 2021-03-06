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

/** NOW UNUSED **/
require '../../src/global.php';
mustBeLoggedIn();

$telephone = UserTelephone::findByIDForUserAccount($_POST['id'], $CURRENT_USER);
if (!$telephone) die("No Telephone!");

if ($_POST['CSFRToken'] == $_SESSION['CSFRToken']) {
	$telephone->updateSimpleSchedule($_POST['days'],$_POST['fromHours'],$_POST['fromMins'],$_POST['toHours'],$_POST['toMins']);
}

$_SESSION['flashOK'] = "Setting Changed";
header("Location: /mySimpleSchedule.php");

