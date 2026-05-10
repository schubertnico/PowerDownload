<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PowerDownload\Tests\Support\MockDbHandler;

class FunctionsHelperTest extends TestCase
{
    protected function setUp(): void
    {
        global $settings, $template, $smilies, $glossary, $badwords, $users, $alt_switch,
               $page, $list, $total, $install, $inadmin;

        $settings = [
            'dlspeed' => 56,
            'date_format' => 'd.m.Y',
            'script_file' => 'downloads.php?',
            'spages' => 10,
        ];
        $template = [
            'alt_1' => '#FFFFFF',
            'alt_2' => '#F0F0F0',
            'footer_bg' => '#CCCCCC',
            'header_bg' => '#333333',
            'table_border' => '#000000',
        ];
        $smilies = [];
        $glossary = [];
        $badwords = [];
        $users = [];
        $alt_switch = 0;
        $page = 1;
        $list = '';
        $total = 0;
        $install = 0;
        $inadmin = 0;
    }

    // ==================== user() Tests ====================

    #[Test]
    public function userReturnsDeletedForMissingUser(): void
    {
        global $users;
        $users = [];
        $this->assertSame('Gelöscht', user(999));
    }

    #[Test]
    public function userReturnsDeletedForEmptyNick(): void
    {
        global $users;
        $users[1] = ['nick' => '', 'email' => 'test@test.com'];
        $this->assertSame('Gelöscht', user(1));
    }

    #[Test]
    public function userReturnsLinkWithEmail(): void
    {
        global $users;
        $users[1] = ['nick' => 'TestUser', 'email' => 'test@test.com', 'icq' => 0, 'homepage' => ''];
        $result = user(1);
        $this->assertStringContainsString('mailto:', $result);
        $this->assertStringContainsString('TestUser', $result);
    }

    #[Test]
    public function userDoesNotShowIcqAfterRemoval(): void
    {
        // BUG-029: ICQ-Anbindung wurde entfernt. Auch wenn das Datenbankfeld
        // vorhanden ist, darf user() keine ICQ-Verlinkung mehr ausgeben.
        global $users;
        $users[1] = ['nick' => 'TestUser', 'email' => 'test@test.com', 'icq' => 12345, 'homepage' => ''];
        $result = user(1);
        $this->assertStringNotContainsString('icq.im', $result);
        $this->assertStringNotContainsString('icq.gif', $result);
    }

    #[Test]
    public function userShowsHomepageWhenSet(): void
    {
        global $users, $inadmin;
        $inadmin = 0;
        $users[1] = ['nick' => 'TestUser', 'email' => 'test@test.com', 'icq' => 0, 'homepage' => 'https://example.com'];
        $result = user(1);
        $this->assertStringContainsString('https://example.com', $result);
        $this->assertStringContainsString('www.gif', $result);
    }

    #[Test]
    public function userShowsHomepageInAdmin(): void
    {
        global $users, $inadmin;
        $inadmin = 1;
        $users[1] = ['nick' => 'TestUser', 'email' => 'test@test.com', 'icq' => 0, 'homepage' => 'https://example.com'];
        $result = user(1);
        $this->assertStringContainsString('../', $result);
        $this->assertStringContainsString('www.gif', $result);
    }

    #[Test]
    public function userEscapesHtmlInNick(): void
    {
        global $users;
        $users[1] = ['nick' => '<script>xss</script>', 'email' => 'test@test.com', 'icq' => 0, 'homepage' => ''];
        $result = user(1);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    #[Test]
    public function userShowsHomepageEvenWhenIcqLegacyValuePresent(): void
    {
        // BUG-029: ICQ wurde entfernt. Homepage muss weiterhin angezeigt werden,
        // auch wenn der Datensatz noch ein altes ICQ-Feld enthält.
        global $users, $inadmin;
        $inadmin = 0;
        $users[1] = ['nick' => 'FullUser', 'email' => 'full@test.com', 'icq' => 99999, 'homepage' => 'https://full.com'];
        $result = user(1);
        $this->assertStringNotContainsString('icq.im', $result);
        $this->assertStringContainsString('https://full.com', $result);
        $this->assertStringContainsString('FullUser', $result);
    }

    // ==================== bbcode_apply_smilies() Tests ====================

    #[Test]
    public function bbcodeApplySmiliesReplacesSmilies(): void
    {
        $smilies = [
            ['old' => ':)', 'neu' => 'smile.gif'],
            ['old' => ':(', 'neu' => 'sad.gif'],
        ];
        $result = bbcode_apply_smilies('Hello :) World :(', $smilies);
        $this->assertStringContainsString('smile.gif', $result);
        $this->assertStringContainsString('sad.gif', $result);
        $this->assertStringNotContainsString(':)', $result);
    }

    #[Test]
    public function bbcodeApplySmiliesSkipsEmptyOld(): void
    {
        $smilies = [['old' => '', 'neu' => 'test.gif']];
        $result = bbcode_apply_smilies('Hello World', $smilies);
        $this->assertSame('Hello World', $result);
    }

    #[Test]
    public function bbcodeApplySmiliesEscapesNeu(): void
    {
        $smilies = [['old' => ':)', 'neu' => 'path/to"img.gif']];
        $result = bbcode_apply_smilies(':)', $smilies);
        $this->assertStringContainsString('&quot;', $result);
    }

    // ==================== bbcode_apply_tags() Tests ====================

    #[Test]
    public function bbcodeApplyTagsConvertsBold(): void
    {
        $result = bbcode_apply_tags('[b]test[/b]');
        $this->assertSame('<b>test</b>', $result);
    }

    #[Test]
    public function bbcodeApplyTagsConvertsItalic(): void
    {
        $result = bbcode_apply_tags('[i]test[/i]');
        $this->assertSame('<i>test</i>', $result);
    }

    #[Test]
    public function bbcodeApplyTagsConvertsUnderline(): void
    {
        $result = bbcode_apply_tags('[u]test[/u]');
        $this->assertSame('<u>test</u>', $result);
    }

    #[Test]
    public function bbcodeApplyTagsConvertsUrl(): void
    {
        $result = bbcode_apply_tags('[url]https://example.com[/url]');
        $this->assertStringContainsString('<a href="https://example.com"', $result);
        $this->assertStringContainsString('target="_blank"', $result);
    }

    #[Test]
    public function bbcodeApplyTagsConvertsEmail(): void
    {
        $result = bbcode_apply_tags('[email]test@example.com[/email]');
        $this->assertStringContainsString('mailto:test@example.com', $result);
    }

    #[Test]
    public function bbcodeApplyTagsConvertsUrlWithLabel(): void
    {
        $result = bbcode_apply_tags('[url=https://example.com]Click Here[/url]');
        $this->assertStringContainsString('Click Here', $result);
        $this->assertStringContainsString('example.com', $result);
    }

    #[Test]
    public function bbcodeApplyTagsConvertsImg(): void
    {
        $result = bbcode_apply_tags('[img]https://example.com/img.jpg[/img]');
        $this->assertStringContainsString('<img src="https://example.com/img.jpg"', $result);
    }

    #[Test]
    public function bbcodeApplyTagsConvertsImgWithoutProtocol(): void
    {
        $result = bbcode_apply_tags('[img]example.com/img.jpg[/img]');
        $this->assertStringContainsString('<img src="http://example.com/img.jpg"', $result);
    }

    #[Test]
    public function bbcodeApplyTagsMultipleTags(): void
    {
        $result = bbcode_apply_tags('[b]bold[/b] and [i]italic[/i]');
        $this->assertStringContainsString('<b>bold</b>', $result);
        $this->assertStringContainsString('<i>italic</i>', $result);
    }

    // ==================== bbcode_apply_glossary() Tests ====================

    #[Test]
    public function bbcodeApplyGlossaryReplacesTerms(): void
    {
        $glossary = [
            ['old' => 'PHP', 'neu' => '<abbr title="PHP: Hypertext Preprocessor">PHP</abbr>'],
        ];
        $result = bbcode_apply_glossary('Learn PHP today', $glossary);
        $this->assertStringContainsString('<abbr', $result);
    }

    #[Test]
    public function bbcodeApplyGlossarySkipsEmptyOld(): void
    {
        $glossary = [['old' => '', 'neu' => 'replacement']];
        $result = bbcode_apply_glossary('unchanged', $glossary);
        $this->assertSame('unchanged', $result);
    }

    // ==================== bbcode_apply_badwords() Tests ====================

    #[Test]
    public function bbcodeApplyBadwordsCensorsWords(): void
    {
        $badwords = ['badword'];
        $result = bbcode_apply_badwords('This is a badword test', $badwords);
        $this->assertStringContainsString('b******', $result);
        $this->assertStringNotContainsString('badword', $result);
    }

    #[Test]
    public function bbcodeApplyBadwordsCaseInsensitive(): void
    {
        $badwords = ['bad'];
        $result = bbcode_apply_badwords('This is BAD', $badwords);
        $this->assertStringContainsString('B**', $result);
    }

    #[Test]
    public function bbcodeApplyBadwordsSkipsEmpty(): void
    {
        $badwords = [''];
        $result = bbcode_apply_badwords('unchanged', $badwords);
        $this->assertSame('unchanged', $result);
    }

    // ==================== bbcode_protect_emails() Tests ====================

    #[Test]
    public function bbcodeProtectEmailsEncodesEmail(): void
    {
        $result = bbcode_protect_emails('Contact: user@example.com');
        $this->assertStringNotContainsString('user@example.com', $result);
        $this->assertStringContainsString('&#', $result);
    }

    #[Test]
    public function bbcodeProtectEmailsNoEmailUnchanged(): void
    {
        $result = bbcode_protect_emails('No email here');
        $this->assertSame('No email here', $result);
    }

    // ==================== bbcode() integration Tests ====================

    #[Test]
    public function bbcodeWithSmilies(): void
    {
        global $smilies;
        $smilies = [['old' => ':)', 'neu' => 'smile.gif']];
        $result = bbcode('Hello :)', 'N', 'Y', 'N', 'N', 'N');
        $this->assertStringContainsString('smile.gif', $result);
    }

    #[Test]
    public function bbcodeWithGlossary(): void
    {
        global $glossary;
        $glossary = [['old' => 'PHP', 'neu' => '<b>PHP</b>']];
        $result = bbcode('Learn PHP', 'N', 'N', 'Y', 'N', 'Y');
        $this->assertStringContainsString('<b>PHP</b>', $result);
    }

    #[Test]
    public function bbcodeWithBadwords(): void
    {
        global $badwords;
        $badwords = ['damn'];
        $result = bbcode('Oh damn', 'Y', 'N', 'N', 'N', 'N');
        $this->assertStringNotContainsString('damn', $result);
    }

    #[Test]
    public function bbcodeAllFeaturesEnabled(): void
    {
        global $smilies, $glossary, $badwords;
        $smilies = [['old' => ':)', 'neu' => 'smile.gif']];
        $glossary = [['old' => 'API', 'neu' => '<b>API</b>']];
        $badwords = ['bad'];
        $result = bbcode('[b]Hello[/b] :) API bad', 'Y', 'Y', 'Y', 'Y', 'N');
        $this->assertStringContainsString('<b>Hello</b>', $result);
        $this->assertStringContainsString('smile.gif', $result);
    }

    // ==================== seiten_page_link() Tests ====================

    #[Test]
    public function seitenPageLinkCurrentPage(): void
    {
        $result = seiten_page_link('test.php?', 3, '', true);
        $this->assertSame('<b>[3]</b> ', $result);
    }

    #[Test]
    public function seitenPageLinkNonCurrentPage(): void
    {
        $result = seiten_page_link('test.php?', 3, '&foo=bar', false);
        $this->assertStringContainsString('page=3', $result);
        $this->assertStringContainsString('&amp;foo=bar', $result);
        $this->assertStringContainsString('<a href=', $result);
    }

    // ==================== seiten_calc_range() Tests ====================

    #[Test]
    public function seitenCalcRangeMiddle(): void
    {
        $range = seiten_calc_range(5, 10, 5);
        $this->assertArrayHasKey('before', $range);
        $this->assertArrayHasKey('after', $range);
        $this->assertSame(2, $range['before']);
        $this->assertSame(2, $range['after']);
    }

    #[Test]
    public function seitenCalcRangeAtStart(): void
    {
        $range = seiten_calc_range(1, 10, 5);
        $this->assertSame(0, $range['before']);
        $this->assertGreaterThan(0, $range['after']);
    }

    #[Test]
    public function seitenCalcRangeAtEnd(): void
    {
        $range = seiten_calc_range(10, 10, 5);
        $this->assertSame(0, $range['after']);
        $this->assertGreaterThan(0, $range['before']);
    }

    #[Test]
    public function seitenCalcRangeNearStart(): void
    {
        $range = seiten_calc_range(2, 10, 5);
        $this->assertSame(1, $range['before']);
    }

    // ==================== seiten_render_limited() Tests ====================

    #[Test]
    public function seitenRenderLimitedShowsCurrentPage(): void
    {
        $result = seiten_render_limited(3, 10, 5, 'test.php?', '');
        $this->assertStringContainsString('<b>[3]</b>', $result);
    }

    #[Test]
    public function seitenRenderLimitedShowsSurroundingPages(): void
    {
        $result = seiten_render_limited(5, 10, 5, 'test.php?', '');
        $this->assertStringContainsString('page=4', $result);
        $this->assertStringContainsString('page=6', $result);
    }

    // ==================== seiten() extended Tests ====================

    #[Test]
    public function seitenWithLimitedPages(): void
    {
        global $page, $settings;
        $page = 5;
        $settings['spages'] = 5;

        $result = seiten(100, 10, '', 'test.php?');
        $this->assertStringContainsString('Seiten (10)', $result);
        $this->assertStringContainsString('<b>[5]</b>', $result);
    }

    #[Test]
    public function seitenShowsFirstPageLink(): void
    {
        global $page, $settings;
        $page = 3;
        $settings['spages'] = 0;

        $result = seiten(50, 10, '', 'test.php?');
        $this->assertStringContainsString('&laquo;', $result);
        $this->assertStringContainsString('page=1', $result);
    }

    #[Test]
    public function seitenShowsLastPageLink(): void
    {
        global $page, $settings;
        $page = 1;
        $settings['spages'] = 0;

        $result = seiten(50, 10, '', 'test.php?');
        $this->assertStringContainsString('&raquo;', $result);
        $this->assertStringContainsString('page=5', $result);
    }

    #[Test]
    public function seitenOnLastPageNoNextLink(): void
    {
        global $page, $settings;
        $page = 5;
        $settings['spages'] = 0;

        $result = seiten(50, 10, '', 'test.php?');
        $this->assertStringNotContainsString('&raquo;', $result);
    }

    #[Test]
    public function seitenOnFirstPageNoFirstLink(): void
    {
        global $page, $settings;
        $page = 1;
        $settings['spages'] = 0;

        $result = seiten(50, 10, '', 'test.php?');
        $this->assertStringNotContainsString('&laquo;', $result);
    }

    #[Test]
    public function seitenHandlesZeroPerpage(): void
    {
        global $page, $settings;
        $page = 1;
        $settings['spages'] = 0;

        $result = seiten(50, 0, '', 'test.php?');
        $this->assertStringContainsString('Seiten', $result);
    }

    #[Test]
    public function seitenWithLink(): void
    {
        global $page, $settings;
        $page = 1;
        $settings['spages'] = 0;

        $result = seiten(30, 10, '&ordner_id=5', 'test.php?');
        $this->assertStringContainsString('ordner_id=5', $result);
    }

    // ==================== replace_simple_vars() Tests ====================

    #[Test]
    public function replaceSimpleVarsReplacesAllVars(): void
    {
        $template = ['alt_1' => '#FFF', 'alt_2' => '#000', 'footer_bg' => '#CCC', 'header_bg' => '#333', 'table_border' => '#999'];
        $settings = ['script_file' => 'dl.php?'];
        $row = [
            'name' => 'Test', 'titel' => 'Title', 'votes' => 5, 'vote' => '8.5',
            'vote_form' => '<form>', 'downloads' => 100, 'views' => 200,
            'text' => 'Description', 'screens' => '<img>', 'id' => '42',
            'autor' => 'Author', 'files' => '3', 'subdirs' => '2', 'filename' => 'test.zip',
        ];
        $result = replace_simple_vars('{name} {titel} {votes} {downloads} {views} {id} {autor} {filename}', $row, $template, $settings, 'listhtml');
        $this->assertStringContainsString('Test', $result);
        $this->assertStringContainsString('Title', $result);
        $this->assertStringContainsString('5', $result);
        $this->assertStringContainsString('100', $result);
        $this->assertStringContainsString('42', $result);
    }

    #[Test]
    public function replaceSimpleVarsEscapesHtml(): void
    {
        $result = replace_simple_vars('{name}', ['name' => '<b>XSS</b>'], [], [], null);
        $this->assertStringContainsString('&lt;b&gt;', $result);
    }

    #[Test]
    public function replaceSimpleVarsHandlesTemplateVars(): void
    {
        $template = ['alt_1' => '#AAA', 'alt_2' => '#BBB', 'footer_bg' => '#CCC', 'header_bg' => '#DDD', 'table_border' => '#EEE'];
        $result = replace_simple_vars('{alt_1} {alt_2} {footer_bg} {header_bg} {table_border}', [], $template, [], null);
        $this->assertStringContainsString('#AAA', $result);
        $this->assertStringContainsString('#BBB', $result);
        $this->assertStringContainsString('#CCC', $result);
    }

    #[Test]
    public function replaceSimpleVarsHandlesList(): void
    {
        $result = replace_simple_vars('{list}', [], [], [], 'list content');
        $this->assertStringContainsString('list content', $result);
    }

    #[Test]
    public function replaceSimpleVarsHandlesNullList(): void
    {
        $result = replace_simple_vars('{list}', [], [], [], null);
        $this->assertSame('', $result);
    }

    #[Test]
    public function replaceSimpleVarsNl2brText(): void
    {
        $result = replace_simple_vars('{text}', ['text' => "line1\nline2"], [], [], null);
        $this->assertStringContainsString('<br', $result);
    }

    // ==================== replace_expensive_vars() Tests ====================

    #[Test]
    public function replaceExpensiveVarsReplacesSize(): void
    {
        $result = replace_expensive_vars('{size}', ['size' => 1048576], []);
        $this->assertStringContainsString('MB', $result);
    }

    #[Test]
    public function replaceExpensiveVarsReplacesDlspeed(): void
    {
        global $settings;
        $settings['dlspeed'] = 56;
        $result = replace_expensive_vars('{dlspeed}', ['size' => 1024], $settings);
        $this->assertStringContainsString('sek', $result);
    }

    #[Test]
    public function replaceExpensiveVarsReplacesTime(): void
    {
        $result = replace_expensive_vars('{time}', ['time' => 1000000000], ['date_format' => 'd.m.Y']);
        $this->assertMatchesRegularExpression('/\d{2}\.\d{2}\.\d{4}/', $result);
    }

    #[Test]
    public function replaceExpensiveVarsReplacesAlt(): void
    {
        global $alt_switch, $template;
        $alt_switch = 0;
        $template = ['alt_1' => '#FFFFFF', 'alt_2' => '#F0F0F0'];
        $result = replace_expensive_vars('{alt}', [], []);
        $this->assertStringContainsString('#FFFFFF', $result);
    }

    #[Test]
    public function replaceExpensiveVarsReplacesTraffic(): void
    {
        $result = replace_expensive_vars('{traffic}', ['traffic' => 1073741824], []);
        $this->assertStringContainsString('GB', $result);
    }

    #[Test]
    public function replaceExpensiveVarsReplacesUploader(): void
    {
        global $users;
        $users[5] = ['nick' => 'Uploader', 'email' => 'up@test.com', 'icq' => 0, 'homepage' => ''];
        $result = replace_expensive_vars('{uploader}', ['uploader' => 5], []);
        $this->assertStringContainsString('Uploader', $result);
    }

    #[Test]
    public function replaceExpensiveVarsSkipsWhenNotPresent(): void
    {
        $result = replace_expensive_vars('no placeholders here', ['size' => 999], []);
        $this->assertSame('no placeholders here', $result);
    }

    // ==================== replace() extended Tests ====================

    #[Test]
    public function replaceHandlesCount(): void
    {
        $result = replace('{count}', ['count' => 42]);
        $this->assertStringContainsString('42', $result);
    }

    #[Test]
    public function replaceHandlesRows(): void
    {
        global $total;
        $total = 99;
        $result = replace('{rows} {count}', ['key1' => 'val1']);
        $this->assertStringContainsString('99', $result);
    }

    #[Test]
    public function replaceHandlesSizeAndDlspeed(): void
    {
        $result = replace('{size} - {dlspeed}', ['size' => 1048576]);
        $this->assertStringContainsString('MB', $result);
        $this->assertStringContainsString('sek', $result);
    }

    // ==================== split_query helpers Tests ====================

    #[Test]
    public function splitQueryEscapeStatementEscapesQuotes(): void
    {
        $result = split_query_escape_statement('INSERT INTO t VALUES ("test")');
        $this->assertStringContainsString('\\"test\\"', $result);
    }

    #[Test]
    public function splitQueryEscapeStatementHandlesDoubleEscaped(): void
    {
        $result = split_query_escape_statement('value \\"quoted\\"');
        $this->assertSame('value \\\\\\"quoted\\\\\\"', $result);
    }

    #[Test]
    public function splitQueryIsStringToggleAtStart(): void
    {
        $this->assertFalse(split_query_is_string_toggle("'test", 0));
    }

    #[Test]
    public function splitQueryIsStringToggleWithQuote(): void
    {
        $this->assertTrue(split_query_is_string_toggle(" 'test", 1));
    }

    #[Test]
    public function splitQueryIsStringToggleWithEscapedQuote(): void
    {
        $this->assertFalse(split_query_is_string_toggle("\\'test", 1));
    }

    #[Test]
    public function splitQueryIsStringToggleNotQuote(): void
    {
        $this->assertFalse(split_query_is_string_toggle("ab", 1));
    }

    // ==================== split_query() with install mode ====================

    #[Test]
    public function splitQueryInstallMode(): void
    {
        global $install;
        $install = 1;
        $return = [];
        $sql = 'INSERT INTO t VALUES ("test"); SELECT 1;';
        split_query($return, $sql);
        $this->assertCount(1, $return);
        $this->assertStringContainsString('\\"test\\"', $return[0]);
        $install = 0;
    }

    // ==================== dlspeed() edge cases ====================

    #[Test]
    public function dlspeedWithZeroSpeed(): void
    {
        global $settings;
        $settings['dlspeed'] = 0;
        $result = dlspeed(1024);
        $this->assertStringContainsString('sek', $result);
        $settings['dlspeed'] = 56;
    }

    #[Test]
    public function dlspeedWithNegativeSpeed(): void
    {
        global $settings;
        $settings['dlspeed'] = -5;
        $result = dlspeed(1024);
        $this->assertStringContainsString('sek', $result);
        $settings['dlspeed'] = 56;
    }

    // ==================== alt_switch() extended Tests ====================

    #[Test]
    public function altSwitchCyclesThroughColors(): void
    {
        global $alt_switch, $template;
        $alt_switch = 0;
        $template['alt_1'] = '#AAA';
        $template['alt_2'] = '#BBB';

        $r1 = alt_switch(); // alt_switch becomes 1
        $r2 = alt_switch(); // alt_switch becomes 2, resets to 0
        $r3 = alt_switch(); // alt_switch becomes 1 again

        $this->assertSame('#AAA', $r1);
        $this->assertSame('#BBB', $r2);
        $this->assertSame('#AAA', $r3);
    }

    // ==================== treeview functions with mock DB ====================

    #[Test]
    public function treeviewOrdnerWithEmptyResult(): void
    {
        global $db_handler, $sordner_id, $settings, $sql_table, $release, $screen_id;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]);
        $sql_table = ['ordner' => 'pdl3_ordner'];
        $sordner_id = 0;
        $release = null;
        $screen_id = 0;

        ob_start();
        treeview_ordner(0, '');
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    #[Test]
    public function treeviewOrdnerWithFolders(): void
    {
        global $db_handler, $sordner_id, $settings, $sql_table, $release, $screen_id;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['ordner_id' => 1, 'name' => 'Folder1'],
        ]);
        $db_handler->addResult([]); // recursive call returns empty
        $sql_table = ['ordner' => 'pdl3_ordner'];
        $sordner_id = 99; // not matching
        $settings['script_file'] = 'dl.php?';
        $release = null;
        $screen_id = 0;

        ob_start();
        treeview_ordner(0, '');
        $output = ob_get_clean();

        $this->assertStringContainsString('Folder1', $output);
        $this->assertStringContainsString('folder.gif', $output);
    }

    #[Test]
    public function treeviewOrdnerCurrentFolder(): void
    {
        global $db_handler, $sordner_id, $settings, $sql_table, $release, $screen_id;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['ordner_id' => 5, 'name' => 'Current'],
        ]);
        $db_handler->addResult([]);
        $sql_table = ['ordner' => 'pdl3_ordner'];
        $sordner_id = 5;
        $settings['script_file'] = 'dl.php?';
        $release = null;
        $screen_id = 0;

        ob_start();
        treeview_ordner(0, '');
        $output = ob_get_clean();

        $this->assertStringContainsString('folder_open.gif', $output);
        $this->assertStringContainsString('Current', $output);
    }

    #[Test]
    public function treeviewOrdnerCurrentWithRelease(): void
    {
        global $db_handler, $sordner_id, $settings, $sql_table, $release, $screen_id;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['ordner_id' => 5, 'name' => 'Folder'],
        ]);
        $db_handler->addResult([]);
        $sql_table = ['ordner' => 'pdl3_ordner'];
        $sordner_id = 5;
        $settings['script_file'] = 'dl.php?';
        $release = ['name' => 'MyRelease', 'release_id' => 10];
        $screen_id = 0;

        ob_start();
        treeview_ordner(0, '');
        $output = ob_get_clean();

        $this->assertStringContainsString('MyRelease', $output);
        $this->assertStringContainsString('&raquo;', $output);
    }

    #[Test]
    public function treeviewOrdnerCurrentWithScreen(): void
    {
        global $db_handler, $sordner_id, $settings, $sql_table, $release, $screen_id;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['ordner_id' => 5, 'name' => 'Folder'],
        ]);
        $db_handler->addResult([]);
        $sql_table = ['ordner' => 'pdl3_ordner'];
        $sordner_id = 5;
        $settings['script_file'] = 'dl.php?';
        $release = ['name' => 'MyRelease', 'release_id' => 10];
        $screen_id = 3;

        ob_start();
        treeview_ordner(0, '');
        $output = ob_get_clean();

        $this->assertStringContainsString('Screenshot', $output);
    }

    #[Test]
    public function treeviewOrdnerWithEmptyHead(): void
    {
        global $db_handler, $sordner_id, $settings, $sql_table, $release, $screen_id;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['ordner_id' => 1, 'name' => 'Sub'],
        ]);
        $db_handler->addResult([]);
        $sql_table = ['ordner' => 'pdl3_ordner'];
        $sordner_id = 99;
        $settings['script_file'] = 'dl.php?';
        $release = null;
        $screen_id = 0;

        ob_start();
        treeview_ordner(0, '');
        $output = ob_get_clean();

        $this->assertStringContainsString('spacer.gif', $output);
    }

    // ==================== treeview_pfeil() Tests ====================

    #[Test]
    public function treeviewPfeilRootFolder(): void
    {
        global $db_handler, $settings, $sql_table, $release_id, $screen_id, $ordner_id;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['ordner_id' => 1, 'sordner_id' => 0, 'name' => 'Root'],
        ]);
        $sql_table = ['ordner' => 'pdl3_ordner'];
        $settings['script_file'] = 'dl.php?';
        $release_id = 0;
        $screen_id = 0;
        $ordner_id = 1;

        ob_start();
        treeview_pfeil(1);
        $output = ob_get_clean();

        $this->assertStringContainsString('Index', $output);
        $this->assertStringContainsString('Root', $output);
    }

    #[Test]
    public function treeviewPfeilWithSubfolder(): void
    {
        global $db_handler, $settings, $sql_table, $release_id, $screen_id, $ordner_id;
        $db_handler = new MockDbHandler();
        // First call: folder 2 with parent 1
        $db_handler->addResult([
            ['ordner_id' => 2, 'sordner_id' => 1, 'name' => 'SubFolder'],
        ]);
        // Recursive call: folder 1 is root
        $db_handler->addResult([
            ['ordner_id' => 1, 'sordner_id' => 0, 'name' => 'Root'],
        ]);
        $sql_table = ['ordner' => 'pdl3_ordner'];
        $settings['script_file'] = 'dl.php?';
        $release_id = 0;
        $screen_id = 0;
        $ordner_id = 2;

        ob_start();
        treeview_pfeil(2);
        $output = ob_get_clean();

        $this->assertStringContainsString('Index', $output);
        $this->assertStringContainsString('Root', $output);
        $this->assertStringContainsString('&raquo;', $output);
        $this->assertStringContainsString('SubFolder', $output);
    }

    #[Test]
    public function treeviewPfeilWithReleaseLink(): void
    {
        global $db_handler, $settings, $sql_table, $release_id, $screen_id, $ordner_id;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['ordner_id' => 1, 'sordner_id' => 0, 'name' => 'Root'],
        ]);
        $sql_table = ['ordner' => 'pdl3_ordner'];
        $settings['script_file'] = 'dl.php?';
        $release_id = 5;
        $screen_id = 0;
        $ordner_id = 99;

        ob_start();
        treeview_pfeil(1);
        $output = ob_get_clean();

        $this->assertStringContainsString('<a href=', $output);
        $this->assertStringContainsString('ordner_id=1', $output);
    }

    // ==================== treeview_select() Tests ====================

    #[Test]
    public function treeviewSelectReturnsEmptyForNoFolders(): void
    {
        global $db_handler, $sql_table;
        treeview_select_reset_cache();
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]);
        $sql_table = ['ordner' => 'pdl3_ordner'];

        $result = treeview_select(0, '');
        $this->assertSame('', $result);
    }

    #[Test]
    public function treeviewSelectReturnsOptions(): void
    {
        global $db_handler, $sql_table;
        treeview_select_reset_cache();
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['ordner_id' => 1, 'sordner_id' => 0, 'name' => 'Folder1'],
            ['ordner_id' => 2, 'sordner_id' => 0, 'name' => 'Folder2'],
        ]);
        $sql_table = ['ordner' => 'pdl3_ordner'];

        $result = treeview_select(0, '');
        $this->assertStringContainsString('<option value="1">Folder1</option>', $result);
        $this->assertStringContainsString('<option value="2">Folder2</option>', $result);
    }

    #[Test]
    public function treeviewSelectNested(): void
    {
        global $db_handler, $sql_table;
        treeview_select_reset_cache();
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['ordner_id' => 1, 'sordner_id' => 0, 'name' => 'Parent'],
            ['ordner_id' => 2, 'sordner_id' => 1, 'name' => 'Child'],
        ]);
        $sql_table = ['ordner' => 'pdl3_ordner'];

        $result = treeview_select(0, '');
        $this->assertStringContainsString('Parent', $result);
        $this->assertStringContainsString('-Child', $result);
    }
}
