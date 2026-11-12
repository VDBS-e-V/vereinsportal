<?php

namespace App\Modules\Onboarding\Enums;

/** Dokumente des Onboardings. Satzung und Willkommensbrief gehören zu den Mitgliedern, die Erklärungen zu den Teamenden. */
enum OnboardingDocumentKind: string
{
    case Statute = 'statute';
    case WelcomeLetter = 'welcome_letter';
    case Commitment = 'commitment';
    case CodeOfConduct = 'code_of_conduct';

    public function label(): string
    {
        return match ($this) {
            self::Statute => 'Satzung',
            self::WelcomeLetter => 'Willkommensbrief',
            self::Commitment => 'Verpflichtungs- und Schweigepflichterklärung',
            self::CodeOfConduct => 'Datenschutz und Verhaltenskodex',
        };
    }

    /** @return list<self> */
    public static function forMembers(): array
    {
        return [self::Statute, self::WelcomeLetter];
    }

    /** @return list<self> */
    public static function forTeam(): array
    {
        return [self::Commitment, self::CodeOfConduct];
    }
}
