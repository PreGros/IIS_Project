<?php

namespace App\PL\Table\Team;

use App\BL\Team\TeamManager;
use App\PL\Table\Base\BaseTable;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class TeamTable extends BaseTable
{
    private UrlGeneratorInterface $router;

    private TeamManager $teamManager;

    public function __construct(Environment $environment, UrlGeneratorInterface $router, TeamManager $teamManager)
    {
        parent::__construct($environment);
        $this->router = $router;
        $this->teamManager = $teamManager;
    }

    public function init(array $options = []): self
    {
        parent::init($options);

        $this
            ->addColumn('name', 'Name', true)
            ->addColumn('isLeader', 'Is Leader', true);

        return $this;
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefault('userId', null)
            ->setAllowedTypes('userId', 'int');
    }

    protected function setData(): \Traversable
    {
        foreach ($this->teamManager->getTeamsByUser($this->options['userId']) as $team){
            yield [
                'name' => 
                    $this->renderTwigStringColumn('<a href="{{ row.url }}">{{ row.name }}</a>', [
                        'url' => $this->router->generate('team_info', ['id' => $team->getId()]),
                        'name' => $team->getName()
                    ]),
                'isLeader' => $team->isUserLeader() ? '<i class="bi bi-check-lg" title="Leader"/>' : '<i class="bi bi-x-lg" title="Not leader"/>'
            ];;
        }
    }

}
