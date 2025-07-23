<?php

//ddev-generated
// If you want to take over this file and customize it, remove the line above
// and ddev will respect it and won't overwrite the file.

$extensionKey = getenv('EXTENSION_NAME');
$typo3AdminUser = getenv('TYPO3_INSTALL_ADMIN_USER');
$typo3AdminPassword = getenv('TYPO3_INSTALL_ADMIN_PASSWORD');
$supportedVersions = explode(' ', getenv('TYPO3_VERSIONS'));

// Check if composer.json exists
$composerJsonPath = __DIR__ . '/../composer.json';
if (file_exists($composerJsonPath)) {
    $composerJsonContent = file_get_contents($composerJsonPath);
    $composerData = json_decode($composerJsonContent, true);
    $description = $composerData['description'];
} else {
    $description = 'composer.json file not found. =(';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $extensionKey; ?></title>
    <link
            rel="stylesheet"
            href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css"
    >
    <style>
        .flex {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
<header class="container">
    <h1>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="-0.5 -0.5 24 24" id="Typo3-Icon--Streamline-Svg-Logos.svg" height="40" width="40"><path fill="#F49700" d="M9.895582291666667 0.5915863541666667c-0.4094479166666667 0.35015104166666666 -0.7003020833333333 0.7595869791666667 -0.7003020833333333 1.9823292708333333 0 3.32738125 4.19994375 13.322414583333334 7.060448958333334 13.322414583333334 0.32128125 0.004360416666666667 0.6412927083333333 -0.04125625 0.9485583333333334 -0.13522083333333335l-0.0060375 0.0016770833333333334 -0.07309687499999999 0.11725208333333334C14.656414583333333 19.815003125000004 11.685221875 22.70162291666667 9.887244791666667 22.759530208333334L9.832571875000001 22.760416666666668C5.927195833333333 22.760416666666668 0.38405927083333335 10.970137500000002 0.38405927083333335 5.7827270833333335c0 -0.8170270833333334 0.185265 -1.45805625 0.46686645833333335 -1.8563635416666668C2.194099375 2.2849110416666667 6.394071875000001 0.9991703125 9.895582291666667 0.5915863541666667ZM15.379429166666668 0.23958333333333334c3.616366666666667 0 7.236446875 0.5835842708333334 7.236446875 2.6252104166666665 0 4.142515625000001 -2.627055208333333 9.1632 -3.9683864583333337 9.1632 -2.391760416666667 0 -5.372680208333334 -6.652869791666666 -5.372680208333334 -9.980222291666667C13.274809375 0.5304494791666666 13.856541666666667 0.23958333333333334 15.373870833333335 0.23958333333333334h0.0055583333333333335Z" stroke-width="1"></path></svg>
        <?php echo $extensionKey; ?></h1>
</header>
<main class="container">
    <blockquote><?php echo $description; ?></blockquote>
    <hr/>
    <p>Run <code>ddev install all</code> to install all TYPO3 instances below:</p>
    <?php
    foreach ($supportedVersions as $version) {
        $directoryPath = '/var/www/html/.Build/' . $version;
        if (is_dir($directoryPath)) {
            echo "<article class='flex'><kbd>{$version}</kbd><div><strong>Frontend</strong><br/><strong>Backend</strong></div><div><a target='_blank' href='https://{$version}.{$extensionKey}.ddev.site'>https://{$version}.{$extensionKey}.ddev.site</a><br/><a target='_blank' href='https://{$version}.{$extensionKey}.ddev.site/typo3/?u={$typo3AdminUser}&p={$typo3AdminPassword}'>https://{$version}.{$extensionKey}.ddev.site/typo3</a></div></article>";
        } else {
            echo "<article>Version {$version} is not installed. Run <code>ddev install {$version}</code> to install.</article>";
        }
    }
?>
    <h2>Additional information</h2>
    <h4>DDEV commands</h4>
    <?php
// Directories to scan for DDEV commands
$directories = [
    '/var/www/html/.ddev/commands/web',
    '/var/www/html/.ddev/commands/host',
];

foreach ($directories as $directory) {
    foreach (new DirectoryIterator($directory) as $fileInfo) {
        $filePath = $fileInfo->getPathname();
        $fileName = $fileInfo->getFilename();

        if ($fileName[0] === '.' || $fileInfo->isDir()) {
            continue;
        }

        $fileContent = file($filePath);
        if (str_starts_with($fileContent[0], '#!/bin/bash')) {
            $description = '';
            $usage = '';
            $example = '';

            foreach ($fileContent as $line) {
                if (str_starts_with($line, '## Description:')) {
                    $description = trim(str_replace('## Description:', '', $line));
                } elseif (str_starts_with($line, '## Usage:')) {
                    $usage = trim(str_replace('## Usage:', '', $line));
                } elseif (str_starts_with($line, '## Example:')) {
                    $example = trim(str_replace('## Example:', '', $line));
                }
            }

            echo "<article><code>ddev $usage</code><br/>$description<br/> <em>Example: $example</em></article>";
        }
    }
}
?>
    <details open>
        <summary><h4>TYPO3 Backend Credentials</h4></summary>
        <ul>
            <li>Username: <code><?php echo $typo3AdminUser; ?></code></li>
            <li>Password: <code><?php echo $typo3AdminPassword; ?></code></li>
        </ul>
    </details>
</main>

</body>
</html>
