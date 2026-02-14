<?php

/*
 * Copyright (C) 2025 Jacobi Carter
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

namespace ClueBot3\Config;

class DefaultConfig
{
    public static int $age = 0;
    public static string $format = "";
    public static int $minarchthreads = 0;
    public static int $minkeepthreads = 0;
    public static string $header = '{{Talkarchive}}';
    public static array $archivenow = ['{{User:ClueBot III/ArchiveNow}}'];
    public static int $headerlevel = 2;
    public static int $nogenerateindex = 0;
    public static int $maxkeepthreads = 0;
    public static int $maxkeepbytes = 2;
    public static string $transformheader = '';
    public static int $maxarchsize = 0;
    public static int $numberstart = 1;
    public static string $key = '';

    public static bool $archivebox = false;
    public static string $box_width = "238px";
    public static bool $box_advert = false;
    public static bool $box_separator = true;
    public static string $image = "[[File:Crystal Clear app file-manager.png]]";
    public static string $image_width = "40px";
    public static bool $search = true;
    public static string $talkcolor = '';
    public static string $talkcolour = '';
    public static bool $index = false;
}
