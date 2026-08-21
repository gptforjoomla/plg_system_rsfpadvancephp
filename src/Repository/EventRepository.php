<?php

declare(strict_types=1);

namespace VDM\Plugin\System\Rsfpadvancephp\Repository;

use Joomla\Database\DatabaseInterface;

/**
 * Reads and writes Advance PHP event configuration records.
 *
 * The repository deliberately keeps the legacy #__rsform_advancephp table and
 * JSON payload format so existing installations can be upgraded without losing
 * administrator-entered PHP snippets. Only retained event indexes are modified
 * on save; removed/native RSForm!Pro event indexes remain stored but are never
 * executed by this plugin.
 */
final class EventRepository
{
    private const TABLE = '#__rsform_advancephp';

    /**
     * @param   DatabaseInterface  $db  Joomla database service.
     */
    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    /**
     * Load event activation flags and code snippets for an RSForm form.
     *
     * @param   int  $formId  RSForm!Pro form identifier.
     *
     * @return  array{events_active: array<int,int>, events_code: array<int,string>} Legacy-compatible event data.
     */
    public function load(int $formId): array
    {
        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('events_active'),
                $this->db->quoteName('events_code'),
            ])
            ->from($this->db->quoteName(self::TABLE))
            ->where($this->db->quoteName('form_id') . ' = :formId')
            ->bind(':formId', $formId);

        $row = $this->db->setQuery($query)->loadAssoc();

        return [
            'events_active' => $this->normaliseActive($row['events_active'] ?? ''),
            'events_code'   => $this->normaliseCode($row['events_code'] ?? ''),
        ];
    }

    /**
     * Save retained event settings while preserving removed legacy slots.
     *
     * @param   int                $formId       RSForm!Pro form identifier.
     * @param   array<int,int>     $active       Updated active flags keyed by old legacy index.
     * @param   array<int,string>  $encodedCode  Base64-encoded code keyed by old legacy index.
     *
     * @return  void
     */
    public function saveRetained(int $formId, array $active, array $encodedCode): void
    {
        $existing = $this->load($formId);
        $eventsActive = $existing['events_active'];
        $eventsCode = $existing['events_code'];

        foreach ($active as $index => $state) {
            $eventsActive[(int) $index] = $state === 1 ? 1 : 0;

            if ($eventsActive[(int) $index] === 1) {
                $eventsCode[(int) $index] = $encodedCode[(int) $index] ?? '';
            } else {
                $eventsCode[(int) $index] = '';
            }
        }

        ksort($eventsActive);
        ksort($eventsCode);

        $eventsActiveJson = json_encode($eventsActive, JSON_THROW_ON_ERROR);
        $eventsCodeJson = json_encode($eventsCode, JSON_THROW_ON_ERROR);

        if ($this->exists($formId)) {
            $query = $this->db->getQuery(true)
                ->update($this->db->quoteName(self::TABLE))
                ->set($this->db->quoteName('events_active') . ' = :active')
                ->set($this->db->quoteName('events_code') . ' = :code')
                ->where($this->db->quoteName('form_id') . ' = :formId')
                ->bind(':active', $eventsActiveJson)
                ->bind(':code', $eventsCodeJson)
                ->bind(':formId', $formId);

            $this->db->setQuery($query)->execute();

            return;
        }

        $columns = ['form_id', 'events_active', 'events_code'];
        $query = $this->db->getQuery(true)
            ->insert($this->db->quoteName(self::TABLE))
            ->columns($this->db->quoteName($columns))
            ->values(':formId, :active, :code')
            ->bind(':formId', $formId)
            ->bind(':active', $eventsActiveJson)
            ->bind(':code', $eventsCodeJson);

        $this->db->setQuery($query)->execute();
    }

    /**
     * Resolve a form ID from an RSForm!Pro submission ID.
     *
     * @param   int|string  $submissionId  RSForm!Pro submission identifier.
     *
     * @return  int|null  Form ID when found, otherwise null.
     */
    public function getFormIdBySubmissionId(int|string $submissionId): ?int
    {
        $submissionId = (string) $submissionId;

        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('FormId'))
            ->from($this->db->quoteName('#__rsform_submissions'))
            ->where($this->db->quoteName('SubmissionId') . ' = :submissionId')
            ->bind(':submissionId', $submissionId);

        $formId = $this->db->setQuery($query)->loadResult();

        return $formId === null ? null : (int) $formId;
    }

    /**
     * Check whether a configuration row exists.
     *
     * @param   int  $formId  RSForm!Pro form identifier.
     *
     * @return  bool
     */
    private function exists(int $formId): bool
    {
        $query = $this->db->getQuery(true)
            ->select('1')
            ->from($this->db->quoteName(self::TABLE))
            ->where($this->db->quoteName('form_id') . ' = :formId')
            ->bind(':formId', $formId);

        return (bool) $this->db->setQuery($query)->loadResult();
    }

    /**
     * Decode legacy JSON active flags and provide all twelve legacy indexes.
     *
     * @param   string  $json  Stored JSON string.
     *
     * @return  array<int,int>
     */
    private function normaliseActive(string $json): array
    {
        $active = $this->decodeArray($json);

        for ($index = 0; $index <= 11; $index++) {
            $active[$index] = (int) ($active[$index] ?? 0);
        }

        ksort($active);

        return $active;
    }

    /**
     * Decode legacy JSON code payload and provide all twelve legacy indexes.
     *
     * @param   string  $json  Stored JSON string.
     *
     * @return  array<int,string>
     */
    private function normaliseCode(string $json): array
    {
        $code = $this->decodeArray($json);

        for ($index = 0; $index <= 11; $index++) {
            $code[$index] = (string) ($code[$index] ?? '');
        }

        ksort($code);

        return $code;
    }

    /**
     * Safely decode a JSON object/array with numeric keys.
     *
     * @param   string  $json  Stored JSON string.
     *
     * @return  array<int,mixed>
     */
    private function decodeArray(string $json): array
    {
        if (trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            return [];
        }

        $normalised = [];

        foreach ($decoded as $key => $value) {
            $normalised[(int) $key] = $value;
        }

        return $normalised;
    }
}
