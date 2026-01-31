<?php

namespace ClueBot3\Tests;

use PHPUnit\Framework\TestCase;
use Wikipedia\Api;
use Wikipedia\Query;
use Wikipedia\Index;
use Monolog\Logger;

use function ClueBot3\doarchive;
use function ClueBot3\parsetemplate;

class DoarchiveTest extends TestCase
{
    private $wpapi;
    private $wpq;

    protected function setUp(): void
    {
        $this->wpapi = $this->createMock(Api::class);
        $this->wpq = $this->createMock(Query::class);

        $GLOBALS['wpapi'] = $this->wpapi;
        $GLOBALS['wpq'] = $this->wpq;
        $GLOBALS['wpi'] = $this->createMock(Index::class);
        $GLOBALS['logger'] = $this->createMock(Logger::class);
        $GLOBALS['user'] = 'ClueBot III';
        $GLOBALS['pass'] = 'testpassword';
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['wpapi'], $GLOBALS['wpq'], $GLOBALS['wpi'],
              $GLOBALS['logger'], $GLOBALS['user'], $GLOBALS['pass']);
    }

    private function callDoarchive(array $overrides = [])
    {
        $defaults = [
            'Talk:Test', 'Talk:Test/Archives/', '%%i', 24,
            0, 0, '{{Talkarchive}}',
            ['{{User:ClueBot III/ArchiveNow}}'],
            2, 1, 0, 0, '', 0, 1, ''
        ];
        $args = array_replace($defaults, $overrides);
        return doarchive(...$args);
    }

    public function testSkipsNoRevisions()
    {
        $this->wpapi->method('revisions')->willReturn(false);
        $this->assertFalse($this->callDoarchive());
    }

    public function testMinkeepPreventsArchiving()
    {
        $page = "Header\n== Section A ==\nOld body A\n== Section B ==\nOld body B\n";
        $ts = gmdate('Y-m-d\TH:i:s\Z', time() - 86400);

        $this->wpapi->method('revisions')->willReturnCallback(
            function ($p, $count, $dir, $content = false) use ($page, $ts) {
                if ($content) {
                    return [['timestamp' => $ts, 'slots' => ['main' => ['*' => $page]]]];
                }
                return [['timestamp' => $ts, 'revid' => 1]];
            }
        );

        $this->wpapi->expects($this->never())->method('edit');

        // minkeep=2, same as number of sections
        $this->callDoarchive([5 => 2]);
    }

    public function testRollbackOnFailedEdit()
    {
        $content = "Header\n== Old Section ==\nOld body\n";
        $ts = gmdate('Y-m-d\TH:i:s\Z', time() - 172800);

        $n = 0;
        $this->wpapi->method('revisions')->willReturnCallback(
            function ($p, $count, $dir, $full = false) use ($content, $ts, &$n) {
                $n++;
                if ($full && $n === 1) {
                    return [['timestamp' => $ts, 'slots' => ['main' => ['*' => $content]]]];
                }
                if (!$full) {
                    return [['timestamp' => gmdate('Y-m-d\TH:i:s\Z', time() - 172800), 'revid' => 100]];
                }
                return [['timestamp' => $ts, 'slots' => ['main' => ['*' => $content]]]];
            }
        );

        $edits = [];
        $this->wpapi->method('edit')->willReturnCallback(
            function () use (&$edits) {
                $edits[] = func_get_args();
                return count($edits) !== 2; // second edit (source page) fails
            }
        );
        $this->wpapi->method('backlinks')->willReturn([]);

        $this->callDoarchive();

        $this->assertCount(3, $edits, 'Expected archive + source + rollback');
        $this->assertStringContainsString('Unarchiving', $edits[2][2]);
    }

    public function testBadKeyFallback()
    {
        $content = "Header\n== Old Thread ==\nOld content\n";
        $ts = gmdate('Y-m-d\TH:i:s\Z', time() - 172800);

        $n = 0;
        $this->wpapi->method('revisions')->willReturnCallback(
            function ($p, $count, $dir, $full = false) use ($content, $ts, &$n) {
                $n++;
                if ($full && $n === 1) {
                    return [['timestamp' => $ts, 'slots' => ['main' => ['*' => $content]]]];
                }
                if (!$full) {
                    return [['timestamp' => gmdate('Y-m-d\TH:i:s\Z', time() - 172800), 'revid' => 100]];
                }
                return [['timestamp' => $ts, 'slots' => ['main' => ['*' => $content]]]];
            }
        );

        $edits = [];
        $this->wpapi->method('edit')->willReturnCallback(function () use (&$edits) {
            $edits[] = func_get_args();
            return true;
        });
        $this->wpq->method('getpage')->willReturn('');
        $this->wpapi->method('backlinks')->willReturn([]);

        // prefix doesn't start with page name + wrong key => should fall back
        $this->callDoarchive([
            1 => 'Other:Totally/Different/',
            15 => 'wrong_key',
        ]);

        if (count($edits) > 0) {
            $this->assertStringStartsWith('Talk:Test/Archives/', $edits[0][0]);
        }
    }

    // parsetemplate tests

    public function testParsetemplate()
    {
        $tpl = 'Some text {{User:ClueBot III/ArchiveThis'
            . '|archiveprefix=Talk:Example/Archives/'
            . '|format=%%i'
            . '|age=72'
            . '}} more text';

        $this->wpq->method('getpage')->willReturn($tpl);
        $this->wpapi->method('revisions')->willReturn(false);

        // Should not throw
        parsetemplate('Talk:Example');
        $this->assertTrue(true);
    }

    public function testParsetemplateOnce()
    {
        $tpl = '{{User:ClueBot III/ArchiveThis'
            . '|archiveprefix=Talk:Example/Archives/'
            . '|format=%%i'
            . '|age=24'
            . '|once=1'
            . '}}';

        $this->wpq->method('getpage')->willReturn($tpl);

        $edits = [];
        $this->wpapi->method('edit')->willReturnCallback(function () use (&$edits) {
            $edits[] = func_get_args();
            return true;
        });
        $this->wpapi->method('revisions')->willReturn(false);

        parsetemplate('Talk:Example');

        $this->assertNotEmpty($edits);
        $this->assertStringContainsString('Commenting out config', $edits[0][2]);
    }
}
