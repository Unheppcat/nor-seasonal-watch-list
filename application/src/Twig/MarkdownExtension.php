<?php

namespace App\Twig;

use ParsedownExtra;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkdownExtension extends AbstractExtension
{
    private ParsedownExtra $parsedown;

    public function __construct()
    {
        $this->parsedown = new ParsedownExtra();
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('markdown', [$this, 'markdown'], ['is_safe' => ['html']]),
        ];
    }

    public function markdown(?string $content): string
    {
        if ($content === null) {
            return '';
        }

        return $this->parsedown->text($content);
    }
}
