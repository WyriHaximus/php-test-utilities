<?php declare(strict_types=1);

$root = dirname(__DIR__);
$composerFile = $root . '/composer.json';
$readmeFile = $root . '/README.md';

if (! is_file($composerFile)) {
    fwrite(STDERR, "composer.json not found at {$composerFile}\n");

    exit(1);
}

if (! is_file($readmeFile)) {
    fwrite(STDERR, "README.md not found at {$readmeFile}\n");

    exit(1);
}

/** @var list<string> $wrapperPackages */
$wrapperPackages = [
    'wyrihaximus/qa-tooling-wrapper',
    'wyrihaximus/phpstan-rules-wrapper',
];

/**
 * @param array<string, string> $require
 *
 * @return list<string>
 */
function collectPackages(array $require): array
{
    $packages = [];

    foreach (array_keys($require) as $package) {
        if ($package === 'php' || str_starts_with($package, 'ext-') || $package === 'composer-plugin-api') {
            continue;
        }

        $packages[] = $package;
    }

    sort($packages, SORT_STRING);

    return $packages;
}

/**
 * @param list<string> $packages
 */
function renderPackageList(array $packages): string
{
    if ($packages === []) {
        return '_No tooling packages._';
    }

    return implode(
        "\n",
        array_map(
            static fn (string $package): string => sprintf(
                '* [`%s`](https://packagist.org/packages/%s)',
                $package,
                $package,
            ),
            $packages,
        ),
    );
}

/** @var array<string, list<string>> $toolingBySource */
$toolingBySource = [];

foreach ($wrapperPackages as $wrapperPackage) {
    $wrapperComposerFile = $root . '/vendor/' . $wrapperPackage . '/composer.json';

    if (! is_file($wrapperComposerFile)) {
        $toolingBySource[$wrapperPackage] = [];

        continue;
    }

    /** @var array<string, mixed> $wrapperComposer */
    $wrapperComposer = json_decode((string) file_get_contents($wrapperComposerFile), true, 512, JSON_THROW_ON_ERROR);

    /** @var array<string, string> $wrapperRequire */
    $wrapperRequire = $wrapperComposer['require'] ?? [];

    $toolingBySource[$wrapperPackage] = collectPackages($wrapperRequire);
}

$readme = (string) file_get_contents($readmeFile);
$newReadme = $readme;
$updatedSections = 0;
$missingSections = [];

foreach ($toolingBySource as $source => $packages) {
    $pattern = sprintf(
        '/(<!-- included-tooling:%s:start -->\r?\n)(.*?)(\r?\n<!-- included-tooling:%s:end -->)/s',
        preg_quote($source, '/'),
        preg_quote($source, '/'),
    );
    $list = renderPackageList($packages);
    $sectionReadme = preg_replace($pattern, '$1' . $list . '$3', $newReadme, 1, $count);

    if ($count !== 1 || ! is_string($sectionReadme)) {
        $missingSections[] = $source;

        continue;
    }

    if ($sectionReadme !== $newReadme) {
        ++$updatedSections;
    }

    $newReadme = $sectionReadme;
}

if ($missingSections !== []) {
    fwrite(
        STDERR,
        'Skipped missing Included tooling sections in README.md: ' . implode(', ', $missingSections) . "\n",
    );
}

if ($updatedSections === 0) {
    exit(0);
}

file_put_contents($readmeFile, $newReadme);
