<?php

declare(strict_types=1);

/**
 * Administrator layout for the retained Advance PHP event editors.
 *
 * Available variables from the plugin:
 * - array<int, AdvancePhpEvent> $events Retained event descriptors.
 * - array<int,int> $active Active flags keyed by legacy event index.
 * - array<int,string> $code Decoded PHP code keyed by legacy event index.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use VDM\Plugin\System\Rsfpadvancephp\Value\AdvancePhpEvent;

?>
<div class="rsfpadvancephp__intro alert alert-info">
    <?php echo Text::_('PLG_SYSTEM_RSFPADVANCEPHP_ADMIN_INTRO'); ?>
</div>

<div class="accordion rsfpadvancephp__events" id="rsfpadvancephp-events">
    <?php foreach ($events as $event) : ?>
        <?php
        /** @var AdvancePhpEvent $event */
        $index = $event->oldIndex;
        $enabled = (int) ($active[$index] ?? 0) === 1;
        $collapseId = 'rsfpadvancephp-event-' . $index;
        $headingId = 'rsfpadvancephp-event-heading-' . $index;
        $fieldName = 'rsfpadvancephp_active_' . $index;
        ?>
        <section class="accordion-item rsfpadvancephp__event" data-rsfpadvancephp-event="<?php echo (int) $index; ?>">
            <h2 class="accordion-header" id="<?php echo htmlspecialchars($headingId, ENT_QUOTES, 'UTF-8'); ?>">
                <button
                    class="accordion-button<?php echo $enabled ? '' : ' collapsed'; ?>"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#<?php echo htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8'); ?>"
                    aria-expanded="<?php echo $enabled ? 'true' : 'false'; ?>"
                    aria-controls="<?php echo htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8'); ?>"
                >
                    <?php echo Text::_('PLG_SYSTEM_RSFPADVANCEPHP_ACTIVE_' . $event->keySuffix); ?>
                </button>
            </h2>
            <div
                id="<?php echo htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8'); ?>"
                class="accordion-collapse collapse<?php echo $enabled ? ' show' : ''; ?>"
                aria-labelledby="<?php echo htmlspecialchars($headingId, ENT_QUOTES, 'UTF-8'); ?>"
            >
                <div class="accordion-body">
                    <fieldset class="mb-3">
                        <legend class="visually-hidden">
                            <?php echo Text::_('PLG_SYSTEM_RSFPADVANCEPHP_ACTIVE') . ' ' . Text::_('PLG_SYSTEM_RSFPADVANCEPHP_ACTIVE_' . $event->keySuffix); ?>
                        </legend>
                        <div class="form-check form-check-inline">
                            <input
                                class="form-check-input js-rsfpadvancephp-toggle"
                                type="radio"
                                name="<?php echo htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'); ?>"
                                id="<?php echo htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'); ?>_1"
                                value="1"
                                data-code-target="rsfpadvancephp-code-wrap-<?php echo (int) $index; ?>"
                                <?php echo $enabled ? 'checked' : ''; ?>
                            >
                            <label class="form-check-label" for="<?php echo htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'); ?>_1">
                                <?php echo Text::_('JYES'); ?>
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input
                                class="form-check-input js-rsfpadvancephp-toggle"
                                type="radio"
                                name="<?php echo htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'); ?>"
                                id="<?php echo htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'); ?>_0"
                                value="0"
                                data-code-target="rsfpadvancephp-code-wrap-<?php echo (int) $index; ?>"
                                <?php echo $enabled ? '' : 'checked'; ?>
                            >
                            <label class="form-check-label" for="<?php echo htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'); ?>_0">
                                <?php echo Text::_('JNO'); ?>
                            </label>
                        </div>
                    </fieldset>

                    <div id="rsfpadvancephp-code-wrap-<?php echo (int) $index; ?>" class="rsfpadvancephp__code<?php echo $enabled ? '' : ' d-none'; ?>">
                        <p class="form-text">
                            <strong><?php echo Text::_('PLG_SYSTEM_RSFPADVANCEPHP_NOTICE'); ?></strong>
                        </p>
                        <pre class="rsfpadvancephp__context"><code><?php echo htmlspecialchars(Text::_('PLG_SYSTEM_RSFPADVANCEPHP_NOTICE_' . $event->keySuffix), ENT_NOQUOTES, 'UTF-8'); ?></code></pre>
                        <label class="form-label" for="rsfpadvancephp_code_<?php echo (int) $index; ?>">
                            <?php echo Text::_('PLG_SYSTEM_RSFPADVANCEPHP_CODE_LABEL'); ?>
                        </label>
                        <textarea
                            class="form-control rsfpadvancephp__textarea"
                            name="rsfpadvancephp_code_<?php echo (int) $index; ?>"
                            id="rsfpadvancephp_code_<?php echo (int) $index; ?>"
                            rows="14"
                            spellcheck="false"
                        ><?php echo htmlspecialchars($code[$index] ?? '', ENT_COMPAT, 'UTF-8'); ?></textarea>
                    </div>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
</div>
