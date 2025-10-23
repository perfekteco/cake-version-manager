<?php
declare(strict_types=1);

namespace Versioning\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

class InitCommand extends VersioningCommand
{
    public static function defaultName(): string
    {
        return 'versioning init';
    }

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Vous permet d\'initialiser le système de versions de votre application ou d\'un vos plugins.';
    }

    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('Initialize version system for application or specific plugin')
            ->addOption('plugin', [
                'short' => 'p',
                'help' => 'Plugin name',
                'required' => false,
            ]);

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $plugin = $args->getOption('plugin');
        $target = $plugin ? "plugin '{$plugin}'" : 'application';

        $io->out("<info>Initialisation du système de version pour {$target}</info>");
        $io->hr();

        $versionFile = $this->getVersionFilePath($plugin);

        if (file_exists($versionFile)) {
            $overwrite = $io->askChoice(
                "Le fichier de version existe déjà. Écraser?",
                ['y', 'n'],
                'n'
            );
            if ($overwrite !== 'y') {
                return self::CODE_SUCCESS;
            }
        }

        $versionData = [
            'PRODUCT' => $io->ask('Nom du produit:', $plugin ?: 'Mon Application'),
            'MAJOR_VERSION' => (int)$io->ask('Version majeure:', '1'),
            'MINOR_VERSION' => (int)$io->ask('Version mineure:', '0'),
            'PATCH_VERSION' => (int)$io->ask('Version patch:', '0'),
            'EXTRA_VERSION' => $io->ask('Version extra (dev, beta, rc1, etc.):', ''),
            'DEV_STATUS' => $io->askChoice(
                'Statut de développement:',
                ['Development', 'Alpha', 'Beta', 'Stable'],
                'Development'
            ),
            'CODENAME' => $io->ask('Nom de code:', 'Phoenix'),
            'RELDATE' => date('d/m/Y'),
            'COPYRIGHT' => $io->ask('Copyright:', 'Copyright © ' . date('Y')),
            'changelog' => [],
        ];

        $this->saveVersionData($versionData, $plugin);
        $this->initializeChangelogFile($versionData, $plugin);

        $versionString = $this->getShortVersion($versionData);
        $io->success("Système de version initialisé: {$versionString}");

        return self::CODE_SUCCESS;
    }

    private function initializeChangelogFile(array $versionData, ?string $plugin = null): void
    {
        $changelogFile = $this->getChangelogFilePath($plugin);
        $content = $this->generateMarkdownChangelog($versionData, $plugin);
        file_put_contents($changelogFile, $content);
    }
}
