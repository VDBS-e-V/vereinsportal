<?php

namespace App\Modules\Audit\Enums;

/** Ereignisse des Protokolls. Neue Ereignisse hier ergänzen: Wert, Bezeichnung, Gruppe und Gewicht an einer Stelle. */
enum AuditEvent: string
{
    case Login = 'auth.login';
    case LoginFailed = 'auth.login_failed';
    case TwoFactorFailed = 'auth.two_factor_failed';

    case PasswordChanged = 'account.password_changed';
    case PasswordSet = 'account.password_set';
    case EmailChanged = 'account.email_changed';
    case SessionsEnded = 'account.sessions_ended';
    case ProfileUpdated = 'account.profile_updated';
    case ProfileEdited = 'account.profile_edited';
    case ProfileVisibilityChanged = 'account.profile_visibility_changed';
    case ProfilePhotoChanged = 'account.profile_photo_changed';
    case NotificationsChanged = 'account.notifications_changed';
    case AccountDeletionRequested = 'account.deletion_requested';
    case AccountDeletionCancelled = 'account.deletion_cancelled';
    case AccountAnonymized = 'account.anonymized';

    case TwoFactorEnabled = 'two_factor.enabled';
    case TwoFactorDisabled = 'two_factor.disabled';
    case RecoveryCodesRenewed = 'two_factor.recovery_codes_renewed';

    case UserCreated = 'user.created';
    case UserInvited = 'user.invited';
    case UserDisabled = 'user.disabled';
    case UserEnabled = 'user.enabled';
    case UserTwoFactorReset = 'user.two_factor_reset';
    case UserPasswordLinkSent = 'user.password_link_sent';

    case RoleAssigned = 'role.assigned';
    case RoleEnded = 'role.ended';

    case PersonCreated = 'person.created';
    case PersonUpdated = 'person.updated';
    case PersonDeleted = 'person.deleted';

    case PartnerCreated = 'partner.created';
    case PartnerUpdated = 'partner.updated';
    case PartnerDeleted = 'partner.deleted';
    case PartnersImported = 'partner.imported';

    case ArticleCreated = 'article.created';
    case ArticleUpdated = 'article.updated';
    case ArticleDeleted = 'article.deleted';
    case PageUpdated = 'page.updated';

    case AccessRequestApproved = 'access_request.approved';
    case AccessRequestRejected = 'access_request.rejected';

    case OnboardingDocumentUploaded = 'onboarding.document_uploaded';
    case TeamOnboardingStarted = 'onboarding.team_started';
    case TeamOnboardingCompleted = 'onboarding.team_completed';

    case AuditExported = 'audit.exported';

    case SystemTaskRun = 'system.task_run';

    case MailingSent = 'mailing.sent';
    case MailSuppressionChanged = 'mailing.suppression_changed';
    case MailingWorkflow = 'mailing.workflow';

    case ContactsImported = 'contacts.imported';

    case OauthClientCreated = 'oauth.client_created';
    case OauthClientChanged = 'oauth.client_changed';
    case OauthClientDeleted = 'oauth.client_deleted';
    case OauthKeysRenewed = 'oauth.keys_renewed';
    case OauthConsentGiven = 'oauth.consent_given';
    case OauthConsentWithdrawn = 'oauth.consent_withdrawn';

    case FormStatusChanged = 'form.status_changed';
    case FormExported = 'form.exported';
    case FormSettingsChanged = 'form.settings_changed';
    case MailSegmentSaved = 'mailing.segment_saved';
    case MailSystemChanged = 'mailing.system_changed';
    case MailDesignChanged = 'mailing.design_changed';
    case BoardCalendarChanged = 'board.calendar_changed';
    case BoardMeetingSaved = 'board.meeting_saved';
    case BoardMeetingDeleted = 'board.meeting_deleted';
    case BoardMinutesApproved = 'board.minutes_approved';
    case BoardDocumentAdded = 'board.document_added';
    case BoardDocumentRemoved = 'board.document_removed';
    case BoardInvitationSent = 'board.invitation_sent';
    case BoardGuestsChanged = 'board.guests_changed';
    case BoardResolutionSaved = 'board.resolution_saved';
    case BoardResolutionDeleted = 'board.resolution_deleted';
    case TaskProjectSaved = 'task.project_saved';
    case TaskProjectDeleted = 'task.project_deleted';
    case TaskSaved = 'task.saved';
    case TaskDeleted = 'task.deleted';
    case ContactTopicSaved = 'contact_forms.topic_saved';
    case ContactTopicDeleted = 'contact_forms.topic_deleted';
    case ContactFormSaved = 'contact_forms.form_saved';
    case ContactFormDeleted = 'contact_forms.form_deleted';
    case FaqCategorySaved = 'faq.category_saved';
    case FaqCategoryDeleted = 'faq.category_deleted';
    case FaqEntrySaved = 'faq.entry_saved';
    case FaqEntryDeleted = 'faq.entry_deleted';
    case RoleLinksSaved = 'role_links.saved';
    case CoordinationSaved = 'coordinations.saved';
    case SeminarRequested = 'seminars.requested';
    case SeminarStatusChanged = 'seminars.status_changed';
    case QuoteApproved = 'seminars.quote_approved';
    case SeminarReleased = 'seminars.released';
    case SeminarPlanned = 'seminars.planned';
    case SeminarTeamConfirmed = 'seminars.team_confirmed';
    case SeminarCompleted = 'seminars.completed';
    case PaymentOrderCreated = 'seminars.payment_created';
    case PaymentOrderDecided = 'seminars.payment_decided';
    case QuoteSent = 'seminars.quote_sent';
    case QuotePricesSaved = 'seminars.quote_prices_saved';
    case CoordinationDeleted = 'coordinations.deleted';
    case NavigationSaved = 'navigation.saved';
    case TicketCreated = 'tickets.created';
    case TicketMessagePosted = 'tickets.message_posted';
    case TicketAssigned = 'tickets.assigned';
    case TicketStatusChanged = 'tickets.status_changed';

    public function label(): string
    {
        return match ($this) {
            self::Login => 'Angemeldet',
            self::LoginFailed => 'Anmeldung fehlgeschlagen',
            self::TwoFactorFailed => 'Zwei-Faktor-Code falsch',
            self::PasswordChanged => 'Passwort geändert',
            self::PasswordSet => 'Passwort festgelegt oder zurückgesetzt',
            self::EmailChanged => 'E-Mail-Adresse geändert',
            self::SessionsEnded => 'Andere Sitzungen beendet',
            self::ProfileUpdated => 'Stammdaten geändert',
            self::ProfileEdited => 'Profil bearbeitet',
            self::ProfileVisibilityChanged => 'Sichtbarkeit des Profils geändert',
            self::ProfilePhotoChanged => 'Profilfoto geändert',
            self::NotificationsChanged => 'Benachrichtigungen geändert',
            self::AccountDeletionRequested => 'Kontolöschung beantragt',
            self::AccountDeletionCancelled => 'Kontolöschung abgebrochen',
            self::AccountAnonymized => 'Konto gelöscht (anonymisiert)',
            self::TwoFactorEnabled => 'Zwei-Faktor eingerichtet',
            self::TwoFactorDisabled => 'Zwei-Faktor entfernt',
            self::RecoveryCodesRenewed => 'Wiederherstellungscodes erneuert',
            self::UserCreated => 'Konto angelegt',
            self::UserInvited => 'Einladung gesendet',
            self::UserDisabled => 'Konto gesperrt',
            self::UserEnabled => 'Konto entsperrt',
            self::UserTwoFactorReset => 'Zwei-Faktor zurückgesetzt',
            self::UserPasswordLinkSent => 'Passwort-Link gesendet',
            self::RoleAssigned => 'Rolle vergeben',
            self::RoleEnded => 'Rolle beendet',
            self::PersonCreated => 'Person angelegt',
            self::PersonUpdated => 'Person geändert',
            self::PersonDeleted => 'Person gelöscht',
            self::PartnerCreated => 'Schule angelegt',
            self::PartnerUpdated => 'Schule geändert',
            self::PartnerDeleted => 'Schule gelöscht',
            self::PartnersImported => 'Schulen importiert',
            self::ArticleCreated => 'Artikel angelegt',
            self::ArticleUpdated => 'Artikel geändert',
            self::ArticleDeleted => 'Artikel gelöscht',
            self::PageUpdated => 'Seite geändert',
            self::AccessRequestApproved => 'Zugangsantrag angenommen',
            self::AccessRequestRejected => 'Zugangsantrag abgelehnt',
            self::OnboardingDocumentUploaded => 'Onboarding-Dokument hochgeladen',
            self::TeamOnboardingStarted => 'Einarbeitung der Teamenden begonnen',
            self::TeamOnboardingCompleted => 'Einarbeitung der Teamenden abgeschlossen',
            self::AuditExported => 'Protokoll exportiert',
            self::SystemTaskRun => 'Systemaufgabe ausgeführt',
            self::MailingSent => 'Schreiben versendet',
            self::MailSuppressionChanged => 'Adresssperre geändert',
            self::MailingWorkflow => 'Schreiben: Freigabe und Planung',
            self::ContactsImported => 'Mailingliste importiert',
            self::OauthClientCreated => 'Anbindung angelegt',
            self::OauthClientChanged => 'Anbindung geändert',
            self::OauthClientDeleted => 'Anbindung gelöscht',
            self::OauthKeysRenewed => 'Anmeldeschlüssel erneuert',
            self::OauthConsentGiven => 'Zustimmung zur Datenweitergabe erteilt',
            self::OauthConsentWithdrawn => 'Zustimmung zur Datenweitergabe widerrufen',
            self::FormStatusChanged => 'Antrag bearbeitet',
            self::FormExported => 'Sammelliste der Anträge erstellt',
            self::FormSettingsChanged => 'Einstellungen der Anträge geändert',
            self::MailSegmentSaved => 'Verteiler geändert',
            self::MailSystemChanged => 'Systemmail geändert',
            self::MailDesignChanged => 'Mail-Design oder Mailbild geändert',
            self::BoardCalendarChanged => 'Kalender des Vorstands geändert',
            self::BoardMeetingSaved => 'Vorstandssitzung gespeichert',
            self::BoardMeetingDeleted => 'Vorstandssitzung gelöscht',
            self::BoardMinutesApproved => 'Sitzungsprotokoll genehmigt',
            self::BoardDocumentAdded => 'Anhang zur Vorstandssitzung abgelegt',
            self::BoardDocumentRemoved => 'Anhang zur Vorstandssitzung gelöscht',
            self::BoardInvitationSent => 'Einladung zur Vorstandssitzung gesendet',
            self::BoardGuestsChanged => 'Gäste einer Vorstandssitzung geändert',
            self::BoardResolutionSaved => 'Vorstandsbeschluss gespeichert',
            self::BoardResolutionDeleted => 'Vorstandsbeschluss gelöscht',
            self::TaskProjectSaved => 'Aufgabenprojekt gespeichert',
            self::TaskProjectDeleted => 'Aufgabenprojekt gelöscht',
            self::TaskSaved => 'Aufgabe gespeichert',
            self::TaskDeleted => 'Aufgabe gelöscht',
            self::ContactTopicSaved => 'Kontaktthema gespeichert',
            self::ContactTopicDeleted => 'Kontaktthema gelöscht',
            self::ContactFormSaved => 'Kontaktformular gespeichert',
            self::ContactFormDeleted => 'Kontaktformular gelöscht',
            self::FaqCategorySaved => 'FAQ-Kategorie gespeichert',
            self::FaqCategoryDeleted => 'FAQ-Kategorie gelöscht',
            self::FaqEntrySaved => 'FAQ-Frage gespeichert',
            self::FaqEntryDeleted => 'FAQ-Frage gelöscht',
            self::RoleLinksSaved => 'Schnellzugriffe gespeichert',
            self::CoordinationSaved => 'Koordination gespeichert',
            self::SeminarRequested => 'Seminar angefragt',
            self::SeminarStatusChanged => 'Seminar: Status geändert',
            self::QuoteApproved => 'Kostenvoranschlag intern freigegeben',
            self::SeminarReleased => 'Seminar freigegeben',
            self::SeminarPlanned => 'Seminar geplant',
            self::SeminarTeamConfirmed => 'Seminarteam bestätigt',
            self::SeminarCompleted => 'Seminar abgeschlossen',
            self::PaymentOrderCreated => 'Zahlungsauftrag angelegt',
            self::PaymentOrderDecided => 'Zahlungsauftrag entschieden',
            self::QuoteSent => 'Kostenvoranschlag gesendet',
            self::QuotePricesSaved => 'Preisliste für Kostenvoranschläge gespeichert',
            self::CoordinationDeleted => 'Koordination gelöscht',
            self::NavigationSaved => 'Navigation gespeichert',
            self::TicketCreated => 'Ticket angelegt',
            self::TicketMessagePosted => 'Ticket: Nachricht geschrieben',
            self::TicketAssigned => 'Ticket zugewiesen',
            self::TicketStatusChanged => 'Ticket: Status geändert',
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::Login, self::LoginFailed, self::TwoFactorFailed => 'auth',
            self::PasswordChanged, self::PasswordSet, self::EmailChanged, self::SessionsEnded, self::ProfileUpdated, self::ProfileEdited, self::ProfileVisibilityChanged, self::ProfilePhotoChanged, self::NotificationsChanged, self::AccountDeletionRequested, self::AccountDeletionCancelled, self::AccountAnonymized => 'account',
            self::TwoFactorEnabled, self::TwoFactorDisabled, self::RecoveryCodesRenewed => 'two_factor',
            self::UserCreated, self::UserInvited, self::UserDisabled, self::UserEnabled, self::UserTwoFactorReset, self::UserPasswordLinkSent => 'users',
            self::RoleAssigned, self::RoleEnded => 'roles',
            self::PersonCreated, self::PersonUpdated, self::PersonDeleted => 'people',
            self::PartnerCreated, self::PartnerUpdated, self::PartnerDeleted, self::PartnersImported => 'partners',
            self::ArticleCreated, self::ArticleUpdated, self::ArticleDeleted => 'articles',
            self::PageUpdated => 'pages',
            self::AccessRequestApproved, self::AccessRequestRejected => 'access_requests',
            self::OnboardingDocumentUploaded, self::TeamOnboardingStarted, self::TeamOnboardingCompleted => 'onboarding',
            self::AuditExported => 'audit',
            self::SystemTaskRun => 'system',
            self::MailingSent, self::MailSuppressionChanged, self::MailingWorkflow, self::MailSegmentSaved, self::MailSystemChanged, self::MailDesignChanged => 'mailings',
            self::ContactsImported => 'contacts',
            self::OauthClientCreated, self::OauthClientChanged, self::OauthClientDeleted, self::OauthKeysRenewed, self::OauthConsentGiven, self::OauthConsentWithdrawn => 'sso',
            self::FormStatusChanged, self::FormExported, self::FormSettingsChanged => 'forms',
            self::BoardCalendarChanged, self::BoardMeetingSaved, self::BoardMeetingDeleted, self::BoardMinutesApproved, self::BoardDocumentAdded, self::BoardDocumentRemoved, self::BoardInvitationSent, self::BoardGuestsChanged, self::BoardResolutionSaved, self::BoardResolutionDeleted => 'board',
            self::TaskProjectSaved, self::TaskProjectDeleted, self::TaskSaved, self::TaskDeleted => 'tasks',
            self::ContactTopicSaved, self::ContactTopicDeleted, self::ContactFormSaved, self::ContactFormDeleted => 'contact_forms',
            self::FaqCategorySaved, self::FaqCategoryDeleted, self::FaqEntrySaved, self::FaqEntryDeleted => 'faq',
            self::RoleLinksSaved => 'role_links',
            self::CoordinationSaved, self::CoordinationDeleted => 'coordinations',
            self::SeminarRequested, self::SeminarStatusChanged, self::QuoteApproved, self::QuoteSent, self::QuotePricesSaved, self::SeminarReleased, self::SeminarPlanned, self::SeminarTeamConfirmed, self::SeminarCompleted, self::PaymentOrderCreated, self::PaymentOrderDecided => 'seminars',
            self::NavigationSaved => 'navigation',
            self::TicketCreated, self::TicketMessagePosted, self::TicketAssigned, self::TicketStatusChanged => 'tickets',
        };
    }

    /** @return array<string, string> Gruppe => Bezeichnung */
    public static function categories(): array
    {
        return [
            'auth' => 'Anmeldung',
            'account' => 'Konto',
            'two_factor' => 'Zwei-Faktor',
            'users' => 'Nutzerverwaltung',
            'roles' => 'Rollen',
            'people' => 'Personen',
            'partners' => 'Schulen',
            'onboarding' => 'Onboarding',
            'access_requests' => 'Zugangsanträge',
            'articles' => 'Artikel',
            'pages' => 'Seiten',
            'audit' => 'Protokoll',
            'system' => 'System',
            'mailings' => 'Schreiben',
            'contacts' => 'Teamkontakte',
            'sso' => 'Anbindungen',
            'forms' => 'Anträge',
            'board' => 'Vorstand',
            'tasks' => 'Aufgaben',
            'contact_forms' => 'Kontaktformular',
            'faq' => 'FAQ',
            'role_links' => 'Schnellzugriffe',
            'coordinations' => 'Koordinationen',
            'seminars' => 'Seminare',
            'navigation' => 'Navigation',
            'tickets' => 'Tickets',
        ];
    }

    /** Farbton für Badges: Warnendes und Sperrendes fällt auf. */
    public function tone(): string
    {
        return match ($this) {
            self::LoginFailed, self::TwoFactorFailed, self::PersonDeleted, self::PartnerDeleted, self::AccountAnonymized, self::AccessRequestRejected, self::ArticleDeleted, self::OauthClientDeleted, self::BoardMeetingDeleted, self::BoardResolutionDeleted, self::TaskProjectDeleted, self::ContactTopicDeleted, self::ContactFormDeleted, self::FaqCategoryDeleted, self::FaqEntryDeleted, self::CoordinationDeleted => 'error',
            self::SystemTaskRun, self::UserDisabled, self::UserTwoFactorReset, self::TwoFactorDisabled, self::RoleEnded, self::AccountDeletionRequested => 'warning',
            self::Login, self::UserEnabled, self::TwoFactorEnabled, self::TeamOnboardingCompleted => 'success',
            default => 'neutral',
        };
    }

    /** @return list<self> */
    public static function inCategory(string $category): array
    {
        return array_values(array_filter(self::cases(), static fn (self $case): bool => $case->category() === $category));
    }
}
