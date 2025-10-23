<?php
declare(strict_types=1);

namespace Versioning\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Plugin;

abstract class VersioningUtilityCommand extends Command
{
    protected const APP_VERSION_FILE = CONFIG . 'version.php';
    protected const PLUGIN_VERSION_FILE = 'config/version.php';

    /**
     * Get version file path
     */
    protected function getVersionFilePath(?string $plugin = null): string
    {
        if ($plugin) {
            return Plugin::path($plugin) . self::PLUGIN_VERSION_FILE;
        }
        return self::APP_VERSION_FILE;
    }

    /**
     * Check if version system is initialized
     */
    protected function isVersionInitialized(?string $plugin = null): bool
    {
        $versionFile = $this->getVersionFilePath($plugin);
        return file_exists($versionFile);
    }

    /**
     * Check if a plugin exists in the plugins directory
     */
    protected function isPluginInPluginsDirectory(?string $plugin = null): bool
    {
        if (!$plugin) {
            return true; // L'application principale est toujours valide
        }

        $pluginPath = Plugin::path($plugin);
        $pluginsDir = ROOT . DS . 'plugins' . DS;
        
        // Vérifie que le chemin du plugin commence par le répertoire plugins/
        return str_starts_with($pluginPath, $pluginsDir);
    }

    /**
     * Get version data with plugin directory check
     */
    protected function getVersionData(?string $plugin = null): array
    {
        if ($plugin && !$this->isPluginInPluginsDirectory($plugin)) {
            return $this->getEmptyVersionData();
        }

        $versionFile = $this->getVersionFilePath($plugin);
        
        if (!file_exists($versionFile)) {
            return $this->getEmptyVersionData();
        }

        $data = include $versionFile;
        
        // Pour les plugins
        if ($plugin && is_array($data)) {
            return $this->validateVersionData($data);
        }
        
        // Pour l'application principale
        if (!$plugin && is_array($data) && isset($data['application'])) {
            return $this->validateVersionData($data['application']);
        }

        return $this->getEmptyVersionData();
    }

    /**
     * Get empty version data structure
     */
    protected function getEmptyVersionData(): array
    {
        return [
            'PRODUCT' => 'N/A',
            'MAJOR_VERSION' => 0,
            'MINOR_VERSION' => 0,
            'PATCH_VERSION' => 0,
            'EXTRA_VERSION' => '',
            'DEV_STATUS' => 'N/A',
            'CODENAME' => 'N/A',
            'RELDATE' => 'N/A',
            'COPYRIGHT' => 'N/A',
            'changelog' => [],
        ];
    }

    /**
     * Validate and ensure all required keys exist in version data
     */
    protected function validateVersionData(array $versionData): array
    {
        $defaults = $this->getEmptyVersionData();
        
        // Assure que toutes les clés requises existent
        foreach ($defaults as $key => $defaultValue) {
            if (!array_key_exists($key, $versionData)) {
                $versionData[$key] = $defaultValue;
            }
        }

        return $versionData;
    }

    /**
     * Save version data
     */
    protected function saveVersionData(array $versionData, ?string $plugin = null): void
    {
        $versionFile = $this->getVersionFilePath($plugin);
        $directory = dirname($versionFile);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Valider les données avant sauvegarde
        $versionData = $this->validateVersionData($versionData);

        if ($plugin) {
            $content = "<?php\nreturn " . var_export($versionData, true) . ";\n";
        } else {
            $content = "<?php\nreturn ['application' => " . var_export($versionData, true) . "];\n";
        }

        file_put_contents($versionFile, $content);
    }

    /**
     * Get short version string with safety checks
     */
    protected function getShortVersion(array $versionData): string
    {
        // Utiliser des valeurs par défaut sécurisées
        $major = $versionData['MAJOR_VERSION'] ?? 0;
        $minor = $versionData['MINOR_VERSION'] ?? 0;
        $patch = $versionData['PATCH_VERSION'] ?? 0;
        $extra = $versionData['EXTRA_VERSION'] ?? '';
        
        $version = "{$major}.{$minor}.{$patch}";
        
        if (!empty($extra)) {
            $version .= '-' . $extra;
        }
        
        return $version;
    }

    /**
     * Check if version string is valid
     */
    protected function isValidVersion(string $version): bool
    {
        return (bool)preg_match('/^\d+\.\d+\.\d+(-[a-zA-Z0-9\.]+)?$/', $version);
    }

    /**
     * Parse version string into components
     */
    protected function parseVersion(string $versionString): array
    {
        $parts = explode('-', $versionString);
        $mainVersion = $parts[0];
        $extra = $parts[1] ?? '';

        $versionParts = explode('.', $mainVersion);
        
        // Assurer qu'on a exactement 3 parties
        $major = (int)($versionParts[0] ?? 0);
        $minor = (int)($versionParts[1] ?? 0);
        $patch = (int)($versionParts[2] ?? 0);

        return [
            'major' => $major,
            'minor' => $minor,
            'patch' => $patch,
            'extra' => $extra,
        ];
    }

    /**
     * Initialize changelog entry
     */
    protected function initializeChangelogEntry(array $versionData, string $description, ?string $plugin = null): void
    {
        $version = $this->getShortVersion($versionData);
        $versionData = $this->getVersionData($plugin);

        if (!isset($versionData['changelog'])) {
            $versionData['changelog'] = [];
        }

        $versionData['changelog'][$version] = [
            'release_date' => $versionData['RELDATE'] ?? date('d/m/Y'),
            'description' => $description,
            'added' => [],
            'changed' => [],
            'deprecated' => [],
            'removed' => [],
            'fixed' => [],
            'security' => [],
        ];

        $this->saveVersionData($versionData, $plugin);
    }

    /**
     * Get changelog file path
     */
    protected function getChangelogFilePath(?string $plugin = null): string
    {
        if ($plugin) {
            return Plugin::path($plugin) . 'CHANGELOG.md';
        }
        return ROOT . DS . 'CHANGELOG.md';
    }

    /**
     * Generate Markdown changelog
     */
    protected function generateMarkdownChangelog(array $versionData, ?string $plugin = null): string
    {
        $productName = $versionData['PRODUCT'] ?? 'Unknown Product';
        $content = "# Changelog - {$productName}\n\n";
        $content .= "Toutes les modifications notables de ce projet seront documentées dans ce fichier.\n\n";

        $changelog = $versionData['changelog'] ?? [];

        if (!empty($changelog)) {
            $versions = array_keys($changelog);
            usort($versions, 'version_compare');
            $versions = array_reverse($versions);

            foreach ($versions as $version) {
                $entry = $changelog[$version];
                $releaseDate = $entry['release_date'] ?? 'Unknown Date';
                $content .= "## [{$version}] - {$releaseDate}\n\n";
                
                if (!empty($entry['description'])) {
                    $content .= "**{$entry['description']}**\n\n";
                }

                $changeTypes = [
                    'added' => '🆕 Ajouté',
                    'changed' => '🔄 Modifié',
                    'deprecated' => '⚠️ Déprécié',
                    'removed' => '🗑️ Supprimé',
                    'fixed' => '🔧 Corrigé',
                    'security' => '🔒 Sécurité',
                ];

                foreach ($changeTypes as $type => $title) {
                    if (!empty($entry[$type]) && is_array($entry[$type])) {
                        $content .= "### {$title}\n\n";
                        foreach ($entry[$type] as $change) {
                            $content .= "- {$change}\n";
                        }
                        $content .= "\n";
                    }
                }
            }
        } else {
            $currentVersion = $this->getShortVersion($versionData);
            $releaseDate = $versionData['RELDATE'] ?? 'Unknown Date';
            $content .= "## [{$currentVersion}] - {$releaseDate}\n\n";
            $content .= "**Version initiale**\n\n";
        }

        return $content;
    }

    /**
     * Display error if version system not initialized
     */
    protected function displayNotInitializedError(ConsoleIo $io, ?string $plugin = null): void
    {
        $target = $plugin ? "le plugin '{$plugin}'" : "l'application";
        $io->error("Le système de version n'est pas initialisé pour {$target}.");
        $io->out("Utilisez: <info>bin/cake versioning init" . ($plugin ? " -p {$plugin}" : "") . "</info>");
    }
}
