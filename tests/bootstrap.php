<?php

require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../cluebot3.config.php';

\ClueBot3\Config::init();
$GLOBALS['logger'] = new \Psr\Log\NullLogger();
