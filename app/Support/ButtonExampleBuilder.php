<?php

namespace App\Support;

final class ButtonExampleBuilder
{
    private const VARIANTS = [
        'primary' => 'Primär',
        'secondary' => 'Sekundär',
        'quiet' => 'Ruhig',
        'danger' => 'Gefahr',
        'accent' => 'Akzent',
    ];

    private const SIZES = [
        'sm' => 'Klein',
        'md' => 'Mittel',
        'lg' => 'Groß',
    ];

    private const ELEMENTS = [
        'button' => 'Button',
        'link' => 'Link',
    ];

    /**
     * @return array<string, string>
     */
    public function variants(): array
    {
        return self::VARIANTS;
    }

    /**
     * @return array<string, string>
     */
    public function sizes(): array
    {
        return self::SIZES;
    }

    /**
     * @return array<string, string>
     */
    public function elements(): array
    {
        return self::ELEMENTS;
    }

    /**
     * @return array{
     *     label: string,
     *     variant: string,
     *     size: string,
     *     element: string,
     *     icon: string|null,
     *     class: string,
     *     code: string
     * }
     */
    public function build(
        ?string $label,
        ?string $variant,
        ?string $size,
        ?string $element,
        ?string $icon,
    ): array {
        $label = trim((string) $label);
        $label = $label !== ''
            ? mb_substr($label, 0, 80)
            : 'Speichern';

        $variant = array_key_exists(
            (string) $variant,
            self::VARIANTS,
        )
            ? (string) $variant
            : 'primary';

        $size = array_key_exists(
            (string) $size,
            self::SIZES,
        )
            ? (string) $size
            : 'md';

        $element = array_key_exists(
            (string) $element,
            self::ELEMENTS,
        )
            ? (string) $element
            : 'button';

        $icon = trim((string) $icon);
        $icon = $icon !== ''
            ? $icon
            : null;

        $classes = ['btn'];

        if ($variant !== 'primary') {
            $classes[] = 'btn--'.$variant;
        }

        if ($size !== 'md') {
            $classes[] = 'btn--'.$size;
        }

        $class = implode(' ', $classes);
        $escapedLabel = e($label);

        $iconCode = $icon !== null
            ? sprintf(
                '    <x-vdbs.icon name="%s" size="18" />'.PHP_EOL,
                e($icon),
            )
            : '';

        if ($element === 'link') {
            $code = sprintf(
                '<a class="%s" href="#">'.PHP_EOL
                .'%s'
                .'    %s'.PHP_EOL
                .'</a>',
                $class,
                $iconCode,
                $escapedLabel,
            );
        } else {
            $code = sprintf(
                '<button class="%s" type="button">'.PHP_EOL
                .'%s'
                .'    %s'.PHP_EOL
                .'</button>',
                $class,
                $iconCode,
                $escapedLabel,
            );
        }

        return [
            'label' => $label,
            'variant' => $variant,
            'size' => $size,
            'element' => $element,
            'icon' => $icon,
            'class' => $class,
            'code' => $code,
        ];
    }
}
