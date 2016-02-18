<?PHP
	include '../wikibot.classes.php';
	include 'utils.config.php';

	$wpapi = new wikipediaapi;

	$wpapi->login($user,$pass);

	$u = $argv[1];

	$info = $wpapi->users($u);

	$cont = null; $lastcount = 5000;

	$count = 0;

	$nscounts = array();
	$autocounts = array();

	$rbk = 0;

	$auto = 0;

	while ($lastcount == 5000) {
		$tmp = $wpapi->usercontribs($u,5000,&$cont);
		$lastcount = count($tmp);
		$count += $lastcount;

		foreach ($tmp as $edit) {
			$nscounts[$edit['ns']]++;
			if (stripos($edit['comment'],'WP:TW')!==false) $autocounts['twinkle']++;
			elseif (stripos($edit['comment'],'AutoWikiBrowser')!==false) $autocounts['awb']++;
			elseif (stripos($edit['comment'],'WP:FRIENDLY')!==false) $autocounts['friendly']++;
			elseif (stripos($edit['comment'],'User:AWeenieMan/furme')!==false) $autocounts['furme']++;
			elseif (stripos($edit['comment'],'Wikipedia:Tools/Navigation_popups')!==false) $autocounts['popups']++;
			elseif (stripos($edit['comment'],'User:MichaelBillington/MWT')!==false) $autocounts['mwt']++;
			elseif (stripos($edit['comment'],'WP:NPW')!==false) $autocounts['npw']++;
			elseif (fnmatch('Reverted * edit* by * (*) to last revision by *',$edit['comment'])) $autocounts['amelvand']++;
			if (fnmatch('Reverted edits by * to last version by *',$edit['comment'])) $rbk++;
		}
	}

	$logs = array();

	$lastcount = 5000;
	$lastlogid = -1;
	$start = null;
	while ($lastcount == 5000) {
		$tmp = $wpapi->logs($u,null,5000,null,$start,null,'newer');
		$lastcount = count($tmp);
		foreach ($tmp as $log) {
			if ($log['logid'] <= $lastlogid) continue;
			$logs[$log['action']]++;
			$start = $log['timestamp'];
			$lastlogid = $log['logid'];
		}
	}

	echo '== Report for User:'.$u.' =='."\n";
	echo 'User groups: '.implode(' ',$info[0]['groups'])."\n";
	echo 'Edits (including deleted edits): '.$info[0]['editcount']."\n";
	echo 'Edits: '.$count."\n\n";
	echo '=== Action Counts ==='."\n";
	echo 'Rollbacks: '.$rbk."\n";
	foreach ($logs as $k => $v) {
		switch ($k) {
			case 'block': echo 'Users blocked: '.$v."\n"; break;
			case 'rights': echo 'User rights modified: '.$v."\n"; break;
			case 'create2': echo 'Accounts created: '.$v."\n"; break;
			case 'delete': echo 'Pages deleted: '.$v."\n"; break;
			case 'patrol': echo 'Pages patrolled: '.$v."\n"; break;
			case 'protect': echo 'Pages protected: '.$v."\n"; break;
			case 'restore': echo 'Pages restored: '.$v."\n"; break;
			case 'unblock': echo 'Users unblocked: '.$v."\n"; break;
			case 'unprotect': echo 'Pages unprotected: '.$v."\n"; break;
			case 'upload': echo 'Files uploaded: '.$v."\n"; break;
			case 'renameuser': echo 'Users renamed: '.$v."\n"; break;
			case 'grant': echo 'Rights granted: '.$v."\n"; break;
			case 'revoke': echo 'Rights revoked: '.$v."\n"; break;
			case 'move': echo 'Pages moved: '.$v."\n"; break;
			case 'move_redir': echo 'Pages moved over redirect: '.$v."\n"; break;
			default: echo $k.': '.$v."\n"; break;
		}
	}
	echo "\n";
	echo '=== Automated edits ==='."\n";
	foreach ($autocounts as $k => $v) { echo 'Edits using '.$k.': '.$v."\n"; $auto += $v; }
	echo 'Total automated edits: '.$auto."\n\n";
	echo '=== Namespace counts ==='."\n";
	echo '{|'."\n";
	echo '! Namespace !! Count !! Percent'."\n";
	foreach ($nscounts as $k => $v) {
		echo '| '.$k.' || '.$v.' || '.($v/$count * 100).'%'."\n";
	}
	echo '|}'."\n";
