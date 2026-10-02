<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit\Installer;

use PHPUnit\Framework\Attributes\Test;

/**
 * Texte von Installer und update.php: Sie-Form, echte Umlaute.
 */
final class WordingTest extends InstallerTestCase
{
    /**
     * @return list<string>
     */
    private static function files(): array
    {
        $root = self::rootDir();
        $files = array_merge(
            glob($root . '/pdl-inc/installer/*.php') ?: [],
            glob($root . '/pdl-inc/installer/templates/*.php') ?: [],
            [$root . '/install.php', $root . '/update.php', $root . '/pdl-inc/pdl_setup_required.inc.php', $root . '/pdl-inc/pdl_localconfig.inc.php', $root . '/pdl-inc/pdl_config.local.example.php'],
        );
        sort($files);

        return $files;
    }

    #[Test]
    public function noPseudoUmlautsInProseAndStrings(): void
    {
        $words = '/\b(fuer|ueber|koennen|muessen|moechten|waehlen|pruefen|loeschen|geloescht|aendern|geaendert|schliessen|groesse|strasse|zurueck|naechste|spaeter|ausfuehren|ausgefuehrt|hinzufuegen|uebernehmen|uebernommen|verfuegbar|ungueltig|gueltig|aktualisierung|pruefung|eintraege|schluessel|unveraendert|zugaenglich)\b/i';

        foreach (self::files() as $file) {
            $lines = file($file) ?: [];

            foreach ($lines as $number => $line) {
                // Bezeichner (z. B. $updater, Methodennamen) zählen nicht, nur Wörter.
                self::assertDoesNotMatchRegularExpression($words, (string) preg_replace('/\$\w+|->\w+|::\w+|\w+\(/', '', $line), basename($file) . ':' . ($number + 1));
            }
        }
    }

    #[Test]
    public function visitorsAreAddressedFormally(): void
    {
        foreach (self::files() as $file) {
            $text = (string) file_get_contents($file);

            self::assertDoesNotMatchRegularExpression('/\b(du|dein|deine|deinen|dir|dich|fuehre|führe)\b/u', $text, basename($file));
        }
    }
}
