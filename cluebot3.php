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
$logger = new \Monolog\Logger('cluebot3');
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

$wph = new \Wikipedia\Http($logger);
$wpq = new \Wikipedia\Query($wph, $logger);
$wpi = new \Wikipedia\Index($wph, $logger);
$wpapi = new \Wikipedia\Api($wph, $logger);

if (!$wpapi->login($user, $pass)) {
    die('Failed to authenticate');
}

while (1) {
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

    foreach ($titles as $title) {
        parsetemplate($title);
    }
    $time = time();
    while ((time() - $time) < 21600) {
        sleep(1);
    }
}
