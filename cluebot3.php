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

// In a build pack log to stderr (no NFS), else log to disk (NFS)
if (getenv('NO_HOME')) {
    $logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Logger::INFO));
} else {
    $logger->pushHandler(
        new \Monolog\Handler\RotatingFileHandler(
            getenv('HOME') . '/logs/cluebot3.log',
            2,
            \Monolog\Logger::INFO,
            true,
            0600,
            false
        )
    );
}

$wph = new \Wikipedia\Http($logger);
$wpq = new \Wikipedia\Query($wph, $logger);
$wpi = new \Wikipedia\Index($wph, $logger);
$wpapi = new \Wikipedia\Api($wph, $logger);

while (true) {
    if (!$wpapi->login($user, $pass)) {
        die('Failed to authenticate');
    }

    $titles = array();
    $continue = null;
    $ei = $wpapi->embeddedin('User:' . $user . '/ArchiveThis', 500, $continue);
    if ($ei != null) {
        foreach ($ei as $data) {
            $titles[] = $data['title'];
        }
        while (isset($ei[499])) {
            $ei = $wpapi->embeddedin('User:' . $user . '/ArchiveThis', 500, $continue);
            if ($ei != null) {
                foreach ($ei as $data) {
                    $titles[] = $data['title'];
                }
            }
        }
    }

    $logger->addInfo("Processing " . count($titles) . " titles");
    shuffle($titles);
    foreach ($titles as $title) {
        parsetemplate($title);
    }

    $logger->addInfo("Sleeping until next execution");
    $start_time = time();
    while ((time() - $start_time) < 21600) {
        sleep(1);
    }
}
