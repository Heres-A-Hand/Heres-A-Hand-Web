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
require '../libs/recaptcha/recaptchalib.php';

checkUserSession();
 
if ($CURRENT_USER) {
	header("Location: /");
	die();
}

$s = getSmarty();
$s->assign('PAGETITLE','Forgot Password for');
$s->assign('CANONICALURL','/forgotPassword.php');

if (isset($_POST['email'])) {

	$resp = recaptcha_check_answer(RECAPTCHA_PRIVATE_KEY,$_SERVER["REMOTE_ADDR"],$_POST["recaptcha_challenge_field"],$_POST["recaptcha_response_field"]);

	if ($resp->is_valid) {

		$userEmail = UserEmail::findByEmail($_POST['email']);

		if ($userEmail) {
			logInfo("Forgotten Password requested for UserEmail:".$userEmail->getId());

			$ps = getPheanstalk();
			if ($ps) $ps->useTube(BEANSTALKD_QUE)->put(json_encode(array("type"=>"ForgottenPassword","userEmailID"=>$userEmail->getId())),1000,5);

			$s->display('forgotPassword.sent.htm');
			die();
		} else {
			logWarning("Email not Known when trying to log in. ".$_POST['email']);
			$s->assign('flashError','Details not known');
		}

	} else {
		
		$s->assign('flashError','Please type in the words from the image.');
	}

}


$s->assign('PAGETITLE','Forgot Password for');
$s->assign('CANONICALURL','/forgotPassword.php');
$s->assign('recaptcha',recaptcha_get_html(RECAPTCHA_PUBLIC_KEY));

$email = isset($_POST['email']) && $_POST['email'] ? $_POST['email'] : (isset($_GET['email']) && $_GET['email'] ? $_GET['email'] : '');
$s->assign('email', $email);
$s->display('forgotPassword.htm');
