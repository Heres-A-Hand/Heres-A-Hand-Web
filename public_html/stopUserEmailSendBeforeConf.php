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

$email = UserEmail::findByID($_GET['id']);
if (!$email) die("not Loaded\n");
if (!$email->getSendBeforeConfirmation()) die("already stopped\n");
if (!$email->checkStopSendBeforeConfirmationCode($_GET['c'])) {
	logWarning("Wrong Code when trying to stop send before confirmation UserEmail:".$email->getId());
	die('Code wrong');
}


$s = getSmarty();
$s->assign('PAGETITLE','Thanks!');
$s->assign('email',$email);	

if (isset($_POST['confirm']) && $_POST['confirm'] == 'yes') {
	$email->updateStopSendBeforeConfirmationCode(false);
	$s->display('stopUserEmailSendBeforeConf.done.htm');
} else {
	$s->display('stopUserEmailSendBeforeConf.htm');
}
