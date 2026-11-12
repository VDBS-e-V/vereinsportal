<p>Hausschrift Lato (selbst gehostet): 400 Fließtext, 700 Betonung, 900 nur Überschriften.</p>
@foreach (['display' => 'Display', 'h1' => 'Überschrift 1', 'h2' => 'Überschrift 2', 'h3' => 'Überschrift 3', 'h4' => 'Überschrift 4', 'body-lg' => 'Lesetext groß', 'body' => 'Fließtext', 'small' => 'Meta und Labels', 'caption' => 'Caption'] as $size => $label)
    <p style="font-size:var(--font-size-{{ $size }});max-width:none;margin-bottom:var(--space-2)"><strong>{{ $label }}</strong> <code>--font-size-{{ $size }}</code></p>
@endforeach
