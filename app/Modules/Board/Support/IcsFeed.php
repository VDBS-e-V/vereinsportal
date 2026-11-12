<?php

namespace App\Modules\Board\Support;

use App\Modules\Board\Models\BoardMeeting;
use Illuminate\Support\Collection;

/** Baut den iCal-Kalender (.ics) der Vorstandssitzungen. Die Zeiten sind Berliner Ortszeit; es werden nur Termine, Orte und Links ausgegeben, nie Tagesordnung oder Protokoll. */
final class IcsFeed
{
    private const TZ = 'Europe/Berlin';

    /** @param  Collection<int, BoardMeeting>  $meetings */
    public static function build(Collection $meetings, string $host, string $name = 'Vorstandssitzungen'): string
    {
        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//VDBS//Vereinsportal//DE', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH', 'X-WR-CALNAME:'.self::escape($name), 'X-WR-TIMEZONE:'.self::TZ, 'REFRESH-INTERVAL;VALUE=DURATION:PT6H', 'X-PUBLISHED-TTL:PT6H', ...self::timezone()];

        foreach ($meetings as $meeting) {
            $description = array_filter([$meeting->online_link ? 'Online: '.$meeting->online_link : null]);
            $lines = [...$lines, 'BEGIN:VEVENT', 'UID:board-meeting-'.$meeting->id.'@'.$host, 'DTSTAMP:'.($meeting->updated_at ?? now())->copy()->utc()->format('Ymd\THis\Z'), 'DTSTART;TZID='.self::TZ.':'.$meeting->starts_at->format('Ymd\THis'), 'DTEND;TZID='.self::TZ.':'.$meeting->ends_at->format('Ymd\THis'), 'SUMMARY:'.self::escape($meeting->title)];

            if (filled($meeting->location)) {
                $lines[] = 'LOCATION:'.self::escape($meeting->location);
            }

            if ($description !== []) {
                $lines[] = 'DESCRIPTION:'.self::escape(implode("\n", $description));
            }

            if (filled($meeting->online_link)) {
                $lines[] = 'URL:'.$meeting->online_link;
            }

            $lines[] = 'STATUS:'.($meeting->status === 'cancelled' ? 'CANCELLED' : 'CONFIRMED');
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map(self::fold(...), $lines))."\r\n";
    }

    public static function escape(string $text): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n", "\r"], ['\\\\', '\;', '\,', '\n', '\n', '\n'], $text);
    }

    /** Zeilen höchstens 75 Byte lang, Fortsetzung mit Leerzeichen; Zeichen werden nicht zerschnitten. */
    public static function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $parts = [];
        $current = '';
        $limit = 75;

        foreach (mb_str_split($line) as $char) {
            if (strlen($current) + strlen($char) > $limit) {
                $parts[] = $current;
                $current = '';
                $limit = 74;
            }

            $current .= $char;
        }

        $parts[] = $current;

        return implode("\r\n ", $parts);
    }

    /** @return list<string> */
    private static function timezone(): array
    {
        return ['BEGIN:VTIMEZONE', 'TZID:'.self::TZ, 'BEGIN:DAYLIGHT', 'TZOFFSETFROM:+0100', 'TZOFFSETTO:+0200', 'TZNAME:CEST', 'DTSTART:19700329T020000', 'RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU', 'END:DAYLIGHT', 'BEGIN:STANDARD', 'TZOFFSETFROM:+0200', 'TZOFFSETTO:+0100', 'TZNAME:CET', 'DTSTART:19701025T030000', 'RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU', 'END:STANDARD', 'END:VTIMEZONE'];
    }
}
