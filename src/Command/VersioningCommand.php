<?php
declare(strict_types=1);

namespace Versioning\Command;

use Versioning\Command\VersioningUtilityCommand;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

class VersioningCommand extends VersioningUtilityCommand
{
    public static function defaultName(): string
    {
        return 'versioning';
    }

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Vous permet de gérer les versions de votre application et de vos plugins.';
    }

    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('Display current version information')
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

        $io->out('<info>CakePHP Versioning Manager</info>');
        $io->hr();

        if (!$this->isVersionInitialized($plugin)) {
            $this->displayNotInitializedError($io, $plugin);
            return self::CODE_SUCCESS;
        }

        $versionData = $this->getVersionData($plugin); // Maintenant sécurisé
        $versionString = $this->getShortVersion($versionData);
        
        if ($plugin) {
            $io->out("Plugin '{$plugin}': <info>{$versionData['PRODUCT']} {$versionString}</info>");
        } else {
            $io->out("Application: <info>{$versionData['PRODUCT']} {$versionString}</info>");
        }

        return self::CODE_SUCCESS;
    }
}
