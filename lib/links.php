<?php

/*
 * Copyright (C) 2026 Jacobi Carter
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

function buildlinkreplacements($page, $apage, $archsects, &$search, &$replace)
{
    foreach ($archsects as $header => $data) {
        $anchor = str_replace('%', '.', urlencode(str_replace(' ', '_', $header)));
        $newanchor = str_replace('%', '.', urlencode(str_replace(' ', '_', trim($data['header']))));
        addlinkreplacement($page . '#' . $anchor, $apage . '#' . $newanchor, $search, $replace);
        addlinkreplacement(
            $page . '#' . str_replace('.20', '_', $anchor),
            $apage . '#' . str_replace('.20', '_', $newanchor),
            $search,
            $replace
        );
        addlinkreplacement($page . '#' . $header, $apage . '#' . trim($data['header']), $search, $replace);
    }
}

function addlinkreplacement($from, $to, &$search, &$replace)
{
    if (!isset($search[$from])) {
        $search[$from] = $from;
        $replace[$from] = $to;
    }
}

function getbacklinks($page)
{
    global $wpapi;

    $pagelist = array();
    $continue = null;
    do {
        foreach ($wpapi->backlinks($page, 500, $continue) as $data) {
            $pagelist[] = $data['title'];
        }
    } while ($continue !== null);

    return $pagelist;
}

function fixlinks($page, $pagelist, $search, $replace)
{
    global $logger;
    global $wpapi;
    global $wph;

    foreach (array_chunk($pagelist, 500) as $titles) {
        $params = array(
            'action' => 'query',
            'prop' => 'revisions',
            'rvslots' => 'main',
            'rvprop' => 'content',
            'format' => 'json',
            'titles' => implode('|', $titles),
        );
        $continue = array();
        do {
            // We expect/handle partial content, which the API response warns about
            // To avoid logging the warning, decode the response inline here rather than using unserialize
            $revisionData = json_decode(
                $wph->post($wpapi->apiurl, http_build_query(array_merge($params, $continue))),
                true
            );
            if (!is_array($revisionData)) {
                $logger->error("[" . $page . "] Failed to decode API response for backlink revisions");
                break;
            }
            if (isset($revisionData['error'])) {
                $logger->error(
                    "[" . $page . "] API error for backlink revisions: " .
                    var_export($revisionData['error'], true)
                );
                break;
            }

            foreach ($revisionData['query']['pages'] ?? [] as $pageData) {
                if (!isset($pageData['revisions'][0]['slots']['main']['*'])) {
                    continue;
                }

                $title = $pageData['title'];
                $data = $pageData['revisions'][0]['slots']['main']['*'];
                $newdata = str_replace($search, $replace, $data);
                if ($data != $newdata) {
                    $logger->info("[" . $page . "] Updating backlinks on " . $title);
                    $wpapi->edit($title, $newdata, 'Fixing links to archived content. (BOT)', true, true);
                }
            }

            $continue = $revisionData['continue'] ?? array();
        } while (!empty($continue));
    }
}

function fixlinks_parallel($page, $pagelist, $search, $replace)
{
    global $logger;

    $workers = min(Config::$link_update_workers, (int)ceil(count($pagelist) / 500));
    if ($workers <= 1 || !function_exists('pcntl_fork')) {
        fixlinks($page, $pagelist, $search, $replace);
        return;
    }

    $pids = array();
    foreach (array_chunk($pagelist, (int)ceil(count($pagelist) / $workers)) as $batch) {
        $pid = pcntl_fork();
        if ($pid == -1) {
            $logger->error("Failed to fork");
            die();
        }
        if ($pid != 0) {
            // Parent
            $logger->debug("Created fork with " . $pid);
            $pids[] = $pid;
        } else {
            // Child
            fixlinks($page, $batch, $search, $replace);
            die();
        }
    }

    $logger->info("[" . $page . "] waiting on " . count($pids) . " link update workers");
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
    }
}

function get_archive_titles($archiveprefix)
{
    global $wpapi;

    $tmp = extractnamespace($archiveprefix);
    $titles = array();
    $continue = null;
    do {
        foreach ($wpapi->listprefix($tmp[1], namespacetoid($tmp[0]), 500, $continue) as $data) {
            $titles[] = $data['title'];
        }
    } while ($continue !== null);
    return $titles;
}

function sort_titles_newest_first($titles)
{
    global $wpapi;

    $created = array();
    foreach ($titles as $title) {
        $tmp = $wpapi->revisions($title, 1, 'newer');
        $created[$title] = $tmp[0]['timestamp'] ?? '';
    }
    arsort($created);
    return array_keys($created);
}
