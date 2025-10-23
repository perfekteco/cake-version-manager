<?php
declare(strict_types=1);

namespace Versioning\View\Cell;

use Cake\View\Cell;
use Cake\Core\Plugin;

/**
 * AppVersion cell
 */
class AppVersionCell extends Cell
{
    /**
     * Display method for AppVersion integration
     *
     * @param array $options Options for the cell
     * @return void
     */
    public function display(array $options = []): void
    {
        $versionData = $this->getVersionData($options['plugin'] ?? null);
        $version = $this->getShortVersion($versionData);
        
        $this->set([
            'version' => $version,
            'logo' => $options['logo'] ?? 'app.logo.svg',
            'logoAlt' => $options['logoAlt'] ?? 'App Version',
            'logoClass' => $options['logoClass'] ?? 'icon',
            'showHiddenText' => $options['showHiddenText'] ?? true,
            'versionText' => $options['versionText'] ?? __('MOD_VERSION_CURRENT_VERSION_TEXT', $version),
        ]);
    }

    /**
     * Get version data
     */
    private function getVersionData(?string $plugin = null): array
    {
        if ($plugin) {
            $versionFile = Plugin::path($plugin) . 'config/version.php';
        } else {
            $versionFile = CONFIG . 'version.php';
        }

        if (!file_exists($versionFile)) {
            return $this->getEmptyVersionData();
        }

        $data = include $versionFile;
        
        if ($plugin && is_array($data)) {
            return $this->validateVersionData($data);
        }
        
        if (!$plugin && is_array($data) && isset($data['application'])) {
            return $this->validateVersionData($data['application']);
        }

        return $this->getEmptyVersionData();
    }

    /**
     * Get empty version data structure
     */
    private function getEmptyVersionData(): array
    {
        return [
            'PRODUCT' => 'Application',
            'MAJOR_VERSION' => 0,
            'MINOR_VERSION' => 0,
            'PATCH_VERSION' => 0,
            'EXTRA_VERSION' => '',
        ];
    }

    /**
     * Validate version data
     */
    private function validateVersionData(array $versionData): array
    {
        $defaults = $this->getEmptyVersionData();
        
        foreach ($defaults as $key => $defaultValue) {
            if (!array_key_exists($key, $versionData)) {
                $versionData[$key] = $defaultValue;
            }
        }

        return $versionData;
    }

    /**
     * Get short version string
     */
    private function getShortVersion(array $versionData): string
    {
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
}
