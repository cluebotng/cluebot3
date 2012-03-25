<?PHP
	include '../wikibot.classes.php';
	include 'utils.config.php';

	$wpapi = new wikipediaapi;
	$wpq = new wikipediaquery;
	$wpi = new wikipediaindex;

	$wpapi->login($user,$pass);

	$count = $limit = 1000;

	$i = $offset = 3000;

	$j = count(explode("\n",file_get_contents('mdashes.txt')));

	$fp = fopen('mdashes.txt','a');

	while (($count == $limit) or ($count == 0)) {
		$search = $wpapi->search(' &mdash; ',$limit,$offset,0);
		$count = count($search);
		$offset += $count;
		foreach ($search as $page) {
			$i++;
			echo '['.str_pad($i,5,'0',STR_PAD_LEFT).'] ['.str_pad($j,5,'0',STR_PAD_LEFT).']: ';
			if (stripos($wpq->getpage($page['title']),' &mdash; ') !== false) {
				$j++;
				fwrite($fp,$page['title']."\n");
			}
		}
	}
?>
