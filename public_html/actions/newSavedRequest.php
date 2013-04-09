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

if ($group->canMakeRequests() && isset($_POST['newMessageSummary']) && trim($_POST['newMessageSummary']) && $_POST['CSFRToken'] == $_SESSION['CSFRToken']) {
	
	
	
	$id = $group->newSavedRequest($CURRENT_USER, $_POST['newMessageSummary'], $_POST['newMessageRequest']);
	
	
	
	$_SESSION['flashOK'] = "Request saved for use later!";
	
	header("Location: /savedRequests.php?supportGroup=".$group->getId());
} else {
	$_SESSION['flashError'] = "Error; you must enter a summary";
	header("Location: /");
}
