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

require_once 'lib/bot.php';
require_once 'cluebot3.config.php';

date_default_timezone_set('Europe/London');
include 'vendor/autoload.php';

// Logger
$logger = new \Monolog\Logger('cluebot3');
$logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Logger::DEBUG));

$wikiLogger = new \Monolog\Logger('cluebot3.wikipedia');
$wikiLogger->pushHandler(new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Logger::INFO));

$wph = new \Wikipedia\Http($wikiLogger);
$wpq = new \Wikipedia\Query($wph, $wikiLogger);
$wpi = new \Wikipedia\Index($wph, $wikiLogger);
$wpapi = new \Wikipedia\Api($wph, $wikiLogger);

if (!$wpapi->login($user, $pass)) {
    die('Failed to authenticate\n');
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

$logger->info("Processing " . $target_title);
parsetemplate($target_title);
