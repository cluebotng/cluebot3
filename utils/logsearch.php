<?PHP



    include '../wikibot.classes.php';
    include 'utils.config.php';

    $wpapi = new wikipediaapi();

    $wpapi->login($user, $pass);

    $lastcount = 5000;
    $lastlogid = -1;
    $start = null;
    while ($lastcount == 5000) {
        $tmp = $wpapi->logs(null, null, 5000, 'block', $start, null, 'newer');
        $lastcount = count($tmp);
        foreach ($tmp as $log) {
            if ($log['logid'] <= $lastlogid) {
                continue;
            }
            if ($log['action'] == 'block') {
                if (isset($log['block']['duration'])) {
                    $duration = strtolower($log['block']['duration']);
                    if (
                        ($duration != 'indefinite')
                        and ($duration != 'infinite')
                        and (!fnmatch('* 2005', $duration))
                        and (!fnmatch('* 2006', $duration))
                        and (!fnmatch('* 2007', $duration))
                        and (!fnmatch('* 2008', $duration))
                        and (!fnmatch('*second*', $duration))
                        and (!fnmatch('*hour*', $duration))
                        and (!fnmatch('*minute*', $duration))
                        and (!fnmatch('*day*', $duration))
                        and (!fnmatch('*week*', $duration))
                        and (!fnmatch('*month*', $duration))
                        and (!fnmatch('*year*', $duration))
                    ) {
                        print_r($log);
                    }
                }
            }
            $start = $log['timestamp'];
            $lastlogid = $log['logid'];
        }
    }
