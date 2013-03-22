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

$premiumPassword = "IBroughtYouACow";

if (isset($_POST['email']) && isset($_POST['title']) && trim($_POST['email']) && trim($_POST['title']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ) {

	$resp = recaptcha_check_answer(RECAPTCHA_PRIVATE_KEY,$_SERVER["REMOTE_ADDR"],$_POST["recaptcha_challenge_field"],$_POST["recaptcha_response_field"]);

	if ($resp->is_valid) {

		$group = SupportGroup::create($_POST['title']);
		$group->addUser(UserAccount::findByEmailOrCreate($_POST['email']),true);
		if ($_POST['password'] == $premiumPassword) $group->makePremium();

		$s = getSmarty();
		$s->assign('PAGETITLE','Sign up to');
		$s->assign('CANONICALURL','/signUp.php');
		$s->assign('email',$_POST['email']);
		$s->display('signUp.done.htm');
		die();

	}


}

$s = getSmarty();
$s->assign('PAGETITLE','Sign up to');
$s->assign('CANONICALURL','/signUp.php');
$s->assign('recaptcha',recaptcha_get_html(RECAPTCHA_PUBLIC_KEY));
$s->display('signUp.htm');
