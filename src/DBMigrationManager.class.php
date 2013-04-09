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



class DBMigrationManager {

	public static function upgrade($verbose = false) {
		
		$db = getDB();
		
		// First, the migrations table.
		$stat = $db->query("SELECT true FROM pg_tables WHERE tablename = 'migration';");
		$tableExists = ($stat->rowCount() == 1);
		
		if ($tableExists) {
			if ($verbose) print "Migrations table exists.\n";
		} else {
			if ($verbose) print "Creating migration table.\n";
			$db->query("CREATE TABLE migration ( id VARCHAR(255) NOT NULL, installed_at TIMESTAMP WITHOUT TIME ZONE  NOT NULL, PRIMARY KEY(id)  )");
		}

		// Now load all possible migrations from disk & sort them
		$migrations = array();
		$dir = dirname(__FILE__).'/../sql/migrations/';
		$handle = opendir($dir);		
		while (false !== ($file = readdir($handle))) {
			if ($file != '.' && $file != '..') {
				if ($verbose) echo "Loading ".$file."\n";
				if (substr($file, -4) == '.sql') {
					$migrations[] = new DBMigration(substr($file, 0, -4), file_get_contents($dir.$file));
				}
			}
		}
		closedir($handle);
		usort($migrations, "DBMigrationManager::compareMigrations");
		
		// Now see what is already applied 
		// ... in an O(N^2) loop inside a loop, performance could be better but doesn't matter here for now.
		$stat = $db->query("SELECT id FROM migration");
		while($result = $stat->fetch()) {
			foreach($migrations as $migration) { 
				if ($migration->getId() == $result['id']) {
					$migration->setIsApplied();
				}
			}
		}
		
		// Finally apply the new ones!
		if ($verbose) {
			foreach($migrations as $migration) {
				if (!$migration->getApplied()) {
					print "Will apply ".$migration->getId()."\n";				
				} else {
					print "Already Applied ".$migration->getId()."\n";
				}
			}
		}
		$stat = $db->prepare("INSERT INTO migration (id, installed_at) VALUES (:id, :at)");
		foreach($migrations as $migration) {
			if (!$migration->getApplied()) {
				if ($verbose) print "Applying ".$migration->getId()."\n";
				$db->beginTransaction();
				$migration->performMigration($db);
				$stat->execute(array('id'=>$migration->getId(),'at'=>date('Y-m-h H:i:s')));
				$db->commit();
				if ($verbose) print "Applied ".$migration->getId()."\n";
			}
		}
		
		if ($verbose) print "Done\n";
		
		
	}
	
	private static function compareMigrations(DBMigration $a, DBMigration $b) {
		if ($a->getIdAsUnixTimeStamp() == $b->getIdAsUnixTimeStamp()) return 0;
		return ($a->getIdAsUnixTimeStamp() < $b->getIdAsUnixTimeStamp()) ? -1 : 1;
	}
}