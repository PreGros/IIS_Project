<?php

namespace App\PL\Table\Team;

use App\BL\Team\TeamManager;
use App\PL\Table\Base\BaseTable;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class MembersTable extends BaseTable
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
            ->addColumn('email', 'Email')
            ->addColumn('nickname', 'Nickname')
            ->addColumn('isLeader', 'Is leader?')
            ->addColumn('action', 'Action', true);

        return $this;
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefault('teamId', null)
            ->setAllowedTypes('teamId', 'int');
    }

    protected function setData(): \Traversable
    {
        foreach ($this->teamManager->getTeamMembers($this->options['teamId']) as $member){
            yield [
                'email' => $member->getEmail(),
                'nickname' => $member->getNickname(),
                'isLeader' => $member->isLeader() ? 'Yes' : 'No',
                'action' =>
                    $this->renderTwigStringColumn('<a href="{{ row.url }}" class="btn btn-primary">Detail</a>', [
                        'url' => $this->router->generate('user_info', ['id' => $member->getId()])
                    ]) .
                    ' ' .
                    ($member->isLeader() ?
                    'Cannot delete leader' :
                    $this->renderTwigStringColumn('<a href="{{ row.url }}" class="btn btn-danger">Delete</a>', [
                        'url' => $this->router->generate('delete_member', ['teamId' => $this->options['teamId'], 'memberId' => $member->getId()])
                    ]))
            ];
        }
    }

}
