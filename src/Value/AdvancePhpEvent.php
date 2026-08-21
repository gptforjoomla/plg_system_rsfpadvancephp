<?php

declare(strict_types=1);

namespace VDM\Plugin\System\Rsfpadvancephp\Value;

/**
 * Describes one retained Advance PHP event slot.
 *
 * The original plugin stored twelve event slots in #__rsform_advancephp. Joomla
 * 6 compatibility keeps the table and old indexes intact, but this plugin now
 * executes only the five custom events that are not duplicated by current
 * RSForm!Pro native scripting features.
 */
final class AdvancePhpEvent
{
    /**
     * @param   int     $oldIndex     Original zero-based storage index in the legacy JSON arrays.
     * @param   string  $keySuffix    Language-key suffix used for event titles and context help.
     * @param   string  $method       RSForm!Pro plugin event method handled by this extension.
     * @param   string  $description  English architecture documentation for developers.
     */
    public function __construct(
        public readonly int $oldIndex,
        public readonly string $keySuffix,
        public readonly string $method,
        public readonly string $description
    ) {
    }
}
