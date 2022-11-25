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
            ->addColumn('nickname', 'Nickname', true)
            ->addColumn('email', 'Email')
            ->addColumn('isLeader', 'Is Leader', true);
        
        if ($this->options['canModify']){
            $this->addColumn('action', 'Action', true);
        }

        return $this;
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefault('teamId', null)
            ->setDefault('canModify', false)
            ->setAllowedTypes('teamId', 'int')
            ->setAllowedTypes('canModify', 'bool');
    }

    protected function setData(): \Traversable
    {
        foreach ($this->teamManager->getTeamMembers($this->options['teamId']) as $member){
            $data = [
                'email' => $member->getEmail(),
                //'nickname' => $member->getNickname(),
                'nickname' => 
                    $this->renderTwigStringColumn('<a href="{{ row.url }}">{{ row.nickname }}</a>', [
                        'url' => $this->router->generate('user_info', ['id' => $member->getId()]),
                        'nickname' => $member->getNickname()
                    ]),
                'isLeader' => $member->isLeader() ? '<i class="bi bi-check-lg" title="Leader"/>' : '<i class="bi bi-x-lg" title="Not Leader"/>'
            ];

            if ($this->options['canModify']){
                $data['action'] =
                    ($member->isLeader() ?
                        'Cannot delete leader' :
                        $this->renderTwigStringColumn(
                            '<a href="{{ row.url }}" class="btn btn-danger" onclick="return confirm(\'Are you sure?\')">Delete</a>', [
                                'url' => $this->router->generate('delete_member', ['teamId' => $this->options['teamId'], 'memberId' => $member->getId()])
                            ]
                        )
                    );
            }

            yield $data;
        }
    }

}
