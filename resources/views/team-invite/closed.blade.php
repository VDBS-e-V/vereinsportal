<x-layouts.app title="Bewerbung nicht möglich" width="narrow">
    <x-vdbs.layout.page-header title="Dieser Link gilt nicht mehr" lead="Der Bewerbungslink ist abgelaufen, gesperrt oder das Seminar sucht kein Team mehr." :breadcrumb="[['label' => 'Start', 'href' => route('home')], ['label' => 'Bewerbung']]" />
    <p>Wenn Sie sich weiter als Teamende:r engagieren möchten, melden Sie sich bei der Koordination des Verbands. Auf der <a href="{{ route('portal.contact') }}">Kontaktseite</a> finden Sie die Ansprechpersonen.</p>
</x-layouts.app>
