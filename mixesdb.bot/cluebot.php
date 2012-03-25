<?PHP
	declare(ticks = 1);

	function sig_handler($signo) {
		switch ($signo) {
			case SIGCHLD:
				while (($x = pcntl_waitpid(0, $status, WNOHANG)) != -1) {
					if ($x == 0) break;
					$status = pcntl_wexitstatus($status);
				}
				break;
		}
	}

	pcntl_signal(SIGCHLD,   "sig_handler");


	include '../wikibot.classes.php';
	include 'cluebot.config.php';

	$wpapi = new wikipediaapi;
	$wpi = new wikipediaindex;

	$wpapi->apiurl = 'http://mixesdb.com/db/api.php';
	$wpi->indexurl = 'http://mixesdb.com/db/index.php';

	$wpapi->login($user,$pass);

	$continue = null;

	$users = array();

	$keepgoing = true;

	while ($keepgoing == true) {
		$tmp = $wpapi->listprefix('',0,5000,&$continue);
		foreach ($tmp as $d) {
			$pages[] = $d['title'];
		}
		if ($continue == null) $keepgoing = false;
	}

//	print_r($pages);die();

	foreach ($pages as $pg) {
		$data = $wpapi->revisions($pg,1,'older',true,null,true,false,false,false);
//		if (pcntl_fork() == 0) {
			$data = $data[0]['*'];
			$newdata = str_ireplace(
				array('<pre>' , '</pre>' , '{| border="1" cellpadding="4" style="border-collapse:collapse;"', '{{center}}|', ' align="center" |'),
				array('<list>', '</list>', '{|{{NormalTableFormat}}', '', ''),
				$data);
			if ($newdata != $data) {
				$wpi->forcepost($pg,$newdata,'Doing mass replace on main namespace.');
			}

//			die();
//		}
	}
?>
