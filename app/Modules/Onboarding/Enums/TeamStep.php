<?php

namespace App\Modules\Onboarding\Enums;

/**
 * Schritte der Einarbeitung der Teamenden. Masernschutz und Führungszeugnis prüft und trägt die Koordination ein (die Nachweise werden nicht
 * hochgeladen, nur das Datum und die Notiz stehen im Portal). Verpflichtungserklärung und Verhaltenskodex bestätigen die Teamenden selbst,
 * nachdem sie das jeweilige Dokument gelesen haben.
 */
enum TeamStep: string
{
    case Measles = 'measles';
    case Certificate = 'certificate';
    case Commitment = 'commitment';
    case CodeOfConduct = 'code_of_conduct';

    public function label(): string
    {
        return match ($this) {
            self::Measles => 'Nachweis Masernschutz',
            self::Certificate => 'Erweitertes Führungszeugnis',
            self::Commitment => 'Verpflichtungs- und Schweigepflichterklärung',
            self::CodeOfConduct => 'Datenschutz und Verhaltenskodex',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Measles => 'Bringen Sie den Nachweis zu einem Termin mit. Die Koordination prüft ihn und trägt es hier ein; der Nachweis selbst wird nicht im Portal gespeichert.',
            self::Certificate => 'Bringen Sie Ihr erweitertes Führungszeugnis zu einem Termin mit. Die Koordination nimmt Einsicht und trägt es hier ein; das Zeugnis wird nicht im Portal gespeichert.',
            self::Commitment => 'Lesen Sie die Erklärung und bestätigen Sie sie hier.',
            self::CodeOfConduct => 'Lesen Sie den Datenschutz- und Verhaltenskodex und bestätigen Sie ihn hier.',
        };
    }

    /** Können die Teamenden den Schritt selbst bestätigen? */
    public function bySelf(): bool
    {
        return $this === self::Commitment || $this === self::CodeOfConduct;
    }

    public function documentKind(): ?OnboardingDocumentKind
    {
        return match ($this) {
            self::Commitment => OnboardingDocumentKind::Commitment,
            self::CodeOfConduct => OnboardingDocumentKind::CodeOfConduct,
            default => null,
        };
    }

    /** Bezeichnung des Datums, das die Koordination einträgt. */
    public function dateLabel(): ?string
    {
        return match ($this) {
            self::Measles => 'Nachweis gesehen am',
            self::Certificate => 'Ausstellungsdatum des Führungszeugnisses',
            default => null,
        };
    }
}
