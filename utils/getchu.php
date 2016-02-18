<?PHP


    include '../wikibot.classes.php';
    include 'utils.config.php';

    $wpq = new wikipediaquery();
    $wpapi = new wikipediaapi();
    $wpi = new wikipediaindex();

    preg_match_all('/^\=\=\=\s*(.*)\s+\xe2\x86\x92\s+(.*)\s*\=\=\=$/m', $wpq->getpage('Wikipedia:Changing username'), $m, PREG_SET_ORDER);
