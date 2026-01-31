<?php

namespace ClueBot3\Tests;

use PHPUnit\Framework\TestCase;

use function ClueBot3\splitintosections;
use function ClueBot3\extractnamespace;
use function ClueBot3\namespacetoid;
use function ClueBot3\strip_comments;

class BotTest extends TestCase
{
    public function testSplitBasicSections()
    {
        $input = "Header text\n== Section A ==\nBody A\n== Section B ==\nBody B\n";
        $result = splitintosections($input);

        $this->assertSame("Header text\n", $result[0]);
        $this->assertArrayHasKey('Section A', $result);
        $this->assertArrayHasKey('Section B', $result);
        $this->assertStringContainsString('Body A', $result['Section A']['content']);
        $this->assertStringContainsString('Body B', $result['Section B']['content']);
    }

    public function testSplitEmpty()
    {
        $result = splitintosections('');
        $this->assertSame('', $result[0]);
        $this->assertCount(1, $result);
    }

    public function testSplitPlainText()
    {
        $result = splitintosections('Just plain text with no headings.');
        $this->assertSame('Just plain text with no headings.', $result[0]);
        $this->assertCount(1, $result);
    }

    public function testSplitDupes()
    {
        $input = "== Dupe ==\nFirst\n== Dupe ==\nSecond\n";
        $result = splitintosections($input);
        $this->assertArrayHasKey('Dupe', $result);
        $this->assertArrayHasKey('Dupe 2', $result);
    }

    public function testSplitLevel3()
    {
        $result = splitintosections("=== Sub A ===\nContent A\n=== Sub B ===\nContent B\n", 3);
        $this->assertArrayHasKey('Sub A', $result);
        $this->assertArrayHasKey('Sub B', $result);
    }

    public function testSplitTrailingHeading()
    {
        // Regression: heading at end of string without trailing newline
        // used to read past the string buffer
        $result = splitintosections("Some text\n== Trailing ==");
        $this->assertIsArray($result);
    }

    public function testSplitL3InsideL2()
    {
        $input = "== L2 ==\nBody\n=== L3 ===\nSub body\n";
        $result = splitintosections($input, 2);

        $this->assertArrayHasKey('L2', $result);
        $this->assertCount(2, $result);
        $this->assertStringContainsString('=== L3 ===', $result['L2']['content']);
    }

    public function testExtractUserTalk()
    {
        $result = extractnamespace('User talk:Example');
        $this->assertSame('User talk', $result[0]);
        $this->assertSame('Example', $result[1]);
    }

    public function testExtractWP()
    {
        $result = extractnamespace('Wikipedia:Village pump');
        $this->assertSame('Wikipedia', $result[0]);
        $this->assertSame('Village pump', $result[1]);
    }

    public function testExtractNoNamespace()
    {
        // Bug fix: used to reference undefined $m[4]
        $result = extractnamespace('Main Page');
        $this->assertSame('', $result[0]);
        $this->assertSame('Main Page', $result[1]);
    }

    public function testExtractCaseInsensitive()
    {
        $r = extractnamespace('USER TALK:Someone');
        $this->assertSame('USER TALK', $r[0]);
        $this->assertSame('Someone', $r[1]);
    }

    public function testExtractPortal()
    {
        $r = extractnamespace('Portal:Science');
        $this->assertSame('Portal', $r[0]);
        $this->assertSame('Science', $r[1]);
    }

    public function testNsIds()
    {
        $this->assertSame(0, namespacetoid(''));
        $this->assertSame(1, namespacetoid('talk'));
        $this->assertSame(2, namespacetoid('user'));
        $this->assertSame(3, namespacetoid('user talk'));
        $this->assertSame(4, namespacetoid('wikipedia'));
        $this->assertSame(5, namespacetoid('wikipedia talk'));
        $this->assertSame(100, namespacetoid('portal'));
        $this->assertSame(101, namespacetoid('portal talk'));
    }

    public function testNsIdCaseInsensitive()
    {
        $this->assertSame(2, namespacetoid('User'));
        $this->assertSame(3, namespacetoid('User Talk'));
        $this->assertSame(4, namespacetoid('WIKIPEDIA'));
    }

    public function testNsIdUnderscores()
    {
        $this->assertSame(3, namespacetoid('user_talk'));
        $this->assertSame(5, namespacetoid('wikipedia_talk'));
    }

    public function testStripComment()
    {
        $this->assertSame('hello  world', strip_comments('hello <!-- removed --> world'));
    }

    public function testStripNoOp()
    {
        $this->assertSame('no comments here', strip_comments('no comments here'));
    }

    public function testStripWithTrim()
    {
        $this->assertSame('value', strip_comments('  value  <!-- x -->', true));
    }

    public function testStripNoTrim()
    {
        $this->assertSame('  value  ', strip_comments('  value  <!-- x -->', false));
    }

    public function testStripGreedyMatch()
    {
        // The regex is greedy so it eats everything between first <!-- and last -->
        $this->assertSame('a', strip_comments('a <!-- 1 --> b <!-- 2 -->'));
    }
}
