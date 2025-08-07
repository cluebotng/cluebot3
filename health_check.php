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
$logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::INFO));

// API
$wph = new \Wikipedia\Http($logger);
$wpapi = new \Wikipedia\Api($wph, $logger);

/* Get our last edit time */
$usercontribs = $wpapi->usercontribs($user, 1);
if (count($usercontribs) != 1) {
    $logger->addError('Failed to find usercontribs for ' . $user);
    exit(1);
}
$last_contrib_timestamp = $usercontribs[0]['timestamp'];

 /* If we edited within the last 24 hours, then all good */
if (strtotime($last_contrib_timestamp) > (time() - 86400)) {
    $logger->addInfo('Last contribution was within last 24h (' . $last_contrib_timestamp . ')');
    exit(0);
}

/* Get out uptime, since this is for a container, just check the 'init' pid */
$current_uptime = filemtime("/proc/1");

/* If we have been running for less than 6 hours, then all good (back off) */
if ($current_uptime > (time() - 21600)) {
$logger->addInfo('Uptime less than 6 hours (' . $current_uptime . ')');
exit(0);
}

/* Otherwise, we need to die */
$logger->addError('Are you death or paradise? ' . $last_contrib_timestamp . ' / ' . $current_uptime);
exit(1);
