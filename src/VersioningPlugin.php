<?php
declare(strict_types=1);

namespace Versioning;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use DirectoryIterator;
use ReflectionClass;
use ReflectionException;

class VersioningPlugin extends BasePlugin
{
    protected ?string $name = 'Versioning';
    protected bool $routesEnabled = false;

    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);
    }

    public function console(CommandCollection $commands): CommandCollection
    {
        // Découverte automatique des commandes dans src/Command/
        $found = $this->discoverCommands();
        if (count($found)) {
            $commands->addMany($found);
        }

        return $commands;
    }

    /**
     * Découvre automatiquement les commandes dans le dossier Command/
     * Version corrigée pour supporter les noms avec espaces comme Bake
     */
    protected function discoverCommands(): array
    {
        $path = $this->getPath() . 'src' . DS . 'Command' . DS;
        $namespace = 'Versioning\\Command\\';

        if (!file_exists($path)) {
            return [];
        }

        $iterator = new DirectoryIterator($path);
        $commands = [];

        foreach ($iterator as $item) {
            if ($item->isDot() || $item->isDir() || $item->getExtension() !== 'php') {
                continue;
            }

            $filename = $item->getBasename('.php');
            $class = $namespace . $filename;

            // Vérifie que la classe existe et est une commande valide
            try {
                $reflection = new ReflectionClass($class);
            } catch (ReflectionException $e) {
                continue;
            }

            // Vérifie que c'est une sous-classe de Command et qu'elle n'est pas abstraite
            if (!$reflection->isSubclassOf('Cake\Command\Command') || 
                !$reflection->isInstantiable() ||
                $reflection->isAbstract()) {
                continue;
            }

            // Vérifie que la classe a la méthode defaultName()
            if (!$reflection->hasMethod('defaultName')) {
                continue;
            }

            // Récupère le nom de la commande
            $commandName = $class::defaultName();
            
            if ($commandName) {
                $commands[$commandName] = $class;
            }
        }

        return $commands;
    }
}
