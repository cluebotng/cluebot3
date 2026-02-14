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

final class ConfigParsingTest extends TestCase
{
    public static function existingConfigsData(): array
    {
        $expected_tests = [];
        if ($handle = opendir('tests/data/raw-pages')) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry[0] === '.' || !is_dir('tests/data/raw-pages/' . $entry)) {
                    continue;
                }

                $wiki_text = file_get_contents('tests/data/raw-pages/' . $entry . '/page.txt');
                $expected_results = [];
                foreach (glob('tests/data/raw-pages/' . $entry . '/*.json') as $expected_result_path) {
                    $expected_results[] = json_decode(file_get_contents($expected_result_path), true);
                }
                $expected_tests[] = [$wiki_text, $expected_results];
            }
        }
        return $expected_tests;
    }

    #[DataProvider('existingConfigsData')]
    public function testCorrectNumberOfConfigBlocksFound(string $wiki_text, array $expected_results): void
    {
        $config_blocks = find_config_blocks("ClueBot III", $wiki_text);
        $this->assertEquals(count($expected_results), count($config_blocks));
    }

    #[DataProvider('existingConfigsData')]
    public function testParsedConfigOptionsMatch(string $wiki_text, array $expected_results): void
    {
        $config_blocks = find_config_blocks("ClueBot III", $wiki_text);
        foreach ($expected_results as $idx => $expected_config) {
            $config = build_config_from_config_block($config_blocks[$idx]);
            $this->assertNotNull($config);

            $boolean_fields = [
                'archivebox',
                'box-advert',
                'box-separator',
                'search',
                'index',
            ];

            // Check anything that has been set in the previous config matches the new config
            // Note: this doesn't check defaults
            foreach (array_keys($expected_config) as $config_key) {
                $clean_config_key = str_replace('-', '_', $config_key);

                $expected_value = in_array($config_key, $boolean_fields) ? $expected_config[$config_key] === 'yes' : $expected_config[$config_key];

                $this->assertEquals($expected_value,
                    $config->{$clean_config_key},
                    '"' . $config_key . '" does not match');
            }
        }
    }
}
