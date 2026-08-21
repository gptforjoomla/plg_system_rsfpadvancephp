<?php

declare(strict_types=1);

/**
 * Service provider for the Advance PHP for RSForm!Pro system plugin.
 *
 * Joomla loads this file during extension bootstrapping. It registers the
 * plugin as a service and injects the Joomla event dispatcher, application and
 * database service without relying on deprecated factory APIs.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use VDM\Plugin\System\Rsfpadvancephp\Extension\Rsfpadvancephp;

return new class () implements ServiceProviderInterface {
    /**
     * Register the plugin service in Joomla's dependency injection container.
     *
     * @param   Container  $container  The Joomla service container.
     *
     * @return  void
     */
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            static function (Container $container): PluginInterface {
                $plugin = new Rsfpadvancephp(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('system', 'rsfpadvancephp'),
                    $container->get(DatabaseInterface::class)
                );

                $plugin->setApplication($container->get(CMSApplicationInterface::class));

                return $plugin;
            }
        );
    }
};
