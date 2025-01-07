<?php
$extensionKey = getenv('EXTENSION_NAME');
$typo3AdminUser = getenv('TYPO3_INSTALL_ADMIN_USER');
$typo3AdminPassword = getenv('TYPO3_INSTALL_ADMIN_PASSWORD');
$supportedVersions = explode(' ', getenv('TYPO3_VERSIONS'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo($extensionKey); ?></title>
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css"
    >
</head>
<body>
<header class="container">
    <h1><img src="logo.svg" alt="TYPO3"/> <?php echo($extensionKey); ?></h1>
</header>
<main class="container">
    <p>Run <code>ddev install all</code> to install all TYPO3 instances below:</p>
    <?php
    foreach ($supportedVersions as $version) {
        $directoryPath = "/var/www/html/.test/" . $version;
        if (is_dir($directoryPath)) {
            echo "<article><a target='_blank' href='https://{$version}.{$extensionKey}.ddev.site/typo3/'>https://{$version}.{$extensionKey}.ddev.site/typo3</a></article>";
        } else {
            echo "<article>Version {$version} is not installed. Run <code>ddev install {$version}</code> to install.</article>";
        }
    }
    ?>
    <h2>Additional information</h2>
    <details open>
        <summary><h3>TYPO3 Backend Credentials</h3></summary>
        <ul>
            <li>Username: <code><?php echo($typo3AdminUser); ?></code></li>
            <li>Password: <code><?php echo($typo3AdminPassword); ?></code></li>
        </ul>
    </details>
    <h3>DDEV commands</h3>
    <?php
    $directory = '/var/www/html/.ddev/commands/web';

    foreach (new DirectoryIterator($directory) as $fileInfo) {
        $filePath = $fileInfo->getPathname();
        $fileName = $fileInfo->getFilename();

        if ($fileName[0] === '.' || $fileInfo->isDir()) {
            continue;
        }

        $fileContent = file($filePath);
        if (strpos($fileContent[0], '#!/bin/bash') === 0) {
            $description = '';
            $usage = '';
            $example = '';

            foreach ($fileContent as $line) {
                if (strpos($line, '## Description:') === 0) {
                    $description = trim(str_replace('## Description:', '', $line));
                } elseif (strpos($line, '## Usage:') === 0) {
                    $usage = trim(str_replace('## Usage:', '', $line));
                } elseif (strpos($line, '## Example:') === 0) {
                    $example = trim(str_replace('## Example:', '', $line));
                }
            }

            echo "<article><code>ddev $usage</code><br/>$description<br/> Example: $example</article>";
        }
    }
    ?>
</main>

</body>
</html>
