<?php

namespace App\PL\Table\Tournament;

use App\BL\Tournament\TournamentManager;
use App\PL\Table\Base\BaseTable;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class TournamentUserTable extends BaseTable
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
            ->addColumn('name', 'Name', true)
            ->addColumn('approved', 'Is approved', true)
            ->addColumn('winner', 'Is winner', true);

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
        foreach ($this->tournamentManager->getTournamentsByUserParticipant($this->options['userId']) as $tournament){
            yield [
                'name' => 
                    $this->renderTwigStringColumn('<a href="{{ row.url }}">{{ row.name }}</a>', [
                        'url' => $this->router->generate('tournament_info', ['id' => $tournament->getId()]),
                        'name' => $tournament->getName()
                    ]),
                'approved' => $tournament->getApproved() ? '<i class="bi bi-check-lg" title="Approved"/>' : '<i class="bi bi-x-lg" title="Not approved"/>',
                'winner' => $tournament->isUserWinner() === null ? '<i class="bi bi-dash-lg" title="Tournament not finished"/>' :
                    ($tournament->isUserWinner() ? '<i class="bi bi-check-lg" title="Winner"/>' : '<i class="bi bi-x-lg" title="Not winner"/>')
            ];;
        }
    }

}
