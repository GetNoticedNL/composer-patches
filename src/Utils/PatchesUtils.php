<?php

namespace GetNoticed\ComposerPatches\Utils;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Semver\Semver;

class PatchesUtils
{
    /**
     * @param \GetNoticed\ComposerPatches\Data\Patch[] $patches
     * @param \Composer\IO\IOInterface                 $io
     * @param \Composer\Composer                       $composer
     *
     * @return \GetNoticed\ComposerPatches\Data\Patch[]
     * @throws \Exception
     */
    public static function getApplicablePatches(array $patches, IOInterface $io, Composer $composer)
    {
        $allPackages = $composer->getRepositoryManager()->getLocalRepository()->getPackages();
        $applicablePatches = [];

        foreach ($patches as $patch) {
            $package = self::getPackageByName($allPackages, $patch->getTargetName());

            if ($package === null) {
                continue;
            }

            foreach ($patch->getConditions() as $conditionIndex => $condition) {
                $conditionTargetName = $condition->getTargetName() === '_self'
                    ? $patch->getTargetName() : $condition->getTargetName();
                $conditionPackage = self::getPackageByName($allPackages, $conditionTargetName);

                if ($conditionPackage === null && $condition->isOptional() !== true) {
                    throw new \Exception(
                        sprintf(
                            'Patch %s must be installed, but condition %d relies on package %s, which is not present.',
                            $patch->getName(),
                            $conditionIndex,
                            $conditionTargetName
                        )
                    );
                }
                if (!Semver::satisfies($conditionPackage->getVersion(), $condition->getConstraint()->getConstraint())) {
                    continue 2;
                }
            }

            $applicablePatches[] = $patch;
        }

        return $applicablePatches;
    }

    /**
     * @param \GetNoticed\ComposerPatches\Data\Patch[] $patches
     * @param \Composer\IO\IOInterface                 $io
     * @param \Composer\Composer                       $composer
     *
     * @throws \Exception
     */
    public static function installPatches(
        array $patches,
        IOInterface $io,
        Composer $composer
    ) {
        $allPackages = $composer->getRepositoryManager()->getLocalRepository()->getPackages();
        $downloader = $composer->getDownloadManager();
        $installer = $composer->getInstallationManager();
        $reinstalledPackages = [];

        if (empty($patches)) {
            $io->write('<info>Patching - no applicable patches!</info>');

            return;
        }

        $io->write(str_repeat('-', 72));
        $io->write(sprintf('Applying <info>%d</info> applicable patches', count($patches)));

        foreach ($patches as $patch) {
            // Load package from installed list
            $package = self::getPackageByName($allPackages, $patch->getTargetName());

            if ($package === null) {
                throw new \Exception('System error - package to patch no longer exists.');
            }

            // Reinstall package before applying patch
            if (array_key_exists($package->getName(), $reinstalledPackages) !== true) {
                $io->write(
                    sprintf(
                        '  - Reinstalling <info>%s</info> (<comment>%s</comment>) for patching',
                        $package->getName(),
                        $package->getVersion()
                    )
                );
                self::reinstallPackage($downloader, $installer, $package);
                $reinstalledPackages[] = $package->getName();
            }

            // Apply patch
            $io->write(
                sprintf(
                    '  - Patching <info>%s</info> (<comment>%s</comment>): %s %s',
                    $package->getName(),
                    $package->getPrettyVersion(),
                    $patch->getName(),
                    $patch->getDescription()
                )
            );
            $applyCommand = sprintf('git apply -p%d %s 2>&1', $patch->getPrecision(), $patch->getFilePath());
            exec($applyCommand, $patchOutput, $patchExitCode);

            // Check patch results
            if ($patchExitCode !== 0) {
                $io->writeError(sprintf('<error>Unable to apply "%s"</error>', $patch->getName()));
                $io->writeError(
                    array_map(
                        function (string $patchOutputLine) {
                            return sprintf('<error>%s</error>', $patchOutputLine);
                        },
                        $patchOutput
                    )
                );

                throw new \Exception(
                    'Unable to install patches, please check configuration and try again after fixing the errors.'
                );
            }

            $io->write(str_repeat('-', 72));
        }
    }

    /**
     * @param \Composer\Package\Package[] $packages
     * @param string                      $name
     *
     * @return \Composer\Package\Package|null
     */
    public static function getPackageByName(array $packages, string $name)
    {
        foreach ($packages as $package) {
            if ($package->getName() === $name) {
                return $package;
            }
        }

        return null;
    }

    /**
     * @param \Composer\Downloader\DownloadManager    $downloader
     * @param \Composer\Installer\InstallationManager $installationManager
     * @param \Composer\Package\Package               $package
     */
    protected static function reinstallPackage(
        \Composer\Downloader\DownloadManager $downloader,
        \Composer\Installer\InstallationManager $installationManager,
        \Composer\Package\Package $package
    ): void {
        $targetDir = $installationManager->getInstallPath($package);

        $downloader->remove($package, $targetDir);
        $downloader->download($package, $targetDir);
    }
}
