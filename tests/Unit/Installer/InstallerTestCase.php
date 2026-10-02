<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit\Installer;

use PHPUnit\Framework\TestCase;

// Schon beim Laden der Testklassen nötig (Datenprovider laufen vor setUpBeforeClass()).
require_once dirname(__DIR__, 3) . '/pdl-inc/installer/autoload.php';

/**
 * Gemeinsame Hilfen für die Installer-Tests: Autoloader und temporäre
 * Verzeichnisse, die nach jedem Test wieder entfernt werden.
 */
abstract class InstallerTestCase extends TestCase
{
    /** @var list<string> */
    private array $tempDirs = [];

    protected function tearDown(): void
    {
        foreach ($this->tempDirs as $dir) {
            self::removeDir($dir);
        }
        $this->tempDirs = [];
    }

    protected static function rootDir(): string
    {
        return dirname(__DIR__, 3);
    }

    /**
     * Leeres Verzeichnis mit den Unterverzeichnissen pdl-inc/ und logs/.
     */
    protected function makeRoot(): string
    {
        $dir = sys_get_temp_dir() . '/pdl_installer_' . bin2hex(random_bytes(6));
        mkdir($dir . '/pdl-inc', 0o777, true);
        mkdir($dir . '/logs', 0o777, true);
        $this->tempDirs[] = $dir;

        return $dir;
    }

    private static function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);

        foreach (is_array($items) ? $items : [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            if (is_dir($path)) {
                self::removeDir($path);
            } else {
                @chmod($path, 0o666);
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
