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

$premiumPassword = "IBroughtYouACow";

if (isset($_POST['title']) && trim($_POST['title'])  && $_POST['CSFRToken'] == $_SESSION['CSFRToken']) {

	$group = SupportGroup::create($_POST['title']);
	$group->addUser($CURRENT_USER,true);
	if ($_POST['password'] == $premiumPassword) $group->makePremium();
	
	$_SESSION['flashOK'] = "New group created! Now add the people you want to this group.";

	header("Location: /team.php?supportGroup=".$group->getId());
}




