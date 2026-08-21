<?php

declare(strict_types=1);

/**
 * Legacy entry point retained for Joomla package compatibility.
 *
 * Joomla 6 instantiates the plugin through services/provider.php and the
 * namespaced class in src/Extension/Rsfpadvancephp.php. This file intentionally
 * contains no plugin class so the extension remains compatible with the modern
 * dependency injection architecture while preserving the historical plugin file
 * name referenced by the manifest.
 */

defined('_JEXEC') or die;
