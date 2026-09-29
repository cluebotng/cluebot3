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

class Config
{
    public static $user = 'ClueBot III';
    public static $pass = '';

    // Pages mapped to the list of archive prefixes they are allowed to additionally use.
    // For example, an entry of `'User_talk:DamianZaremba_Scripts' => ['User_talk:DamianZaremba']`,
    // would allow an archive prefix of `User_talk:DamianZaremba` in addition to `User_talk:DamianZaremba_Scripts`.
    public static $allowed_archive_prefixes = [
        'User_talk:DamianZaremba_Scripts' => ['User_talk:DamianZaremba'],
    ];

    public static function init()
    {
        if ($bot_password = getenv('CLUEBOT3_BOT_PASSWORD')) {
            self::$pass = $bot_password;
        }
    }
}
