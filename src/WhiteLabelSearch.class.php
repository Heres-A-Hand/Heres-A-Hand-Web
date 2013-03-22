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



class WhiteLabelSearch extends BaseSearch {
	
	public function __construct() {
		parent::__construct();
		$this->className = 'WhiteLabel';
	}

	protected $orderBy = 'id ASC';
	
	protected $adminUser;
	
	public function userCanAdmin(UserAccount $user) {
		// sys admin can admin all white label groups!
		if (!$user->getIsSystemAdmin()) $this->adminUser = $user;
	}

	protected function execute() {
		if ($this->searchDone) throw new Exception("Search already done!");
		$db = getDB();
		$where = array();
		$joins = array();
		$vars = array();
		
		if ($this->adminUser) {
			$joins[] = " JOIN user_admins_white_label ON user_admins_white_label.white_label_id = white_label.id";
			$where[] = " user_admins_white_label.user_account_id = :uid ";
			$vars['uid'] = $this->adminUser->getId();
		}
		
		$sql = "SELECT white_label.* ".
			"FROM white_label ".implode(" ", $joins).
			(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "").
			"ORDER BY ".$this->orderBy;
		$stat = $db->prepare($sql);
		$stat->execute($vars);
		while($d = $stat->fetch(PDO::FETCH_ASSOC)) {
			$this->results[] = $d;
		}
		$this->searchDone = true;
	}
	
	
}



