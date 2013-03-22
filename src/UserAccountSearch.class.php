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


class UserAccountSearch extends BaseSearch {
	
	public function __construct() {
		parent::__construct();
		$this->className = 'UserAccount';				
	}

	protected $canAdminWhiteLabel;
	
	public function canAdminWhiteLabel(WhiteLabel $whiteLabel) {
		$this->canAdminWhiteLabel = $whiteLabel;
	}
	
	protected $inWhiteLabel;
	
	public function inWhiteLabel(WhiteLabel $whiteLabel) {
		$this->inWhiteLabel = $whiteLabel;
	}

	protected $orderBy = 'id ASC';
	
	protected function execute() {
		if ($this->searchDone) throw new Exception("Search already done!");
		$db = getDB();
		$where = array();
		$joins = array();
		$vars = array();
		
		if ($this->canAdminWhiteLabel) {
			$joins[] = " JOIN user_admins_white_label ON user_admins_white_label.user_account_id = user_account.id";
			$where[] = " user_admins_white_label.white_label_id = :wlid ";
			$vars['wlid'] = $this->canAdminWhiteLabel->getId();			
		}
		
		if ($this->inWhiteLabel) {
			$joins[] = " JOIN user_in_group ON user_in_group.user_account_id = user_account.id";
			$joins[] = " JOIN support_group ON user_in_group.support_group_id = support_group.id";
			$where[] = " support_group.white_label_id = :wlid2 ";
			$vars['wlid2'] = $this->inWhiteLabel->getId();			
		}
		
		$sql = "SELECT user_account.* ".
			"FROM user_account ".implode(" ", $joins).
			(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "").
			" GROUP BY user_account.id "."ORDER BY ".$this->orderBy;
		$stat = $db->prepare($sql);
		$stat->execute($vars);
		while($d = $stat->fetch(PDO::FETCH_ASSOC)) {
			$this->results[] = $d;
		}
		$this->searchDone = true;
	}
	
	
}


