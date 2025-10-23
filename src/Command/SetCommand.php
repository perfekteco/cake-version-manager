<?php
declare(strict_types=1);

namespace Versioning\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

class SetCommand extends VersioningCommand
{
    public static function defaultName(): string
    {
        return 'versioning set';
    }

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Vous permet de définir un n° de versions spécifique pour votre application ou pour un de vos plugins.';
    }

    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('Set version or changelog information')
            ->addArgument('action', [
                'help' => 'Action: "num" pour définir le numéro, "changelog" pour définir la date ou description',
                'required' => true,
                'choices' => ['num', 'changelog'],
            ])
            ->addArgument('version', [
                'help' => 'Numéro de version (X.X.X) ou version cible pour changelog',
                'required' => true,
            ])
            ->addArgument('params', [
                'help' => 'Paramètres supplémentaires: date (jj/mm/aaaa) ou type de changement',
                'required' => false,
            ])
            ->addArgument('description', [
                'help' => 'Description du changement',
                'required' => false,
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
        $action = $args->getArgument('action');
        $version = $args->getArgument('version');
        $params = $args->getArgument('params');
        $description = $args->getArgument('description');
        $plugin = $args->getOption('plugin');

        switch ($action) {
            case 'num':
                return $this->setVersion($io, $version, $plugin);
            case 'changelog':
                return $this->setChangelog($io, $version, $params, $description, $plugin);
            default:
                $io->error("Action invalide: '{$action}'");
                return self::CODE_ERROR;
        }
    }

    private function setVersion(ConsoleIo $io, string $versionString, ?string $plugin = null): int
    {
        if (!$this->isValidVersion($versionString)) {
            $io->error("Format de version invalide: '{$versionString}'. Format attendu: X.Y.Z[-suffix]");
            return self::CODE_ERROR;
        }

        $versionData = $this->getVersionData($plugin);
        if (empty($versionData)) {
            $target = $plugin ? "le plugin '{$plugin}'" : "l'application";
            $io->error("Le système de version n'est pas initialisé pour {$target}. Utilisez 'versioning init' d'abord.");
            return self::CODE_ERROR;
        }

        $parsedVersion = $this->parseVersion($versionString);

        $oldVersion = $this->getShortVersion($versionData);
        $versionData['MAJOR_VERSION'] = $parsedVersion['major'];
        $versionData['MINOR_VERSION'] = $parsedVersion['minor'];
        $versionData['PATCH_VERSION'] = $parsedVersion['patch'];
        $versionData['EXTRA_VERSION'] = $parsedVersion['extra'];
        $versionData['RELDATE'] = date('d/m/Y');

        $newVersion = $this->getShortVersion($versionData);
        $releaseDescription = $io->ask('Description de la release:', "Release {$newVersion}");

        $this->saveVersionData($versionData, $plugin);
        $this->initializeChangelogEntry($versionData, $releaseDescription, $plugin);

        $io->success("Version définie: {$oldVersion} → {$newVersion}");

        return self::CODE_SUCCESS;
    }

    private function setChangelog(ConsoleIo $io, string $version, ?string $params, ?string $description, ?string $plugin = null): int
    {
        $versionData = $this->getVersionData($plugin);
        if (empty($versionData)) {
            $target = $plugin ? "le plugin '{$plugin}'" : "l'application";
            $io->error("Le système de version n'est pas initialisé pour {$target}. Utilisez 'versioning init' d'abord.");
            return self::CODE_ERROR;
        }

        if (!isset($versionData['changelog'][$version])) {
            $io->error("La version '{$version}' n'existe pas dans le changelog");
            return self::CODE_ERROR;
        }

        if ($params === 'reldate') {
            // Définir la date de release
            if (!$description) {
                $io->error("La date est requise (format: jj/mm/aaaa)");
                return self::CODE_ERROR;
            }
            if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $description)) {
                $io->error("Format de date invalide: '{$description}'. Utilisez jj/mm/aaaa");
                return self::CODE_ERROR;
            }
            $versionData['changelog'][$version]['release_date'] = $description;
            $this->saveVersionData($versionData, $plugin);
            $io->success("Date de release définie pour la version {$version}: {$description}");
            return self::CODE_SUCCESS;
        }

        // Ajouter une entrée au changelog
        $validTypes = ['added', 'changed', 'deprecated', 'removed', 'fixed', 'security'];
        if (!in_array($params, $validTypes)) {
            $io->error("Type de changement invalide: '{$params}'. Utilisez: " . implode(', ', $validTypes));
            return self::CODE_ERROR;
        }

        if (!$description) {
            $io->error("La description du changement est requise");
            return self::CODE_ERROR;
        }

        $versionData['changelog'][$version][$params][] = $description;
        $this->saveVersionData($versionData, $plugin);

        $io->success("Changement ajouté au changelog de la version {$version}");

        return self::CODE_SUCCESS;
    }
}
