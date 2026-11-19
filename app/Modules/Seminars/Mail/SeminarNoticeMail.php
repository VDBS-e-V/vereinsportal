<?php

namespace App\Modules\Seminars\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;

/**
 * Mitteilung zu einem Seminar. Der Anlass (z. B. „team_confirmed“) bestimmt die Vorlage im Mail-Builder („seminar.team_confirmed“);
 * der Aufrufer liefert nur die Werte der Platzhalter. Betreff und Absätze stehen als Eigenschaften zur Verfügung, damit sich
 * Versand und Tests darauf beziehen können. Ob die Person die Mitteilung abschalten darf, entscheidet der Aufrufer.
 */
final class SeminarNoticeMail extends TemplatedMail
{
    public readonly string $subjectLine;

    /** @var list<string> Die Absätze der Mail ohne Anrede und Hinweis zum Link. */
    public readonly array $paragraphs;

    /** @param  array<string, string|list<string>>  $fields */
    public function __construct(public readonly string $event, public readonly ?string $name, public readonly array $fields, public readonly ?string $url = null, public readonly ?string $urlLabel = null)
    {
        $rendered = $this->rendered();
        $this->subjectLine = $rendered->subject;
        $this->paragraphs = self::paragraphsOf($rendered->html, self::greeting($name).',');
    }

    protected function systemKey(): string
    {
        return 'seminar.'.$this->event;
    }

    protected function values(): array
    {
        return $this->fields + ['anrede' => self::greeting($this->name), 'link' => (string) $this->url, 'link_text' => filled($this->urlLabel) ? (string) $this->urlLabel : 'Im Vereinsportal öffnen'];
    }

    /** @return list<string> */
    private static function paragraphsOf(string $html, string $greeting): array
    {
        preg_match_all('/<p[^>]*class="vdbs-text"[^>]*>(.*?)<\/p>/s', $html, $matches);
        $paragraphs = [];

        foreach ($matches[1] as $raw) {
            $text = trim(html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if ($text !== '' && $text !== $greeting && $text !== '(Anmeldung nötig)') {
                $paragraphs[] = $text;
            }
        }

        return $paragraphs;
    }
}
