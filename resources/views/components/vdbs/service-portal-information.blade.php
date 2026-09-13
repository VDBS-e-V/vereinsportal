@props([
    'showIntro' => true,
])

<x-vdbs.templates.service-content>
    @if ($showIntro)
        <x-slot:intro>
            <section class="service-information__intro" aria-labelledby="service-information-heading">
                <h1 id="service-information-heading">Unser Service-Portal</h1>

                <div class="service-information__block">
                    <h2>Smart. Vernetzt. Engagiert.</h2>
                    <p>
                        Wir freuen uns, Ihnen unser brandneues Service-Portal vorstellen zu dürfen – eine zentrale
                        Anlaufstelle, die speziell entwickelt wurde, um die Zusammenarbeit zu optimieren und den
                        Informationsaustausch für alle Beteiligten zu vereinfachen. Egal, ob Sie Teamer:in,
                        Vereinsmitglied, Teil der Verwaltung oder eine Schule (Schüler:innen, Lehrkräfte etc.) sind,
                        dieses Portal bietet Ihnen maßgeschneiderte Funktionen und Zugang zu relevanten Ressourcen.
                    </p>
                </div>

                <div class="service-information__block">
                    <h2>Was erwartet Sie im Portal?</h2>
                    <p>
                        Unser Verein engagiert sich intensiv in den Bereichen SV-Arbeit, Medienkompetenz,
                        Schulbibliothek und Demokratiebildung. Das Service-Portal bündelt alle wichtigen Informationen
                        und Tools rund um diese Themen und darüber hinaus:
                    </p>

                    <div class="service-information__audiences">
                        <p>
                            <strong>Für Teamer:innen:</strong><br>
                            Hier finden Sie alle relevanten Materialien für Ihre Workshops – von Präsentationen und
                            Arbeitsblättern bis hin zu organisatorischen Hinweisen und Best Practices. Eine vereinfachte
                            Workshop-Planung und der Austausch mit anderen Teamer:innen werden ebenfalls ermöglicht.
                        </p>
                        <p>
                            <strong>Für Vereinsmitglieder:</strong><br>
                            Bleiben Sie stets auf dem Laufenden über aktuelle Projekte, Termine und interne Informationen.
                            Melden Sie sich für Veranstaltungen an, tauschen Sie sich mit anderen Mitgliedern aus und
                            bringen Sie sich aktiv ins Vereinsleben ein.
                        </p>
                        <p>
                            <strong>Für die Verwaltung:</strong><br>
                            Das Portal bietet eine effiziente Plattform für die interne Organisation und Kommunikation.
                            Verwalten Sie Mitgliedsdaten, planen Sie Ressourcen und behalten Sie den Überblick über alle
                            vereinsrelevanten Prozesse.
                        </p>
                        <p>
                            <strong>Für Schulen (Schüler:innen, Lehrkräfte etc.):</strong><br>
                            Schulen können sich über unser Workshop-Angebot informieren, Anfragen stellen und relevante
                            Materialien für den Unterricht oder die SV-Arbeit herunterladen. Das Portal erleichtert die
                            Kommunikation und Koordination zwischen den Schulen und dem Verein erheblich.
                        </p>
                    </div>
                </div>

                <div class="service-information__block">
                    <h2>Ihre Vorteile auf einen Blick</h2>
                    <p>
                        Zentraler Informationszugang • Effiziente Kommunikation • Ressourcenmanagement • Transparenz •
                        Vereinfachte Prozesse
                    </p>
                </div>
            </section>
        </x-slot:intro>
    @endif

    <x-slot:faq>
        <section id="faq" class="service-faq" aria-labelledby="service-faq-heading">
            <h2 id="service-faq-heading">Häufig gestellte Fragen</h2>
            <p class="service-faq__intro">
                In diesem Abschnitt versuchen wir die häufigsten Fragen zum VDBS Service-Portal zu beantworten.
                Sollten Sie dennoch weitere Fragen haben oder Ergänzungen zu diesen Fragen haben, können Sie uns
                jederzeit unter <a href="mailto:kontakt@portal.vdb.schule">kontakt@portal.vdb.schule</a> kontaktieren.
            </p>

            <div class="service-faq__items">
                <details>
                    <summary>Was ist das Service-Portal und wofür wurde es entwickelt?</summary>
                    <div class="service-faq__answer">
                        <p>
                            Das Service-Portal ist Ihre zentrale Online-Anlaufstelle für alle Belange rund um unseren
                            Verein. Es wurde entwickelt, um die Kommunikation, den Informationsaustausch und die
                            Organisation für <strong>Teamer:innen, Vereinsmitglieder, die Verwaltung und Schulen</strong>
                            zu vereinfachen und zu optimieren.
                        </p>
                    </div>
                </details>

                <details>
                    <summary>Welche Themenbereiche deckt der Verein ab, die im Portal relevant sind?</summary>
                    <div class="service-faq__answer">
                        <p>
                            Im Portal stehen insbesondere die Bereiche SV-Arbeit, Medienkompetenz, Schulbibliothek und
                            Demokratiebildung im Mittelpunkt. Ergänzend finden Sie dort vereinsinterne Informationen,
                            Materialien, Termine und Werkzeuge für die Zusammenarbeit.
                        </p>
                    </div>
                </details>

                <details>
                    <summary>Wer kann das Service-Portal nutzen?</summary>
                    <div class="service-faq__answer">
                        <p>Das Portal richtet sich an alle, die mit unserem Verein in Verbindung stehen:</p>
                        <ul>
                            <li><strong>Teamer:innen:</strong> Für Workshop-Materialien, Planung und Austausch.</li>
                            <li><strong>Vereinsmitglieder:</strong> Für aktuelle Infos, Termine und zur aktiven Teilnahme.</li>
                            <li><strong>Verwaltung:</strong> Für interne Organisation, Mitglieder- und Ressourcenmanagement.</li>
                            <li><strong>Schulen (Schüler:innen, Lehrkräfte etc.):</strong> Für Infos zu Workshops, Anfragen und Lehrmaterialien.</li>
                        </ul>
                    </div>
                </details>

                <details>
                    <summary>Welche Vorteile bietet mir das Portal?</summary>
                    <div class="service-faq__answer">
                        <p>Das Portal bietet Ihnen zahlreiche Vorteile, darunter:</p>
                        <ul>
                            <li><strong>Zentraler Informationszugang:</strong> Alle wichtigen Dokumente und Ansprechpartner an einem Ort.</li>
                            <li><strong>Effiziente Kommunikation:</strong> Vereinfachter Austausch zwischen allen Beteiligten.</li>
                            <li><strong>Umfassende Ressourcen:</strong> Leichter Zugriff auf Workshop-Materialien, Leitfäden und Best Practices.</li>
                            <li><strong>Transparenz:</strong> Immer auf dem neuesten Stand über Vereinsaktivitäten und Projekte.</li>
                            <li><strong>Vereinfachte Prozesse:</strong> Von der Workshop-Anmeldung bis zur internen Verwaltung – alles digital und unkompliziert.</li>
                        </ul>
                    </div>
                </details>

                <details>
                    <summary>Wie erhalte ich Zugang zum Service-Portal?</summary>
                    <div class="service-faq__answer">
                        <p>
                            Informationen zum Zugang (z.B. Registrierung, Login-Daten) erhalten Sie von der
                            Vereinsverwaltung oder direkt über die Anmeldeoptionen auf der Startseite des Portals. Bei
                            Fragen helfen wir Ihnen gerne weiter.
                        </p>
                    </div>
                </details>

                <details>
                    <summary>Wo finde ich Hilfe, wenn ich Probleme mit dem Portal habe?</summary>
                    <div class="service-faq__answer">
                        <p>
                            Sollten Sie technische Schwierigkeiten haben oder Fragen zur Nutzung des Portals aufkommen,
                            wenden Sie sich bitte an den Support, dessen Kontaktdaten Sie im Portal selbst oder auf
                            unserer Vereins-Website finden.
                        </p>
                    </div>
                </details>
            </div>
        </section>
    </x-slot:faq>

    <x-slot:callout>
        <section class="service-promo service-promo--access service-information__access" aria-labelledby="service-information-access-heading">
            <div class="service-promo__copy">
                <h2 id="service-information-access-heading">Zugang zum Portal</h2>
                <p>
                    Hier finden Sie alle Informationen, die Sie benötigen, um auf das VDBS Serviceportal zuzugreifen.
                    Wenn Sie bereits ein Konto haben, können Sie sich hier anmelden. Wenn Sie noch kein Konto haben,
                    können Sie sich hier registrieren.
                </p>

                <div class="service-promo__actions">
                    <a class="btn" href="{{ route('portal.access') }}">
                        <x-vdbs.icon name="arrow-right" size="18" />
                        <span>Zugang zum Portal</span>
                    </a>
                    <a class="service-promo__help" href="{{ route('portal.contact') }}">
                        <x-vdbs.icon name="help" size="18" />
                        <span>Hilfe kontaktieren</span>
                    </a>
                </div>
            </div>

            <img
                class="service-promo__image"
                src="{{ asset('images/portal/portal-access.jpg') }}"
                alt="Steinerner Torbogen"
            >
        </section>
    </x-slot:callout>
</x-vdbs.templates.service-content>
