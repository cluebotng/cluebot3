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

function get_master_indexes()
{
    global $wpapi;
    $user_namespace = namespacetoid("User");
    $page_prefixes = [
        Config::$user . '/Master Detailed Indices/',
        Config::$user . '/Indices/',
    ];

    $index_titles = [];
    foreach ($page_prefixes as $prefix) {
        $prefix_regex = '/^' . preg_quote('User:' . $prefix, '/') . '/';
        $continue = null;
        do {
            foreach ($wpapi->listprefix($prefix, $user_namespace, 500, $continue) as $page) {
                $title = preg_replace($prefix_regex, '', $page['title']);
                if (!array_key_exists($title, $index_titles)) {
                    $index_titles[$title] = [];
                }
                $index_titles[$title][] = $page['title'];
            }
        } while ($continue !== null);
    }

    return $index_titles;
}

function get_detailed_indexes()
{
    global $wpapi;
    $user_namespace = namespacetoid("User");
    $prefix_regex = '/^' . preg_quote('User:' . Config::$user . '/Detailed Indices/', '/') . '/';

    $index_titles = [];
    $continue = null;
    do {
        foreach ($wpapi->listprefix(Config::$user . '/Detailed Indices/', $user_namespace, 500, $continue) as $page) {
            $title = preg_replace($prefix_regex, '', $page['title']);
            $index_titles[$title] = $page['title'];
        }
    } while ($continue !== null);

    return $index_titles;
}
