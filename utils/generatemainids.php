<?PHP
	include '../wikibot.classes.php';
	include 'utils.config.php';

	$wpapi = new wikipediaapi;

	$wpapi->login($user,$pass);

	$fd = fopen('mainids.txt','a+');

	$ts = null;
	for ($i = 0; $i < 2000; $i++) {
		$x = $wpapi->recentchanges(5000,0,'older',$ts);
//		print_r($x);
		foreach ($x as $y) {
			$ts = $y['timestamp'];
			fwrite($fd,$y['revid']."\n");
		}
	}
?>
