<?php

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Plugin;

/**
 * Commande de gestion du versioning sémantique pour CakePHP 5
 * 
 * Gère les versions de l'application principale et des plugins
 * avec conservation de l'historique dans des fichiers dédiés
 */
class VersionCommand extends Command
{
    /**
     * Chemin du fichier de version de l'application principale
     */
    const APP_VERSION_FILE = CONFIG . 'version.php';

    /**
     * Nom du fichier de version dans les plugins
     */
    const PLUGIN_VERSION_FILE = 'config/version.php';

    /**
     * Nom du fichier changelog
     */
    const CHANGELOG_FILE = 'CHANGELOG.md';

    /**
     * Configuration par défaut pour une nouvelle version
     */
    protected $defaultVersionConfig = [
        'PRODUCT' => 'Application',
        'MAJOR_VERSION' => 1,
        'MINOR_VERSION' => 0,
        'PATCH_VERSION' => 0,
        'EXTRA_VERSION' => '',
        'DEV_STATUS' => 'Development',
        'CODENAME' => 'Default',
        'RELDATE' => null,
        'COPYRIGHT' => 'Copyright © ' . date('Y'),
        'changelog' => []
    ];

    /**
     * Configuration des options de la commande
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('Gestionnaire de version sémantique pour CakePHP')
            ->addOption('plugin', [
                'short' => 'p',
                'help' => 'Nom du plugin à versionner',
                'required' => false
            ])
            ->addOption('help', [
                'short' => 'h',
                'help' => 'Afficher l\'aide',
                'boolean' => true
            ]);

        $parser
            ->addSubcommand('current', [
                'help' => 'Affiche la version actuelle',
                'parser' => [
                    'description' => 'Affiche la version actuelle de l\'application ou d\'un plugin',
                    'options' => [
                        'plugin' => [
                            'short' => 'p',
                            'help' => 'Nom du plugin'
                        ]
                    ]
                ]
            ])
            ->addSubcommand('bump', [
                'help' => 'Incrémenter la version',
                'parser' => [
                    'description' => 'Incrémenter la version selon le type spécifié',
                    'arguments' => [
                        'type' => [
                            'help' => 'Type d\'incrémentation: major, minor, patch, extra',
                            'required' => true,
                            'choices' => ['major', 'minor', 'patch', 'extra']
                        ]
                    ],
                    'options' => [
                        'plugin' => [
                            'short' => 'p',
                            'help' => 'Nom du plugin'
                        ]
                    ]
                ]
            ])
            ->addSubcommand('set', [
                'help' => 'Définir une version spécifique',
                'parser' => [
                    'description' => 'Définir une version spécifique',
                    'arguments' => [
                        'version' => [
                            'help' => 'Version au format X.Y.Z[-suffix]',
                            'required' => true
                        ]
                    ],
                    'options' => [
                        'plugin' => [
                            'short' => 'p',
                            'help' => 'Nom du plugin'
                        ]
                    ]
                ]
            ])
            ->addSubcommand('init', [
                'help' => 'Initialiser le système de version',
                'parser' => [
                    'description' => 'Initialiser le système de version pour l\'application ou un plugin',
                    'arguments' => [
                        'plugin' => [
                            'help' => 'Nom du plugin (optionnel)',
                            'required' => false
                        ]
                    ]
                ]
            ])
            ->addSubcommand('list', [
                'help' => 'Lister toutes les versions',
                'parser' => [
                    'description' => 'Lister les versions de l\'application et des plugins'
                ]
            ])
            ->addSubcommand('changelog', [
                'help' => 'Gérer le changelog',
                'parser' => [
                    'description' => 'Gestion interactive du changelog',
                    'arguments' => [
                        'version' => [
                            'help' => 'Version spécifique (optionnelle)',
                            'required' => false
                        ]
                    ],
                    'options' => [
                        'plugin' => [
                            'short' => 'p',
                            'help' => 'Nom du plugin'
                        ]
                    ]
                ]
            ])
            ->addSubcommand('view', [
                'help' => 'Afficher le changelog',
                'parser' => [
                    'description' => 'Afficher le changelog complet ou d\'une version spécifique',
                    'arguments' => [
                        'version' => [
                            'help' => 'Version spécifique (optionnelle)',
                            'required' => false
                        ]
                    ],
                    'options' => [
                        'plugin' => [
                            'short' => 'p',
                            'help' => 'Nom du plugin'
                        ]
                    ]
                ]
            ])
            ->addSubcommand('history', [
                'help' => 'Afficher l\'historique des versions',
                'parser' => [
                    'description' => 'Afficher l\'historique des versions',
                    'arguments' => [
                        'plugin' => [
                            'help' => 'Nom du plugin (optionnel)',
                            'required' => false
                        ]
                    ]
                ]
            ])
            ->addSubcommand('export', [
                'help' => 'Exporter le changelog en Markdown',
                'parser' => [
                    'description' => 'Exporter le changelog en format Markdown',
                    'arguments' => [
                        'plugin' => [
                            'help' => 'Nom du plugin (optionnel)',
                            'required' => false
                        ]
                    ]
                ]
            ]);

        return $parser;
    }

    /**
     * Méthode principale - Affiche l'aide
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        // Si l'option --help est utilisée
        if ($args->getOption('help')) {
            $io->out($this->getOptionParser()->help());
            return self::CODE_SUCCESS;
        }

        $io->out('<info>Gestionnaire de Version Sémantique CakePHP</info>');
        $io->hr();
        $io->out('Commandes disponibles:');
        $io->out('');
        $io->out('<comment>Gestion des Versions:</comment>');
        $io->out('  current [--plugin|-p]       Affiche la version actuelle');
        $io->out('  bump <type> [--plugin|-p]   Incrémente la version');
        $io->out('  set <version> [--plugin|-p] Définit une version spécifique');
        $io->out('  list                        Liste toutes les versions');
        $io->out('  init [plugin]               Initialise le système de version');
        $io->out('');
        $io->out('<comment>Gestion du Changelog:</comment>');
        $io->out('  changelog [version] [--plugin|-p] Gestion du changelog');
        $io->out('  view [version] [--plugin|-p]      Affiche le changelog');
        $io->out('  history [plugin]                  Historique des versions');
        $io->out('  export [plugin]                   Exporte le changelog en Markdown');
        $io->out('');
        $io->out('<comment>Options globales:</comment>');
        $io->out('  --help, -h                 Affiche cette aide');
        $io->out('  --plugin, -p <plugin>      Spécifie le plugin à versionner');
        $io->out('');
        $io->out('<comment>Exemples:</comment>');
        $io->out('  bin/cake version bump minor --plugin ContactManager');
        $io->out('  bin/cake version current -p ContactManager');
        $io->out('  bin/cake version --help');
        $io->out('  bin/cake version bump --help');

        return self::CODE_SUCCESS;
    }

    /**
     * Affiche la version actuelle de l'application ou d'un plugin
     */
    public function current(Arguments $args, ConsoleIo $io)
    {
        $plugin = $args->getOption('plugin');
        $versionData = $this->getVersionData($plugin);
        
        if ($versionData === null) {
            $io->error("Le système de version n'est pas initialisé pour " . ($plugin ? "le plugin '{$plugin}'" : "l'application"));
            return self::CODE_ERROR;
        }

        if ($plugin) {
            $io->info("Version du plugin '{$plugin}': " . $this->getShortVersion($versionData));
        } else {
            $io->info("Version de l'application: " . $this->getShortVersion($versionData));
        }
        
        $io->out("Version complète: " . $this->getLongVersion($versionData));
        $io->out("Statut: " . $versionData['DEV_STATUS']);
        $io->out("Date de release: " . $versionData['RELDATE']);

        return self::CODE_SUCCESS;
    }

    /**
     * Incrémente la version selon le type spécifié
     */
    public function bump(Arguments $args, ConsoleIo $io)
    {
        $type = $args->getArgument('type');
        if (!$type) {
            $io->error("Le type d'incrémentation est requis. Utilisez: major, minor, patch ou extra");
            $io->out($this->getOptionParser()->subcommand('bump')->help());
            return self::CODE_ERROR;
        }

        $plugin = $args->getOption('plugin');
        $versionData = $this->getVersionData($plugin);
        
        if ($versionData === null) {
            $io->error("Le système de version n'est pas initialisé pour " . ($plugin ? "le plugin '{$plugin}'" : "l'application"));
            return self::CODE_ERROR;
        }

        $oldVersion = $this->getShortVersion($versionData);
        $newVersionData = $this->incrementVersion($versionData, $type, $io);

        if ($newVersionData === null) {
            $io->error("Type de version invalide: '{$type}'. Utilisez: major, minor, patch ou extra");
            return self::CODE_ERROR;
        }

        $newVersion = $this->getShortVersion($newVersionData);
        
        // Demander une description pour la release
        $io->out("");
        $io->info("Création de la version: {$oldVersion} → <comment>{$newVersion}</comment>");
        $description = $io->ask("Description de cette version:", "Release {$newVersion}");

        // Sauvegarder la nouvelle version
        $this->saveVersionData($newVersionData, $plugin);
        
        // Initialiser l'entrée du changelog
        $this->initializeChangelogEntry($newVersionData, $description, $plugin);

        $io->success("Version incrémentée: {$oldVersion} → {$newVersion}");
        $io->info("Ajoutez les détails des changements avec: bin/cake version changelog {$newVersion}" . ($plugin ? " -p {$plugin}" : ""));

        return self::CODE_SUCCESS;
    }

    /**
     * Définit une version spécifique
     */
    public function set(Arguments $args, ConsoleIo $io)
    {
        $versionString = $args->getArgument('version');
        if (!$versionString) {
            $io->error("La version est requise");
            $io->out($this->getOptionParser()->subcommand('set')->help());
            return self::CODE_ERROR;
        }

        if (!$this->isValidVersion($versionString)) {
            $io->error("Format de version invalide: '{$versionString}'. Format attendu: X.Y.Z[-suffix]");
            return self::CODE_ERROR;
        }

        $plugin = $args->getOption('plugin');
        $versionData = $this->getVersionData($plugin) ?: $this->defaultVersionConfig;
        $parsedVersion = $this->parseVersion($versionString);

        // Mettre à jour les composants de version
        $versionData['MAJOR_VERSION'] = $parsedVersion['major'];
        $versionData['MINOR_VERSION'] = $parsedVersion['minor'];
        $versionData['PATCH_VERSION'] = $parsedVersion['patch'];
        $versionData['EXTRA_VERSION'] = $parsedVersion['extra'];
        $versionData['RELDATE'] = date('d-F-Y');

        $newVersion = $this->getShortVersion($versionData);
        
        // Demander une description
        $description = $io->ask("Description de la version {$newVersion}:", "Release {$newVersion}");

        $this->saveVersionData($versionData, $plugin);
        $this->initializeChangelogEntry($versionData, $description, $plugin);

        $io->success("Version définie à: {$newVersion}");

        return self::CODE_SUCCESS;
    }

    /**
     * Initialise le système de version
     */
    public function init(Arguments $args, ConsoleIo $io)
    {
        // Si le plugin est passé en option, il prime sur l'argument
        $plugin = $args->getOption('plugin') ?: $args->getArgument('plugin');
        $target = $plugin ? "le plugin '{$plugin}'" : "l'application principale";
        $versionFile = $this->getVersionFilePath($plugin);

        if (file_exists($versionFile)) {
            $overwrite = $io->askChoice(
                "Le fichier de version existe déjà pour {$target}. Voulez-vous le réinitialiser?",
                ['y', 'n'],
                'n'
            );
            if ($overwrite !== 'y') {
                return self::CODE_SUCCESS;
            }
        }

        $versionData = $this->defaultVersionConfig;
        
        // Collecter les informations
        $io->out("<info>Initialisation du système de version pour {$target}</info>");
        $io->hr();
        
        $versionData['PRODUCT'] = $io->ask('Nom du produit:', $plugin ?: 'Mon Application');
        $versionData['MAJOR_VERSION'] = (int)$io->ask('Version majeure:', '1');
        $versionData['MINOR_VERSION'] = (int)$io->ask('Version mineure:', '0');
        $versionData['PATCH_VERSION'] = (int)$io->ask('Version patch:', '0');
        $versionData['DEV_STATUS'] = $io->askChoice(
            'Statut de développement:',
            ['Development', 'Alpha', 'Beta', 'Stable'],
            'Development'
        );
        $versionData['CODENAME'] = $io->ask('Nom de code:', 'Phoenix');
        $versionData['RELDATE'] = date('d-F-Y');
        $versionData['COPYRIGHT'] = $io->ask('Copyright:', 'Copyright © ' . date('Y'));

        $this->saveVersionData($versionData, $plugin);
        
        // Initialiser le fichier CHANGELOG.md
        $this->initializeChangelogFile($versionData, $plugin);

        $versionString = $this->getShortVersion($versionData);
        $io->success("Système de version initialisé pour {$target}: {$versionString}");

        return self::CODE_SUCCESS;
    }

    /**
     * Liste les versions de tous les composants
     */
    public function list(Arguments $args, ConsoleIo $io)
    {
        $io->info('=== VERSION APPLICATION PRINCIPALE ===');
        $appVersion = $this->getVersionData();
        if ($appVersion) {
            $io->out($this->getLongVersion($appVersion));
        } else {
            $io->out('Non initialisée - utilisez: bin/cake version init');
        }
        
        $io->out('');

        // Lister les plugins avec système de version
        $io->info('=== VERSIONS DES PLUGINS ===');
        $plugins = Plugin::loaded();
        $hasVersionedPlugins = false;

        foreach ($plugins as $plugin) {
            $pluginVersion = $this->getVersionData($plugin);
            if ($pluginVersion) {
                $hasVersionedPlugins = true;
                $io->out("{$plugin}: " . $this->getShortVersion($pluginVersion) . " - {$pluginVersion['DEV_STATUS']}");
            }
        }

        if (!$hasVersionedPlugins) {
            $io->out('Aucun plugin avec système de version initialisé');
            $io->out('Initialisez un plugin avec: bin/cake version init NomDuPlugin');
        }

        return self::CODE_SUCCESS;
    }

    /**
     * Gestion interactive du changelog
     */
    public function changelog(Arguments $args, ConsoleIo $io)
    {
        $plugin = $args->getOption('plugin');
        $version = $args->getArgument('version');
        
        $versionData = $this->getVersionData($plugin);
        if ($versionData === null) {
            $io->error("Le système de version n'est pas initialisé");
            return self::CODE_ERROR;
        }

        if (!$version) {
            $version = $io->ask('Pour quelle version?', $this->getShortVersion($versionData));
        }

        $action = $io->askChoice(
            'Action à effectuer:',
            ['add', 'view', 'edit_description'],
            'add'
        );

        switch ($action) {
            case 'add':
                return $this->addChangelogEntry($version, $plugin, $io);
            case 'view':
                return $this->viewChangelog($version, $plugin, $io);
            case 'edit_description':
                return $this->editChangelogDescription($version, $plugin, $io);
        }

        return self::CODE_SUCCESS;
    }

    /**
     * Affiche le changelog
     */
    public function view(Arguments $args, ConsoleIo $io)
    {
        $plugin = $args->getOption('plugin');
        $version = $args->getArgument('version');
        
        return $this->viewChangelog($version, $plugin, $io);
    }

    /**
     * Affiche l'historique des versions
     */
    public function history(Arguments $args, ConsoleIo $io)
    {
        // Si le plugin est passé en option, il prime sur l'argument
        $plugin = $args->getOption('plugin') ?: $args->getArgument('plugin');
        $versionData = $this->getVersionData($plugin);
        if ($versionData === null || empty($versionData['changelog'])) {
            $io->error("Aucun historique de version disponible");
            return self::CODE_ERROR;
        }

        $target = $plugin ? "du plugin '{$plugin}'" : "de l'application";
        $io->info("HISTORIQUE DES VERSIONS {$target}");
        $io->hr();

        $versions = array_keys($versionData['changelog']);
        usort($versions, 'version_compare');
        $versions = array_reverse($versions);

        foreach ($versions as $version) {
            $entry = $versionData['changelog'][$version];
            $changeCount = $this->countChanges($entry);
            
            $io->out("<info>{$version}</info> - {$entry['release_date']}");
            $io->out("    {$changeCount} changement(s) - {$entry['description']}");
            $io->out("");
        }

        return self::CODE_SUCCESS;
    }

    /**
     * Exporte le changelog en format Markdown
     */
    public function export(Arguments $args, ConsoleIo $io)
    {
        // Si le plugin est passé en option, il prime sur l'argument
        $plugin = $args->getOption('plugin') ?: $args->getArgument('plugin');
        $versionData = $this->getVersionData($plugin);
        if ($versionData === null || empty($versionData['changelog'])) {
            $io->error("Aucun changelog à exporter");
            return self::CODE_ERROR;
        }

        $changelogContent = $this->generateMarkdownChangelog($versionData, $plugin);
        $changelogFile = $this->getChangelogFilePath($plugin);
        
        file_put_contents($changelogFile, $changelogContent);
        
        $io->success("Changelog exporté: " . basename($changelogFile));

        return self::CODE_SUCCESS;
    }

    /***************************************************************************
     * MÉTHODES PROTÉGÉES - LOGIQUE MÉTIER
     **************************************************************************/

    /**
     * Récupère les données de version
     */
    protected function getVersionData($plugin = null)
    {
        $versionFile = $this->getVersionFilePath($plugin);
        
        if (!file_exists($versionFile)) {
            return null;
        }

        $data = include $versionFile;
        
        // Pour les plugins, le fichier contient directement le tableau de version
        if ($plugin && is_array($data)) {
            return $data;
        }
        
        // Pour l'application principale, on prend la clé 'application'
        if (!$plugin && is_array($data) && isset($data['application'])) {
            return $data['application'];
        }

        return null;
    }

    /**
     * Sauvegarde les données de version
     */
    protected function saveVersionData($versionData, $plugin = null)
    {
        $versionFile = $this->getVersionFilePath($plugin);
        $directory = dirname($versionFile);
        
        // Créer le répertoire si nécessaire
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if ($plugin) {
            // Pour les plugins: fichier avec le tableau de version directement
            $content = "<?php\nreturn " . var_export($versionData, true) . ";\n";
        } else {
            // Pour l'application: structure avec clé 'application'
            $content = "<?php\nreturn ['application' => " . var_export($versionData, true) . "];\n";
        }

        file_put_contents($versionFile, $content);
    }

    /**
     * Incrémente la version selon le type
     */
    protected function incrementVersion($versionData, $type, ConsoleIo $io)
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

        $versionData['RELDATE'] = date('d-F-Y');
        return $versionData;
    }

    /**
     * Initialise une entrée dans le changelog
     */
    protected function initializeChangelogEntry($versionData, $description, $plugin = null)
    {
        $version = $this->getShortVersion($versionData);
        $versionData = $this->getVersionData($plugin) ?: $versionData;

        if (!isset($versionData['changelog'])) {
            $versionData['changelog'] = [];
        }

        $versionData['changelog'][$version] = [
            'release_date' => $versionData['RELDATE'],
            'description' => $description,
            'added' => [],
            'changed' => [],
            'deprecated' => [],
            'removed' => [],
            'fixed' => [],
            'security' => []
        ];

        $this->saveVersionData($versionData, $plugin);
    }

    /**
     * Ajoute une entrée au changelog
     */
    protected function addChangelogEntry($version, $plugin, ConsoleIo $io)
    {
        $versionData = $this->getVersionData($plugin);
        if (!isset($versionData['changelog'][$version])) {
            $io->error("La version {$version} n'existe pas dans le changelog");
            return self::CODE_ERROR;
        }

        $changeType = $io->askChoice(
            'Type de changement:',
            ['added', 'changed', 'deprecated', 'removed', 'fixed', 'security'],
            'added'
        );

        $description = $io->ask('Description du changement:');

        $versionData['changelog'][$version][$changeType][] = $description;
        $this->saveVersionData($versionData, $plugin);

        $io->success("Changement ajouté au changelog de la version {$version}");

        return self::CODE_SUCCESS;
    }

    /**
     * Affiche le changelog
     */
    protected function viewChangelog($version = null, $plugin = null, ConsoleIo $io)
    {
        $versionData = $this->getVersionData($plugin);
        if ($versionData === null || empty($versionData['changelog'])) {
            $io->error("Aucun changelog disponible");
            return self::CODE_ERROR;
        }

        $target = $plugin ? "du plugin '{$plugin}'" : "de l'application";
        
        if ($version) {
            // Afficher une version spécifique
            if (isset($versionData['changelog'][$version])) {
                $io->info("CHANGELOG - Version {$version} - {$target}");
                $io->hr();
                $this->displayChangelogEntry($versionData['changelog'][$version], $version, $io);
            } else {
                $io->error("La version {$version} n'existe pas dans le changelog");
                return self::CODE_ERROR;
            }
        } else {
            // Afficher tout le changelog
            $io->info("CHANGELOG COMPLET - {$target}");
            $io->hr();

            $versions = array_keys($versionData['changelog']);
            usort($versions, 'version_compare');
            $versions = array_reverse($versions);

            foreach ($versions as $version) {
                $this->displayChangelogEntry($versionData['changelog'][$version], $version, $io);
                $io->hr();
            }
        }

        return self::CODE_SUCCESS;
    }

    /**
     * Affiche une entrée du changelog
     */
    protected function displayChangelogEntry($entry, $version, ConsoleIo $io)
    {
        $io->out("<info>Version {$version}</info> - {$entry['release_date']}");
        $io->out("Description: {$entry['description']}");
        $io->out("");

        $changeTypes = [
            'added' => ['title' => 'Nouveautés', 'icon' => '🆕'],
            'changed' => ['title' => 'Modifications', 'icon' => '🔄'],
            'deprecated' => ['title' => 'Dépréciations', 'icon' => '⚠️'],
            'removed' => ['title' => 'Suppressions', 'icon' => '🗑️'],
            'fixed' => ['title' => 'Corrections', 'icon' => '🔧'],
            'security' => ['title' => 'Sécurité', 'icon' => '🔒']
        ];

        foreach ($changeTypes as $type => $info) {
            if (!empty($entry[$type])) {
                $io->out("<comment>{$info['icon']} {$info['title']}</comment>");
                foreach ($entry[$type] as $change) {
                    $io->out("  • {$change}");
                }
                $io->out("");
            }
        }
    }

    /**
     * Initialise le fichier CHANGELOG.md
     */
    protected function initializeChangelogFile($versionData, $plugin = null)
    {
        $changelogFile = $this->getChangelogFilePath($plugin);
        $content = $this->generateMarkdownChangelog($versionData, $plugin);
        file_put_contents($changelogFile, $content);
    }

    /**
     * Génère le contenu Markdown du changelog
     */
    protected function generateMarkdownChangelog($versionData, $plugin = null)
    {
        $productName = $versionData['PRODUCT'];
        $content = "# Changelog - {$productName}\n\n";
        $content .= "Toutes les modifications notables de ce projet seront documentées dans ce fichier.\n\n";

        if (!empty($versionData['changelog'])) {
            $versions = array_keys($versionData['changelog']);
            usort($versions, 'version_compare');
            $versions = array_reverse($versions);

            foreach ($versions as $version) {
                $entry = $versionData['changelog'][$version];
                $content .= "## [{$version}] - {$entry['release_date']}\n\n";
                
                if (!empty($entry['description'])) {
                    $content .= "**{$entry['description']}**\n\n";
                }

                $changeTypes = [
                    'added' => '🆕 Nouveautés',
                    'changed' => '🔄 Modifications', 
                    'deprecated' => '⚠️ Dépréciations',
                    'removed' => '🗑️ Suppressions',
                    'fixed' => '🔧 Corrections',
                    'security' => '🔒 Sécurité'
                ];

                foreach ($changeTypes as $type => $title) {
                    if (!empty($entry[$type])) {
                        $content .= "### {$title}\n\n";
                        foreach ($entry[$type] as $change) {
                            $content .= "- {$change}\n";
                        }
                        $content .= "\n";
                    }
                }
            }
        } else {
            $content .= "## [{$versionData['MAJOR_VERSION']}.{$versionData['MINOR_VERSION']}.{$versionData['PATCH_VERSION']}] - {$versionData['RELDATE']}\n\n";
            $content .= "**Version initiale**\n\n";
        }

        return $content;
    }

    /***************************************************************************
     * MÉTHODES UTILITAIRES
     **************************************************************************/

    /**
     * Retourne le chemin du fichier de version
     */
    protected function getVersionFilePath($plugin = null)
    {
        if ($plugin) {
            return Plugin::path($plugin) . self::PLUGIN_VERSION_FILE;
        }
        
        return self::APP_VERSION_FILE;
    }

    /**
     * Retourne le chemin du fichier CHANGELOG.md
     */
    protected function getChangelogFilePath($plugin = null)
    {
        if ($plugin) {
            return Plugin::path($plugin) . self::CHANGELOG_FILE;
        }
        
        return ROOT . DS . self::CHANGELOG_FILE;
    }

    /**
     * Formate une version courte
     */
    protected function getShortVersion($versionData)
    {
        $version = "{$versionData['MAJOR_VERSION']}.{$versionData['MINOR_VERSION']}.{$versionData['PATCH_VERSION']}";
        
        if (!empty($versionData['EXTRA_VERSION'])) {
            $version .= '-' . $versionData['EXTRA_VERSION'];
        }
        
        return $version;
    }

    /**
     * Formate une version longue
     */
    protected function getLongVersion($versionData)
    {
        return "{$versionData['PRODUCT']} {$this->getShortVersion($versionData)} "
             . "{$versionData['DEV_STATUS']} [{$versionData['CODENAME']}] {$versionData['RELDATE']}";
    }

    /**
     * Vérifie si une version est valide
     */
    protected function isValidVersion($version)
    {
        return preg_match('/^\d+\.\d+\.\d+(-[a-zA-Z0-9\.]+)?$/', $version);
    }

    /**
     * Parse une version en composants
     */
    protected function parseVersion($versionString)
    {
        $parts = explode('-', $versionString);
        $mainVersion = $parts[0];
        $extra = isset($parts[1]) ? $parts[1] : '';

        list($major, $minor, $patch) = explode('.', $mainVersion);

        return [
            'major' => (int)$major,
            'minor' => (int)$minor,
            'patch' => (int)$patch,
            'extra' => $extra
        ];
    }

    /**
     * Compte le nombre de changements dans une entrée
     */
    protected function countChanges($changelogEntry)
    {
        $count = 0;
        $types = ['added', 'changed', 'deprecated', 'removed', 'fixed', 'security'];
        
        foreach ($types as $type) {
            if (isset($changelogEntry[$type]) && is_array($changelogEntry[$type])) {
                $count += count($changelogEntry[$type]);
            }
        }
        
        return $count;
    }

    /**
     * Édite la description d'une entrée de changelog
     */
    protected function editChangelogDescription($version, $plugin, ConsoleIo $io)
    {
        $versionData = $this->getVersionData($plugin);
        if (!isset($versionData['changelog'][$version])) {
            $io->error("La version {$version} n'existe pas dans le changelog");
            return self::CODE_ERROR;
        }

        $currentDescription = $versionData['changelog'][$version]['description'];
        $newDescription = $io->ask("Nouvelle description pour la version {$version}:", $currentDescription);

        $versionData['changelog'][$version]['description'] = $newDescription;
        $this->saveVersionData($versionData, $plugin);

        $io->success("Description mise à jour pour la version {$version}");

        return self::CODE_SUCCESS;
    }
}
