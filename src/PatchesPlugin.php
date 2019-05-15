<?php declare(strict_types=1);

namespace GetNoticed\ComposerPatches;

use Composer\{
    Composer,
    EventDispatcher\EventSubscriberInterface,
    IO\IOInterface,
    Plugin\PluginInterface,
    Plugin\Capable,
    Script\Event,
    Script\ScriptEvents
};
use GetNoticed\ComposerPatches\{Converters\PatchConverter, Utils\ArrayUtils, Utils\OutputUtils, Utils\PatchesUtils};

class PatchesPlugin implements PluginInterface, EventSubscriberInterface, Capable
{
    const EXIT_CODE_SUCCESS = 0;
    const EXIT_CODE_FAILURE = 1;

    const PATTERN_DETECT_PATCHES_COMPOSER_SOURCE = '(vendor\/)(?P<vendor>[\w\-\_]*)(\/)(?P<package>[\w\-\_]*)(\/)(?P<patchesfile>.*)';

    /**
     * @var array
     */
    private $extra = [];

    /**
     * @var bool
     */
    private $enabled = false;

    /**
     * @var \GetNoticed\ComposerPatches\Data\Patch[]
     */
    private $patches = [];

    /**
     * @var \GetNoticed\ComposerPatches\Data\Patch[]
     */
    private $applicablePatches = [];

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        // Initialize
        /** @var \Composer\Package\RootPackageInterface|\Composer\Package\RootPackage $rootPackage */
        $rootPackage = $composer->getPackage();
        $this->extra = $rootPackage->getExtra();
        $this->enabled = ArrayUtils::get($this->extra, 'patching-enabled', false);

        // If not enabled, return and stop processing here
        if ($this->isEnabled() !== true) {
            return;
        }

        // Send MOTD
        OutputUtils::sendMotd($io);
    }

    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::PRE_AUTOLOAD_DUMP => ['onPreAutoloadDump']
        ];
    }

    public function getCapabilities()
    {
        return [];
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param \Composer\Script\Event $scriptEvent
     *
     * @throws \Exception
     */
    public function onPreAutoloadDump(Event $scriptEvent)
    {
        $io = $scriptEvent->getIO();
        $composer = $scriptEvent->getComposer();

        if ($this->isEnabled() !== true) {
            return;
        }

        // Load patches from file
        $this->loadPatchesFromFile($io, $composer, (string)ArrayUtils::get($this->extra, 'patching-patches-file'));
        $this->applicablePatches = PatchesUtils::getApplicablePatches($this->patches, $io, $composer);

        PatchesUtils::installPatches($this->applicablePatches, $io, $composer);
    }

    /**
     * @param \Composer\IO\IOInterface $io
     * @param \Composer\Composer       $composer
     * @param string|null              $patchesFilePath
     */
    protected function loadPatchesFromFile(IOInterface $io, Composer $composer, ?string $patchesFilePath): void
    {
        if ($this->isEnabled() !== true) {
            return;
        }

        $patchesFilePath = trim($patchesFilePath);

        if (empty($patchesFilePath)) {
            $this->writeErrorExit($io, '<error>Patching is enabled, but no patch file has been given.</error>');
        }

        // Check if patches file is located within package
        $patchPackage = $patchPackageExists = false;
        $matchResult = preg_match(
            sprintf('/%s/', self::PATTERN_DETECT_PATCHES_COMPOSER_SOURCE),
            $patchesFilePath,
            $matches
        );

        if ($matchResult === 1) {
            $vendorName = $matches['vendor'];
            $packageName = $matches['package'];

            $patchPackage = true;

            /** @var \Composer\Package\Package[] $allPackages */
            $allPackages = $composer->getRepositoryManager()->getLocalRepository()->getPackages();

            foreach ($allPackages as $package) {
                if ($package->getName() === sprintf('%s/%s', $vendorName, $packageName)) {
                    $patchPackageExists = true;
                }
            }
        }

        if ($patchPackage === true && $patchPackageExists === false) {
            $io->write(
                '<comment>The patches file should be loaded from a package, but it is not yet installed. Skipping for now.</comment>'
            );
        } else {
            $patchesFilePath = realpath($patchesFilePath);

            if (realpath($patchesFilePath) === false || file_exists(realpath($patchesFilePath)) === false) {
                $this->writeErrorExit(
                    $io, '<error>Patching is enabled, but the specified patch file can not be reached.</error>'
                );
            }

            $patchesData = \json_decode(\file_get_contents($patchesFilePath), true);

            if (empty($patchesData) || is_array($patchesData) !== true) {
                $this->writeErrorExit(
                    $io,
                    sprintf(
                        '<error>Syntax error in patch file: (%s) %s</error>',
                        \json_last_error() ?: '-',
                        \json_last_error_msg() ?: 'No valid data received (check syntax in README.md)'
                    )
                );
            }

            $this->patches = PatchConverter::convertPatches($patchesData);
        }
    }

    private function writeErrorExit(IOInterface $io, $messages)
    {
        $io->writeError($messages);

        exit(self::EXIT_CODE_FAILURE);
    }
}
