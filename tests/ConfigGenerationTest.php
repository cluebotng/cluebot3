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

namespace ClueBot3\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ClueBot3\Config;

use function ClueBot3\UserConfig\build_config_from_config_block;
use function ClueBot3\UserConfig\find_config_blocks;

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

                $raw_config = file_get_contents('tests/data/config-snippets/' . $entry . '/page.txt');
                $expected_config = file_get_contents('tests/data/config-snippets/' . $entry . '/normative.txt');
                $meta = json_decode(file_get_contents('tests/data/config-snippets/' . $entry . '/meta.json'), true);

                $expected_tests[] = [$raw_config, $expected_config, $meta];
            }
        }
        return $expected_tests;
    }

    #[DataProvider('existingConfigsData')]
    public function testGeneratedConfigMatchesExpectedConfig(
        string $raw_config,
        string $expected_config,
        array $meta
    ): void {
        $config_blocks = find_config_blocks("ClueBot III", $raw_config);
        $this->assertEquals(count($config_blocks), 1);

        $config = build_config_from_config_block($meta['title'], $config_blocks[0]);
        $this->assertNotNull($config);

        $generated_config = $config->toWiki();

        $generated_config = rtrim($generated_config, "\n");
        $expected_config = rtrim($expected_config, "\n");

        $this->assertEquals($generated_config, $expected_config);
    }

    public function testMismatchedArchivePrefixNotInAllowListResetsToDefault(): void
    {
        $raw_config = '{{User:ClueBot III/ArchiveThis' .
            '|archiveprefix=Some Other Page/Archives/' .
            '|format=Y/F}}';

        $config_blocks = find_config_blocks("ClueBot III", $raw_config);
        $this->assertCount(1, $config_blocks);

        $config = build_config_from_config_block("Test Page", $config_blocks[0]);

        $this->assertEquals("Test Page/Archives/", $config->archiveprefix);
    }

    public function testCaseMismatchedArchivePrefixIsRewrittenToPageCase(): void
    {
        $raw_config = '{{User:ClueBot III/ArchiveThis' .
            '|archiveprefix=Talk:Casa_by_the_sea/Archive' .
            '|format=%%i}}';

        $config_blocks = find_config_blocks("ClueBot III", $raw_config);
        $this->assertCount(1, $config_blocks);

        $config = build_config_from_config_block("Talk:Casa by the Sea", $config_blocks[0]);
        $this->assertEquals("Talk:Casa by the Sea/Archive", $config->archiveprefix);
        $this->assertTrue($config->rewrite);
        $this->assertStringContainsString("|archiveprefix=Talk:Casa by the Sea/Archive\n", $config->toWiki());
    }

    public static function archivePrefixBoundaryProvider(): array
    {
        return [
            'exact page' => ['Talk:Foo', 'Talk:Foo', 'Talk:Foo', false],
            'subpage' => ['Talk:Foo', 'Talk:Foo/Archive', 'Talk:Foo/Archive', false],
            'subpage with underscores' => ['Talk:Foo bar', 'Talk:Foo_bar/Archive', 'Talk:Foo_bar/Archive', false],
            'case mismatch exact page' => ['Talk:Foo', 'talk:foo', 'Talk:Foo', true],
            'case mismatch subpage' => [
                'Talk:Casa by the Sea',
                'Talk:Casa_by_the_sea/Archive',
                'Talk:Casa by the Sea/Archive',
                true,
            ],
            'sibling page' => ['Talk:Foo', 'Talk:Foobar/Archive', 'Talk:Foo/Archives/', true],
            'sibling page case mismatch' => ['Talk:Foo', 'Talk:FOObar/Archive', 'Talk:Foo/Archives/', true],
            'non-subpage suffix' => ['Talk:Foo', 'Talk:Foo archive', 'Talk:Foo/Archives/', true],
        ];
    }

    #[DataProvider('archivePrefixBoundaryProvider')]
    public function testArchivePrefixBoundary(
        string $page,
        string $archive_prefix,
        string $expected_prefix,
        bool $expected_rewrite
    ): void {
        $raw_config = '{{User:ClueBot III/ArchiveThis' .
            '|archiveprefix=' . $archive_prefix .
            '|format=%%i}}';

        $config_blocks = find_config_blocks("ClueBot III", $raw_config);
        $this->assertCount(1, $config_blocks);

        $config = build_config_from_config_block($page, $config_blocks[0]);

        $this->assertEquals($expected_prefix, $config->archiveprefix);
        $this->assertEquals($expected_rewrite, $config->rewrite);
    }

    public function testMismatchedArchivePrefixInAllowListIsKept(): void
    {
        $page = 'User talk:DamianZaremba Scripts';
        $allowed_prefix = 'User talk:DamianZaremba';

        $original_allowed_archive_prefixes = Config::$allowed_archive_prefixes;
        Config::$allowed_archive_prefixes = [$page => [$allowed_prefix]];

        try {
            $raw_config = '{{User:ClueBot III/ArchiveThis' .
                '|archiveprefix=' . $allowed_prefix .
                '|format=Y/F}}';

            $config_blocks = find_config_blocks("ClueBot III", $raw_config);
            $this->assertCount(1, $config_blocks);

            $config = build_config_from_config_block($page, $config_blocks[0]);

            $this->assertEquals($allowed_prefix, $config->archiveprefix);
        } finally {
            Config::$allowed_archive_prefixes = $original_allowed_archive_prefixes;
        }
    }
}
