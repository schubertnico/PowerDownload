<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit Tests for pdl_functions.inc.php
 */
class FunctionsTest extends TestCase
{
    // ==================== size() Tests ====================

    #[Test]
    public function sizeReturnsKilobytesForSmallValues(): void
    {
        $this->assertSame('1 KB', size(1024));
        $this->assertSame('0 KB', size(0));
        $this->assertSame('0.5 KB', size(512));
    }

    #[Test]
    public function sizeReturnsMegabytesForMediumValues(): void
    {
        $this->assertSame('1 MB', size(1024 * 1024));
        $this->assertSame('1.5 MB', size(1024 * 1024 * 1.5));
    }

    #[Test]
    public function sizeReturnsGigabytesForLargeValues(): void
    {
        $this->assertSame('1 GB', size(1024 * 1024 * 1024));
        $this->assertSame('2.5 GB', size((int)(1024 * 1024 * 1024 * 2.5)));
    }

    #[Test]
    public function sizeReturnsTerabytesForVeryLargeValues(): void
    {
        $this->assertSame('1 TB', size(1024 * 1024 * 1024 * 1024));
    }

    #[Test]
    #[DataProvider('sizeDataProvider')]
    public function sizeHandlesVariousInputs(int|float $input, string $expected): void
    {
        $this->assertSame($expected, size($input));
    }

    public static function sizeDataProvider(): array
    {
        return [
            'zero bytes' => [0, '0 KB'],
            '100 bytes' => [100, '0.1 KB'],
            '500 bytes' => [500, '0.5 KB'],
            '1 KB' => [1024, '1 KB'],
            '10 KB' => [10240, '10 KB'],
            '100 KB' => [102400, '100 KB'],
            '1 MB' => [1048576, '1 MB'],
            '10 MB' => [10485760, '10 MB'],
            '1 GB' => [1073741824, '1 GB'],
        ];
    }

    // ==================== ascii_encode() Tests ====================

    #[Test]
    public function asciiEncodeConvertsSimpleString(): void
    {
        $result = ascii_encode('abc');
        $this->assertSame('&#97;&#98;&#99;', $result);
    }

    #[Test]
    public function asciiEncodeConvertsEmailAddress(): void
    {
        $result = ascii_encode('test@example.com');
        $this->assertStringContainsString('&#', $result);
        $this->assertStringNotContainsString('@', $result);
        $this->assertStringNotContainsString('.', $result);
    }

    #[Test]
    public function asciiEncodeReturnsEmptyStringForEmptyInput(): void
    {
        $this->assertSame('', ascii_encode(''));
    }

    #[Test]
    public function asciiEncodeConvertsSpecialCharacters(): void
    {
        $result = ascii_encode('<>');
        $this->assertSame('&#60;&#62;', $result);
    }

    // ==================== generate_string() Tests ====================

    #[Test]
    public function generateStringReturnsCorrectLength(): void
    {
        $this->assertSame(0, strlen(generate_string(0)));
        $this->assertSame(1, strlen(generate_string(1)));
        $this->assertSame(16, strlen(generate_string(16)));
        $this->assertSame(100, strlen(generate_string(100)));
    }

    #[Test]
    public function generateStringContainsOnlyAlphanumericCharacters(): void
    {
        $result = generate_string(100);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $result);
    }

    #[Test]
    public function generateStringProducesDifferentResults(): void
    {
        $result1 = generate_string(32);
        $result2 = generate_string(32);
        // Very unlikely to be the same
        $this->assertNotSame($result1, $result2);
    }

    // ==================== dlspeed() Tests ====================

    #[Test]
    public function dlspeedReturnsSecondsForSmallFiles(): void
    {
        // With default dlspeed of 56 KB/s
        $result = dlspeed(1024); // 1 KB
        $this->assertStringContainsString('sek', $result);
    }

    #[Test]
    public function dlspeedReturnsMinutesForMediumFiles(): void
    {
        // 10 MB at 56 KB/s = ~183 seconds = ~3 minutes
        $result = dlspeed(10 * 1024 * 1024);
        $this->assertStringContainsString('min', $result);
    }

    #[Test]
    public function dlspeedReturnsHoursForLargeFiles(): void
    {
        // 1 GB at 56 KB/s = ~18724 seconds = ~5.2 hours
        $result = dlspeed(1024 * 1024 * 1024);
        $this->assertStringContainsString('std', $result);
    }

    // ==================== split_query() Tests ====================
    // Note: split_query() is designed for SQL dumps where each statement ends
    // with semicolon followed by another statement. The last statement without
    // a following statement is intentionally not added to the result.

    #[Test]
    public function splitQuerySplitsMultipleQueries(): void
    {
        $return = [];
        // Function needs a following query after semicolon to detect it
        $sql = "SELECT * FROM users; INSERT INTO logs VALUES (1);";

        split_query($return, $sql);

        // Only first query is captured, second one is trailing
        $this->assertCount(1, $return);
        $this->assertSame('SELECT * FROM users', $return[0]);
    }

    #[Test]
    public function splitQuerySplitsThreeQueries(): void
    {
        $return = [];
        $sql = "SELECT 1; SELECT 2; SELECT 3;";

        split_query($return, $sql);

        // First two queries captured, third is trailing
        $this->assertCount(2, $return);
        $this->assertSame('SELECT 1', $return[0]);
        $this->assertSame(' SELECT 2', $return[1]);
    }

    #[Test]
    public function splitQueryIgnoresComments(): void
    {
        $return = [];
        $sql = "# This is a comment\nSELECT * FROM users; SELECT 2;";

        split_query($return, $sql);

        $this->assertCount(1, $return);
        $this->assertStringNotContainsString('#', $return[0]);
        $this->assertStringContainsString('SELECT * FROM users', $return[0]);
    }

    #[Test]
    public function splitQueryHandlesQuotedSemicolons(): void
    {
        $return = [];
        $sql = "INSERT INTO test VALUES ('value;with;semicolons'); SELECT 2;";

        split_query($return, $sql);

        $this->assertCount(1, $return);
        $this->assertStringContainsString('value;with;semicolons', $return[0]);
    }

    #[Test]
    public function splitQueryReturnsEmptyForSingleQuery(): void
    {
        $return = [];
        $sql = "SELECT * FROM users;";

        split_query($return, $sql);

        // Single query with no following query returns empty
        $this->assertCount(0, $return);
    }

    // ==================== bbcode() Tests ====================

    #[Test]
    public function bbcodeConvertsBoldTag(): void
    {
        $result = bbcode('[b]bold text[/b]', 'N', 'N', 'N', 'Y', 'N');
        $this->assertStringContainsString('<b>', $result);
        $this->assertStringContainsString('</b>', $result);
    }

    #[Test]
    public function bbcodeConvertsItalicTag(): void
    {
        $result = bbcode('[i]italic text[/i]', 'N', 'N', 'N', 'Y', 'N');
        $this->assertStringContainsString('<i>', $result);
        $this->assertStringContainsString('</i>', $result);
    }

    #[Test]
    public function bbcodeConvertsUnderlineTag(): void
    {
        $result = bbcode('[u]underline text[/u]', 'N', 'N', 'N', 'Y', 'N');
        $this->assertStringContainsString('<u>', $result);
        $this->assertStringContainsString('</u>', $result);
    }

    #[Test]
    public function bbcodeConvertsUrlTag(): void
    {
        $result = bbcode('[url]https://example.com[/url]', 'N', 'N', 'N', 'Y', 'N');
        $this->assertStringContainsString('<a href=', $result);
        $this->assertStringContainsString('example.com', $result);
    }

    #[Test]
    public function bbcodeEscapesHtmlWhenDisabled(): void
    {
        $result = bbcode('<script>alert("xss")</script>', 'N', 'N', 'N', 'N', 'N');
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    #[Test]
    public function bbcodeAllowsHtmlWhenEnabled(): void
    {
        $result = bbcode('<b>bold</b>', 'N', 'N', 'N', 'N', 'Y');
        $this->assertStringContainsString('<b>bold</b>', $result);
    }

    // ==================== replace() Tests ====================

    #[Test]
    public function replaceSubstitutesNameVariable(): void
    {
        $result = replace('{name}', ['name' => 'Test Name']);
        $this->assertSame('Test Name', $result);
    }

    #[Test]
    public function replaceSubstitutesMultipleVariables(): void
    {
        $result = replace('{name} - {downloads}', [
            'name' => 'Test',
            'downloads' => 100,
        ]);
        $this->assertSame('Test - 100', $result);
    }

    #[Test]
    public function replaceHandlesMissingVariables(): void
    {
        $result = replace('{name} - {missing}', ['name' => 'Test']);
        $this->assertStringContainsString('Test', $result);
    }

    #[Test]
    public function replaceEscapesHtmlInName(): void
    {
        $result = replace('{name}', ['name' => '<script>alert("xss")</script>']);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    // ==================== alt_switch() Tests ====================

    #[Test]
    public function altSwitchAlternatesBetweenColors(): void
    {
        global $alt_switch, $template;

        $alt_switch = 0;
        $template['alt_1'] = '#FFFFFF';
        $template['alt_2'] = '#F0F0F0';

        $result1 = alt_switch();
        $result2 = alt_switch();

        $this->assertSame('#FFFFFF', $result1);
        $this->assertSame('#F0F0F0', $result2);
    }

    // ==================== seiten() Tests ====================

    #[Test]
    public function seitenReturnsEmptyForSinglePage(): void
    {
        global $page, $settings;
        $page = 1;
        $settings['spages'] = 10;

        $result = seiten(5, 10, '', 'test.php?');
        $this->assertSame('', $result);
    }

    #[Test]
    public function seitenReturnsPaginationForMultiplePages(): void
    {
        global $page, $settings;
        $page = 1;
        $settings['spages'] = 10;

        $result = seiten(100, 10, '', 'test.php?');
        $this->assertStringContainsString('Seiten', $result);
        $this->assertStringContainsString('page=', $result);
    }

    #[Test]
    public function seitenShowsCurrentPageBold(): void
    {
        global $page, $settings;
        $page = 1;
        $settings['spages'] = 0;

        $result = seiten(30, 10, '', 'test.php?');
        $this->assertStringContainsString('<b>[1]</b>', $result);
    }
}
