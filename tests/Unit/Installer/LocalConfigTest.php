<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit\Installer;

use PHPUnit\Framework\Attributes\Test;
use PowerDownload\LocalConfig;

final class LocalConfigTest extends InstallerTestCase
{
    /**
     * @param array<string, string> $env
     *
     * @return \Closure(string): (string|false)
     */
    private static function env(array $env): \Closure
    {
        return static fn (string $name): string|false => $env[$name] ?? false;
    }

    private function writeLocal(string $root, string $php): string
    {
        $file = $root . '/pdl-inc/' . LocalConfig::FILENAME;
        file_put_contents($file, $php);

        return $file;
    }

    #[Test]
    public function withoutEnvironmentAndFileTheDefaultsApply(): void
    {
        $root = $this->makeRoot();
        $config = LocalConfig::load(self::env([]), $root . '/pdl-inc/' . LocalConfig::FILENAME);

        self::assertSame(LocalConfig::SOURCE_DEFAULTS, $config['source']);
        self::assertSame(LocalConfig::DEFAULT_DB, $config['db']);
        self::assertFalse(LocalConfig::isConfigured($config));
        self::assertSame('pdl3', $config['db']['database']);
        self::assertSame('localhost', $config['db']['host']);
        self::assertNotSame('pdl_user', $config['db']['user'], 'Die Vorgaben dürfen keine Docker-Zugangsdaten enthalten.');
    }

    #[Test]
    public function fileValuesOverrideDefaults(): void
    {
        $root = $this->makeRoot();
        $file = $this->writeLocal($root, "<?php return ['db' => ['host' => 'sql.example.org', 'port' => 3307, 'user' => 'u', 'password' => 'p', 'database' => 'd']];");
        $config = LocalConfig::load(self::env([]), $file);

        self::assertSame(LocalConfig::SOURCE_FILE, $config['source']);
        self::assertSame(['host' => 'sql.example.org', 'port' => 3307, 'user' => 'u', 'password' => 'p', 'database' => 'd', 'persistent' => false], $config['db']);
        self::assertTrue(LocalConfig::isConfigured($config));
    }

    #[Test]
    public function environmentOverridesFileKeyByKey(): void
    {
        $root = $this->makeRoot();
        $file = $this->writeLocal($root, "<?php return ['db' => ['host' => 'filehost', 'user' => 'fileuser', 'password' => 'filepw', 'database' => 'filedb']];");
        $config = LocalConfig::load(self::env(['PDL_DB_HOST' => 'db', 'PDL_DB_NAME' => 'pdl3']), $file);

        self::assertSame(LocalConfig::SOURCE_ENVIRONMENT, $config['source']);
        self::assertSame('db', $config['db']['host']);
        self::assertSame('pdl3', $config['db']['database']);
        self::assertSame('fileuser', $config['db']['user'], 'Nicht gesetzte Variablen lassen den Wert aus der Datei stehen.');
        self::assertSame('filepw', $config['db']['password']);
    }

    #[Test]
    public function emptyVariablesDoNotCountExceptThePassword(): void
    {
        $values = LocalConfig::fromEnvironment(self::env([
            'PDL_DB_HOST' => '  ',
            'PDL_DB_USER' => '',
            'PDL_DB_NAME' => '',
            'PDL_DB_PASS' => '',
            'PDL_DB_PORT' => 'abc',
        ]));

        self::assertSame(['password' => ''], $values);
    }

    #[Test]
    public function portFromEnvironmentMustBeInRange(): void
    {
        self::assertSame(['port' => 3307], LocalConfig::fromEnvironment(self::env(['PDL_DB_PORT' => ' 3307 '])));
        self::assertSame([], LocalConfig::fromEnvironment(self::env(['PDL_DB_PORT' => '70000'])));
        self::assertSame([], LocalConfig::fromEnvironment(self::env(['PDL_DB_PORT' => '0'])));
    }

    #[Test]
    public function applyIgnoresWrongTypesAndUnknownKeys(): void
    {
        $db = LocalConfig::apply(LocalConfig::DEFAULT_DB, ['db' => ['host' => 42, 'port' => '3307', 'user' => 'x', 'persistent' => 'yes', 'foo' => 'bar']]);

        self::assertSame('localhost', $db['host']);
        self::assertSame(3306, $db['port']);
        self::assertSame('x', $db['user']);
        self::assertFalse($db['persistent']);
        self::assertSame(LocalConfig::DEFAULT_DB, LocalConfig::apply(LocalConfig::DEFAULT_DB, 'kein Array'));
    }

    #[Test]
    public function renderedFileReturnsExactlyTheSameValues(): void
    {
        $root = $this->makeRoot();
        $db = [
            'host' => 'sql.example.org',
            'port' => 3306,
            'user' => "o'brien",
            'password' => "a'b\"c\\d\$e?>f\nzweite Zeile <?php",
            'database' => 'downloads_db',
            'persistent' => false,
        ];
        $source = LocalConfig::render($db, '2026-10-02 14:03:11');
        $file = $root . '/pdl-inc/' . LocalConfig::FILENAME;

        self::assertTrue(LocalConfig::writeFile($file, $source));
        self::assertSame($db, (require $file)['db']);
        self::assertStringContainsString('Erzeugt vom Web-Installer am 2026-10-02 14:03:11.', $source);
        self::assertSame($db, LocalConfig::load(self::env([]), $file)['db']);
    }

    #[Test]
    public function renderStripsUnexpectedCharactersFromTheTimestamp(): void
    {
        $source = LocalConfig::render(LocalConfig::DEFAULT_DB, "2026-10-02 */ evil(); /*");

        self::assertStringNotContainsString('evil', $source);
    }

    #[Test]
    public function writeFileNeverOverwrites(): void
    {
        $root = $this->makeRoot();
        $file = $root . '/pdl-inc/' . LocalConfig::FILENAME;
        file_put_contents($file, 'alt');

        self::assertFalse(LocalConfig::writeFile($file, 'neu'));
        self::assertSame('alt', file_get_contents($file));
    }

    #[Test]
    public function writeFileFailsWithoutDirectory(): void
    {
        $root = $this->makeRoot();

        self::assertFalse(LocalConfig::writeFile($root . '/fehlt/' . LocalConfig::FILENAME, 'x'));
    }

    #[Test]
    public function configFileSetsTheGlobalVariablesFromLocalConfig(): void
    {
        $result = (static function (): array {
            $sql_table = null;
            include dirname(__DIR__, 3) . '/pdl-inc/pdl_config.inc.php';

            return [
                'port' => $config_sql_port,
                'type' => $config_sql_type,
                'source' => $config_source,
                'tables' => $sql_table,
            ];
        })();

        self::assertIsInt($result['port']);
        self::assertSame('MySQL', $result['type']);
        self::assertContains($result['source'], [LocalConfig::SOURCE_DEFAULTS, LocalConfig::SOURCE_ENVIRONMENT, LocalConfig::SOURCE_FILE]);
        self::assertIsArray($result['tables']);
        self::assertCount(15, $result['tables']);
    }

    #[Test]
    public function exampleFileIsAValidTemplate(): void
    {
        $example = require self::rootDir() . '/pdl-inc/pdl_config.local.example.php';
        $db = LocalConfig::apply(LocalConfig::DEFAULT_DB, $example);

        self::assertSame('DATENBANKNAME', $db['database']);
        self::assertSame(3306, $db['port']);
    }
}
