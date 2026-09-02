<?php

declare(strict_types=1);

namespace WyriHaximus\Tests\TestUtilities\Composer;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Throwable;
use WyriHaximus\TestUtilities\Composer\Installer;
use WyriHaximus\TestUtilities\TestCase;

use function array_filter;
use function chmod;
use function clearstatcache;
use function count;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function hash;
use function hash_equals;
use function implode;
use function is_dir;
use function json_decode;
use function json_encode;
use function mkdir;
use function str_contains;
use function symlink;
use function sys_get_temp_dir;
use function uniqid;

use const DIRECTORY_SEPARATOR;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const PHP_INT_MAX;
use const PHP_OS_FAMILY;

final class InstallerTest extends TestCase
{
    private string $projectRoot = '';

    private string $realComposerJsonHash = '';

    /** @var list<string> */
    private array $ioMessages = [];

    protected function setUp(): void
    {
        parent::setUp();

        $realComposerJson = file_get_contents($this->realComposerJsonPath());
        self::assertIsString($realComposerJson);
        $this->realComposerJsonHash = hash('sha512', $realComposerJson);
    }

    protected function tearDown(): void
    {
        if ($this->projectRoot !== '' && is_dir($this->projectRoot)) {
            try {
                $this->rmdir($this->projectRoot);
            } catch (Throwable) {
            }
        }

        $realComposerJson = file_get_contents($this->realComposerJsonPath());
        self::assertIsString($realComposerJson);
        self::assertTrue(
            hash_equals($this->realComposerJsonHash, hash('sha512', $realComposerJson)),
            'The project composer.json must not be modified by Installer tests.',
        );

        parent::tearDown();
    }

    #[Test]
    public function getSubscribedEvents(): void
    {
        self::assertSame(
            [ScriptEvents::PRE_AUTOLOAD_DUMP => ['findEventListeners', PHP_INT_MAX]],
            Installer::getSubscribedEvents(),
        );
    }

    #[Test]
    public function pluginLifecycleMethodsDoNothing(): void
    {
        $installer = new Installer();
        $composer  = Mockery::mock(Composer::class);
        $io        = Mockery::mock(IOInterface::class);
        $io->shouldNotReceive('write');

        $installer->activate($composer, $io);
        $installer->deactivate($composer, $io);
        $installer->uninstall($composer, $io);
    }

    #[Test]
    public function findEventListenersReturnsWhenComposerJsonIsMissing(): void
    {
        $this->createProject([]);

        Installer::findEventListeners($this->createEvent());

        self::assertSame([], $this->ioMessages);
    }

    #[Test]
    public function findEventListenersReturnsWhenComposerJsonIsUnreadable(): void
    {
        $this->createProject([]);
        $this->createUnreadableComposerJson();

        /** @phpstan-ignore ergebnis.noErrorSuppression */
        @Installer::findEventListeners($this->createEvent());

        self::assertSame([], $this->ioMessages);
    }

    #[Test]
    public function findEventListenersReturnsWhenComposerJsonIsInvalid(): void
    {
        $this->createProject(['composer.json' => '{invalid']);

        Installer::findEventListeners($this->createEvent());

        self::assertSame([], $this->ioMessages);
    }

    #[Test]
    public function findEventListenersReturnsWhenRequireDevIsMissing(): void
    {
        $this->createProject([
            'composer.json' => $this->encodeJson(['name' => 'example/project']),
        ]);

        Installer::findEventListeners($this->createEvent());

        self::assertSame([], $this->ioMessages);
    }

    #[Test]
    public function findEventListenersReturnsWhenRequireDevIsNotAnArray(): void
    {
        $this->createProject([
            'composer.json' => $this->encodeJson([
                'name'        => 'example/project',
                'require-dev' => 'wyrihaximus/makefiles',
            ]),
        ]);

        Installer::findEventListeners($this->createEvent());

        self::assertSame([], $this->ioMessages);
    }

    #[Test]
    public function findEventListenersReturnsWhenMakefilesIsMissing(): void
    {
        $this->createProject([
            'composer.json' => $this->encodeJson([
                'name'        => 'example/project',
                'require-dev' => ['phpunit/phpunit' => '^13'],
            ]),
        ]);

        Installer::findEventListeners($this->createEvent());

        self::assertSame([], $this->ioMessages);
    }

    #[Test]
    public function findEventListenersReturnsWhenMakefilesIsMissingForSelfPackage(): void
    {
        $this->createProject([
            'composer.json' => $this->encodeJson([
                'name'        => 'wyrihaximus/test-utilities',
                'require-dev' => ['phpunit/phpunit' => '^13'],
            ]),
        ]);

        Installer::findEventListeners($this->createEvent());

        self::assertSame([], $this->ioMessages);
    }

    #[Test]
    public function findEventListenersUpdatesSelfPackageOnlyOnceWhenAlsoListedAsDependency(): void
    {
        $this->createProject([
            'composer.json' => $this->encodeJson([
                'name'        => 'wyrihaximus/test-utilities',
                'require-dev' => [
                    'wyrihaximus/makefiles'        => '^0.14',
                    'wyrihaximus/test-utilities'   => '^1.0',
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ]);

        Installer::findEventListeners($this->createEvent());

        self::assertComposerScriptsContainMakeCommand();
        self::assertSame(1, $this->countIoMessagesContaining('Adding <fg=cyan>make on-install-or-update || true</>'));
    }

    #[Test]
    public function findEventListenersUpdatesSelfPackage(): void
    {
        $this->createProject([
            'composer.json' => $this->encodeJson([
                'name'        => 'wyrihaximus/test-utilities',
                'require-dev' => ['wyrihaximus/makefiles' => '^0.14'],
            ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ]);

        Installer::findEventListeners($this->createEvent());

        self::assertComposerScriptsContainMakeCommand();
    }

    /** @return iterable<string, array{0: string, 1: string}> */
    public static function provideConsumerPackages(): iterable
    {
        yield 'require-dev dependency' => ['require-dev', 'wyrihaximus/test-utilities'];
        yield 'require dependency' => ['require', 'wyrihaximus/async-test-utilities'];
        yield 'compress test utilities' => ['require-dev', 'wyrihaximus/compress-test-utilities'];
        yield 'react mutex test utilities' => ['require-dev', 'wyrihaximus/react-mutex-test-utilities'];
        yield 'mixed case package name' => ['require-dev', 'WyriHaximus/Test-Utilities'];
    }

    #[Test]
    #[DataProvider('provideConsumerPackages')]
    public function findEventListenersUpdatesConsumerPackage(string $section, string $package): void
    {
        $composerJson = [
            'name'        => 'example/project',
            'require'     => [],
            'require-dev' => ['wyrihaximus/makefiles' => '^0.14'],
        ];

        if ($section === 'require-dev') {
            $composerJson['require-dev'][$package] = '^1.0';
        } else {
            $composerJson['require'] = [$package => '^1.0'];
        }

        $this->createProject([
            'composer.json' => $this->encodeJson($composerJson, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ]);

        Installer::findEventListeners($this->createEvent());

        self::assertComposerScriptsContainMakeCommand();
    }

    #[Test]
    public function findEventListenersSkipsNonArrayRequirementSections(): void
    {
        $this->createProject([
            'composer.json' => $this->encodeJson([
                'name'        => 'example/project',
                'require-dev' => ['wyrihaximus/makefiles' => '^0.14'],
                'require'     => 'wyrihaximus/test-utilities',
            ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ]);

        Installer::findEventListeners($this->createEvent());

        self::assertSame([], $this->ioMessages);
    }

    #[Test]
    public function findEventListenersUpdatesWhenRequireIsInvalidButRequireDevHasTestUtilities(): void
    {
        $this->createProject([
            'composer.json' => $this->encodeJson([
                'name'        => 'example/project',
                'require'     => 'wyrihaximus/test-utilities',
                'require-dev' => [
                    'wyrihaximus/makefiles'        => '^0.14',
                    'wyrihaximus/test-utilities'   => '^1.0',
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ]);

        Installer::findEventListeners($this->createEvent());

        self::assertComposerScriptsContainMakeCommand();
    }

    #[Test]
    public function findEventListenersUpdatesOnlyOnceWhenMultipleTestUtilitiesArePresent(): void
    {
        $this->createProject([
            'composer.json' => $this->encodeJson([
                'name'        => 'example/project',
                'require-dev' => [
                    'wyrihaximus/makefiles'              => '^0.14',
                    'wyrihaximus/test-utilities'         => '^1.0',
                    'wyrihaximus/async-test-utilities'   => '^1.0',
                ],
                'require'     => [],
            ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ]);

        Installer::findEventListeners($this->createEvent());

        self::assertComposerScriptsContainMakeCommand();
        self::assertSame(1, $this->countIoMessagesContaining('Adding <fg=cyan>make on-install-or-update || true</>'));
    }

    #[Test]
    public function isAbsolutePath(): void
    {
        $method = new ReflectionMethod(Installer::class, 'isAbsolutePath');

        self::assertTrue($method->invoke(null, '/tmp/project/composer.json'));
        self::assertTrue($method->invoke(null, 'C:\\project\\composer.json'));
        self::assertFalse($method->invoke(null, 'composer.json'));
        self::assertFalse($method->invoke(null, 'relative/C:/project/composer.json'));
    }

    #[Test]
    public function addMakeOnInstallOrUpdateRefusesRelativeComposerJsonPath(): void
    {
        $realComposerJson = file_get_contents($this->realComposerJsonPath());
        self::assertIsString($realComposerJson);

        $method = new ReflectionMethod(Installer::class, 'addMakeOnInstallOrUpdate');
        $method->invoke(null, $this->createIo(), '');

        self::assertStringContainsString(
            'Refusing to write relative <fg=cyan>composer.json</> aborting',
            implode("\n", $this->ioMessages),
        );
        self::assertSame($realComposerJson, file_get_contents($this->realComposerJsonPath()));
    }

    #[Test]
    public function addMakeOnInstallOrUpdateAbortsWhenComposerJsonIsUnreadable(): void
    {
        $this->createProject([]);

        $method = new ReflectionMethod(Installer::class, 'addMakeOnInstallOrUpdate');
        /** @phpstan-ignore ergebnis.noErrorSuppression */
        @$method->invoke(null, $this->createIo(), $this->projectRoot);

        self::assertStringContainsString('Unable to read <fg=cyan>composer.json</> aborting', implode("\n", $this->ioMessages));
    }

    #[Test]
    public function addMakeOnInstallOrUpdateCreatesScriptsSection(): void
    {
        $this->createProject([
            'composer.json' => $this->encodeJson([
                'name'        => 'wyrihaximus/test-utilities',
                'require-dev' => ['wyrihaximus/makefiles' => '^0.14'],
                'scripts'     => 'invalid',
            ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ]);

        Installer::findEventListeners($this->createEvent());

        self::assertComposerScriptsContainMakeCommand();
    }

    #[Test]
    public function addMakeOnInstallOrUpdateReplacesLegacyScripts(): void
    {
        $this->createProject([
            'composer.json' => $this->encodeJson([
                'name'        => 'wyrihaximus/test-utilities',
                'require-dev' => ['wyrihaximus/makefiles' => '^0.14'],
                'scripts'     => [
                    'post-install-cmd' => [
                        'composer normalize',
                        'composer update --lock --no-scripts',
                        'echo keep-me',
                    ],
                    'post-update-cmd' => [
                        'composer normalize',
                        'echo keep-me-too',
                    ],
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ]);

        Installer::findEventListeners($this->createEvent());

        $composerJson = $this->readComposerJson();
        $scripts      = $composerJson['scripts'];
        self::assertIsArray($scripts);

        self::assertSame(
            ['echo keep-me', 'make on-install-or-update || true'],
            $scripts['post-install-cmd'],
        );
        self::assertSame(
            ['echo keep-me-too', 'make on-install-or-update || true'],
            $scripts['post-update-cmd'],
        );
    }

    #[Test]
    public function addMakeOnInstallOrUpdateLeavesExistingMakeScriptUntouched(): void
    {
        $this->createProject([
            'composer.json' => $this->encodeJson([
                'name'        => 'wyrihaximus/test-utilities',
                'require-dev' => ['wyrihaximus/makefiles' => '^0.14'],
                'scripts'     => [
                    'post-install-cmd' => ['make on-install-or-update || true'],
                    'post-update-cmd'  => ['make on-install-or-update || true'],
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ]);

        $before = file_get_contents($this->projectRoot . 'composer.json');

        Installer::findEventListeners($this->createEvent());

        self::assertSame($before, file_get_contents($this->projectRoot . 'composer.json'));
        self::assertStringContainsString('Finished <fg=cyan>make on-install-or-update || true</> to scripts', implode("\n", $this->ioMessages));
    }

    #[Test]
    public function getVendorDirThrowsWhenVendorDirectoryDoesNotExist(): void
    {
        $this->createProject([
            'composer.json' => $this->encodeJson([
                'name'        => 'wyrihaximus/test-utilities',
                'require-dev' => ['wyrihaximus/makefiles' => '^0.14'],
            ]),
        ]);
        $this->rmdir($this->projectRoot . 'vendor');

        $this->expectException(Throwable::class);
        $this->expectExceptionMessageIsOrContains('vendor-dir must be a string');

        Installer::findEventListeners($this->createEvent());
    }

    #[Test]
    public function getVendorDirThrowsWhenVendorDirectoryIsEmpty(): void
    {
        $this->createProject([
            'composer.json' => $this->encodeJson([
                'name'        => 'wyrihaximus/test-utilities',
                'require-dev' => ['wyrihaximus/makefiles' => '^0.14'],
            ]),
        ]);

        $event = new Event(
            ScriptEvents::PRE_AUTOLOAD_DUMP,
            $this->createComposer(''),
            $this->createIo(),
        );

        $this->expectException(Throwable::class);
        $this->expectExceptionMessageIsOrContains('vendor-dir must be a string');

        Installer::findEventListeners($event);
    }

    /** @param array<string, string> $files */
    private function createProject(array $files): void
    {
        $this->projectRoot = sys_get_temp_dir() .
            DIRECTORY_SEPARATOR .
            'w-h-installer-' .
            uniqid() .
            DIRECTORY_SEPARATOR;

        mkdir($this->projectRoot . 'vendor', 0o777, true);

        foreach ($files as $path => $contents) {
            file_put_contents($this->projectRoot . $path, $contents);
        }
    }

    /** @param array<string, mixed> $data */
    private function encodeJson(array $data, int $flags = JSON_UNESCAPED_SLASHES): string
    {
        $json = json_encode($data, $flags);
        self::assertIsString($json);

        return $json;
    }

    private function createEvent(): Event
    {
        return new Event(
            ScriptEvents::PRE_AUTOLOAD_DUMP,
            $this->createComposer($this->projectRoot . 'vendor'),
            $this->createIo(),
        );
    }

    private function createComposer(string $vendorDir): Composer
    {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('get')->with('vendor-dir')->andReturn($vendorDir);

        $composer = Mockery::mock(Composer::class);
        $composer->shouldReceive('getConfig')->andReturn($config);

        return $composer;
    }

    private function createIo(): IOInterface
    {
        $io = Mockery::mock(IOInterface::class);
        $io->shouldReceive('write')->andReturnUsing(function (string $message): void {
            $this->ioMessages[] = $message;
        });

        return $io;
    }

    private function assertComposerScriptsContainMakeCommand(): void
    {
        $composerJson = $this->readComposerJson();
        $scripts      = $composerJson['scripts'];
        self::assertIsArray($scripts);

        self::assertSame(['make on-install-or-update || true'], $scripts['post-install-cmd']);
        self::assertSame(['make on-install-or-update || true'], $scripts['post-update-cmd']);

        $composerJsonRaw = file_get_contents($this->projectRoot . 'composer.json');
        self::assertIsString($composerJsonRaw);
        self::assertStringEndsWith("\r\n", $composerJsonRaw);

        self::assertSame(1, $this->countIoMessagesContaining('Adding <fg=cyan>make on-install-or-update || true</>'));
        self::assertSame(1, $this->countIoMessagesContaining('Writing new <fg=cyan>composer.json</>'));
    }

    private function countIoMessagesContaining(string $needle): int
    {
        return count(array_filter(
            $this->ioMessages,
            static fn (string $message): bool => str_contains($message, $needle),
        ));
    }

    private function realComposerJsonPath(): string
    {
        return dirname(__DIR__, 2) . '/composer.json';
    }

    /** @return array<string, mixed> */
    private function readComposerJson(): array
    {
        $jsonRaw = file_get_contents($this->projectRoot . 'composer.json');
        self::assertIsString($jsonRaw);

        $decoded = json_decode($jsonRaw, true);
        self::assertIsArray($decoded);

        /** @var array<string, mixed> $json */
        $json = $decoded;

        return $json;
    }

    private function createUnreadableComposerJson(): void
    {
        $composerJsonPath = $this->projectRoot . 'composer.json';

        if (PHP_OS_FAMILY === 'Windows') {
            mkdir($composerJsonPath);

            /** @phpstan-ignore ergebnis.noErrorSuppression */
            if (@file_get_contents($composerJsonPath) !== false) {
                self::markTestSkipped('Unable to create unreadable composer.json fixture');
            }

            return;
        }

        $target = $this->projectRoot . 'composer.json.target';
        file_put_contents($target, '{}');
        chmod($target, 0o000);
        clearstatcache();

        /** @phpstan-ignore ergebnis.noErrorSuppression */
        if (! @symlink($target, $composerJsonPath)) {
            self::markTestSkipped('Unable to create unreadable composer.json fixture');
        }

        /** @phpstan-ignore ergebnis.noErrorSuppression */
        if (@file_get_contents($composerJsonPath) === false) {
            return;
        }

        self::markTestSkipped('Unable to create unreadable composer.json fixture');
    }
}
