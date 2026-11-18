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
$wpapi = new \Wikipedia\Api($wph, $logger);

if (!$wpapi->login(Config::$user, Config::$pass)) {
    die("Failed to authenticate\n");
}

foreach (get_target_titles() as $title) {
    print $title . "\n";
}
