<?PHP



    include 'wikibot.classes.php';
    include 'cluebot/cluebot.config.php'; /* MySQL information */
    include 'cluebotcommons.config.php';

    $wpi = new wikipediaindex();
    $wpq = new wikipediaquery();
    $wpapi = new wikipediaapi();

    $wpapi->login($user, $pass);

    $wikidata = "\n\n".'== ClueBots report as of ~~~~~ =='."\n";
    $wikidata .= 'Hello, {{subst:BASEPAGENAME}}!  Here are the statistics on the ClueBots as you requested.'."\n";
    $wikidata .= '=== ClueBot ==='."\n";

    include 'cluebot/cluebot.heuristics.config.php';
    $stats = unserialize(file_get_contents('cluebot/cluebot.heuristics.stats.txt'));

    $i = 0;
    $theuristics = '';
    $eheuristics = array();
    foreach ($heuristics as $heuristic) {
        ++$i;
        $theuristics .= ', '.$heuristic;
        if ($i % 3 == 0) {
            $eheuristics[] = substr($theuristics, 2);
            $theuristics = '';
        }
    }
    $eheuristics[] = substr($theuristics, 2);

    $wikidata .= '{| style="float: right; clear: right;" class="infobox plainlinks"'."\n";
    $wikidata .= '|-'."\n";
    $wikidata .= '|align="center" colspan=2|ClueBot has the following heuristics enabled: '."\n".implode("<br />\n", $eheuristics)."\n";
    $wikidata .= '|-'."\n";
    $wikidata .= '!Heuristic'."\n";
    $wikidata .= '!Count'."\n";
    foreach ($stats as $heuristic => $count) {
        $wikidata .= '|-'."\n";
        $wikidata .= '|'.$heuristic."\n";
        $wikidata .= '|'.$count."\n";
    }
    $wikidata .= '|}'."\n";

    $ov = unserialize(file_get_contents('cluebot/oftenvandalized.txt'));
    foreach ($ov as $title => $array) {
        if (count($array) == 0) {
            unset($ov[$title]);
        }
    }
    file_put_contents('cluebot/oftenvandalized.txt', serialize($ov));
    $count = count($ov);

    $titles = unserialize(file_get_contents('cluebot/titles.txt'));
    foreach ($titles as $title => $time) {
        if ((time() - $time) > (24 * 60 * 60)) {
            unset($titles[$title]);
        }
    }
    file_put_contents('cluebot/titles.txt', serialize($titles));
    $tcount = count($titles);

    foreach ($ov as $x => $y) {
        $ocount[$x] = count($y);
    }
    arsort($ocount);
    foreach ($ocount as $x => $y) {
        $mova = $x;
        $movacount = $y;
        break;
    }

    preg_match('/\(\'\'\'\[\[([^|]*)\|more...\]\]\'\'\'\)/iU', $wpq->getpage('Wikipedia:Today\'s featured article/'.date('F j, Y')), $tfa);
    $tfa = $tfa[1];

    if (!preg_match('/(yes|enable|true)/i', $wpq->getpage('User:ClueBot/Run'))) {
        $run = false;
    } else {
        $run = true;
    }

    $wikidata .= 'ClueBot is currently '.($run ? 'enabled' : 'disabled').'.  ClueBot currently has '.$wpq->contribcount('ClueBot').' contributions.'."\n\n";

    $wikidata .= 'ClueBot has attempted to revert '.$tcount.' unique article/user combinations in the last 24 hours.  '.
        'ClueBot knows of '.$count.' different articles that have been vandalized in the last 48 hours.'."\n\n";

    $wikidata .= '[['.$mova.']] is the most vandalized page with a total of '.$movacount.' vandalisms in the last 48 hours.  '.
        'Today\'s featured article is: [['.$tfa.']].'."\n\n";

    $ircconfig = explode("\n", $wpq->getpage('User:'.$owner.'/CBChannels.js'));
    $tmp = array();
    foreach ($ircconfig as $tmpline) {
        if (substr($tmpline, 0, 1) != '#') {
            $tmpline = explode('=', $tmpline, 2);
            $tmp[trim($tmpline[0])] = trim($tmpline[1]);
        }
    }

    $ircchannel = $tmp['ircchannel'];

    $wikidata .= 'ClueBot logs all information to [irc://irc.freenode.net/'.substr($ircchannel, 1).' '.$ircchannel.'].'."\n";

    $wikidata .= '{| class="infobox plainlinks" style="float: none; clear: none;"'."\n".'|-'."\n".'|colspan=2 align="center"|The following users have beat ClueBot to the revert the most:'."\n";
    $wikidata .= '|-'."\n".'!User'."\n".'!Count'."\n";

    $mysql = mysql_pconnect($mysqlhost.':'.$mysqlport, $mysqluser, $mysqlpass);
    mysql_select_db($mysqldb, $mysql);
    $q = mysql_query('SELECT `user`,COUNT(`id`) AS `count` FROM `cluebot_enwiki`.`beaten` WHERE `user` != \'\' GROUP BY `user` HAVING `count` > 1 ORDER BY `count` DESC LIMIT 5');
    while ($x = mysql_fetch_assoc($q)) {
        $wikidata .= '|-'."\n";
        $wikidata .= '|[[User:'.$x['user'].'|]]'."\n";
        $wikidata .= '|'.$x['count']."\n";
    }
    $wikidata .= '|}'."\n\n";
    unset($x, $q);

    unset($x, $y, $count, $ov, $tcount, $ocount, $mova, $movacount, $tfa, $run, $title, $titles, $time, $top5beat);
    unset($count, $heuristic, $stats, $heuristics);

    $wikidata .= '=== ClueBot II ==='."\n";
    if (!preg_match('/(yes|enable|true)/i', $wpq->getpage('User:ClueBot II/Run'))) {
        $run = false;
    } else {
        $run = true;
    }
    $wikidata .= 'ClueBot II is currently '.($run ? 'enabled' : 'disabled').'.  ClueBot II currently has '.$wpq->contribcount('ClueBot II').' contributions.'."\n\n";
    $wikidata .= 'ClueBot II has removed '.count(unserialize(file_get_contents('cluebot2/unsetredlinks.txt'))).' redlinks from [[WP:SCV]] in the last 24 hours.'."\n\n";

    $wikidata .= '=== ClueBot III ==='."\n";

    if (!preg_match('/(yes|enable|true)/i', $wpq->getpage('User:ClueBot III/Run'))) {
        $run = false;
    } else {
        $run = true;
    }
    $wikidata .= 'ClueBot III is currently '.($run ? 'enabled' : 'disabled').'.  ClueBot III currently has '.$wpq->contribcount('ClueBot III').' contributions.'."\n\n";

    $titles = array();
    $ei = $wpapi->embeddedin('User:ClueBot III/ArchiveThis', 500, $continue);
    foreach ($ei as $data) {
        $titles[] = $data['title'];
    }
    while (isset($ei[499])) {
        $ei = $wpapi->embeddedin('User:ClueBot III/ArchiveThis', 500, $continue);
        foreach ($ei as $data) {
            $titles[] = $data['title'];
        }
    }

    $wikidata .= count($titles).' users/pages use ClueBot III\'s archiving.'."\n\n";

    $wikidata .= '=== ClueBot IV ==='."\n";

    if (!preg_match('/(yes|enable|true)/i', $wpq->getpage('User:ClueBot IV/Run'))) {
        $run = false;
    } else {
        $run = true;
    }
    $wikidata .= 'ClueBot IV is currently '.($run ? 'enabled' : 'disabled').'.  ClueBot IV currently has '.$wpq->contribcount('ClueBot IV').' contributions.'."\n\n";

    unset($run);

    $wpi->post('User talk:'.$owner, $wpq->getpage('User talk:'.$owner).$wikidata.'~~~~', 'Reporting on ClueBots.');
