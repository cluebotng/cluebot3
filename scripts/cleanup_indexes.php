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

require_once __DIR__ . '/../lib/bot.php';
require_once __DIR__ . '/../lib/indexes.php';
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../cluebot3.config.php';

date_default_timezone_set('Europe/London');
include __DIR__ . '/../vendor/autoload.php';

Config::init();

// Logger
$logger = new \Monolog\Logger('cluebot3');
$logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Logger::INFO));

$wph = new \Wikipedia\Http($logger);
$wpq = new \Wikipedia\Query($wph, $logger);
$wpapi = new \Wikipedia\Api($wph, $logger);

if (!$wpapi->login(Config::$user, Config::$pass)) {
    die("Failed to authenticate\n");
}

$target_titles = get_target_titles();
$logger->info("Found " . count($target_titles) . " titles");

// Cleanup top level indexes - this is a 1:1 mapping with the page title
foreach (get_master_indexes() as $page_title => $index_titles) {
    if (!in_array($page_title, $target_titles)) {
        foreach ($index_titles as $index_title) {
            $logger->info('Cleaning up old master index: ' . $page_title . ' (' . $index_title . ')');
            $wpapi->edit(
                $index_title,
                '{{db-u1}}',
                'Removing old index page. (BOT)',
                true,
                true
            );
        }
    }
}
