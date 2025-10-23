<?php
declare(strict_types=1);

namespace Versioning\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Plugin;

class InitAllCommand extends VersioningCommand
{
    public static function defaultName(): string
    {
        return 'versioning init all';
    }
  
    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Vous permet d\'initialiser le système de versions de votre application et de tous vos plugins.';
    }

    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('Initialize version system for application and all plugins');

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $io->out("<info>Initialisation du système de version pour l'application et tous les plugins</info>");
        $io->hr();

        $initializedCount = 0;
        $skippedCount = 0;

        // Application
        $io->out("<info>Application:</info>");
        if ($this->isVersionInitialized()) {
            $io->out("  Déjà initialisée - ignorée");
            $skippedCount++;
        } else {
            $versionData = [
                'PRODUCT' => 'Mon Application',
                'MAJOR_VERSION' => 1,
                'MINOR_VERSION' => 0,
                'PATCH_VERSION' => 0,
                'EXTRA_VERSION' => '',
                'DEV_STATUS' => 'Development',
                'CODENAME' => 'Phoenix',
                'RELDATE' => date('d/m/Y'),
                'COPYRIGHT' => 'Copyright © ' . date('Y'),
                'changelog' => [],
            ];

            $this->saveVersionData($versionData);
            $this->initializeChangelogFile($versionData);

            $versionString = $this->getShortVersion($versionData);
            $io->out("  Initialisée: {$versionString}");
            $initializedCount++;
        }

        // Plugins
        $plugins = Plugin::loaded();
        if (!empty($plugins)) {
            $io->out('');
            $io->out("<info>Plugins:</info>");
            
            foreach ($plugins as $plugin) {
                $io->out("  {$plugin}:");
                if ($this->isVersionInitialized($plugin)) {
                    $io->out("    Déjà initialisé - ignoré");
                    $skippedCount++;
                } else {
                    $versionData = [
                        'PRODUCT' => $plugin,
                        'MAJOR_VERSION' => 1,
                        'MINOR_VERSION' => 0,
                        'PATCH_VERSION' => 0,
                        'EXTRA_VERSION' => '',
                        'DEV_STATUS' => 'Development',
                        'CODENAME' => 'Default',
                        'RELDATE' => date('d/m/Y'),
                        'COPYRIGHT' => 'Copyright © ' . date('Y'),
                        'changelog' => [],
                    ];

                    $this->saveVersionData($versionData, $plugin);
                    $this->initializeChangelogFile($versionData, $plugin);

                    $versionString = $this->getShortVersion($versionData);
                    $io->out("    Initialisé: {$versionString}");
                    $initializedCount++;
                }
            }
        } else {
            $io->out('');
            $io->out("Aucun plugin trouvé");
        }

        $io->hr();
        $io->success("Initialisation terminée: {$initializedCount} systèmes initialisés, {$skippedCount} ignorés");

        return self::CODE_SUCCESS;
    }

    private function initializeChangelogFile(array $versionData, ?string $plugin = null): void
    {
        $changelogFile = $this->getChangelogFilePath($plugin);
        $content = $this->generateMarkdownChangelog($versionData, $plugin);
        file_put_contents($changelogFile, $content);
    }
}
