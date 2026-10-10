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

use ClueBot3\Config;

function find_config_blocks($user, $text)
{
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
                if (strcasecmp(substr($data, $block_position, 8), '<nowiki>') == 0) {
                    $ignore_block = true;
                    $block_position += 7;
                }
            }
            if (strcasecmp(substr($data, $block_position, 9), '</nowiki>') == 0) {
                $ignore_block = false;
                $block_position += 8;
            }
            ++$block_position;
        }
        ++$start_position;
    }

    return $config_blocks;
}

function parse_config_block($text)
{
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
            if (strcasecmp(substr($text, $pos, 9), '</nowiki>') == 0) {
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
        if (strcasecmp(substr($text, $pos, 8), '<nowiki>') == 0) {
            $ignore_block = true;
            $pos += 8;
            continue;
        }

        // Empty nowiki tag - skip the tag
        if (strcasecmp(substr($text, $pos, 9), '<nowiki/>') == 0) {
            $pos += 9;
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

function build_config_from_config_block(string $page, RawConfig $block)
{
    global $logger;
    $config = new ArchiveConfig();

    $options = parse_config_block($block->text);
    if (!$options) {
        return $config;
    }

    if (array_key_exists('archiveprefix', $options) && !empty($options['archiveprefix'])) {
        $config->archiveprefix = $options['archiveprefix'];
    } else {
        // This hits the fallback where the archive prefix is not prefixed with the page,
        // so set it to the implicit value up-front.
        // Note: this has a side effect that toWiki will populate the value.
        $config->archiveprefix = $page . '/Archives/';
        $config->rewrite = true;
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

    // The prefix is under the page if it is the page itself, or a subpage of it,
    // e.g. `Talk:Foo` or `Talk:Foo/Archive` but not `Talk:Foobar/Archive`.
    $is_prefix_under_page = fn (string $prefix, string $page) =>
        $prefix === $page || str_starts_with($prefix, $page . '/');

    // If the archive prefix is not under the same page, it must be allowed.
    if (!$is_prefix_under_page(str_replace('_', ' ', $config->archiveprefix), $page)) {
        // Previous behaviour applied this case insensitively - pages are actually case sensitive e.g.
        // [[Apple]] and [[APPLE]] are 2 distinct pages
        // To minimise user impact support translating the config e.g.
        // On `Talk:Casa by the Sea` from `Talk:Casa by the sea/Archives/` to `Talk:Casa by the Sea/Archives/`
        // using our rewrite support.
        if ($is_prefix_under_page(strtolower(str_replace('_', ' ', $config->archiveprefix)), strtolower($page))) {
            $logger->warning(
                'Archive prefix has case mismatch for page; page=' . $page . ', prefix=' . $config->archiveprefix
            );
            $config->archiveprefix = $page . substr($config->archiveprefix, strlen($page));
            $config->rewrite = true;
        } else {
            // Check if the prefix is explicitly allowed via Config::$allowed_archive_prefixes,
            // otherwise reset to the default.
            $clean_archiveprefix = str_replace('_', ' ', $config->archiveprefix);
            $allowed_prefixes = array_map(
                fn ($prefix) => str_replace('_', ' ', $prefix),
                Config::$allowed_archive_prefixes[$page] ?? []
            );
            if (!in_array($clean_archiveprefix, $allowed_prefixes)) {
                $logger->error(
                    'Archive prefix not allowed for page; page=' . $page . ', prefix=' . $config->archiveprefix
                );
                $config->archiveprefix = $page . '/Archives/';
                $config->rewrite = true;
            }
        }
    }

    $config->is_valid = true;
    return $config;
}
