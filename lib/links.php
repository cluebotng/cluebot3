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

function fixlinks($pagelist, $search, $replace)
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
                $logger->error('Failed to decode API response');
                break;
            }
            if (isset($revisionData['error'])) {
                $logger->error('API Error: ' . var_export($revisionData['error'], true));
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
                    $wpapi->edit($title, $newdata, 'Fixing links to archived content. (BOT)', true, true);
                }
            }

            $continue = $revisionData['continue'] ?? array();
        } while (!empty($continue));
    }
}
