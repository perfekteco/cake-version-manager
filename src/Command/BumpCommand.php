<?php
declare(strict_types=1);

namespace Versioning\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

class BumpCommand extends VersioningCommand
{
    public static function defaultName(): string
    {
        return 'versioning bump';
    }

    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('Bump version')
            ->addArgument('type', [
                'help' => 'Type: major, minor, patch, extra',
                'required' => true,
                'choices' => ['major', 'minor', 'patch', 'extra'],
            ])
            ->addOption('plugin', [
                'short' => 'p',
                'help' => 'Plugin name',
                'required' => false,
            ]);

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $type = $args->getArgument('type');
        $plugin = $args->getOption('plugin');

        $versionData = $this->getVersionData($plugin);
        if (empty($versionData)) {
            $target = $plugin ? "le plugin '{$plugin}'" : "l'application";
            $io->error("Le système de version n'est pas initialisé pour {$target}. Utilisez 'versioning init' d'abord.");
            return self::CODE_ERROR;
        }

        $oldVersion = $this->getShortVersion($versionData);

        $newVersionData = $this->incrementVersion($versionData, $type, $io);
        if ($newVersionData === null) {
            $io->error("Type de version invalide: '{$type}'. Utilisez: major, minor, patch ou extra");
            return self::CODE_ERROR;
        }

        $newVersion = $this->getShortVersion($newVersionData);
        
        $io->out("Incrémentation de version: <comment>{$oldVersion}</comment> → <info>{$newVersion}</info>");
        $description = $io->ask('Description de la release:', "Release {$newVersion}");

        $this->saveVersionData($newVersionData, $plugin);
        $this->initializeChangelogEntry($newVersionData, $description, $plugin);

        $io->success("Version incrémentée: {$oldVersion} → {$newVersion}");

        return self::CODE_SUCCESS;
    }

    private function incrementVersion(array $versionData, string $type, ConsoleIo $io): ?array
    {
        switch ($type) {
            case 'major':
                $versionData['MAJOR_VERSION']++;
                $versionData['MINOR_VERSION'] = 0;
                $versionData['PATCH_VERSION'] = 0;
                $versionData['EXTRA_VERSION'] = '';
                break;
            case 'minor':
                $versionData['MINOR_VERSION']++;
                $versionData['PATCH_VERSION'] = 0;
                $versionData['EXTRA_VERSION'] = '';
                break;
            case 'patch':
                $versionData['PATCH_VERSION']++;
                $versionData['EXTRA_VERSION'] = '';
                break;
            case 'extra':
                $extra = $io->ask('Version extra (dev, beta, rc1, etc.):');
                $versionData['EXTRA_VERSION'] = $extra;
                break;
            default:
                return null;
        }

        $versionData['RELDATE'] = date('d/m/Y');
        return $versionData;
    }
}
