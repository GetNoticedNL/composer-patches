<?php declare(strict_types=1);

namespace GetNoticed\ComposerPatches\Utils;

use Composer\IO\IOInterface;

class OutputUtils
{
    /**
     * @param \Composer\IO\IOInterface $io
     */
    public static function sendMotd(IOInterface $io): void
    {
        if (defined('GN_COMPOSER_PATCHES_MOTD_SENT')) {
            return;
        }

        $io->write(sprintf('<info>%s</info>', str_repeat('-', 65)));
        $io->write(
            sprintf('<info>| Get.Noticed B.V. Composer Patches Plugin is active!%s|</info>', str_repeat(' ', 11))
        );
        $io->write(
            sprintf('<info>|</info> See www.getnoticed.nl for more information.%s<info>|</info>', str_repeat(' ', 19))
        );
        $io->write(
            '<info>|</info> Github page: <comment>https://github.com/GetNoticedNL/composer-patches</comment> <info>|</info>'
        );
        $io->write(sprintf('<info>%s</info>', str_repeat('-', 65)));

        define('GN_COMPOSER_PATCHES_MOTD_SENT', true);
    }
}
