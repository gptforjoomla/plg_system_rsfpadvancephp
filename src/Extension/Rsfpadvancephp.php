<?php

declare(strict_types=1);

namespace VDM\Plugin\System\Rsfpadvancephp\Extension;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Throwable;
use VDM\Plugin\System\Rsfpadvancephp\Repository\EventRepository;
use VDM\Plugin\System\Rsfpadvancephp\Value\AdvancePhpEvent;

/**
 * Joomla 6 system plugin for custom RSForm!Pro Advance PHP events.
 *
 * Architecture overview:
 * - Joomla creates this plugin through services/provider.php and injects the
 *   event dispatcher, application and DatabaseInterface services.
 * - RSForm!Pro calls the public onRsform* methods. Their names are intentionally
 *   kept identical to the supported RSForm!Pro integration points used by the
 *   original plugin.
 * - Administrator settings are stored in the legacy #__rsform_advancephp table.
 * - Only five custom event slots are retained and executed. Removed slots remain
 *   stored for data compatibility but are ignored because current RSForm!Pro
 *   provides equivalent native scripting features.
 *
 * Event context documentation:
 * - On Before Store Submissions receives an array with formId, post by reference
 *   and SubmissionId. Existing scripts may modify values inside $args.
 * - On After Store Submissions receives an array with SubmissionId and formId.
 * - On After Show Thankyou Message receives an array with output by reference and
 *   formId by reference. Existing scripts may alter $args['output'].
 * - On After Create Placeholders receives an array with form, placeholders by
 *   reference, values by reference and submission. Existing scripts may alter
 *   placeholders or values in the $args array.
 * - On After Confirm Payment receives the RSForm!Pro submission ID as
 *   $SubmissionId, matching the legacy execution context.
 */
final class Rsfpadvancephp extends CMSPlugin
{
    /** @var bool Joomla should load language files automatically when available. */
    protected $autoloadLanguage = true;

    /** @var array<int, AdvancePhpEvent> Retained events keyed by legacy index. */
    private const RETAINED_EVENTS = [
        3  => ['E', 'onRsformFrontendBeforeStoreSubmissions', 'Before RSForm!Pro stores a submitted record.'],
        4  => ['F', 'onRsformFrontendAfterStoreSubmissions', 'After RSForm!Pro stores a submitted record.'],
        6  => ['H', 'onRsformFrontendAfterShowThankyouMessage', 'Before the thank-you output is finally displayed.'],
        7  => ['I', 'onRsformAfterCreatePlaceholders', 'After RSForm!Pro builds placeholders and submission values.'],
        11 => ['M', 'onRsformAfterConfirmPayment', 'After RSForm!Pro confirms a payment submission.'],
    ];

    /** @var EventRepository Repository for legacy-compatible event persistence. */
    private EventRepository $repository;

    /** @var array<int,int> Cached active flags for the current form. */
    private array $active = [];

    /** @var array<int,string> Cached code snippets for the current form. */
    private array $code = [];

    /** @var int|null Form ID represented by the current cache. */
    private ?int $cachedFormId = null;

    /**
     * @param   DispatcherInterface  $dispatcher  Joomla event dispatcher.
     * @param   array<string,mixed>   $config      Plugin configuration from Joomla.
     * @param   DatabaseInterface    $db          Joomla database service.
     */
    public function __construct(DispatcherInterface $dispatcher, array $config, DatabaseInterface $db)
    {
        parent::__construct($dispatcher, $config);

        $this->repository = new EventRepository($db);
    }

    /**
     * Persist Advance PHP settings when RSForm!Pro saves a form.
     *
     * Only the five retained custom event indexes are updated. Values belonging
     * to removed/native RSForm!Pro event indexes are kept in the database but are
     * not rendered or executed by this plugin.
     *
     * @param   mixed  $form  RSForm!Pro form object/array supplied by RSForm!Pro.
     *
     * @return  void
     */
    public function onRsformFormSave(mixed $form): void
    {
        if (!$this->hasBackendAccess()) {
            return;
        }

        $input = $this->getApplication()->getInput();
        $formId = $input->post->getInt('formId');

        if ($formId <= 0) {
            return;
        }

        $active = [];
        $code = [];

        foreach ($this->getRetainedEvents() as $event) {
            $index = $event->oldIndex;
            $active[$index] = $input->post->getInt('rsfpadvancephp_active_' . $index, 0) === 1 ? 1 : 0;
            $rawCode = (string) $input->post->get('rsfpadvancephp_code_' . $index, '', 'raw');
            $code[$index] = base64_encode($rawCode);
        }

        $this->repository->saveRetained($formId, $active, $code);
        $this->cachedFormId = null;
    }

    /**
     * Add the Advance PHP tab title to the RSForm!Pro form editor.
     *
     * @return  void
     */
    public function onRsformBackendAfterShowFormEditTabsTab(): void
    {
        if (!$this->hasBackendAccess()) {
            return;
        }

        echo '<li><a id="rsfpadvancephp-tab" href="javascript:void(0);"><span>'
            . htmlspecialchars(Text::_('PLG_SYSTEM_RSFPADVANCEPHP_JOOMLA_PROFILE_TAB'), ENT_QUOTES, 'UTF-8')
            . '</span></a></li>';
    }

    /**
     * Render the Advance PHP settings panel in the RSForm!Pro form editor.
     *
     * @return  void
     */
    public function onRsformBackendAfterShowFormEditTabs(): void
    {
        if (!$this->hasBackendAccess()) {
            return;
        }

        $formId = $this->getApplication()->getInput()->getInt('formId');
        $data = $this->repository->load($formId);
        $events = $this->getRetainedEvents();
        $active = $data['events_active'];
        $code = [];

        foreach ($events as $event) {
            $code[$event->oldIndex] = $this->decodeStoredCode($data['events_code'][$event->oldIndex] ?? '');
        }

        $this->registerAssets();

        echo '<div id="rsfpadvancephpdiv" class="rsfpadvancephp">';
        include dirname(__DIR__, 2) . '/layouts/events.php';
        echo '</div>';
    }

    /**
     * Add the plugin information panel to the RSForm!Pro configuration screen.
     *
     * @param   mixed  $tabs  RSForm!Pro tab helper object.
     *
     * @return  void
     */
    public function onRsformBackendAfterShowConfigurationTabs(mixed $tabs): void
    {
        if (!$this->hasBackendAccess() || !is_object($tabs)) {
            return;
        }

        if (method_exists($tabs, 'addTitle')) {
            $tabs->addTitle(Text::_('PLG_SYSTEM_RSFPADVANCEPHP_CONFIG_TAB'), 'form-advancephp');
        }

        if (method_exists($tabs, 'addContent')) {
            $tabs->addContent($this->getConfigurationScreen());
        }
    }

    /**
     * Execute custom PHP before RSForm!Pro stores a submission.
     *
     * Expected $args context: array('formId' => int, 'post' => &array,
     * 'SubmissionId' => int|string). The variable $args remains available to
     * administrator-entered code for backward compatibility.
     *
     * @param   array<string,mixed>  $args  RSForm!Pro event context.
     *
     * @return  void
     */
    public function onRsformFrontendBeforeStoreSubmissions(array $args): void
    {
        $this->executeForForm((int) ($args['formId'] ?? 0), 3, ['args' => &$args]);
    }

    /**
     * Execute custom PHP after RSForm!Pro stores a submission.
     *
     * Expected $args context: array('SubmissionId' => int|string,
     * 'formId' => int). The variable $args remains available to saved code.
     *
     * @param   array<string,mixed>  $args  RSForm!Pro event context.
     *
     * @return  void
     */
    public function onRsformFrontendAfterStoreSubmissions(array $args): void
    {
        $this->executeForForm((int) ($args['formId'] ?? 0), 4, ['args' => &$args]);
    }

    /**
     * Execute custom PHP after RSForm!Pro prepares the thank-you message.
     *
     * Expected $args context: array('output' => &string, 'formId' => &int). The
     * variable $args remains available so saved code can alter output.
     *
     * @param   array<string,mixed>  $args  RSForm!Pro event context.
     *
     * @return  void
     */
    public function onRsformFrontendAfterShowThankyouMessage(array $args): void
    {
        $this->executeForForm((int) ($args['formId'] ?? 0), 6, ['args' => &$args]);
    }

    /**
     * Execute custom PHP after RSForm!Pro creates placeholders.
     *
     * Expected $args context: array('form' => object, 'placeholders' => &array,
     * 'values' => &array, 'submission' => mixed). The variable $args remains
     * available to existing scripts.
     *
     * @param   array<string,mixed>  $args  RSForm!Pro event context.
     *
     * @return  void
     */
    public function onRsformAfterCreatePlaceholders(array $args): void
    {
        $formId = 0;

        if (isset($args['form']) && is_object($args['form']) && isset($args['form']->FormId)) {
            $formId = (int) $args['form']->FormId;
        }

        $this->executeForForm($formId, 7, ['args' => &$args]);
    }

    /**
     * Execute custom PHP after RSForm!Pro confirms a payment.
     *
     * Expected context: $SubmissionId contains the confirmed submission ID, just
     * as it did in the original plugin. The form ID is resolved from
     * #__rsform_submissions before loading the saved event configuration.
     *
     * @param   int|string  $SubmissionId  RSForm!Pro submission identifier.
     *
     * @return  void
     */
    public function onRsformAfterConfirmPayment(int|string $SubmissionId): void
    {
        $formId = $this->repository->getFormIdBySubmissionId($SubmissionId);

        if ($formId === null) {
            return;
        }

        $this->executeForForm($formId, 11, ['SubmissionId' => $SubmissionId]);
    }

    /**
     * Build the RSForm!Pro configuration tab content.
     *
     * @return  string  Translated HTML content.
     */
    private function getConfigurationScreen(): string
    {
        return Text::_('PLG_SYSTEM_RSFPADVANCEPHP_CONFIG_NOTICE');
    }

    /**
     * Execute a retained event code block for a specific form.
     *
     * @param   int                $formId    RSForm!Pro form identifier.
     * @param   int                $oldIndex  Legacy event index to execute.
     * @param   array<string,mixed> $context  Variables made available to saved PHP code.
     *
     * @return  void
     */
    private function executeForForm(int $formId, int $oldIndex, array $context): void
    {
        if ($formId <= 0 || !isset(self::RETAINED_EVENTS[$oldIndex])) {
            return;
        }

        $this->setEvents($formId);

        if (($this->active[$oldIndex] ?? 0) !== 1) {
            return;
        }

        $code = $this->decodeStoredCode($this->code[$oldIndex] ?? '');

        if (trim($code) === '') {
            return;
        }

        $this->executeCode($code, $context);
    }

    /**
     * Execute administrator-provided PHP code in a narrow compatibility scope.
     *
     * The original plugin exposed variables such as $args or $SubmissionId to
     * saved snippets. This method preserves those variables without introducing
     * deprecated Joomla globals. Exceptions and PHP errors are reported through
     * Joomla's application message queue instead of breaking the form flow.
     *
     * @param   string              $code     PHP code without opening/closing tags.
     * @param   array<string,mixed> $context  Variables extracted for legacy snippets.
     *
     * @return  void
     */
    private function executeCode(string $code, array $context): void
    {
        try {
            extract($context, EXTR_SKIP);
            unset($context);
            eval($code);
        } catch (Throwable $throwable) {
            $this->getApplication()->enqueueMessage(
                Text::sprintf('PLG_SYSTEM_RSFPADVANCEPHP_EXECUTION_ERROR', $throwable->getMessage()),
                'error'
            );
        }
    }

    /**
     * Load event settings for a form into memory.
     *
     * @param   int  $formId  RSForm!Pro form identifier.
     *
     * @return  void
     */
    private function setEvents(int $formId): void
    {
        if ($this->cachedFormId === $formId) {
            return;
        }

        $data = $this->repository->load($formId);
        $this->active = $data['events_active'];
        $this->code = $data['events_code'];
        $this->cachedFormId = $formId;
    }

    /**
     * Determine whether the current administrator may edit Advance PHP scripts.
     *
     * @return  bool
     */
    private function hasBackendAccess(): bool
    {
        $user = $this->getApplication()->getIdentity();

        if ($user === null) {
            return false;
        }

        $configured = (array) $this->params->get('access', []);
        $configured = array_values(array_filter(array_map('intval', $configured)));

        if ($configured === []) {
            return true;
        }

        $groups = array_map('intval', $user->getAuthorisedGroups());

        return array_intersect($configured, $groups) !== [];
    }

    /**
     * Register Joomla WebAssetManager assets for the administrator interface.
     *
     * @return  void
     */
    private function registerAssets(): void
    {
        $application = $this->getApplication();

        if (!method_exists($application, 'getDocument')) {
            return;
        }

        $document = $application->getDocument();
        $manager = $document->getWebAssetManager();
        $manager->useStyle('plg_system_rsfpadvancephp.admin')
            ->useScript('plg_system_rsfpadvancephp.admin');
    }

    /**
     * Decode a stored code snippet, accepting both legacy base64 and plain text.
     *
     * @param   string  $stored  Stored code value.
     *
     * @return  string  Executable PHP code without PHP tags.
     */
    private function decodeStoredCode(string $stored): string
    {
        if ($stored === '') {
            return '';
        }

        $decoded = base64_decode($stored, true);

        if ($decoded !== false && base64_encode($decoded) === $stored) {
            return $decoded;
        }

        return $stored;
    }

    /**
     * Return retained event descriptors keyed by legacy storage index.
     *
     * @return  array<int, AdvancePhpEvent>
     */
    private function getRetainedEvents(): array
    {
        $events = [];

        foreach (self::RETAINED_EVENTS as $oldIndex => [$suffix, $method, $description]) {
            $events[$oldIndex] = new AdvancePhpEvent($oldIndex, $suffix, $method, $description);
        }

        return $events;
    }
}
