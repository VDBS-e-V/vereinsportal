<?php

use App\Modules\Access\AccessServiceProvider;
use App\Modules\AccessGovernance\AccessGovernanceServiceProvider;
use App\Modules\AccessRequests\AccessRequestsServiceProvider;
use App\Modules\Articles\ArticlesServiceProvider;
use App\Modules\Audit\AuditServiceProvider;
use App\Modules\Board\BoardServiceProvider;
use App\Modules\ContactForms\ContactFormsServiceProvider;
use App\Modules\Faq\FaqServiceProvider;
use App\Modules\NavigationControl\NavigationControlServiceProvider;
use App\Modules\QuickLinks\QuickLinksServiceProvider;
use App\Modules\Tickets\TicketsServiceProvider;
use App\Modules\Contacts\ContactsServiceProvider;
use App\Modules\Coordinations\CoordinationsServiceProvider;
use App\Modules\DesignLibrary\DesignLibraryServiceProvider;
use App\Modules\Forms\FormsServiceProvider;
use App\Modules\Mailings\MailingsServiceProvider;
use App\Modules\Membership\MembershipServiceProvider;
use App\Modules\Onboarding\OnboardingServiceProvider;
use App\Modules\Pages\PagesServiceProvider;
use App\Modules\Partners\PartnersServiceProvider;
use App\Modules\People\PeopleServiceProvider;
use App\Modules\Quotes\QuotesServiceProvider;
use App\Modules\Seminars\SeminarsServiceProvider;
use App\Modules\Setup\SetupServiceProvider;
use App\Modules\Sso\SsoServiceProvider;
use App\Modules\System\SystemServiceProvider;
use App\Modules\Tasks\TasksServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\VoltServiceProvider;

/*
 * IdentityServiceProvider, AuditServiceProvider und
 * CommunicationServiceProvider sind mit ihren Modulen entfernt worden
 * und kommen zurueck, sobald Access/Compliance-Log/Messaging neu
 * aufgebaut sind.
 */
return [
    AppServiceProvider::class,
    VoltServiceProvider::class,
    AccessServiceProvider::class,
    AccessGovernanceServiceProvider::class,
    AuditServiceProvider::class,
    PartnersServiceProvider::class,
    CoordinationsServiceProvider::class,
    SeminarsServiceProvider::class,
    QuotesServiceProvider::class,
    FormsServiceProvider::class,
    BoardServiceProvider::class,
    TasksServiceProvider::class,
    ContactFormsServiceProvider::class,
    FaqServiceProvider::class,
    QuickLinksServiceProvider::class,
    NavigationControlServiceProvider::class,
    TicketsServiceProvider::class,
    PeopleServiceProvider::class,
    MembershipServiceProvider::class,
    OnboardingServiceProvider::class,
    AccessRequestsServiceProvider::class,
    ArticlesServiceProvider::class,
    SystemServiceProvider::class,
    SetupServiceProvider::class,
    MailingsServiceProvider::class,
    ContactsServiceProvider::class,
    PagesServiceProvider::class,
    SsoServiceProvider::class,
    DesignLibraryServiceProvider::class,
];
