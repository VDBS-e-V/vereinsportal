@extends('design.layout')

@section('title', 'Komponenten')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Designsystem v2</p>
            <h1 class="page-title__title">Komponenten-Werkstatt</h1>
            <p class="page-title__lead">
                Reine HTML-Struktur der neuen Komponenten, bewusst ohne Gestaltung.
                Die Klassen sind in <code>resources/css/vdbs/v2/*.css</code> als leere
                Selektoren angelegt; das Styling wird dort von Hand geschrieben.
                Klassennamen und Markup entsprechen den Mustern des WordPress-Themes,
                damit dasselbe CSS dort wiederverwendet werden kann.
            </p>
        </header>

        <nav class="section stack stack--sm" aria-label="Komponenten">
            <h2>Inhalt</h2>
            <ul class="cluster">
                <li><a href="#hero">Hero „Bühne“</a></li>
                <li><a href="#banner">Banner mit Karte</a></li>
                <li><a href="#pagehero">Page Hero</a></li>
                <li><a href="#intro">Intro-Teaser</a></li>
                <li><a href="#teaser">Teaser mit Bild</a></li>
                <li><a href="#versatz">Bild-Text-Versatz</a></li>
                <li><a href="#bildtext">Bild-Text</a></li>
                <li><a href="#kacheln">Kachelraster (auf Bühne)</a></li>
                <li><a href="#schwerpunkte">Schwerpunkte (Kacheln mit Text)</a></li>
                <li><a href="#karten">Karten-Reihe</a></li>
                <li><a href="#kennzahlen">Kennzahlen-Kacheln</a></li>
                <li><a href="#facts">Facts</a></li>
                <li><a href="#zahlen">Zahlenleiste</a></li>
                <li><a href="#news">News-Raster</a></li>
                <li><a href="#zitate">Zitate</a></li>
                <li><a href="#textblock">Textblock zentriert</a></li>
                <li><a href="#flaeche">Textfläche</a></li>
                <li><a href="#aufruf">Aufruf</a></li>
                <li><a href="#hinweis">Hinweisbox</a></li>
                <li><a href="#bildpaar">Bildpaar</a></li>
                <li><a href="#abschnitte">Textabschnitte</a></li>
                <li><a href="#infospalten">Infospalten</a></li>
                <li><a href="#personen">Ansprechpartner:innen</a></li>
                <li><a href="#vorstand">Vorstand</a></li>
                <li><a href="#termine">Terminliste</a></li>
                <li><a href="#suche">Veranstaltungssuche</a></li>
                <li><a href="#angebot">Angebot-Bereich</a></li>
                <li><a href="#kontakt">Kontaktseite</a></li>
            </ul>
        </nav>

        <section class="section stack">
            <h2 id="regeln">Konventionen</h2>
            <ul>
                <li><strong>Namen:</strong> <code>vdbs-block</code>, <code>vdbs-block__element</code>, <code>vdbs-block--variante</code> (BEM, deutsch wie im Theme).</li>
                <li><strong>Vollbreite Bänder</strong> (Wurzel mit <code>__inner</code>): Hintergrund auf der Wurzel, Breite und Innenabstand auf <code>__inner</code>.</li>
                <li><strong>Buttons</strong> nutzen die vorhandenen Klassen <code>btn</code> und <code>btn--outline</code>; <code>vdbs-aktion</code> steuert nur die Position (halb über der Kante, <code>--flach</code> ohne).</li>
                <li><strong>Hilfsklasse</strong> <code>is-center</code>: zentrierter Text.</li>
                <li><strong>Ganze Fläche klickbar:</strong> bei Kachel, Karte, Terminzeile und News-Eintrag genügt ein Link (der Button); per <code>::before</code> auf <code>.btn</code> mit <code>position: absolute; inset: 0</code> über die Fläche ziehen.</li>
                <li><strong>Platzhalterbild:</strong> <code>public/images/design/platzhalter.svg</code> (3:2).</li>
            </ul>
        </section>

        <section class="section stack" id="gemeinsam">
            <h2>Gemeinsame Bausteine</h2>
            <p class="container--narrow"><code>shared.css</code>: Aktions-Position, Etikett und Meta-Zeile.</p>
            <div class="vdbs-demo">
                <div class="vdbs-aktion"><a class="btn" href="#">Mehr dazu</a></div>
                <div class="vdbs-aktion vdbs-aktion--flach"><a class="btn" href="#">Hier mehr erfahren</a></div>
                <div class="vdbs-buttons"><a class="btn btn--outline" href="#">Outline-Button</a></div>
                <p class="vdbs-tag">Veranstaltung</p>
                <p class="vdbs-meta">Donnerstag, 05.11.2026 | <strong>Berlin</strong></p>
            </div>
        </section>

        <section class="section stack" id="hero">
            <h2>Hero „Bühne“</h2>
            <p class="container--narrow">Vollbild-Foto mit Lila-Karte, Claim und Button. Variante --welle fügt eine Wellenkante unten an. <code>v2/hero.css</code></p>

            {{-- hero-start · Breite: full --}}
            <div class="vdbs-demo vdbs-demo--full">
                <section class="vdbs-banner vdbs-banner--buehne vdbs-banner--welle">
    
                    <figure class="vdbs-banner__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy"></figure>
    

    
                    <div class="vdbs-banner__inner">
        
                        <div class="vdbs-banner__karte">
            
                            <h2 class="vdbs-banner__titel">Weil Schule uns alle angeht!</h2>
            
            
                            <div class="vdbs-buttons"><a class="btn" href="#">Zu unserem Angebot</a></div>
            
                        </div>
        
                    </div>
    
                </section>
            </div>
        </section>

        <section class="section stack" id="banner">
            <h2>Banner mit Karte</h2>
            <p class="container--narrow">Vollbild-Foto mit heller Karte (Titel, Text, Outline-Button). <code>v2/hero.css</code></p>

            {{-- banner-mit-karte · Breite: full --}}
            <div class="vdbs-demo vdbs-demo--full">
                <section class="vdbs-banner vdbs-banner--welle">
    
                    <figure class="vdbs-banner__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy"></figure>
    

    
                    <div class="vdbs-banner__inner">
        
                        <div class="vdbs-banner__karte">
            
                            <h2 class="vdbs-banner__titel">Architektur</h2>
            
            
                            <p>Kurzer Text, der das Thema beschreibt. Drei bis vier Zeilen genügen, damit die Karte kompakt bleibt.</p>
            
            
                            <div class="vdbs-buttons"><a class="btn btn--outline" href="#">Mehr zur Architektur</a></div>
            
                        </div>
        
                    </div>
    
                </section>
            </div>
        </section>

        <section class="section stack" id="pagehero">
            <h2>Page Hero</h2>
            <p class="container--narrow">Band mit zentriertem Seitentitel und Lead. <code>v2/hero.css</code></p>

            {{-- page-hero · Breite: full --}}
            <div class="vdbs-demo vdbs-demo--full">
                <section class="vdbs-pagehero">
                <div class="vdbs-pagehero__inner">
    
                    <h1 class="vdbs-pagehero__titel">Wer sind wir?</h1>
    
    
                    <p class="vdbs-pagehero__lead">Kurzer Lead-Satz, der die Seite einordnet.</p>
    

                </div>
                </section>
            </div>
        </section>

        <section class="section stack" id="intro">
            <h2>Intro-Teaser</h2>
            <p class="container--narrow">Titel, Lead und Button links, Bild rechts. Drei Ausprägungen: mit Lead, schlank (nur Titel + Button), Seitenkopf (Titel + Lead, ohne Button). <code>v2/hero.css</code></p>

            {{-- intro-teaser · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-intro">
    
                    <div class="vdbs-intro__text">
        
                        <h2 class="vdbs-intro__titel">Über den Verein</h2>
        
        
                        <p class="vdbs-intro__lead">Lernen Sie uns kennen!</p>
        
        
                        <div class="vdbs-aktion vdbs-aktion--flach"><a class="btn" href="#">Mehr erfahren</a></div>
        
                    </div>
    

    
                    <figure class="vdbs-intro__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy"><figcaption>Bild: Quelle/Urheber</figcaption></figure>
    
                </section>
            </div>

            {{-- intro-teaser-schlank · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-intro">
    
                    <div class="vdbs-intro__text">
        
                        <h2 class="vdbs-intro__titel">Unsere Arbeit</h2>
        
        
                        <div class="vdbs-aktion vdbs-aktion--flach"><a class="btn" href="#">Hier mehr erfahren</a></div>
        
                    </div>
    

    
                    <figure class="vdbs-intro__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy"><figcaption>Bild: Quelle/Urheber</figcaption></figure>
    
                </section>
            </div>

            {{-- seitenkopf · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-intro">
    
                    <div class="vdbs-intro__text">
        
                        <h2 class="vdbs-intro__titel">Veranstaltungen</h2>
        
        
                        <p class="vdbs-intro__lead">Alle anstehenden Veranstaltungen im Überblick</p>
        
                    </div>
    

    
                    <figure class="vdbs-intro__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy"></figure>
    
                </section>
            </div>
        </section>

        <section class="section stack" id="teaser">
            <h2>Teaser mit Bild</h2>
            <p class="container--narrow">Bild ragt über eine Fläche hinaus. Varianten: links, rechts, hervorgehoben (dunkle Fläche). <code>v2/teaser.css</code></p>

            {{-- teaser-bild-links · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-bildteaser">

    
                    <figure class="vdbs-bildteaser__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy"><figcaption>Bild: Quelle/Urheber</figcaption></figure>
    

    
                    <div class="vdbs-bildteaser__inhalt">
        
                        <h2 class="vdbs-bildteaser__titel">Titel des Teasers</h2>
        

        
                        <p>Kurzer Text, der erklärt, worum es geht, und zum Weiterlesen einlädt. Zwei bis vier Zeilen genügen.</p>
        

        
                        <div class="vdbs-aktion"><a class="btn" href="#">Mehr dazu</a></div>
        
                    </div>
    

                </section>
            </div>

            {{-- teaser-bild-rechts · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-bildteaser vdbs-bildteaser--rechts">

    
                    <figure class="vdbs-bildteaser__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy"><figcaption>Bild: Quelle/Urheber</figcaption></figure>
    

    
                    <div class="vdbs-bildteaser__inhalt">
        
                        <h2 class="vdbs-bildteaser__titel">Titel des Teasers</h2>
        

        
                        <p>Kurzer Text, der erklärt, worum es geht, und zum Weiterlesen einlädt. Zwei bis vier Zeilen genügen.</p>
        

        
                        <div class="vdbs-aktion"><a class="btn" href="#">Mehr dazu</a></div>
        
                    </div>
    

                </section>
            </div>

            {{-- teaser-hervorgehoben · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-bildteaser vdbs-bildteaser--hervorgehoben">
    
                    <figure class="vdbs-bildteaser__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy"><figcaption>Bild: Quelle/Urheber</figcaption></figure>
    

    
                    <div class="vdbs-bildteaser__inhalt">
        
                        <p class="vdbs-tag">Veranstaltung</p>
        
        
                        <p class="vdbs-meta">Donnerstag, 05.11.2026 | <strong>Berlin</strong></p>
        
        
                        <h2 class="vdbs-bildteaser__titel">Fachtagung: Titel der Veranstaltung</h2>
        
        
                        <p>Wie gelingt die politische und gewerkschaftliche Teilhabe Zugewanderter? Expert:innen diskutieren grenzüberschreitende Mobilität und fragen, was Deutschland und die Türkei aus ihren Erfahrungen lernen können.</p>
        
        
                        <div class="vdbs-aktion"><a class="btn" href="#">Zur Anmeldung</a></div>
        
                    </div>
    
                </section>
            </div>
        </section>

        <section class="section stack" id="versatz">
            <h2>Bild-Text-Versatz</h2>
            <p class="container--narrow">Große Bildfläche, Karte überlappt. Varianten: Karte links (hell), Karte rechts (grün). <code>v2/teaser.css</code></p>

            {{-- bild-text-versatz-links · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-versatz">
    
                    <figure class="vdbs-versatz__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy"></figure>
    

    
                    <div class="vdbs-versatz__karte">
        
                        <h2 class="vdbs-versatz__titel">Was können Sie bei uns tun?</h2>
        
        
                        <p>Kurzer Text, der erklärt, worum es geht und was die Besucherinnen und Besucher erwartet. Vier bis sechs Zeilen genügen.</p>
        
        
                        <div class="vdbs-buttons"><a class="btn btn--outline" href="#">Mehr zu unseren Angeboten</a></div>
        
                    </div>
    
                </section>
            </div>

            {{-- bild-text-versatz-rechts · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-versatz vdbs-versatz--rechts vdbs-versatz--gruen">
    
                    <figure class="vdbs-versatz__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy"></figure>
    

    
                    <div class="vdbs-versatz__karte">
        
                        <h2 class="vdbs-versatz__titel">Räume und Arbeitsplätze</h2>
        
        
                        <p>Kurzer Text, der erklärt, worum es geht und was die Besucherinnen und Besucher erwartet. Vier bis sechs Zeilen genügen.</p>
        
        
                        <div class="vdbs-buttons"><a class="btn btn--outline" href="#">Mehr zu unseren Räumen</a></div>
        
                    </div>
    
                </section>
            </div>
        </section>

        <section class="section stack" id="bildtext">
            <h2>Bild-Text</h2>
            <p class="container--narrow">Bild links, rechts Überschrift, Text und Buttons. Variante --rechts spiegelt. <code>v2/teaser.css</code></p>

            {{-- bildtext · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-bildtext" aria-labelledby="bildtext-titel">
                    <figure class="vdbs-bildtext__bild">
                        <img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy">
                        <figcaption>Bild: Quelle/Urheber</figcaption>
                    </figure>

                    <div class="vdbs-bildtext__inhalt">
                        <h2 class="vdbs-bildtext__titel" id="bildtext-titel">Überschrift</h2>
                        <p>
                            Kurzer Text, der erklärt, worum es geht. Zwei bis vier Zeilen genügen.
                        </p>
                        <div class="vdbs-buttons">
                            <a class="btn" href="#">Hauptaktion</a>
                            <a class="btn btn--outline" href="#">Zweite Aktion</a>
                        </div>
                    </div>
                </section>
            </div>

            {{-- bildtext-rechts · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-bildtext vdbs-bildtext--rechts" aria-labelledby="bildtext-titel-rechts">
                    <figure class="vdbs-bildtext__bild">
                        <img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy">
                        <figcaption>Bild: Quelle/Urheber</figcaption>
                    </figure>

                    <div class="vdbs-bildtext__inhalt">
                        <h2 class="vdbs-bildtext__titel" id="bildtext-titel-rechts">Überschrift (Bild rechts)</h2>
                        <p>
                            Kurzer Text, der erklärt, worum es geht. Zwei bis vier Zeilen genügen.
                        </p>
                        <div class="vdbs-buttons">
                            <a class="btn" href="#">Hauptaktion</a>
                            <a class="btn btn--outline" href="#">Zweite Aktion</a>
                        </div>
                    </div>
                </section>
            </div>
        </section>

        <section class="section stack" id="kacheln">
            <h2>Kachelraster (auf Bühne)</h2>
            <p class="container--narrow">Lila Fläche mit Überschrift und vier Kacheln, Button halb über der Kante. <code>v2/kacheln.css</code></p>

            {{-- kachelraster · Breite: full --}}
            <div class="vdbs-demo vdbs-demo--full">
                <section class="vdbs-kacheln">
                <div class="vdbs-kacheln__inner">

    
                    <h2 class="vdbs-kacheln__titel">Mehr erfahren</h2>
    

    
                    <div class="vdbs-kachelraster">

        
                        <div class="vdbs-kachel">
            
                            <h3 class="vdbs-kachel__titel">Wer wir sind</h3>
            

            
                            <div class="vdbs-aktion"><a class="btn" href="#">Mehr über uns erfahren</a></div>
            
                        </div>
        

        
                        <div class="vdbs-kachel">
            
                            <h3 class="vdbs-kachel__titel">Was wir tun</h3>
            

            
                            <div class="vdbs-aktion"><a class="btn" href="#">Unsere Arbeit kennenlernen</a></div>
            
                        </div>
        

        
                        <div class="vdbs-kachel">
            
                            <h3 class="vdbs-kachel__titel">Der Verein im Wandel der Zeit</h3>
            

            
                            <div class="vdbs-aktion"><a class="btn" href="#">Unsere Geschichte nachlesen</a></div>
            
                        </div>
        

        
                        <div class="vdbs-kachel">
            
                            <h3 class="vdbs-kachel__titel">Jubiläum</h3>
            

            
                            <div class="vdbs-aktion"><a class="btn" href="#">Zum Jubiläum</a></div>
            
                        </div>
        

                    </div>
    


                </div>
                </section>
            </div>
        </section>

        <section class="section stack" id="schwerpunkte">
            <h2>Schwerpunkte (Kacheln mit Text)</h2>
            <p class="container--narrow">Drei Kacheln mit Titel, Text und Button. <code>v2/kacheln.css</code></p>

            {{-- schwerpunkte · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-bereich">
    
                    <h2 class="vdbs-bereich__titel">Schwerpunkte unserer Arbeit</h2>
    

    
                    <div class="vdbs-kachelraster vdbs-kachelraster--3">
        
                        <div class="vdbs-kachel vdbs-kachel--text">
            
                            <h3 class="vdbs-kachel__titel">Erster Schwerpunkt</h3>
            
            
                            <p>Kurzer Text, der das Thema beschreibt und zum Weiterlesen einlädt. Drei bis sechs Zeilen genügen.</p>
            
            
                            <div class="vdbs-aktion"><a class="btn" href="#">Mehr dazu</a></div>
            
                        </div>
        

        
                        <div class="vdbs-kachel vdbs-kachel--text">
            
                            <h3 class="vdbs-kachel__titel">Zweiter Schwerpunkt</h3>
            
            
                            <p>Kurzer Text, der das Thema beschreibt und zum Weiterlesen einlädt. Drei bis sechs Zeilen genügen.</p>
            
            
                            <div class="vdbs-aktion"><a class="btn" href="#">Mehr dazu</a></div>
            
                        </div>
        

        
                        <div class="vdbs-kachel vdbs-kachel--text">
            
                            <h3 class="vdbs-kachel__titel">Dritter Schwerpunkt</h3>
            
            
                            <p>Kurzer Text, der das Thema beschreibt und zum Weiterlesen einlädt. Drei bis sechs Zeilen genügen.</p>
            
            
                            <div class="vdbs-aktion"><a class="btn" href="#">Mehr dazu</a></div>
            
                        </div>
        
                    </div>
    
                </section>
            </div>
        </section>

        <section class="section stack" id="karten">
            <h2>Karten-Reihe</h2>
            <p class="container--narrow">Drei Karten, Bild steht oben links über der Karte. <code>v2/kacheln.css</code></p>

            {{-- karten-reihe · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <div class="vdbs-karten">
    
                    <div class="vdbs-karte">
        
                        <figure class="vdbs-karte__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy"><figcaption>Bild: Quelle</figcaption></figure>
        

        
                        <div class="vdbs-karte__inhalt">
            
                            <p class="vdbs-tag">Rückblick</p>
            
            
                            <h3 class="vdbs-karte__titel">Titel der ersten Karte</h3>
            
            
                            <p>Kurzer Text, der erklärt, worum es geht. Drei bis fünf Zeilen genügen.</p>
            
            
                            <div class="vdbs-aktion"><a class="btn" href="#">Zum Rückblick</a></div>
            
                        </div>
        
                    </div>
    

    
                    <div class="vdbs-karte">
        
                        <figure class="vdbs-karte__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy"><figcaption>Bild: Quelle</figcaption></figure>
        

        
                        <div class="vdbs-karte__inhalt">
            
                            <p class="vdbs-tag">News</p>
            
            
                            <h3 class="vdbs-karte__titel">Titel der zweiten Karte</h3>
            
            
                            <p>Kurzer Text, der erklärt, worum es geht. Drei bis fünf Zeilen genügen.</p>
            
            
                            <div class="vdbs-aktion"><a class="btn" href="#">Zum Beitrag</a></div>
            
                        </div>
        
                    </div>
    

    
                    <div class="vdbs-karte">
        
                        <figure class="vdbs-karte__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy"><figcaption>Bild: Quelle</figcaption></figure>
        

        
                        <div class="vdbs-karte__inhalt">
            
                            <p class="vdbs-tag">Publikation</p>
            
            
                            <h3 class="vdbs-karte__titel">Titel der dritten Karte</h3>
            
            
                            <p>Kurzer Text, der erklärt, worum es geht. Drei bis fünf Zeilen genügen.</p>
            
            
                            <div class="vdbs-aktion"><a class="btn" href="#">Zur Publikation</a></div>
            
                        </div>
        
                    </div>
    
                </div>
            </div>
        </section>

        <section class="section stack" id="kennzahlen">
            <h2>Kennzahlen-Kacheln</h2>
            <p class="container--narrow">Sechs Kacheln mit Icon-Fläche, Zahl und Text. <code>v2/kacheln.css</code></p>

            {{-- kennzahlen · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-bereich">
    
                    <h2 class="vdbs-bereich__titel">Der Verein in Zahlen</h2>
    

    
                    <div class="vdbs-kennzahlen">
        
                        <div class="vdbs-kennzahl">
            
                            <div class="vdbs-kennzahl__icon">
                
                                <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="32" cy="22" r="8"/><path d="M18 54v-7a14 14 0 0 1 28 0v7z"/><circle cx="12" cy="28" r="5"/><path d="M4 48v-5a8 8 0 0 1 8-8"/><circle cx="52" cy="28" r="5"/><path d="M60 48v-5a8 8 0 0 0-8-8"/></svg>
                
                            </div>
            

            
                            <div class="vdbs-kennzahl__inhalt">
                
                                <h3 class="vdbs-kennzahl__titel">1.000 Mitglieder</h3>
                
                
                                <p>In 16 Bundesländern und an über 500 Schulen.</p>
                
                            </div>
            
                        </div>
        

        
                        <div class="vdbs-kennzahl">
            
                            <div class="vdbs-kennzahl__icon">
                
                                <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><ellipse cx="24" cy="16" rx="14" ry="5"/><path d="M10 16v24c0 3 6 5 14 5s14-2 14-5V16M10 24c0 3 6 5 14 5s14-2 14-5M10 32c0 3 6 5 14 5s14-2 14-5"/><ellipse cx="44" cy="38" rx="12" ry="4"/><path d="M32 38v14c0 2 5 4 12 4s12-2 12-4V38"/></svg>
                
                            </div>
            

            
                            <div class="vdbs-kennzahl__inhalt">
                
                                <h3 class="vdbs-kennzahl__titel">250.000 Euro</h3>
                
                
                                <p>Fördermittel für Projekte im Jahr 2025.</p>
                
                            </div>
            
                        </div>
        

        
                        <div class="vdbs-kennzahl">
            
                            <div class="vdbs-kennzahl__icon">
                
                                <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><g transform="rotate(40 32 28)"><rect x="25" y="4" width="14" height="26" rx="7"/><path d="M20 26a12 12 0 0 0 24 0M32 38v8"/></g><path d="M6 56c8-6 16 2 24-4"/></svg>
                
                            </div>
            

            
                            <div class="vdbs-kennzahl__inhalt">
                
                                <h3 class="vdbs-kennzahl__titel">10.000 Teilnehmende</h3>
                
                
                                <p>bei Seminaren, Fachtagungen und Veranstaltungen, in Präsenz und online.</p>
                
                            </div>
            
                        </div>
        

        
                        <div class="vdbs-kennzahl">
            
                            <div class="vdbs-kennzahl__icon">
                
                                <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 26 32 14l28 12-28 12z"/><path d="M16 32v12c0 4 8 8 16 8s16-4 16-8V32M56 28v16"/></svg>
                
                            </div>
            

            
                            <div class="vdbs-kennzahl__inhalt">
                
                                <h3 class="vdbs-kennzahl__titel">300 Schülerinnen und Schüler</h3>
                
                
                                <p>werden jährlich zu Multiplikatoren ausgebildet.</p>
                
                            </div>
            
                        </div>
        

        
                        <div class="vdbs-kennzahl">
            
                            <div class="vdbs-kennzahl__icon">
                
                                <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="6" y="22" width="24" height="32" rx="1"/><rect x="18" y="14" width="24" height="32" rx="1"/><rect x="30" y="8" width="24" height="32" rx="1"/><path d="M36 18h12v8H36z"/></svg>
                
                            </div>
            

            
                            <div class="vdbs-kennzahl__inhalt">
                
                                <h3 class="vdbs-kennzahl__titel">120 Publikationen</h3>
                
                
                                <p>bieten Praxishilfen, Leitfäden und Materialien.</p>
                
                            </div>
            
                        </div>
        

        
                        <div class="vdbs-kennzahl">
            
                            <div class="vdbs-kennzahl__icon">
                
                                <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="6" y="14" width="12" height="42" rx="1"/><rect x="20" y="10" width="12" height="46" rx="1"/><path d="M36 14l10-4 10 40-10 4zM40 22l6-2"/></svg>
                
                            </div>
            

            
                            <div class="vdbs-kennzahl__inhalt">
                
                                <h3 class="vdbs-kennzahl__titel">5.000 Medien</h3>
                
                
                                <p>stehen in unseren Schulbibliotheken bereit.</p>
                
                            </div>
            
                        </div>
        
                    </div>
    
                </section>
            </div>
        </section>

        <section class="section stack" id="facts">
            <h2>Facts</h2>
            <p class="container--narrow">Große Karte mit Titel und Button, daneben vier Kennzahl-Kacheln. <code>v2/kacheln.css</code></p>

            {{-- facts-statistik · Breite: full --}}
            <div class="vdbs-demo vdbs-demo--full">
                <section class="vdbs-facts">
                <div class="vdbs-facts__inner">
    
                    <div class="vdbs-facts__raster">
        
                        <div class="vdbs-facts__haupt">
            
                            <h2 class="vdbs-facts__titel">VDBS Facts</h2>
            
            
                            <div class="vdbs-buttons"><a class="btn" href="#">Erfahren Sie mehr über uns</a></div>
            
                        </div>
        

        
                        <div class="vdbs-fact">
            
                            <p class="vdbs-fact__zahl">2023</p>
            
            
                            <p class="vdbs-fact__label">haben wir uns als Verein gegründet.</p>
            
                        </div>
        

        
                        <div class="vdbs-fact">
            
                            <p class="vdbs-fact__zahl">7</p>
            
            
                            <p class="vdbs-fact__label">Schulen haben bereits mit uns Zusammengearbeitet.</p>
            
                        </div>
        

        
                        <div class="vdbs-fact">
            
                            <p class="vdbs-fact__zahl">9</p>
            
            
                            <p class="vdbs-fact__label">SV-Tage dieses Schuljahr durchgeführt.</p>
            
                        </div>
        

        
                        <div class="vdbs-fact">
            
                            <p class="vdbs-fact__zahl">2</p>
            
            
                            <p class="vdbs-fact__label">Kooperationspartner helfen uns bei unserer Arbeit.</p>
            
                        </div>
        
                    </div>
    

                </div>
                </section>
            </div>
        </section>

        <section class="section stack" id="zahlen">
            <h2>Zahlenleiste</h2>
            <p class="container--narrow">Vier Kennzahlen mit Icon, Zahl und Label. <code>v2/kacheln.css</code></p>

            {{-- zahlenleiste · Breite: full --}}
            <div class="vdbs-demo vdbs-demo--full">
                <section class="vdbs-zahlen">
                <div class="vdbs-zahlen__inner">
    
                    <h2 class="vdbs-zahlen__titel">Der Verein in Zahlen</h2>
    

    
                    <div class="vdbs-zahlen__liste">
        
                        <div class="vdbs-zahlen__eintrag">
            
                            <div class="vdbs-zahlen__icon"><svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="32" cy="22" r="8"/><path d="M18 54v-7a14 14 0 0 1 28 0v7z"/><circle cx="12" cy="28" r="5"/><path d="M4 48v-5a8 8 0 0 1 8-8"/><circle cx="52" cy="28" r="5"/><path d="M60 48v-5a8 8 0 0 0-8-8"/></svg></div>
            
            
                            <p class="vdbs-zahlen__zahl">1.000</p>
            
            
                            <p class="vdbs-zahlen__label">Mitglieder</p>
            
                        </div>
        

        
                        <div class="vdbs-zahlen__eintrag">
            
                            <div class="vdbs-zahlen__icon"><svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M8 56V26l24-14 24 14v30zM26 56V40h12v16M32 12V4l9 3-9 3"/></svg></div>
            
            
                            <p class="vdbs-zahlen__zahl">500</p>
            
            
                            <p class="vdbs-zahlen__label">Schulen</p>
            
                        </div>
        

        
                        <div class="vdbs-zahlen__eintrag">
            
                            <div class="vdbs-zahlen__icon"><svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M6 12h20a6 6 0 0 1 6 6v38a5 5 0 0 0-5-5H6zM58 12H38a6 6 0 0 0-6 6v38a5 5 0 0 1 5-5h21z"/></svg></div>
            
            
                            <p class="vdbs-zahlen__zahl">5.000</p>
            
            
                            <p class="vdbs-zahlen__label">Medien</p>
            
                        </div>
        

        
                        <div class="vdbs-zahlen__eintrag">
            
                            <div class="vdbs-zahlen__icon"><svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="8" y="12" width="48" height="44" rx="2"/><path d="M8 26h48M20 6v12M44 6v12M20 36h6M30 36h6M40 36h4M20 46h6M30 46h6"/></svg></div>
            
            
                            <p class="vdbs-zahlen__zahl">120</p>
            
            
                            <p class="vdbs-zahlen__label">Veranstaltungen</p>
            
                        </div>
        
                    </div>
    

                </div>
                </section>
            </div>
        </section>

        <section class="section stack" id="news">
            <h2>News-Raster</h2>
            <p class="container--narrow">Vier Einträge mit quadratischem Bild, Datum, Etikett, Titel und Weiterlesen. <code>v2/kacheln.css</code></p>

            {{-- news-raster · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-news">
    
                    <div class="vdbs-news__kopf">
        
                        <h2 class="vdbs-news__titel">Neuigkeiten</h2>
        
        
                        <p class="vdbs-news__alle"><a href="#">Alle Beiträge ansehen</a></p>
        
                    </div>
    

    
                    <div class="vdbs-news__liste">
        
                        <div class="vdbs-news__eintrag">
            
                            <figure class="vdbs-news__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy"></figure>
            
            
                            <p class="vdbs-news__datum">1.10.2026</p>
            
            
                            <h3 class="vdbs-news__eintrag-titel">Titel des ersten Beitrags</h3>
            
            
                            <p class="vdbs-news__mehr"><a href="#">Weiterlesen</a></p>
            
                        </div>
        

        
                        <div class="vdbs-news__eintrag">
            
                            <figure class="vdbs-news__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy"></figure>
            
            
                            <p class="vdbs-news__datum">17.9.2026</p>
            
            
                            <p class="vdbs-news__tag">aktuelle Veranstaltungen</p>
            
            
                            <h3 class="vdbs-news__eintrag-titel">Titel des zweiten Beitrags, der etwas länger ist</h3>
            
            
                            <p class="vdbs-news__mehr"><a href="#">Weiterlesen</a></p>
            
                        </div>
        

        
                        <div class="vdbs-news__eintrag">
            
                            <figure class="vdbs-news__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy"></figure>
            
            
                            <p class="vdbs-news__datum">7.5.2026</p>
            
            
                            <h3 class="vdbs-news__eintrag-titel">Titel des dritten Beitrags</h3>
            
            
                            <p class="vdbs-news__mehr"><a href="#">Weiterlesen</a></p>
            
                        </div>
        

        
                        <div class="vdbs-news__eintrag">
            
                            <figure class="vdbs-news__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy"></figure>
            
            
                            <p class="vdbs-news__datum">21.4.2026</p>
            
            
                            <h3 class="vdbs-news__eintrag-titel">Titel des vierten Beitrags</h3>
            
            
                            <p class="vdbs-news__mehr"><a href="#">Weiterlesen</a></p>
            
                        </div>
        
                    </div>
    
                </section>
            </div>
        </section>

        <section class="section stack" id="zitate">
            <h2>Zitate</h2>
            <p class="container--narrow">Drei Aussagen mit Quellenangabe. <code>v2/inhalt.css</code></p>

            {{-- zitate · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <div class="vdbs-zitate">
    
                    <blockquote class="vdbs-zitat">
                    <p>„Das Schulsystem ist scheiße!“</p>
                    <cite>Schülerin</cite></blockquote>
    

    
                    <blockquote class="vdbs-zitat">
                    <p>„Schule ist ein Vollzeitjob und dazu kommen Hausaufgaben.“</p>
                    <cite>Schüler</cite></blockquote>
    

    
                    <blockquote class="vdbs-zitat">
                    <p>„Es gibt keine Bildungsgerechtigkeit.“</p>
                    <cite>Schülerin</cite></blockquote>
    
                </div>
            </div>
        </section>

        <section class="section stack" id="textblock">
            <h2>Textblock zentriert</h2>
            <p class="container--narrow">Überschrift und Absatz, zentriert. <code>v2/inhalt.css</code></p>

            {{-- textblock-zentriert · Breite: normal --}}
            <div class="vdbs-demo vdbs-demo--normal">
                <section class="vdbs-textblock">
    
                    <h2 class="is-center vdbs-textblock__titel">Wir brauchen Veränderungen. Jetzt!</h2>
    
    
                    <p class="is-center">Kurzer Text, der erklärt, worum es geht und was die Besucherinnen und Besucher erwartet. Drei bis fünf Zeilen genügen.</p>
    
                </section>
            </div>
        </section>

        <section class="section stack" id="flaeche">
            <h2>Textfläche</h2>
            <p class="container--narrow">Vollbreites Band mit schmaler Textspalte. <code>v2/inhalt.css</code></p>

            {{-- textflaeche · Breite: full --}}
            <div class="vdbs-demo vdbs-demo--full">
                <section class="vdbs-flaeche">
                <div class="vdbs-flaeche__inner">
    
                    <h2 class="vdbs-flaeche__titel">Herzlich willkommen!</h2>
    

    
                    <p>Schön, dass Sie sich für den Verein interessieren. Seit unserer Gründung setzen wir uns für Schülervertretungen und Schulbibliotheken ein. Als gemeinnütziger Verband verbindet uns das Streben nach Teilhabe, Gerechtigkeit und Zusammenhalt.</p>
    

    
                    <p>Wir stehen ein für eine lebendige Schuldemokratie, in der die Teilhabe aller Schülerinnen und Schüler selbstverständlich ist. Und wir setzen uns ein für Bibliotheken als Orte, die allen offenstehen.</p>
    

    
                    <p>Diese Webseite ist Spiegel unserer vielfältigen Arbeit. Schauen Sie sich gerne um!</p>
    

    
                    <p><em>Ihre Vorsitzende und Ihr Vorsitzender des VDBS e.V.</em></p>
    

                </div>
                </section>
            </div>
        </section>

        <section class="section stack" id="aufruf">
            <h2>Aufruf</h2>
            <p class="container--narrow">Band mit Überschrift, Text und Button (z. B. „Werde Mitglied“). <code>v2/inhalt.css</code></p>

            {{-- aufruf-mitglied · Breite: full --}}
            <div class="vdbs-demo vdbs-demo--full">
                <section class="vdbs-aufruf">
                <div class="vdbs-aufruf__inner">
    
                    <h2 class="is-center vdbs-aufruf__titel">Werde Mitglied</h2>
    
    
                    <p class="is-center">Kurzer Text, der erklärt, worum es geht und was die Besucherinnen und Besucher erwartet. Drei bis fünf Zeilen genügen.</p>
    
    
                    <div class="vdbs-buttons"><a class="btn" href="#">Mitgliedserklärung herunterladen</a></div>
    

                </div>
                </section>
            </div>
        </section>

        <section class="section stack" id="hinweis">
            <h2>Hinweisbox</h2>
            <p class="container--narrow">Band mit Icon, Frage, Text und Outline-Button. Varianten: hell, grün. <code>v2/inhalt.css</code></p>

            {{-- hinweisbox · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-hinweis">
    
                    <div class="vdbs-hinweis__icon"><svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="32" cy="32" r="26"/><path d="M32 28v16M26 44h12M32 18v2"/></svg></div>
    

    
                    <div class="vdbs-hinweis__text">
        
                        <h2 class="vdbs-hinweis__titel">Wie viele Medien gibt es? Dürfen Hunde mit?</h2>
        
        
                        <p>Und weitere häufig gestellte Fragen.</p>
        
                    </div>
    

    
                    <div class="vdbs-buttons"><a class="btn btn--outline" href="#">Fragen und Antworten</a></div>
    
                </section>
            </div>

            {{-- hinweisbox-gruen · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-hinweis vdbs-hinweis--gruen">
    
                    <div class="vdbs-hinweis__icon"><svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M6 12h20a6 6 0 0 1 6 6v38a5 5 0 0 0-5-5H6zM58 12H38a6 6 0 0 0-6 6v38a5 5 0 0 1 5-5h21z"/></svg></div>
    

    
                    <div class="vdbs-hinweis__text">
        
                        <h2 class="vdbs-hinweis__titel">Sie suchen die Buchsuche oder Ausleihinfos?</h2>
        
        
                        <p>Die Ausleihe finden Sie im Online-Katalog.</p>
        
                    </div>
    

    
                    <div class="vdbs-buttons"><a class="btn btn--outline" href="#">Zum Katalog</a></div>
    
                </section>
            </div>
        </section>

        <section class="section stack" id="bildpaar">
            <h2>Bildpaar</h2>
            <p class="container--narrow">Zwei Bilder nebeneinander mit Quellenangabe. <code>v2/inhalt.css</code></p>

            {{-- bildpaar · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <div class="vdbs-bildpaar">
    
                    <figure class="vdbs-bildpaar__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy"><figcaption>Bild: Quelle/Urheber</figcaption></figure>
    

    
                    <figure class="vdbs-bildpaar__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy"><figcaption>Bild: Quelle/Urheber</figcaption></figure>
    
                </div>
            </div>
        </section>

        <section class="section stack" id="abschnitte">
            <h2>Textabschnitte</h2>
            <p class="container--narrow">Überschrift mit mehreren Zwischenüberschriften und Text. <code>v2/inhalt.css</code></p>

            {{-- textabschnitte · Breite: normal --}}
            <div class="vdbs-demo vdbs-demo--normal">
                <section class="vdbs-abschnitte">
    
                    <h2 class="vdbs-abschnitte__titel">Was wir tun</h2>
    

    
                    <div class="vdbs-abschnitt">
        
                        <h3 class="vdbs-abschnitt__titel">Wir vernetzen Menschen</h3>
        
        
                        <p>Wir verbinden Schülervertretungen, Bibliotheken und Partner zu einem Netzwerk, das voneinander lernt und gemeinsam wirkt.</p>
        
                    </div>
    

    
                    <div class="vdbs-abschnitt">
        
                        <h3 class="vdbs-abschnitt__titel">Wir machen fit für Beteiligung</h3>
        
        
                        <p>Wir begleiten und bilden alle, die sich in ihrer Schule engagieren wollen – mit Seminaren, Materialien und persönlicher Beratung.</p>
        
                    </div>
    

    
                    <div class="vdbs-abschnitt">
        
                        <h3 class="vdbs-abschnitt__titel">Wir geben Impulse</h3>
        
        
                        <p>Wir beobachten Entwicklungen und bringen Erfahrungen aus der Praxis in die Debatte ein.</p>
        
                    </div>
    
                </section>
            </div>
        </section>

        <section class="section stack" id="infospalten">
            <h2>Infospalten</h2>
            <p class="container--narrow">Zwei Spalten, z. B. Anschrift und Kontakt. <code>v2/personen.css</code></p>

            {{-- infospalten · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <div class="vdbs-infospalten">
    
                    <div>
        
                        <h3 class="vdbs-infospalten__titel">Anschrift</h3>
        
        
                        <p>VDBS e.V.<br>Musterstraße 1<br>12345 Musterstadt</p>
        
                    </div>
    

    
                    <div>
        
                        <h3 class="vdbs-infospalten__titel">Kontakt</h3>
        
        
                        <p>Mail: <a href="mailto:kontakt@example.org">kontakt@example.org</a></p>
        
                    </div>
    
                </div>
            </div>
        </section>

        <section class="section stack" id="personen">
            <h2>Ansprechpartner:innen</h2>
            <p class="container--narrow">Runde Porträts mit Name, Rolle und E-Mail. <code>v2/personen.css</code></p>

            {{-- ansprechpartner · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-bereich">
    
                    <h2 class="is-center vdbs-bereich__titel">Ansprechpartner:innen</h2>
    

    
                    <p class="is-center">Bei Anfragen stehen wir immer gerne zur Verfügung:</p>
    

    
                    <div class="vdbs-personen">
        
                        <div class="vdbs-person">
            
                            <figure class="vdbs-person__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="Vorname Nachname" loading="lazy"></figure>
            
            
                            <h3 class="vdbs-person__name">Vorname Nachname</h3>
            
            
                            <p class="vdbs-person__rolle">Koordinator:in Region A</p>
            
            
                            <p class="vdbs-person__mail"><a href="mailto:region-a@example.org">region-a@example.org</a></p>
            
                        </div>
        

        
                        <div class="vdbs-person">
            
                            <figure class="vdbs-person__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="Vorname Nachname" loading="lazy"></figure>
            
            
                            <h3 class="vdbs-person__name">Vorname Nachname</h3>
            
            
                            <p class="vdbs-person__rolle">Koordinator:in Region B</p>
            
            
                            <p class="vdbs-person__mail"><a href="mailto:region-b@example.org">region-b@example.org</a></p>
            
                        </div>
        
                    </div>
    
                </section>
            </div>
        </section>

        <section class="section stack" id="vorstand">
            <h2>Vorstand</h2>
            <p class="container--narrow">Porträt-Karten (5), 3 pro Reihe, letzte Reihe zentriert. <code>v2/personen.css</code></p>

            {{-- vorstand-raster · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-bereich">
    
                    <h2 class="is-center vdbs-bereich__titel">Vorstand</h2>
    

    
                    <div class="vdbs-vorstand">
        
                        <div class="vdbs-mitglied">
            
                            <figure class="vdbs-mitglied__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="Vorname Nachname" loading="lazy"></figure>
            

            
                            <div class="vdbs-mitglied__text">
                
                                <h3 class="vdbs-mitglied__name">Vorname Nachname</h3>
                
                
                                <p class="vdbs-mitglied__rolle">Vorsitzende:r</p>
                
                            </div>
            
                        </div>
        

        
                        <div class="vdbs-mitglied">
            
                            <figure class="vdbs-mitglied__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="Vorname Nachname" loading="lazy"></figure>
            

            
                            <div class="vdbs-mitglied__text">
                
                                <h3 class="vdbs-mitglied__name">Vorname Nachname</h3>
                
                
                                <p class="vdbs-mitglied__rolle">Stellv. Vorsitzende:r</p>
                
                            </div>
            
                        </div>
        

        
                        <div class="vdbs-mitglied">
            
                            <figure class="vdbs-mitglied__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="Vorname Nachname" loading="lazy"></figure>
            

            
                            <div class="vdbs-mitglied__text">
                
                                <h3 class="vdbs-mitglied__name">Vorname Nachname</h3>
                
                
                                <p class="vdbs-mitglied__rolle">Kassierer:in</p>
                
                            </div>
            
                        </div>
        

        
                        <div class="vdbs-mitglied">
            
                            <figure class="vdbs-mitglied__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="Vorname Nachname" loading="lazy"></figure>
            

            
                            <div class="vdbs-mitglied__text">
                
                                <h3 class="vdbs-mitglied__name">Vorname Nachname</h3>
                
                
                                <p class="vdbs-mitglied__rolle">Beisitzer:in</p>
                
                            </div>
            
                        </div>
        

        
                        <div class="vdbs-mitglied">
            
                            <figure class="vdbs-mitglied__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="Vorname Nachname" loading="lazy"></figure>
            

            
                            <div class="vdbs-mitglied__text">
                
                                <h3 class="vdbs-mitglied__name">Vorname Nachname</h3>
                
                
                                <p class="vdbs-mitglied__rolle">Beisitzer:in</p>
                
                            </div>
            
                        </div>
        
                    </div>
    
                </section>
            </div>
        </section>

        <section class="section stack" id="termine">
            <h2>Terminliste</h2>
            <p class="container--narrow">Zeilen mit Datum | Ort, Titel, Text und Button auf der Trennlinie. <code>v2/termine.css</code></p>

            {{-- terminliste · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-bereich">
    
                    <h2 class="vdbs-bereich__titel">Veranstaltungen</h2>
    

    
                    <div class="vdbs-termine">
        
                        <div class="vdbs-termin">
            
                            <p class="vdbs-meta">Mittwoch, 04.11.2026 | <strong>Online</strong></p>
            
            
                            <h3 class="vdbs-termin__titel">Titel der ersten Veranstaltung</h3>
            
            
                            <p>Kurze Beschreibung der Veranstaltung: Worum geht es, wer spricht, was erwartet die Teilnehmenden?</p>
            
            
                            <div class="vdbs-aktion"><a class="btn" href="#">Weitere Infos &amp; Anmeldung</a></div>
            
                        </div>
        

        
                        <div class="vdbs-termin">
            
                            <p class="vdbs-meta">Mittwoch, 04.11.2026 | <strong>Online</strong></p>
            
            
                            <h3 class="vdbs-termin__titel">Titel der zweiten Veranstaltung</h3>
            
            
                            <p>Kurze Beschreibung der Veranstaltung: Worum geht es, wer spricht, was erwartet die Teilnehmenden?</p>
            
            
                            <div class="vdbs-aktion"><a class="btn" href="#">Weitere Infos &amp; Anmeldung</a></div>
            
                        </div>
        

        
                        <div class="vdbs-termin">
            
                            <p class="vdbs-meta">Donnerstag, 05.11.2026 | <strong>Berlin</strong></p>
            
            
                            <h3 class="vdbs-termin__titel">Titel der dritten Veranstaltung</h3>
            
            
                            <p>Kurze Beschreibung der Veranstaltung: Worum geht es, wer spricht, was erwartet die Teilnehmenden?</p>
            
            
                            <div class="vdbs-aktion"><a class="btn" href="#">Weitere Infos &amp; Anmeldung</a></div>
            
                        </div>
        
                    </div>
    
                </section>
            </div>
        </section>

        <section class="section stack" id="suche">
            <h2>Veranstaltungssuche</h2>
            <p class="container--narrow">Suchleiste, Ergebnisleiste und Terminzeilen mit Plätze-Hinweis. <code>v2/termine.css</code></p>

            {{-- veranstaltungssuche · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-bereich">
    
                    <h2 class="vdbs-bereich__titel">Veranstaltungssuche</h2>
    

    
                    <div class="vdbs-suche">
                        <form class="vdbs-suchleiste" role="search" action="#" onsubmit="return false;">
                    <label class="vdbs-suchleiste__label" for="vdbs-suche-feld">Stichwortsuche</label>
                    <div class="vdbs-suchleiste__feld">
                        <input class="vdbs-suchleiste__eingabe" type="search" id="vdbs-suche-feld" placeholder="Stichwortsuche">
                        <button class="vdbs-suchleiste__senden" type="submit">Suche</button>
                    </div>
                </form>

        
                        <p class="vdbs-suche-erweitert"><a href="#">Erweiterte Suche</a></p>
        
                    </div>
    

    
                    <div class="vdbs-ergebnisleiste">
        
                        <p>Zeige Elemente 1 bis 20</p>
        
        
                        <p>Seite 1 von 9</p>
        
        
                        <p>Insgesamt 178 Treffer</p>
        
                    </div>
    

    
                    <div class="vdbs-veranstaltungen">
        
                        <div class="vdbs-veranstaltung">
            
                            <div class="vdbs-veranstaltung__datum">
                
                                <p class="vdbs-veranstaltung__tag">26</p>
                
                
                                <p class="vdbs-veranstaltung__monat">Okt</p>
                
                
                                <p class="vdbs-veranstaltung__zeit">12:00 - 13:30</p>
                
                            </div>
            

            
                            <div class="vdbs-veranstaltung__inhalt">
                
                                <p class="vdbs-plaetze vdbs-plaetze--voll">keine Plätze frei</p>
                
                
                                <h3 class="vdbs-veranstaltung__titel">Titel der ersten Veranstaltung</h3>
                
                
                                <p class="vdbs-veranstaltung__meta">Veranstaltungsort</p>
                
                
                                <p class="vdbs-veranstaltung__meta">Straße und Hausnummer</p>
                
                
                                <p class="vdbs-veranstaltung__meta">PLZ Ort</p>
                
                
                                <p class="vdbs-veranstaltung__text">Kurze Beschreibung der Veranstaltung: Worum geht es, wer spricht, was erwartet die Teilnehmenden? Zwei bis drei Zeilen genügen …</p>
                
                
                                <div class="vdbs-aktion vdbs-aktion--flach"><a class="btn" href="#">Details</a></div>
                
                            </div>
            
                        </div>
        

        
                        <div class="vdbs-veranstaltung">
            
                            <div class="vdbs-veranstaltung__datum">
                
                                <p class="vdbs-veranstaltung__tag">26</p>
                
                
                                <p class="vdbs-veranstaltung__monat">Okt</p>
                
                
                                <p class="vdbs-veranstaltung__zeit">16:00 - 17:30</p>
                
                            </div>
            

            
                            <div class="vdbs-veranstaltung__inhalt">
                
                                <p class="vdbs-plaetze vdbs-plaetze--voll">keine Plätze frei</p>
                
                
                                <h3 class="vdbs-veranstaltung__titel">Titel der zweiten Veranstaltung</h3>
                
                
                                <p class="vdbs-veranstaltung__meta">Online</p>
                
                
                                <p class="vdbs-veranstaltung__meta">Bundesland</p>
                
                
                                <p class="vdbs-veranstaltung__text">Kurze Beschreibung der Veranstaltung: Worum geht es, wer spricht, was erwartet die Teilnehmenden? Zwei bis drei Zeilen genügen …</p>
                
                
                                <div class="vdbs-aktion vdbs-aktion--flach"><a class="btn" href="#">Details</a></div>
                
                            </div>
            
                        </div>
        

        
                        <div class="vdbs-veranstaltung">
            
                            <div class="vdbs-veranstaltung__datum">
                
                                <p class="vdbs-veranstaltung__tag">27</p>
                
                
                                <p class="vdbs-veranstaltung__monat">Okt</p>
                
                
                                <p class="vdbs-veranstaltung__zeit">10:00 - 15:00</p>
                
                            </div>
            

            
                            <div class="vdbs-veranstaltung__inhalt">
                
                                <p class="vdbs-plaetze vdbs-plaetze--frei">freie Plätze</p>
                
                
                                <h3 class="vdbs-veranstaltung__titel">Titel der dritten Veranstaltung</h3>
                
                
                                <p class="vdbs-veranstaltung__meta">Veranstaltungsort</p>
                
                
                                <p class="vdbs-veranstaltung__meta">PLZ Ort</p>
                
                
                                <p class="vdbs-veranstaltung__text">Kurze Beschreibung der Veranstaltung: Worum geht es, wer spricht, was erwartet die Teilnehmenden? Zwei bis drei Zeilen genügen …</p>
                
                
                                <div class="vdbs-aktion vdbs-aktion--flach"><a class="btn" href="#">Details</a></div>
                
                            </div>
            
                        </div>
        
                    </div>
    
                </section>
            </div>
        </section>

        <section class="section stack" id="angebot">
            <h2>Angebot-Bereich</h2>
            <p class="container--narrow">Großer Titel mit Strich, Text und ausklappbare Module. Varianten: Titel links/rechts, Farbe grün/petrol/rot. <code>v2/angebot.css</code></p>

            {{-- angebot-bereich · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-angebot">
    
                    <div class="vdbs-angebot__kopf">
        
                        <h2 class="vdbs-angebot__titel">Bereich A</h2>
        
                    </div>
    

    
                    <div class="vdbs-angebot__inhalt">
        
                        <p>Einleitung zum Projekt: Zielgruppe, Ziel und worum es geht. Vier bis sechs Zeilen genügen.</p>
        
        
                        <h3 class="vdbs-angebot__zwischentitel">Flexibilität und Anpassungsmöglichkeiten</h3>
        
        
                        <p>Wie sich die Module an die Bedürfnisse vor Ort anpassen lassen.</p>
        
        
                        <details class="vdbs-modul"><summary>Modul 1: Titel (1–3 Tage)</summary>
                        <p>Beschreibung des Moduls: Inhalt, Dauer, Zielgruppe.</p>
                        </details>
        
        
                        <details class="vdbs-modul"><summary>Modul 2: Titel (3–5 Tage)</summary>
                        <p>Beschreibung des Moduls: Inhalt, Dauer, Zielgruppe.</p>
                        </details>
        
        
                        <details class="vdbs-modul"><summary>Modul 3: Titel (1 Schuljahr)</summary>
                        <p>Beschreibung des Moduls: Inhalt, Dauer, Zielgruppe.</p>
                        </details>
        
        
                        <details class="vdbs-modul"><summary>Modul 4: Titel (1 Schuljahr)</summary>
                        <p>Beschreibung des Moduls: Inhalt, Dauer, Zielgruppe.</p>
                        </details>
        
        
                        <details class="vdbs-modul"><summary>Modul 5: Titel (1 Tag)</summary>
                        <p>Beschreibung des Moduls: Inhalt, Dauer, Zielgruppe.</p>
                        </details>
        
                    </div>
    
                </section>
            </div>

            {{-- angebot-bereich-rechts · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-angebot vdbs-angebot--rechts vdbs-angebot--petrol">
    
                    <div class="vdbs-angebot__kopf">
        
                        <h2 class="vdbs-angebot__titel">Bereich B</h2>
        
                    </div>
    

    
                    <div class="vdbs-angebot__inhalt">
        
                        <p>Einleitung zum Projekt: Zielgruppe, Ziel und worum es geht. Vier bis sechs Zeilen genügen.</p>
        
        
                        <h3 class="vdbs-angebot__zwischentitel">Flexibilität und Anpassungsmöglichkeiten</h3>
        
        
                        <p>Wie sich die Module an die Bedürfnisse vor Ort anpassen lassen.</p>
        
        
                        <details class="vdbs-modul"><summary>Modul 1: Titel (1–3 Tage)</summary>
                        <p>Beschreibung des Moduls: Inhalt, Dauer, Zielgruppe.</p>
                        </details>
        
        
                        <details class="vdbs-modul"><summary>Modul 2: Titel (3–5 Tage)</summary>
                        <p>Beschreibung des Moduls: Inhalt, Dauer, Zielgruppe.</p>
                        </details>
        
        
                        <details class="vdbs-modul"><summary>Modul 3: Titel (1 Schuljahr)</summary>
                        <p>Beschreibung des Moduls: Inhalt, Dauer, Zielgruppe.</p>
                        </details>
        
        
                        <details class="vdbs-modul"><summary>Modul 4: Titel (1 Schuljahr)</summary>
                        <p>Beschreibung des Moduls: Inhalt, Dauer, Zielgruppe.</p>
                        </details>
        
        
                        <details class="vdbs-modul"><summary>Modul 5: Titel (1 Tag)</summary>
                        <p>Beschreibung des Moduls: Inhalt, Dauer, Zielgruppe.</p>
                        </details>
        
        
                        <details class="vdbs-modul"><summary>Modul 6: Titel (laufend)</summary>
                        <p>Beschreibung des Moduls: Inhalt, Dauer, Zielgruppe.</p>
                        </details>
        
                    </div>
    
                </section>
            </div>
        </section>

        <section class="section stack" id="kontakt">
            <h2>Kontaktseite</h2>
            <p class="container--narrow">Bild und Kontaktdaten links, Formular rechts. <code>v2/formular.css</code></p>

            {{-- kontakt-seite · Breite: wide --}}
            <div class="vdbs-demo vdbs-demo--wide">
                <section class="vdbs-bereich">
    
                    <h2 class="vdbs-bereich__titel">Wie können wir helfen?</h2>
    

    
                    <div class="vdbs-kontakt">
        
                        <div class="vdbs-kontakt__info">
            
                            <figure class="vdbs-kontakt__bild"><img src="{{ asset('images/design/platzhalter.svg') }}" alt="" loading="lazy"></figure>
            
            
                            <div>
                
                                <h3 class="vdbs-kontakt__titel">E-Mail</h3>
                
                
                                <p><a href="mailto:kontakt@example.org">kontakt@example.org</a></p>
                
                            </div>
            
            
                            <div>
                
                                <h3 class="vdbs-kontakt__titel">Post</h3>
                
                
                                <p>VDBS e.V.<br>Musterstraße 1<br>12345 Musterstadt</p>
                
                            </div>
            
                        </div>
        

                        <form class="vdbs-formular" action="#" method="post" onsubmit="return false;" novalidate>
                    <p class="vdbs-formular__status vdbs-formular__status--ok" role="status">Vielen Dank! Ihre Nachricht wurde gesendet.</p>
                    <div class="vdbs-formular__feld">
                        <label for="vdbs-vorname">Vorname <span class="vdbs-formular__pflicht" aria-hidden="true">*</span></label>
                        <input type="text" id="vdbs-vorname" name="vorname" autocomplete="given-name" required>
                    </div>
                    <div class="vdbs-formular__feld">
                        <label for="vdbs-nachname">Nachname <span class="vdbs-formular__pflicht" aria-hidden="true">*</span></label>
                        <input type="text" id="vdbs-nachname" name="nachname" autocomplete="family-name" required>
                    </div>
                    <div class="vdbs-formular__feld vdbs-formular__feld--voll">
                        <label for="vdbs-email">E-Mail <span class="vdbs-formular__pflicht" aria-hidden="true">*</span></label>
                        <input type="email" id="vdbs-email" name="email" autocomplete="email" required>
                    </div>
                    <div class="vdbs-formular__feld vdbs-formular__feld--voll">
                        <label for="vdbs-betreff">Betreff <span class="vdbs-formular__pflicht" aria-hidden="true">*</span></label>
                        <input type="text" id="vdbs-betreff" name="betreff" required>
                    </div>
                    <div class="vdbs-formular__feld vdbs-formular__feld--voll">
                        <label for="vdbs-nachricht">Nachricht <span class="vdbs-formular__pflicht" aria-hidden="true">*</span></label>
                        <textarea id="vdbs-nachricht" name="nachricht" rows="7" required></textarea>
                    </div>
                    <div class="vdbs-formular__feld vdbs-formular__feld--voll">
                        <div class="vdbs-formular__zustimmung">
                            <input type="checkbox" id="vdbs-datenschutz" name="datenschutz" value="1" required>
                            <label for="vdbs-datenschutz">Ich stimme zu, dass meine Angaben zur Beantwortung meiner Anfrage verarbeitet werden. <span class="vdbs-formular__pflicht" aria-hidden="true">*</span></label>
                        </div>
                    </div>
                    <button type="submit" class="vdbs-formular__senden vdbs-formular__feld--voll">Kontaktanfrage senden</button>
                </form>
                    </div>
    
                </section>
            </div>
        </section>

    </div>
@endsection
