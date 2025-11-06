<?php

namespace App\Models\Concerns;

/**
 * Dublin Core metadata constants and validation rules.
 * 
 * Use this in controllers to validate metadata key-value pairs.
 * All DC fields are stored as metadata records with keys like 'dc.title', 'dc.creator', etc.
 * 
 * Example usage in controller:
 * 
 *   use App\Models\Concerns\DublinCore;
 * 
 *   $request->validate([
 *       'metadata' => 'array',
 *       'metadata.*.key' => ['required', 'string', Rule::in(DublinCore::FIELDS)],
 *       'metadata.*.value' => 'required|string|max:5000',
 *   ]);
 */
class DublinCore
{
    /**
     * Core Dublin Core 15 elements.
     * Stored with 'dc.' prefix in metadata table.
     */
    public const FIELDS = [
        'dc.title',           // Name given to the resource
        'dc.creator',         // Entity responsible for making (e.g., interviewer, narrator)
        'dc.subject',         // Topic or keywords
        'dc.description',     // Account of the content
        'dc.publisher',       // Entity responsible for making available
        'dc.contributor',     // Entity responsible for making contributions
        'dc.date',            // Date associated (ISO 8601: YYYY-MM-DD)
        'dc.type',            // Nature or genre (oral history, interview, etc.)
        'dc.format',          // File format or physical medium
        'dc.identifier',      // Unambiguous reference (URI, accession number)
        'dc.source',          // Related resource from which this is derived
        'dc.language',        // Language of content (ISO 639-1: en, es, fr, etc.)
        'dc.relation',        // Related resource
        'dc.coverage',        // Spatial or temporal topic (e.g., "1960-1970", "Chicago, IL")
        'dc.rights',          // Rights statement or license (e.g., CC-BY, In Copyright)
    ];

    /**
     * Common oral history metadata extensions.
     * Not standard DC, but useful for oral history projects.
     */
    public const ORAL_HISTORY_FIELDS = [
        'oh.interviewer',     // Name of interviewer(s)
        'oh.interviewee',     // Name of narrator(s) / interviewee(s)
        'oh.location',        // Interview location
        'oh.duration',        // Duration in seconds or HH:MM:SS
        'oh.session',         // Session number if multi-part interview
        'oh.restrictions',    // Access or usage restrictions
        'oh.repository',      // Physical repository holding original
        'oh.accession',       // Accession or catalog number
    ];

    /**
     * All allowed metadata keys (DC + oral history extensions).
     */
    public const ALL_FIELDS = [
        ...self::FIELDS,
        ...self::ORAL_HISTORY_FIELDS,
    ];

    /**
     * Validation rules for common DC fields.
     * Use when you want to enforce formats.
     */
    public static function getValidationRules(string $key): array
    {
        return match ($key) {
            'dc.date' => ['nullable', 'date_format:Y-m-d'],
            'dc.language' => ['nullable', 'string', 'max:10'], // ISO 639-1 or 639-3
            'dc.format' => ['nullable', 'string', 'max:100'],
            'dc.rights' => ['nullable', 'string', 'max:500'],
            'oh.duration' => ['nullable', 'string', 'regex:/^\d{1,2}:\d{2}:\d{2}$|^\d+$/'], // HH:MM:SS or seconds
            default => ['nullable', 'string', 'max:5000'],
        };
    }

    /**
     * Human-readable labels for UI display.
     */
    public static function getLabel(string $key): string
    {
        return match ($key) {
            'dc.title' => 'Title',
            'dc.creator' => 'Creator',
            'dc.subject' => 'Subject / Keywords',
            'dc.description' => 'Description',
            'dc.publisher' => 'Publisher',
            'dc.contributor' => 'Contributor',
            'dc.date' => 'Date (YYYY-MM-DD)',
            'dc.type' => 'Type',
            'dc.format' => 'Format',
            'dc.identifier' => 'Identifier',
            'dc.source' => 'Source',
            'dc.language' => 'Language (ISO code)',
            'dc.relation' => 'Related Resource',
            'dc.coverage' => 'Coverage (Time/Place)',
            'dc.rights' => 'Rights Statement',
            'oh.interviewer' => 'Interviewer',
            'oh.interviewee' => 'Interviewee / Narrator',
            'oh.location' => 'Interview Location',
            'oh.duration' => 'Duration',
            'oh.session' => 'Session Number',
            'oh.restrictions' => 'Access Restrictions',
            'oh.repository' => 'Repository',
            'oh.accession' => 'Accession Number',
            default => ucfirst(str_replace(['dc.', 'oh.', '_'], ['', '', ' '], $key)),
        };
    }

    /**
     * Group fields for UI organization.
     */
    public static function getGroups(): array
    {
        return [
            'core' => [
                'label' => 'Core Metadata',
                'fields' => ['dc.title', 'dc.creator', 'dc.date', 'dc.description', 'dc.subject'],
            ],
            'administrative' => [
                'label' => 'Administrative',
                'fields' => ['dc.publisher', 'dc.contributor', 'dc.identifier', 'dc.rights'],
            ],
            'technical' => [
                'label' => 'Technical',
                'fields' => ['dc.format', 'dc.language', 'dc.type'],
            ],
            'relationships' => [
                'label' => 'Relationships',
                'fields' => ['dc.source', 'dc.relation', 'dc.coverage'],
            ],
            'oral_history' => [
                'label' => 'Oral History Specific',
                'fields' => self::ORAL_HISTORY_FIELDS,
            ],
        ];
    }
}
