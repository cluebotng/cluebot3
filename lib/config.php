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

class DefaultConfig {
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

class ArchiveConfig {
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

    public function is_valid() {
        if (
            !empty($this->age ?? '') &&
            !empty($this->archiveprefix ?? '') &&
            !empty($this->format ?? '')
        ) {
            return true;
        }
        return false;
    }

    public function to_wiki() {
        if ($this->is_valid()) {
            $config = "{{User:ClueBot III/ArchiveThis\n";
            $config .= '|archiveprefix=' . $this->archiveprefix . "\n";
            $config .= '|format=' . $this->format . "\n";
            $config .= '|age=' . $this->age . "\n";
            if ($this->minarchthreads != DefaultConfig::$minarchthreads) { $config .= '|minarchthreads=' . $this->minarchthreads . "\n"; }
            if ($this->minkeepthreads != DefaultConfig::$minkeepthreads) { $config .= '|minkeepthreads=' . $this->minkeepthreads . "\n"; }
            if ($this->header != DefaultConfig::$header) { $config .= '|header=' . $this->header . "\n"; }
            if ($this->archivenow != DefaultConfig::$archivenow) { $config .= '|archivenow=' . implode(",", $this->archivenow) . "\n"; }
            if ($this->headerlevel != DefaultConfig::$headerlevel) { $config .= '|headerlevel=' . $this->headerlevel . "\n"; }
            if ($this->nogenerateindex != DefaultConfig::$nogenerateindex) { $config .= '|nogenerateindex=' . $this->nogenerateindex . "\n"; }
            if ($this->maxkeepthreads != DefaultConfig::$maxkeepthreads) { $config .= '|maxkeepthreads=' . $this->maxkeepthreads . "\n"; }
            if ($this->maxkeepbytes != DefaultConfig::$maxkeepbytes) { $config .= '|maxkeepbytes=' . $this->maxkeepbytes . "\n"; }
            if ($this->transformheader != DefaultConfig::$transformheader) { $config .= '|transformheader=' . $this->transformheader . "\n"; }
            if ($this->maxarchsize != DefaultConfig::$maxarchsize) { $config .= '|maxarchsize=' . $this->maxarchsize . "\n"; }
            if ($this->numberstart != DefaultConfig::$numberstart) { $config .= '|numberstart=' . $this->numberstart . "\n"; }
            if ($this->key != DefaultConfig::$key) { $config .= '|key=' . $this->key . "\n"; }

            if ($this->archivebox != DefaultConfig::$archivebox) { $config .= '|archivebox=' . ($this->archivebox ? 'yes' : 'no') . "\n"; }
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
                if ($this->image != DefaultConfig::$image) { $config .= '|image=' . $this->image . "\n"; }
                if ($this->image_width != DefaultConfig::$image_width) { $config .= '|image-width=' . $this->image_width . "\n"; }
                if ($this->search != DefaultConfig::$search) { $config .= '|search=' . ($this->search ? 'yes' : 'no') . "\n"; }
                if ($this->talkcolor != DefaultConfig::$talkcolor) { $config .= '|talkcolor=' . $this->talkcolor . "\n"; }
                if ($this->talkcolour != DefaultConfig::$talkcolour) { $config .= '|talkcolour=' . $this->talkcolour . "\n"; }
            }
            if ($this->index != DefaultConfig::$index) { $config .= '|index=' . ($this->index ? 'yes' : 'no') . "\n"; }
            if ($this->once) { $config .= "|once=1\n"; }

            $config .= "}}";
            return $config;
        }
        return null;
    }
}

class RawConfig {
    public int $start_position;
    public int $end_position;
    public string $text;

    public function __construct($start_position, $end_position, $text)
    {
        $this->start_position = $start_position;
        $this->end_position = $end_position;
        $this->text = $text;
    }
}

function find_config_blocks($user, $text) {
    $config_blocks = [];

    $start_position = 0;
    while (($start_position = stripos($text, '{{user:' . $user . '/archivethis', $start_position)) !== false) {
        $data = substr($text, $start_position);
        $block_depth = 1;
        $block_position = 1;
        $ignore_block = false;
        while (($block_depth != 0) and ($block_position < strlen($data))) {
            if (!$ignore_block) {
                if (substr($data, $block_position, 1) == '{') {
                    ++$block_depth;
                }
                if (substr($data, $block_position, 1) == '}') {
                    --$block_depth;
                }
                if ($block_depth == 0) {
                    $config_blocks[] = new RawConfig(
                        $start_position,
                        $block_position + 1,
                        substr($text, $start_position, $block_position + 1)
                    );
                }
                if (substr($data, $block_position, 8) == '<nowiki>') {
                    $ignore_block = true;
                    $block_position += 7;
                }
            }
            if (substr($data, $block_position, 9) == '</nowiki>') {
                $ignore_block = false;
                $block_position += 8;
            }
            ++$block_position;
        }
        ++$start_position;
    }

    return $config_blocks;
}

function parse_config_block($text) {
    // Start at pos 1 (skipping first {) and block_depth 1
    // After seeing second {, block_depth becomes 2 (outer template level)
    // Nested templates are at block_depth >= 3
    $pos = $block_depth = 1;
    $ignore_block = false;
    $in_value = false;
    $key = $value = "";
    $config = [];

    while ($pos < strlen($text)) {
        $char = $text[$pos];

        // Handle nowiki blocks - content inside is literal
        if ($ignore_block) {
            if (substr($text, $pos, 9) == '</nowiki>') {
                $ignore_block = false;
                $pos += 9;
                continue;
            }
            if ($in_value) {
                $value .= $char;
            } else {
                $key .= $char;
            }
            ++$pos;
            continue;
        }

        // Start of nowiki block - skip the tag
        if (substr($text, $pos, 8) == '<nowiki>') {
            $ignore_block = true;
            $pos += 8;
            continue;
        }

        // Handle template opening brace
        if ($char == '{') {
            ++$block_depth;
            // Append if we're inside a nested template (depth >= 3)
            if ($block_depth >= 3) {
                if ($in_value) {
                    $value .= $char;
                } else {
                    $key .= $char;
                }
            }
            ++$pos;
            continue;
        }

        // Handle template closing brace
        if ($char == '}') {
            --$block_depth;
            // Append if we're still inside a nested template (depth >= 2)
            if ($block_depth >= 2) {
                if ($in_value) {
                    $value .= $char;
                } else {
                    $key .= $char;
                }
            }
            // If block_depth is now 0, save final parameter and return
            if ($block_depth == 0) {
                $clean_key = strtolower(trim($key));
                $clean_value = strip_comments_from_text(rtrim($value));
                if ($clean_key === 'archiveprefix') {
                    $config[$clean_key] = ltrim(html_entity_decode($clean_value, ENT_QUOTES));
                } else {
                    $config[$clean_key] = rtrim($clean_value);
                }
                return $config;
            }
            ++$pos;
            continue;
        }

        // Parameter separator (only at outer template level, depth == 2)
        if ($char == '|' && $block_depth == 2) {
            $clean_key = strtolower(trim($key));
            $clean_value = strip_comments_from_text(rtrim($value));
            if ($clean_key === 'archiveprefix') {
                $config[$clean_key] = ltrim(html_entity_decode($clean_value, ENT_QUOTES));
            } else {
                $config[$clean_key] = rtrim($clean_value);
            }
            $key = $value = "";
            $in_value = false;
            ++$pos;
            continue;
        }

        // Key-value separator (only at outer level and not already in value)
        if ($char == '=' && $block_depth == 2 && !$in_value) {
            $in_value = true;
            ++$pos;
            continue;
        }

        // Regular character - append to key or value
        if ($in_value) {
            $value .= $char;
        } else {
            $key .= $char;
        }
        ++$pos;
    }

    return $config;
}

function strip_comments_from_text($text)
{
    if (str_contains($text, "<!--")) {
        $text = preg_replace("/<!--.*?-->/s", "", $text);
    }
    return trim($text, "\n\r\t\v\x00");
}

function build_config_from_config_block($text) {
    $config = new ArchiveConfig();

    $options = parse_config_block($text);
    if(!$options) {
        return $config;
    }

    if (array_key_exists('archiveprefix', $options) && !empty($options['archiveprefix'])) {
        $config->archiveprefix = $options['archiveprefix'];
    }

    if (array_key_exists('format', $options) && !empty($options['format'])) {
        $config->format = $options['format'];
    }

    if (array_key_exists('age', $options) && $options['age'] !== '') {
        $config->age = (int) $options['age'];
    }

    if (array_key_exists('minarchthreads', $options) && $options['minarchthreads'] !== '') {
        $config->minarchthreads = (int) $options['minarchthreads'];
    }

    if (array_key_exists('minkeepthreads', $options) && $options['minkeepthreads'] !== '') {
        $config->minkeepthreads = (int) $options['minkeepthreads'];
    }

    if (array_key_exists('header', $options) && !empty($options['header'])) {
        $config->header = $options['header'];
    }

    if (array_key_exists('archivenow', $options) && !empty($options['archivenow'])) {
        $config->archivenow = explode(",", $options['archivenow']);
    }

    if (array_key_exists('headerlevel', $options) && $options['headerlevel'] !== '') {
        $config->headerlevel = (int) $options['headerlevel'];
    }

    if (array_key_exists('nogenerateindex', $options) && $options['nogenerateindex'] !== '') {
        $config->nogenerateindex = (int) $options['nogenerateindex'];
    }

    if (array_key_exists('maxkeepthreads', $options) && $options['maxkeepthreads'] !== '') {
        $config->maxkeepthreads = (int) $options['maxkeepthreads'];
    }

    if (array_key_exists('maxkeepbytes', $options) && $options['maxkeepbytes'] !== '') {
        $config->maxkeepbytes = (int) $options['maxkeepbytes'];
    }

    if (array_key_exists('transformheader', $options) && !empty($options['transformheader'])) {
        $config->transformheader = $options['transformheader'];
    }

    if (array_key_exists('maxarchsize', $options) && $options['maxarchsize'] !== '') {
        $config->maxarchsize = (int) $options['maxarchsize'];
    }

    if (array_key_exists('numberstart', $options) && $options['numberstart'] !== '') {
        $config->numberstart = (int) $options['numberstart'];
    }

    if (array_key_exists('key', $options) && !empty($options['key'])) {
        $config->key = $options['key'];
    }

    if (array_key_exists('index', $options)) {
        $config->index = $options['index'] === 'yes';
    }

    if (array_key_exists('archivebox', $options)) {
        $config->archivebox = $options['archivebox'] === 'yes';
    }

    if (array_key_exists('box-width', $options)) {
        $config->box_width = $options['box-width'];
    }

    if (array_key_exists('box-advert', $options)) {
        $config->box_advert = $options['box-advert'] === 'yes';
    }

    if (array_key_exists('box-separator', $options)) {
        $config->box_separator = $options['box-separator'] === 'yes';
    }

    if (array_key_exists('image', $options)) {
        $config->image = $options['image'];
    }

    if (array_key_exists('image-width', $options)) {
        $config->image_width = $options['image-width'];
    }

    if (array_key_exists('search', $options)) {
        $config->search = $options['search'] === 'yes';
    }

    if (array_key_exists('talkcolor', $options)) {
        $config->talkcolor = $options['talkcolor'];
    }

    if (array_key_exists('talkcolour', $options)) {
        $config->talkcolour = $options['talkcolour'];
    }

    if (array_key_exists('once', $options)) {
        $config->once = $options['once'] === '1';
    }

    return $config;
}
