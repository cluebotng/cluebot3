<?PHP
	ini_set('memory_limit','256M');

	function getshortcut ($page) {
		global $wpapi;

		$cont = null;

		$redirs = array();
		$scs = array();

		$newpage = $wpapi->revisions($page,1,'older',false,null,false,false,false,true);
		$newpage = $newpage['title'];

		while ((!isset($t)) or (isset($t[4999]))) {
			$t = $wpapi->backlinks($newpage,5000,$cont,'redirects');
			foreach ($t as $x) $redirs[] = $x;
		}

		foreach ($redirs as $redir) {
			$t = str_replace('Wikipedia:','WP:',$redir['title']);
			$scs[$t] = strlen(str_replace('WP:','',$t))
				- ((strtolower(substr(str_replace('WP:','',$t),0,1)) == strtolower(substr(str_replace('Wikipedia:','',$page),0,1))) ? 0.1 : 0)
				- ((strtoupper($t) == $t) ? 0.1 : 0);
		}

		asort($scs);

		reset($scs);
		$ret = key($scs);

		if (!$ret) $ret = str_replace('Wikipedia:','WP:',$page);
		return $ret;
	}

	function abbrev($string) {
		$string = str_replace(array('.','-','_',',','&'),' ',$string);

		$ret = '';
		$s = explode(' ',$string);

		foreach ($s as $x) {
			for ($i = 0 ; $i < strlen($x) ; $i++) {
				if ($i == 0) $ret .= $x{$i};
				else if (strtoupper($x{$i}) == $x{$i}) $ret .= $x{$i};
			}
		}

		return $ret;
	}

	function getactivity($user) {
		global $wpapi;

		$keepgoing = true;
		$revs = array();

		$count = 0;

		$cont = null;

		$titles = array();

		while ($keepgoing == true) {
			$tmp = $wpapi->usercontribs($user,5000,$cont,'older');
			foreach ($tmp as $k => $d) {
				if (is_numeric($k)) $revs[] = $d;
			}

			if (!isset($tmp[4999])) $keepgoing = false;
		}
		echo 'Total revs: '.count($revs).".\n";

		foreach ($revs as $rev) {
			$t = explode('/',$rev['title']);
			if ($rev['ns'] != 0) $t = explode(':',$t[0],2);
			if ($rev['ns'] == 0) $t[1] = $t[0];
			if (($rev['ns'] == 4) or ($rev['ns'] == 5)) {
				$titles['wp'][$t[1]]++;
			} else if (($rev['ns'] == 0) or ($rev['ns'] == 1)) {
				$titles['art'][$t[1]]++;
			} else if ($rev['ns'] == 3) {
				$titles['us'][$t[1]]++;
			}
		}

		arsort($titles['wp']);
		arsort($titles['art']);
		arsort($titles['us']);
	
		$titles['wp'] = array_slice($titles['wp'], 0, 5, true);
		$titles['art'] = array_slice($titles['art'], 0, 2, true);
		$titles['us'] = array_slice($titles['us'], 0, 2, true);

		foreach ($titles['wp'] as $t => $c) {
			$titles2['wp'][getshortcut('Wikipedia:'.$t)] = $c;
		}

		foreach ($titles['art'] as $t => $c) {
			$titles2['art'][$t] = $c;
		}

		foreach ($titles['us'] as $t => $c) {
			$titles2['us']['User talk:'.$t] = $c;
		}

		print_r($titles2);

		$pages = '';

		foreach ($titles2 as $k => $titles) {
			foreach ($titles as $t => $c) {
				$nopref = str_replace(array('WP:','User talk:'),'',$t);
				if ($k != 'wp') $nopref = abbrev($nopref);
				$pages .= ', [['.$t.'|'.(($k == 'us') ? 'UT:' : '').$nopref.']]';
			}
		}
		$pages = substr($pages,2);
		echo $pages."\n";

		return $pages;
	}


	include '../wikibot.classes.php';
	include 'utils.config.php';

	$wpapi = new wikipediaapi;
	$wpq = new wikipediaquery;
	$wpi = new wikipediaindex;

	$wpapi->login($user,$pass);

//	$data = $wpq->getpage('User:Useight/Highly Active');

	$users = array();

	$skip = true;

//	if (preg_match_all('/\[\[User:(.*)\|\1\]\]/i',$data,$m)) {
//		$m = $m[1];
//		foreach ($m as $user) {
//			if ($user == 'Yamla') { $skip = false; continue; }
//			if ($skip) continue;
//			$users[$user] = getactivity($user);
//			file_put_contents('activity.txt','* [[User:'.$user.'|]]: '.$users[$user]."\n",FILE_APPEND);
//		}
//	}

	$dtp = '';

//	foreach ($users as $u => $d) $dtp .= '* [[User:'.$u.'|]]: '.$d."\n";
//	$dtp = file_get_contents('activity.txt');
//	$wpi->forcepost('User:Cobi/UserActivity',$dtp,'Uploading user report.');

	echo 'User:'.$argv[1].': '.getactivity($argv[1])."\n\n";
?>
