<?php

namespace App\PL\TwigExtensions;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FlashMessagesRenderer extends AbstractExtension
{
    /**
     * sets flash message render function to be visible in twig templates
     * @return array new functions to be added
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_flash_messages', [$this, 'flashRenderer'], ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    /**
     * renders flash messages to twig template
     * @param Environment $environment twig render environment
     * @return string rendered messages
     */
    public function flashRenderer(Environment $environment): string
    {
        return $environment->render('/_base/flash_messages.html.twig');
    }
}
