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

require_once __DIR__ . '/../lib/bot.php';
require_once __DIR__ . '/../lib/links.php';
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../cluebot3.config.php';

date_default_timezone_set('Europe/London');
include __DIR__ . '/../vendor/autoload.php';

Config::init();

// Logger
$logger = new \Monolog\Logger('cluebot3');
$logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Logger::INFO));

$wikiLogger = new \Monolog\Logger('cluebot3.wikipedia');
$wikiLogger->pushHandler(new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Logger::INFO));

$wph = new \Wikipedia\Http($wikiLogger);
$wpq = new \Wikipedia\Query($wph, $wikiLogger);
$wpapi = new \Wikipedia\Api($wph, $wikiLogger);

if (!$wpapi->login(Config::$user, Config::$pass)) {
    die("Failed to authenticate\n");
}

$target_title = $argv[1] ?? null;
if (!$target_title) {
    die("Usage: " . $argv[0] . " <title>\n");
}

if (getenv("BYPASS_SAFETY_CHECK") != "1") {
    $configured_titles = get_target_titles();
    if (!in_array($target_title, $configured_titles)) {
        die("Not found in configured titles: " . $target_title . "\n");
    }
}

if (!$pagedata = $wpq->getpage($target_title)) {
    die("Failed to get page content: " . $target_title . "\n");
}

$search = array();
$replace = array();
foreach (UserConfig\find_config_blocks(Config::$user, $pagedata) as $config_block) {
    $config = UserConfig\build_config_from_config_block($target_title, $config_block);
    if (!$config->is_valid) {
        $logger->warning("Skipping invalid config on " . $target_title . ": " . $config_block->text);
        continue;
    }
    if ($config->transformheader != '') {
        $logger->warning("[" . $target_title . "] config uses transformheader, links using " .
                         "the original (untransformed) headers will not be fixed");
    }

    $titles = sort_titles_newest_first(get_archive_titles($config->archiveprefix));
    $logger->info("[" . $target_title . "] found " . count($titles) . " archives under " . $config->archiveprefix);

    // Sections still on the page should keep their links
    $seen = array();
    foreach (splitintosections($pagedata, $config->headerlevel) as $id => $section) {
        if ($id !== 0) {
            $seen[trim($section['header'])] = $target_title;
        }
    }

    foreach ($titles as $apage) {
        $archsects = array();
        foreach (splitintosections($wpq->getpage($apage) ?? '', $config->headerlevel) as $id => $section) {
            if ($id === 0) {
                continue;
            }

            // Prefer the most recent archive when a header has been archived multiple times
            $header = trim($section['header']);
            if (isset($seen[$header])) {
                $logger->debug("[" . $target_title . "] skipping '" . $header . "' in " . $apage .
                               ", already mapped to " . $seen[$header]);
                continue;
            }
            $seen[$header] = $apage;
            $archsects[$header] = $section;
        }

        $logger->info("[" . $target_title . "] found " . count($archsects) . " sections in " . $apage);
        buildlinkreplacements($target_title, $apage, $archsects, $search, $replace);
    }
}

$pagelist = getbacklinks($target_title);
$logger->info("[" . $target_title . "] found " . count($pagelist) . " pages to check links on");

fixlinks_parallel($target_title, $pagelist, $search, $replace);
$logger->info("[" . $target_title . "] finished fixing links");
