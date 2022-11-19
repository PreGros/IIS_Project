<?php

namespace App\PL\Table\Tournament;

use App\BL\Tournament\TournamentManager;
use App\PL\Table\Base\BaseTable;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class TypesTable extends BaseTable
{
    private UrlGeneratorInterface $router;

    private TournamentManager $tournamentManager;

    public function __construct(Environment $environment, UrlGeneratorInterface $router, TournamentManager $tournamentManager)
    {
        parent::__construct($environment);
        $this->router = $router;
        $this->tournamentManager = $tournamentManager;
    }

    public function init(array $options = []): self
    {
        parent::init($options);

        $this
            ->addColumn('name', 'Name')
            ->addColumn('action', 'Action', true);

        return $this;
    }

    protected function setData(): \Traversable
    {
        foreach ($this->tournamentManager->getTournamentTypes() as $type){
            yield [
                'name' => $type->getName(),
                'action' =>
                    $this->renderTwigStringColumn('<a href="{{ row.url }}" class="btn btn-danger" onclick="return confirm(\'Are you sure?\')">Delete</a>', [
                        'url' => $this->router->generate('tournament_type_delete', ['id' => $type->getId()])
                    ])
            ];
        }
    }

}
