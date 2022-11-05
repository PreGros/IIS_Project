<?php

namespace App\PL\Table\Base;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TableRenderer extends AbstractExtension
{
    /**
     * sets table render function to be visible in twig templates
     * @return array new functions to be added
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('table_render', [$this, 'tableRender'], ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    /**
     * renders table to twig template
     * @param Environment $environment twig render environment
     * @param BaseTable $table table to be rendered
     * @return string rendered table
     */
    public function tableRender(Environment $environment, BaseTable $table): string
    {
        return $environment->render('/_base/table.html.twig', [
            'header' => $table->getColumns(),
            'data' => $table->getData()
        ]);
    }
}
