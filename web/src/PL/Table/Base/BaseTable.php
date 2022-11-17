<?php

namespace App\PL\Table\Base;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

abstract class BaseTable
{
    protected array $options;

    protected array $columns;

    protected array $data;

    private Environment $twig;

    /** for column renderes, twig environment is needed */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * returns table columns for render
     * @return array<array<string,mixed>>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * returns table data for render
     * @return array<array<string,mixed>>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * inits table - resolves options and gets table data
     * inits table before render
     * @param array $options
     * @return self
     */
    public function init(array $options = []): self
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $resolver->resolve($options);
        $this->options = $options;
        $this->data = iterator_to_array($this->setData());

        return $this;
    }

    /**
     * function to check options
     * for usage override in child
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {}

    /**
     * sets table data
     * @return \Traversable iterator over table data
     */
    abstract protected function setData(): \Traversable;

    protected function addColumn(string $name, string $label, bool $renderRaw = false)
    {
        $this->columns[$name] = ['label' => $label, 'raw' => $renderRaw];
        return $this;
    }

    /**
     * renders column from twig in string
     * values will be accessible as values.<key> in template
     * @param string $template
     * @param array $values - values for template
     * @return string rendered column
     */
    protected function renderTwigStringColumn(string $template, array $values = []): string
    {
        return $this->twig->render('/_base/twig_string.html.twig', [
            'template' => $template,
            'row' => $values
        ]);
    }

    /**
     * renders column from twig file
     * values will be accessible as values.<key> in template
     * @param string $template
     * @param array $values - values for template
     * @return string rendered column
     */
    protected function renderTwigColumn(string $templatePath, array $values = []): string
    {
        return $this->twig->render($templatePath, [
            'values' => $values
        ]);
    }
}
