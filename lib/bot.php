<?php

    /*
     * Copyright (C) 2015 Jacobi Carter
     *
     * This file is part of ClueBot III.
     *
     * ClueBot III is free software: you can redistribute it and/or modify
     * it under the terms of the GNU General Public License as published by
     * the Free Software Foundation, either version 2 of the License, or
     * (at your option) any later version.
     *
     * ClueBot III is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     * GNU General Public License for more details.
     *
     * You should have received a copy of the GNU General Public License
     * along with ClueBot III.  If not, see <http://www.gnu.org/licenses/>.
     */

namespace ClueBot3;

function splitintosections($d, $level = 2)
{
    $ret = array();
    $sections = array();

    $th = '';
    $tb = '';
    $s = 0;
    for ($i = 0; $i < strlen($d); ++$i) {
        if (
            (substr($d, $i, $level) == str_repeat('=', $level))
            and ($d[$i + $level] != '=')
            and (($i == 0) or ($d[$i - 1] == "\n"))
        ) {
            $j = 0;
            while (($d[$i + $j] != "\n") and ($i + $j < strlen($d))) {
                ++$j;
            }
            if (
                (substr(trim(substr($d, $i, $j)), -1 * $level, $level) == str_repeat('=', $level))
                and (substr(trim(substr($d, $i, $j)), (-1 * $level) - 1, 1) != '=')
            ) {
                if ($s == 1) {
                    $sections[] = array($th, $tb);
                } else {
                    $header = $tb;
                }
                $s = 1;
                $th = substr(trim(substr($d, $i, $j)), $level, -1 * $level);
                $tb = '';
                $i += $j - 1;
            }
        } else {
            $tb .= $d[$i];
        }
    }

    if ($s == 1) {
        $sections[] = array($th, $tb);
    } else {
        $header = $tb;
    }

    $ret[] = $header;
    foreach ($sections as $section) {
        $id = trim($section[0]);
        $i = 1;
        while (isset($ret[$id])) {
            ++$i;
            $id = trim($section[0]) . ' ' . $i;
        }
        $ret[$id] = array('header' => $section[0], 'content' => $section[1]);
    }

    return $ret;
}

function extractnamespace($page)
{
    if (preg_match('/^((user|wikipedia|image|mediawiki|template|help|category|portal)? ?(talk)?):(.*)$/i', $page, $m)) {
        return array($m[1], $m[4]);
    } else {
        return array('', $m[4]);
    }
}

function namespacetoid($namespace)
{
    $convert = array(
            '' => 0,    'talk' => 1,
            'user' => 2,    'user talk' => 3,
            'wikipedia' => 4,    'wikipedia talk' => 5,
            'image' => 6,    'image talk' => 7,
            'mediawiki' => 8,    'mediawiki talk' => 9,
            'template' => 10,    'template talk' => 11,
            'help' => 12,    'help talk' => 13,
            'category' => 14,    'category talk' => 15,
            'portal' => 100,    'portal talk' => 101,
        );

    return $convert[strtolower(str_replace('_', ' ', $namespace))];
}

function doarchive(
    $page,
    $archiveprefix,
    $archivename,
    $age,
    $minarch,
    $minkeep,
    $defaulthead,
    $archivenow,
    $level,
    $noindex,
    $maxsects,
    $maxbytes,
    $htransform,
    $maxarchsize,
    $archnumberstart,
    $key
) {
    global $logger;
    global $wpq;
    global $wpapi;
    global $wpi;

    $rv = $wpapi->revisions($page, 1, 'older', true);
    if (!is_array($rv)) {
        return false;
    }
    $rv2 = $rv;

    $wpStarttime = gmdate('YmdHis', time());
    $tmp = date_parse($rv[0]['timestamp']);
    $wpEdittime = gmdate('YmdHis', gmmktime(
        $tmp['hour'],
        $tmp['minute'],
        $tmp['second'],
        $tmp['month'],
        $tmp['day'],
        $tmp['year']
    ));
    unset($tmp);

    $cursects = splitintosections($rv[0]['slots']['main']['*'], $level);

    $ans = array();
    $anr = array();
    foreach ($archivenow as $k => $v) {
        $archivenow[$k] = trim($v);
    }
    foreach ($archivenow as $v) {
        $ans[] = $v;
        if (strpos($v, ':') !== false) {
            $anr[] = str_replace('{{', '{{tlu|', $v);
        } else {
            $anr[] = str_replace('{{', '{{tl|', $v);
        }
    }

    $done = false;
    $lastrvid = null;
    while (!$done) {
        $rv = $wpapi->revisions($page, 5000, $dir = 'older', false, $lastrvid);
        foreach ($rv as $rev) {
            if (preg_match('/(\d+)\-(\d+)\-(\d+)T(\d+):(\d+):(\d+)/', $rev['timestamp'], $m)) {
                $time = gmmktime($m[4], $m[5], $m[6], $m[2], $m[3], $m[1]);
                if ((time() - $time) >= ($age * 60 * 60)) {
                    $done = true;
                    break;
                }
            }
        }
        if ((!isset($rv[4999])) and ($done == false)) {
            break;
        }
        $lastrvid = $rev['revid'];
        if (!$lastrvid) {
            break;
        }
    }
    if ($lastrvid == null) {
        $tmp = array(array('slots' => array('main' => array('*' => ''))));
    } else {
        $tmp = $wpapi->revisions($page, 1, 'older', true, $lastrvid);
    }
    $oldsects = splitintosections($tmp[0]['slots']['main']['*'], $level);
    $header = $cursects[0];
    unset($cursects[0]);
    unset($oldsects[0]);
    $keepsects = array();
    $archsects = array();
    foreach ($oldsects as $id => $array) {
        if (!isset($cursects[$id])) {
            unset($oldsects[$id]);
        }
    }
    foreach ($cursects as $id => $array) {
        $an = false;
        foreach ($archivenow as $v) {
            if (strpos($array['content'], $v) !== false) {
                $an = true;
            }
        }
        if ((count($cursects) - count($archsects)) <= $minkeep) {
            $keepsects[$id] = $array;
        } elseif ($an == true) {
            $array['content'] = str_replace($ans, $anr, $array['content']);
            $archsects[$id] = $array;
        } elseif (
            preg_match(
                '/\{\{User:ClueBot III\/DoNotArchiveUntil\|(\d+)\}\}/',
                $array['content'],
                $m
            ) && time() < $m[1]
        ) {
            $keepsects[$id] = $array;
        } elseif (!isset($oldsects[$id])) {
            $keepsects[$id] = $array;
        } elseif (trim($array['content']) == trim($oldsects[$id]['content'])) {
            $archsects[$id] = $array;
        } else {
            $keepsects[$id] = $array;
        }
    }

    if (($maxsects > 0) or ($maxbytes > 0)) {
        $i = 0;
        $b = 0;
        $keepsects = array_reverse($keepsects, true);
        foreach ($keepsects as $id => $array) {
            ++$i;
            $b += strlen($array['content']);
            if (($maxsects > 0) and ($i > $maxsects)) {
                $archsects[$id] = $array;
                unset($keepsects[$id]);
            } elseif (($maxbytes > 0) and ($b > $maxbytes)) {
                $archsects[$id] = $array;
                unset($keepsects[$id]);
            }
        }
        $keepsects = array_reverse($keepsects, true);
    }

    if ($htransform != '') {
        $search = array();
        $replace = array();
        $transforms = explode('&&&', $htransform);
        foreach ($transforms as $v) {
            $v = explode('===', $v, 2);
            $search[] = $v[0];
            $replace[] = $v[1];
        }
        foreach ($archsects as $id => $array) {
            $archsects[$id]['header'] = preg_replace($search, $replace, $array['header']);
        }
    }

    foreach ($oldsects as $id => $array) {
        $tmpsectsprintr['oldsects'][] = $id;
    }
    foreach ($cursects as $id => $array) {
        $tmpsectsprintr['cursects'][] = $id;
    }
    foreach ($keepsects as $id => $array) {
        $tmpsectsprintr['keepsects'][] = $id;
    }
    foreach ($archsects as $id => $array) {
        $tmpsectsprintr['archsects'][] = $id;
    }

    if ((count($archsects) > 0) and (count($archsects) >= $minarch)) {
        $pdata = $header;
        foreach ($keepsects as $array) {
            $pdata .= str_repeat('=', $level) . $array['header'] . str_repeat('=', $level) . $array['content'];
        }

        if (substr(strtolower(str_replace('_', ' ', $archiveprefix)), 0, strlen($page)) != strtolower($page)) {
            global $pass;
            $ckey = trim(md5(trim($page) . trim($archiveprefix) . trim($pass)));
            if (trim($key) != $ckey) {
                $logger->addError('Incorrect key and archiveprefix.  $archiveprefix=\'' .
                                  $archiveprefix . '\';$correctkey=\'' . $ckey . '\';');
                $archiveprefix = $page . '/Archives/';
            }
        }

        if ($age == '99999') {
            $age = 0;
        }

        $i = $archnumberstart;

        $apage = $archiveprefix . gmdate(str_replace('%%i', $i, $archivename), (time() - ($age * 60 * 60)));

        if (($maxarchsize > 10000) and (strpos($archivename, '%%i') !== false)) {
            while (strlen($wpq->getpage($apage)) > $maxarchsize) {
                $apage = $archiveprefix . gmdate(str_replace('%%i', $i, $archivename), (time() - ($age * 60 * 60)));
                ++$i;
            }
        }

        $adata = (($x = $wpq->getpage($apage)) ? $x : $defaulthead . "\n") . "\n";
        foreach ($archsects as $array) {
            $adata .= str_repeat('=', $level) . $array['header'] . str_repeat('=', $level) . $array['content'];
        }
        if (
            !$wpapi->edit(
                $apage,
                $adata,
                'Archiving ' . count($archsects) . ' discussion' .
                          ((count($archsects) > 1) ? 's' : '') . ' from [[' . $page . ']]. (BOT)',
                true,
                true
            )
        ) {
            return false;
        }
        if (
            !$wpapi->edit(
                $page,
                $pdata,
                'Archiving ' . count($archsects) . ' discussion' .
                          ((count($archsects) > 1) ? 's' : '') . ' to [[' . $apage . ']]. (BOT)',
                true,
                true,
                $wpStarttime,
                $wpEdittime
            )
        ) {
            $wpapi->edit(
                $apage,
                $x,
                'Unarchiving ' . count($archsects) . ' discussion' .
                         ((count($archsects) > 1) ? 's' : '') . ' from [[' . $page . ']]. (Archive failed) (BOT)',
                true,
                true
            );

            return false;
        }

        $search = array();
        $replace = array();
        foreach ($archsects as $header => $data) {
            $anchor = str_replace('%', '.', urlencode(str_replace(' ', '_', $header)));
            $newanchor = str_replace('%', '.', urlencode(str_replace(' ', '_', trim($data['header']))));
            $search[] = $page . '#' . $anchor;
            $replace[] = $apage . '#' . $newanchor;
            $search[] = $page . '#' . str_replace('.20', '_', $anchor);
            $replace[] = $apage . '#' . str_replace('.20', '_', $newanchor);
            $search[] = $page . '#' . $header;
            $replace[] = $apage . '#' . trim($data['header']);
        }

        $pagelist = array();
        $continue = null;
        $bl = $wpapi->backlinks($page, 500, $continue);
        foreach ($bl as $data) {
            $pagelist[] = $data['title'];
        }
        while (count($bl) >= 500) {
            $bl = $wpapi->backlinks($page, 500, $continue);
            foreach ($bl as $data) {
                $pagelist[] = $data['title'];
            }
        }

        $forktasklist = array();
        $count = 0;
        foreach ($pagelist as $title) {
            ++$count;
            $group = floor($count / 500);
            $forktasklist[$group][] = $title;
        }
        unset($pagelist);

        for ($i = 0; $i < count($forktasklist); ++$i) {
            foreach ($forktasklist[$i] as $title) {
                $data = $wpq->getpage($title);
                $newdata = str_replace($search, $replace, $data);
                if ($data != $newdata) {
                    $wpapi->edit($title, $newdata, 'Fixing links to archived content. (BOT)', true, true);
                }
            }
        }
    }
    if ($noindex != 1) {
        generateindex($page, $archiveprefix, $level);
    }
}

function generateindex($origpage, $archiveprefix, $level)
{
    global $logger;
    global $user;
    global $wpapi;
    global $wpi;

    $tmp = extractnamespace($archiveprefix);
    $array = $wpapi->listprefix($tmp[1], namespacetoid($tmp[0]), 500);
    if (is_array($array)) {
        $data = '';
        $ddata = '{|class="wikitable sortable"' . "\n" . '! Order !! Header !!' .
                 ' Start Date !! End Date !! Comments !! Size !! Archive' . "\n";
        $newarray = array();
        foreach ($array as $page) {
            $tmp = $wpapi->revisions($page['title'], 1, 'newer');
            $newarray[$page['title']] = $tmp[0]['timestamp'];
        }
        asort($newarray);
        foreach ($newarray as $page => $time) {
            $data .= '* [[' . $page . '|' . str_replace($archiveprefix, '', $page) . ']]' . "\n";
            $ddata .= '{{User:' . $user . '/Detailed Indices/' . $page . '}}' . "\n";
            generatedetailedindex($page, $level);
        }
        $ddata .= '|}';
        $wpapi->edit(
            'User:' . $user . '/Indices/' . $origpage,
            $data,
            'Setting index for [[' . $origpage . ']]. (BOT)'
        );
        $wpapi->edit(
            'User:' . $user . '/Master Detailed Indices/' . $origpage,
            $ddata,
            'Setting detailed index for [[' . $origpage . ']]. (BOT)'
        );
    }
}

function generatedetailedindex($apage, $level, $adata = null, $ret = false)
{
    global $user;
    global $wpq;
    global $wpi;
    global $wpapi;

    $i = 1;

    $version = '1.1';

    if ($adata === null) {
        $adata = $wpq->getpage($apage);
    }

    $checksum = md5(md5($version) . md5($adata));

    $cdata = $wpq->getpage('User:' . $user . '/Detailed Indices/' . $apage);
    if (preg_match('/\<\!-- CB3 MD5:([0-9a-f]{32}) --\>/i', $cdata, $m)) {
        if (trim(strtolower($m[1])) == trim(strtolower($checksum))) {
            return;
        }
    }

    $sects = splitintosections($adata, $level);
    $data = '';
    unset($sects[0]);
    $header = '<!-- CB3 MD5:' . trim($checksum) . ' -->' . "\n" . '{|class="wikitable sortable"' .
              "\n" . '! Order !! Header !! Start Date !! End Date !! Comments !! Size !! Archive' . "\n";

    foreach ($sects as $sect) {
        $data .= '|-' . "\n" . '| ' . $i . ' || ' . trim($sect['header']) . ' || ';
        if (
            preg_match_all(
                '/(\d{2}):(\d{2}), (\d+) ([a-zA-Z]+) (\d{4}) \(UTC\)/i',
                $sect['content'],
                $dates,
                PREG_SET_ORDER
            )
        ) {
            $times = array();
            $month = array('January' => 1, 'February' => 2, 'March' => 3,
                'April' => 4, 'May' => 5, 'June' => 6, 'July' => 7,
                'August' => 8, 'September' => 9, 'October' => 10,
                'November' => 11, 'December' => 12,
                );
            foreach ($dates as $date) {
                $times[] = gmmktime($date[1], $date[2], 0, $month[$date[4]], $date[3], $date[5]);
            }
            sort($times, SORT_NUMERIC);
            $data .= gmdate('Y-m-d H:i', $times[0]) . ' || ' .
                     gmdate('Y-m-d H:i', $times[count($times) - 1]) . ' || ' . count($times);
        } else {
            $data .= 'Unknown || Unknown || Unknown';
        }
        $data .= ' || ' . strlen($sect['content']) . ' || [[' . $apage . '#' .
                 str_replace(
                     array('[[', ']]', "'''", "''", '{{', '}}', '|'),
                     '',
                     trim($sect['header'])
                 ) . '|' . $apage . ']]' . "\n";
        ++$i;
    }
    $footer = '|}';
    if (!$ret) {
        $wpapi->edit(
            'User:' . $user . '/Detailed Indices/' . $apage,
            '<noinclude>' .
                     $header . '</noinclude>' . $data . '<noinclude>' . $footer . '</noinclude>',
            'Updating detailed index for [[' . $apage . ']]. (BOT)'
        );
    }

    return $data;
}

function parsetemplate($page)
{
    global $logger;
    global $wpq;
    global $wpapi;
    global $user;

    $pagedata = $wpq->getpage($page);

    $positions = array();

    $x = 0;
    while (($x = stripos($pagedata, '{{user:' . $user . '/archivethis', $x)) !== false) {
        $positions[] = $x;
        ++$x;
    }

    foreach ($positions as $pkey => $x) {
        $set = array();
        $data = substr($pagedata, $x);
        $pos = 1;
        $depth = 1;
        $q = 0;
        $part = 0;
        $tmp = array('{');
        $tmp2 = array();
        while (($depth != 0) and ($pos < strlen($data))) {
            if (isset($tmp[$part])) {
                $tmp[$part] .= substr($data, $pos, 1);
            } else {
                $tmp[$part] = substr($data, $pos, 1);
            }
            if (!$q) {
                if (substr($data, $pos, 1) == '{') {
                    ++$depth;
                }
                if (substr($data, $pos, 1) == '}') {
                    --$depth;
                }
                if ((substr($data, $pos, 1) == '|') or ($depth == 0)) {
                    if ($depth == 0) {
                        $tmp[$part] = substr($tmp[$part], 0, -1);
                    }
                    $tmp[$part] = substr($tmp[$part], 0, -1);
                    $part = 0;
                    if (!isset($tmp[1])) {
                        $tmp2[] = $tmp[0];
                    } else {
                        $tmp2[strtolower(trim($tmp[0]))] = rtrim($tmp[1]);
                    }
                    unset($tmp);
                    $tmp = array();
                }
                if ($data[$pos] == '=') {
                    if ($part == 0) {
                        $tmp[$part] = substr($tmp[$part], 0, -1);
                        $part = 1;
                    }
                }
                if (substr($data, $pos, 8) == '<nowiki>') {
                    $tmp[$part] = substr($tmp[$part], 0, -1);
                    $q = 1;
                    $pos += 7;
                }
            }
            if (substr($data, $pos, 9) == '</nowiki>') {
                $tmp[$part] = substr($tmp[$part], 0, -1);
                $q = 0;
                $pos += 8;
            }
            ++$pos;
        }
        $positions[$pkey] = array($x, $pos);
        $data = $tmp2;
        unset($pos, $depth, $tmp, $x, $q, $tmp2, $part);

        unset($data[0]);
        $set = $data;
        if ((isset($set['once']) ? trim($set['once']) : 0) == 1) {
            $wpapi->edit(
                $page,
                substr($pagedata, 0, $positions[$pkey][0]) .
                                '<!-- ' . substr(
                                    $pagedata,
                                    $positions[$pkey][0],
                                    $positions[$pkey][1]
                                ) . ' -->' . substr($pagedata, $positions[$pkey][0] + $positions[$pkey][1]),
                'Commenting out config. (BOT)',
                true,
                true
            );
            sleep(3);
        }
        $logger->addInfo('doarchive(' . $page . ','
            . $set['archiveprefix'] . ','
            . $set['format'] . ','
            . $set['age'] . ','
            . (isset($set['minarchthreads']) ? $set['minarchthreads'] : 0) . ','
            . (isset($set['minkeepthreads']) ? $set['minkeepthreads'] : 0) . ','
            . (isset($set['header']) ? $set['header'] : '{{Talkarchive}}') . ','
            . (isset($set['archivenow']) ? $set['archivenow'] : '{{User:ClueBot III/ArchiveNow}}') . ','
            . (isset($set['headerlevel']) ? $set['headerlevel'] : 2) . ','
            . (isset($set['nogenerateindex']) ? $set['nogenerateindex'] : 0) . ','
            . (isset($set['maxkeepthreads']) ? $set['maxkeepthreads'] : 0) . ','
            . (isset($set['maxkeepbytes']) ? $set['maxkeepbytes'] : 0) . ','
            . (isset($set['transformheader']) ? $set['transformheader'] : '') . ','
            . (isset($set['maxarchsize']) ? $set['maxarchsize'] : 0) . ','
            . (isset($set['numberstart']) ? $set['numberstart'] : 1) . ','
            . (isset($set['key']) ? $set['key'] : '')
            . ')');
        if ($pkey > 0) {
            sleep(2);
        }
        doarchive(
            $page,
            $set['archiveprefix'],
            $set['format'],
            $set['age'],
            (isset($set['minarchthreads']) ? $set['minarchthreads'] : 0),
            (isset($set['minkeepthreads']) ? $set['minkeepthreads'] : 0),
            (isset($set['header']) ? $set['header'] : '{{Talkarchive}}'),
            (isset($set['archivenow']) ? explode(',', $set['archivenow']) : array('{{User:ClueBot III/ArchiveNow}}')),
            (isset($set['headerlevel']) ? $set['headerlevel'] : 2),
            (isset($set['nogenerateindex']) ? $set['nogenerateindex'] : 0),
            (isset($set['maxkeepthreads']) ? $set['maxkeepthreads'] : 0),
            (isset($set['maxkeepbytes']) ? $set['maxkeepbytes'] : 0),
            (isset($set['transformheader']) ? $set['transformheader'] : ''),
            (isset($set['maxarchsize']) ? $set['maxarchsize'] : 0),
            (isset($set['numberstart']) ? $set['numberstart'] : 1),
            (isset($set['key']) ? $set['key'] : '')
        );
    }
}
