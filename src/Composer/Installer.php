<?php

declare(strict_types=1);

namespace WyriHaximus\TestUtilities\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Exception;

use function array_key_exists;
use function array_keys;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function hash;
use function hash_equals;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function preg_replace;

use const DIRECTORY_SEPARATOR;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const PHP_INT_MAX;

final class Installer implements PluginInterface, EventSubscriberInterface
{
    /** @return array<string, array<string|int>> */
    public static function getSubscribedEvents(): array
    {
        return [ScriptEvents::PRE_AUTOLOAD_DUMP => ['findEventListeners', PHP_INT_MAX]];
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        // does nothing, see getSubscribedEvents() instead.
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // does nothing, see getSubscribedEvents() instead.
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // does nothing, see getSubscribedEvents() instead.
    }

    /**
     * Called before every dump autoload, generates a fresh PHP class.
     *
     * @phpstan-ignore shipmonk.deadMethod
     */
    public static function findEventListeners(Event $event): void
    {
        $rootPackagePath = dirname(self::getVendorDir($event->getComposer())) . DIRECTORY_SEPARATOR;
        if (! file_exists($rootPackagePath . '/composer.json')) {
            return;
        }

        $jsonRaw = file_get_contents($rootPackagePath . '/composer.json');
        if (! is_string($jsonRaw)) {
            return;
        }

        $json = json_decode($jsonRaw, true);
        if (! is_array($json)) {
            return;
        }

        if (! array_key_exists('require-dev', $json)) {
            return;
        }

        if (! is_array($json['require-dev'])) {
            return;
        }

        $hasMakefiles = false;
        foreach (array_keys($json['require-dev']) as $package) {
            if ($package === 'wyrihaximus/makefiles') {
                $hasMakefiles = true;
                break;
            }
        }

        if (! $hasMakefiles) {
            return;
        }

        unset($hasMakefiles);

        if (array_key_exists('name', $json) && $json['name'] === 'wyrihaximus/test-utilities') {
            self::addMakeOnInstallOrUpdate($event->getIO(), $rootPackagePath);

            return;
        }

        foreach (array_keys($json['require-dev']) as $package) {
            if ($package === 'wyrihaximus/test-utilities') {
                self::addMakeOnInstallOrUpdate($event->getIO(), $rootPackagePath);

                return;
            }
        }
    }

    private static function addMakeOnInstallOrUpdate(IOInterface $io, string $rootPackagePath): void
    {
        $io->write('<info>wyrihaximus/test-utilities:</info> Adding <fg=cyan>make on-install-or-update || true</> to scripts');
        $composerJsonString = file_get_contents($rootPackagePath . '/composer.json');
        if (! is_string($composerJsonString)) {
            $io->write('<error>wyrihaximus/test-utilities:</error> Unable to read <fg=cyan>composer.json</> aborting');

            return;
        }

        $composerJson     = json_decode($composerJsonString, true);
        $composerJsonHash = hash('sha512', (string) json_encode($composerJson, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        if (is_array($composerJson)) {
            if (! (array_key_exists('scripts', $composerJson) && is_array($composerJson['scripts']))) {
                $composerJson['scripts'] = [];
            }

            if (! array_key_exists('post-install-cmd', $composerJson['scripts'])) {
                $composerJson['scripts']['post-install-cmd'] = [];
            }

            $composerJson['scripts']['post-install-cmd'] = self::addMakeOnInstallOrUpdateToScriptsSectionAndRemoveCommandsItReplaces($composerJson['scripts']['post-install-cmd']);

            if (! array_key_exists('post-update-cmd', $composerJson['scripts'])) {
                $composerJson['scripts']['post-update-cmd'] = [];
            }

            $composerJson['scripts']['post-update-cmd'] = self::addMakeOnInstallOrUpdateToScriptsSectionAndRemoveCommandsItReplaces($composerJson['scripts']['post-update-cmd']);
        }

        $replacementComposerJsonString = json_encode($composerJson, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if (is_string($replacementComposerJsonString)) {
            $replacementComposerJsonHash = hash('sha512', $replacementComposerJsonString);
            if (! hash_equals($composerJsonHash, $replacementComposerJsonHash)) {
                $replacementComposerJsonString = preg_replace('/^(  +?)\\1(?=[^ ])/m', '$1', $replacementComposerJsonString);
                if (is_string($replacementComposerJsonString)) {
                    $io->write('<info>wyrihaximus/test-utilities:</info> Writing new <fg=cyan>composer.json</>');
                    file_put_contents($rootPackagePath . '/composer.json', $replacementComposerJsonString);
                }
            }
        }

        if (is_array($composerJson) && array_key_exists('scripts', $composerJson) && is_array($composerJson['scripts'])) {
            if (array_key_exists('post-install-cmd', $composerJson['scripts'])) {
                $composerJson['scripts']['post-install-cmd'] = self::addMakeOnInstallOrUpdateToScriptsSectionAndRemoveCommandsItReplaces($composerJson['scripts']['post-install-cmd']);
            }

            if (array_key_exists('post-update-cmd', $composerJson['scripts'])) {
                $composerJson['scripts']['post-update-cmd'] = self::addMakeOnInstallOrUpdateToScriptsSectionAndRemoveCommandsItReplaces($composerJson['scripts']['post-update-cmd']);
            }
        }

        $replacementComposerJsonString = json_encode($composerJson, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if (is_string($replacementComposerJsonString)) {
            $replacementComposerJsonHash = hash('sha512', $replacementComposerJsonString);
            if (! hash_equals($composerJsonHash, $replacementComposerJsonHash)) {
                $replacementComposerJsonString = preg_replace('/^(  +?)\\1(?=[^ ])/m', '$1', $replacementComposerJsonString);
                if (is_string($replacementComposerJsonString)) {
                    $io->write('<info>wyrihaximus/test-utilities:</info> Writing new <fg=cyan>composer.json</>');
                    file_put_contents($rootPackagePath . '/composer.json', $replacementComposerJsonString . "\r\n");
                }
            }
        }

        $io->write('<info>wyrihaximus/test-utilities:</info> Finished <fg=cyan>make on-install-or-update || true</> to scripts');
    }

    /**
     * @param array<int, string> $scriptsSection
     *
     * @return array<int, string>
     */
    private static function addMakeOnInstallOrUpdateToScriptsSectionAndRemoveCommandsItReplaces(array $scriptsSection): array
    {
        foreach ($scriptsSection as $script) {
            if ($script === 'make on-install-or-update || true') {
                return $scriptsSection;
            }
        }

        $scripts = [];
        foreach ($scriptsSection as $script) {
            if ($script === 'composer normalize' || $script === 'composer update --lock --no-scripts') {
                continue;
            }

            $scripts[] = $script;
        }

        $scripts[] = 'make on-install-or-update || true';

        return $scripts;
    }

    /** @return non-empty-string */
    private static function getVendorDir(Composer $composer): string
    {
        $vendorDir = $composer->getConfig()->get('vendor-dir');
        if ($vendorDir === '' || ! file_exists($vendorDir)) {
            throw new Exception('vendor-dir must be a string');
        }

        return $vendorDir;
    }
}
