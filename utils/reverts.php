<?PHP
    include '../wikibot.classes.php';

    $page = $argv[1];

    $wpapi = new wikipediaapi();

    $keepgoing = true;
    $revs = array();

    $count = 0;

    while ($keepgoing == true) {
        $tmp = $wpapi->revisions($page, 500, 'older', false, $tmp['continue'], false, false, false);
        foreach ($tmp as $k => $d) {
            if (is_numeric($k)) {
                $revs[] = $d;
            }
        }

        if (!isset($tmp[499])) {
            $keepgoing = false;
        }
    }
    echo 'Total revs: '.count($revs).".\n";

    foreach ($revs as $data) {
        $es = strtolower($data['comment']);
        if (fnmatch('reverting *', $es)) {
            $count++;
        }
        if (fnmatch('reverting', $es)) {
            $count++;
        }
        if (fnmatch('reverted *', $es)) {
            $count++;
        }
        if (fnmatch('reverted', $es)) {
            $count++;
        }
        if (fnmatch('revert *', $es)) {
            $count++;
        }
        if (fnmatch('revert', $es)) {
            $count++;
        }
        if (fnmatch('rvt *', $es)) {
            $count++;
        }
        if (fnmatch('rvt', $es)) {
            $count++;
        }
        if (fnmatch('rv *', $es)) {
            $count++;
        }
        if (fnmatch('rv', $es)) {
            $count++;
        }
        if (fnmatch('rvv *', $es)) {
            $count++;
        }
        if (fnmatch('rvv', $es)) {
            $count++;
        }
        if (fnmatch('*undid*', $es)) {
            $count++;
        }
    }

    echo 'Total reverts: '.$count.' ('.(100*$count/count($revs))."%).\n";
    echo 'Total good edits: '.(count($revs)-(2*$count)).' ('.(100*(count($revs)-(2*$count))/count($revs))."%).\n";
