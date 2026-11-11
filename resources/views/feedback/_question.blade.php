@php
    $id = $question['id'];
    $name = 'a_'.$id;
    $type = $question['type'];
    $required = ! empty($question['required']);
    $hint = ($question['hint'] ?? '') !== '' ? $question['hint'] : null;
    $text = $question['text'];
@endphp

<div style="margin-bottom:var(--space-6)">
    @if ($type === 'scale')
        @php
            $faces = ['😞', '🙁', '😐', '🙂', '😀'];
            $labels = $question['labels'] ?? \App\Modules\Feedback\Support\FeedbackDefaults::SCALE_LABELS;
            $options = [];
            foreach (range(1, 5) as $n) {
                $options[$n] = (! empty($question['smileys']) ? $faces[$n - 1].' ' : '').$n.' – '.($labels[$n - 1] ?? '');
            }
        @endphp
        <x-vdbs.forms.radio-group :name="$name" :legend="$text" :options="$options" :hint="$hint" :required="$required" :optional="! $required" />
    @elseif ($type === 'yesno')
        <x-vdbs.forms.radio-group :name="$name" :legend="$text" :options="['yes' => 'Ja', 'no' => 'Nein']" layout="inline" :hint="$hint" :required="$required" :optional="! $required" />
    @elseif ($type === 'choice')
        <x-vdbs.forms.radio-group :name="$name" :legend="$text" :options="array_combine($question['options'], $question['options'])" :hint="$hint" :required="$required" :optional="! $required" />
    @elseif ($type === 'multi')
        <x-vdbs.forms.checkbox-group :name="$name" :legend="$text" :options="array_combine($question['options'], $question['options'])" :hint="$hint" :required="$required" :optional="! $required" />
    @elseif ($type === 'text')
        <x-vdbs.forms.textarea :name="$name" :label="$text" :rows="3" maxlength="2000" :counter="true" :hint="$hint" :required="$required" :optional="! $required" />
    @elseif ($type === 'name')
        <x-vdbs.forms.input :name="$name" :label="$text" maxlength="150" :hint="$hint" :optional="true" autocomplete="off" />
    @endif
</div>
