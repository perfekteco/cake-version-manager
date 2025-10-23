<?php
declare(strict_types=1);

namespace Versioning\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Plugin;

class AllCommand extends VersioningCommand
{
    public static function defaultName(): string
    {
        return 'versioning all';
    }

    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('List all versions (application and plugins)');

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $io->out('<info>CakePHP Versioning Manager - Toutes les versions</info>');
        $io->hr();

        $initializedCount = 0;
        $notInitializedCount = 0;

        // Application
        $appVersion = $this->getVersionData();
        if ($appVersion) {
            $versionString = $this->getShortVersion($appVersion);
            $io->out("Application: <info>{$appVersion['PRODUCT']} {$versionString}</info>");
            $initializedCount++;
        } else {
            $io->out("Application: <error>non initialisé</error>");
            $notInitializedCount++;
        }

        // Plugins
        $plugins = Plugin::loaded();
        if (!empty($plugins)) {
            $io->out('');
            $io->out('<info>Plugins:</info>');
            
            foreach ($plugins as $plugin) {
                $pluginVersion = $this->getVersionData($plugin);
                if ($pluginVersion['PRODUCT'] !== 'N/A') {
                    $versionString = $this->getShortVersion($pluginVersion);
                    $io->out("  {$plugin}: <info>{$pluginVersion['PRODUCT']} {$versionString}</info>");
                    $initializedCount++;
                } else {
                    $io->out("  {$plugin}: <error>non initialisé</error>");
                    $notInitializedCount++;
                }
            }
        } else {
            $io->out('');
            $io->out('Aucun plugin trouvé');
        }

        $io->hr();
        $io->out("Résumé: {$initializedCount} initialisés, {$notInitializedCount} non initialisés");

        return self::CODE_SUCCESS;
    }
}
