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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function ClueBot3\Config\build_config_from_config_block;
use function ClueBot3\Config\find_config_blocks;

require_once 'lib/config.php';

final class ConfigGenerationTest extends TestCase
{
    public static function existingConfigsData(): array
    {
        $expected_tests = [];
        if ($handle = opendir('tests/data/config-snippets')) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry[0] === '.' || !is_dir('tests/data/config-snippets/' . $entry)) {
                    continue;
                }

                $raw_config = file_get_contents('tests/data/config-snippets/' . $entry . '/raw.txt');
                $expected_config = file_get_contents('tests/data/config-snippets/' . $entry . '/expected.txt');
                $expected_tests[] = [$raw_config, $expected_config];
            }
        }
        return $expected_tests;
    }

    #[DataProvider('existingConfigsData')]
    public function testGeneratedConfigMatchesExpectedConfig(string $raw_config, string $expected_config): void
    {
        $config_blocks = find_config_blocks("ClueBot III", $raw_config);
        $this->assertEquals(count($config_blocks), 1);

        $config = build_config_from_config_block($config_blocks[0]);
        $this->assertNotNull($config);

        $generated_config = $config->to_wiki();

        $generated_config = rtrim($generated_config, "\n");
        $expected_config = rtrim($expected_config, "\n");

        $this->assertEquals($generated_config, $expected_config);
    }
}
