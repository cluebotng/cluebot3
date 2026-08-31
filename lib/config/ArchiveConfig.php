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

namespace ClueBot3\UserConfig;

class ArchiveConfig
{
    public string $archiveprefix;
    public string $format;
    public int $age;
    public int $minarchthreads;
    public int $minkeepthreads;
    public string $header;
    public array $archivenow;
    public int $headerlevel;
    public int $nogenerateindex;
    public int $maxkeepthreads;
    public int $maxkeepbytes;
    public string $transformheader;
    public int $maxarchsize;
    public int $numberstart;
    public string $key;

    // This is a flag which causes the config to be removed on the archiving run
    public bool $once = false;
    public bool $rewrite = false;

    // Internal flag
    public bool $is_valid = false;

    // Note: these keys are not used by the bot, but are used in User:ClueBot_III/ArchiveThis
    //       we need to keep the settings to ensure the correct behaviour of the transcluded page
    public bool $archivebox;
    public string $box_width;
    public bool $box_advert;
    public bool $box_separator;
    public string $image;
    public string $image_width;
    public bool $search;
    public string $talkcolor;
    public string $talkcolour;
    public bool $index;

    public function __construct()
    {
        $this->age = DefaultConfig::$age;
        $this->format = DefaultConfig::$format;
        $this->minarchthreads = DefaultConfig::$minarchthreads;
        $this->minkeepthreads = DefaultConfig::$minkeepthreads;
        $this->header = DefaultConfig::$header;
        $this->archivenow = DefaultConfig::$archivenow;
        $this->headerlevel = DefaultConfig::$headerlevel;
        $this->nogenerateindex = DefaultConfig::$nogenerateindex;
        $this->maxkeepthreads = DefaultConfig::$maxkeepthreads;
        $this->maxkeepbytes = DefaultConfig::$maxkeepbytes;
        $this->transformheader = DefaultConfig::$transformheader;
        $this->maxarchsize = DefaultConfig::$maxarchsize;
        $this->numberstart = DefaultConfig::$numberstart;
        $this->key = DefaultConfig::$key;

        $this->index = DefaultConfig::$index;
        $this->archivebox = DefaultConfig::$archivebox;
        $this->box_width = DefaultConfig::$box_width;
        $this->box_advert = DefaultConfig::$box_advert;
        $this->box_separator = DefaultConfig::$box_separator;
        $this->image = DefaultConfig::$image;
        $this->image_width = DefaultConfig::$image_width;
        $this->search = DefaultConfig::$search;
        $this->talkcolor = DefaultConfig::$talkcolor;
        $this->talkcolour = DefaultConfig::$talkcolour;
    }

    public function toWiki()
    {
        if ($this->is_valid) {
            $config = "{{User:ClueBot III/ArchiveThis\n";
            $config .= '|archiveprefix=' . $this->archiveprefix . "\n";
            if ($this->format != DefaultConfig::$format) {
                $config .= '|format=' . $this->format . "\n";
            }
            if ($this->age != DefaultConfig::$age) {
                $config .= '|age=' . $this->age . "\n";
            }
            if ($this->minarchthreads != DefaultConfig::$minarchthreads) {
                $config .= '|minarchthreads=' . $this->minarchthreads . "\n";
            }
            if ($this->minkeepthreads != DefaultConfig::$minkeepthreads) {
                $config .= '|minkeepthreads=' . $this->minkeepthreads . "\n";
            }
            if ($this->header != DefaultConfig::$header) {
                $config .= '|header=' . $this->header . "\n";
            }
            if ($this->archivenow != DefaultConfig::$archivenow) {
                $config .= '|archivenow=' . implode(",", $this->archivenow) . "\n";
            }
            if ($this->headerlevel != DefaultConfig::$headerlevel) {
                $config .= '|headerlevel=' . $this->headerlevel . "\n";
            }
            if ($this->nogenerateindex != DefaultConfig::$nogenerateindex) {
                $config .= '|nogenerateindex=' . $this->nogenerateindex . "\n";
            }
            if ($this->maxkeepthreads != DefaultConfig::$maxkeepthreads) {
                $config .= '|maxkeepthreads=' . $this->maxkeepthreads . "\n";
            }
            if ($this->maxkeepbytes != DefaultConfig::$maxkeepbytes) {
                $config .= '|maxkeepbytes=' . $this->maxkeepbytes . "\n";
            }
            if ($this->transformheader != DefaultConfig::$transformheader) {
                $config .= '|transformheader=' . $this->transformheader . "\n";
            }
            if ($this->maxarchsize != DefaultConfig::$maxarchsize) {
                $config .= '|maxarchsize=' . $this->maxarchsize . "\n";
            }
            if ($this->numberstart != DefaultConfig::$numberstart) {
                $config .= '|numberstart=' . $this->numberstart . "\n";
            }
            if ($this->key != DefaultConfig::$key) {
                $config .= '|key=' . $this->key . "\n";
            }

            if ($this->archivebox != DefaultConfig::$archivebox) {
                $config .= '|archivebox=' . ($this->archivebox ? 'yes' : 'no') . "\n";
            }
            if ($this->archivebox) {
                if ($this->box_width != DefaultConfig::$box_width) {
                    $config .= '|box-width=' . $this->box_width . "\n";
                }
                if ($this->box_advert != DefaultConfig::$box_advert) {
                    $config .= '|box-advert=' . ($this->box_advert ? 'yes' : 'no') . "\n";
                }
                if ($this->box_separator != DefaultConfig::$box_separator) {
                    $config .= '|box-separator=' . ($this->box_separator ? 'yes' : 'no') . "\n";
                }
                if ($this->image != DefaultConfig::$image) {
                    $config .= '|image=' . $this->image . "\n";
                }
                if ($this->image_width != DefaultConfig::$image_width) {
                    $config .= '|image-width=' . $this->image_width . "\n";
                }
                if ($this->search != DefaultConfig::$search) {
                    $config .= '|search=' . ($this->search ? 'yes' : 'no') . "\n";
                }
                if ($this->talkcolor != DefaultConfig::$talkcolor) {
                    $config .= '|talkcolor=' . $this->talkcolor . "\n";
                }
                if ($this->talkcolour != DefaultConfig::$talkcolour) {
                    $config .= '|talkcolour=' . $this->talkcolour . "\n";
                }
            }
            if ($this->index != DefaultConfig::$index) {
                $config .= '|index=' . ($this->index ? 'yes' : 'no') . "\n";
            }
            if ($this->once) {
                $config .= "|once=1\n";
            }

            $config .= "}}";
            return $config;
        }
        return null;
    }
}
