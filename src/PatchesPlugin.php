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

        // Load patches from file
        $this->loadPatchesFromFile($io, (string)ArrayUtils::get($this->extra, 'patching-patches-file'));
        $this->applicablePatches = PatchesUtils::getApplicablePatches($this->patches, $io, $composer);
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

        PatchesUtils::installPatches($this->applicablePatches, $io, $composer);
    }

    /**
     * @param \Composer\IO\IOInterface $io
     * @param string|null              $patchesFilePath
     */
    protected function loadPatchesFromFile(IOInterface $io, ?string $patchesFilePath): void
    {
        if ($this->isEnabled() !== true) {
            return;
        }

        $patchesFilePath = trim($patchesFilePath);

        if (empty($patchesFilePath) || file_exists(realpath($patchesFilePath)) !== true) {
            $this->writeErrorExit(
                $io,
                '<error>Patching is enabled, but no valid patch file has been provided. Please correct this error and run Composer again.</error>'
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

    private function writeErrorExit(IOInterface $io, $messages)
    {
        $io->writeError($messages);

        exit(self::EXIT_CODE_FAILURE);
    }
}
