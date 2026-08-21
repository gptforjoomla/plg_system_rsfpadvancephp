<?php

declare(strict_types=1);

/**
 * Installer script for Advance PHP for RSForm!Pro.
 *
 * The script performs compatibility checks only. It never copies files into the
 * RSForm!Pro component directory, keeping the extension self-contained for
 * Joomla 6. The legacy database tables are managed by SQL install/update files
 * and existing rows in #__rsform_advancephp are preserved during upgrades.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;

/**
 * Joomla installer hooks for the plugin.
 */
final class PlgSystemRsfpadvancephpInstallerScript
{
    /** @var string Minimum Joomla version supported by this modernized plugin. */
    private const MIN_JOOMLA = '6.0.0';

    /** @var string Minimum PHP version supported by this modernized plugin. */
    private const MIN_PHP = '8.3.0';

    /**
     * Validate the target environment before install or update.
     *
     * @param   string            $type    Install operation type.
     * @param   InstallerAdapter  $parent  Joomla installer adapter.
     *
     * @return  bool  True when installation may continue.
     */
    public function preflight(string $type, InstallerAdapter $parent): bool
    {
        if ($type === 'uninstall') {
            return true;
        }

        if (version_compare(PHP_VERSION, self::MIN_PHP, '<')) {
            $parent->getParent()->set('message', Text::sprintf('PLG_SYSTEM_RSFPADVANCEPHP_INSTALL_ERROR_PHP', self::MIN_PHP));

            return false;
        }

        $version = new Version();

        if (!$version->isCompatible(self::MIN_JOOMLA)) {
            $parent->getParent()->set('message', Text::sprintf('PLG_SYSTEM_RSFPADVANCEPHP_INSTALL_ERROR_JOOMLA', self::MIN_JOOMLA));

            return false;
        }

        if (!is_file(JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/rsform.php')) {
            $parent->getParent()->set('message', Text::_('PLG_SYSTEM_RSFPADVANCEPHP_INSTALL_ERROR_RSFORM'));

            return false;
        }

        return true;
    }

    /**
     * Post-install hook.
     *
     * No component files are copied. The method exists to document the intended
     * Joomla 6 self-contained architecture and to provide a future extension
     * point for non-destructive migrations.
     *
     * @param   string            $type    Install operation type.
     * @param   InstallerAdapter  $parent  Joomla installer adapter.
     *
     * @return  bool
     */
    public function postflight(string $type, InstallerAdapter $parent): bool
    {
        return true;
    }
}
