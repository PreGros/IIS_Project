<?php

namespace App\PL\DataTable\Team;

use App\BL\Team\TeamManager;
use App\BL\Util\DataTableAdapter;
use App\BL\Util\DataTableState;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableFactory;
use Omines\DataTablesBundle\Column\NumberColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigStringColumn;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TeamDataTable
{
    private DataTableFactory $factory;

    private UrlGeneratorInterface $router;

    private TeamManager $teamManager;

    public function __construct(DataTableFactory $dataTableFactory, UrlGeneratorInterface $router, TeamManager $teamManager)
    {
        $this->factory = $dataTableFactory;
        $this->router = $router;
        $this->teamManager = $teamManager;
    }

    public function create(): DataTable
    {
        return $this->factory->create()
            ->add('name', TextColumn::class, ['label' => 'Name', 'searchable' => true, 'orderable' => true])
            ->add('leaderNickName', TextColumn::class, ['label' => 'Leader NickName', 'searchable' => true, 'orderable' => true])
            ->add('memberCount', NumberColumn::class, ['label' => 'Count of members', 'searchable' => true, 'orderable' => true])
            ->add('action', TwigStringColumn::class, [
                'label' => 'Akce',
                'searchable' => false,
                'orderable' => false,
                'template' => 
                    '<a href="{{ row.edit }}" class="btn btn-secondary">Edit</a>' .
                    ' ' .
                    '<a href="{{ row.delete }}" class="btn btn-danger" onclick="return confirm(\'U sure?\')">Delete</a>' .
                    ' ' .
                    '<a href="{{ row.members }}" class="btn btn-primary">Members</a>'
                ])
            ->createAdapter(DataTableAdapter::class, [
                'callback' => fn(DataTableState $state) => $this->parseTableData($state),
                'objectForCallback' => $this
            ]);
    }

    private function parseTableData(DataTableState $state): array
    {
        $tableData = [];
        foreach ($this->teamManager->getTeams($state) as $data){
            $tableData[] = [
                'delete' => $this->router->generate('team_delete', ['id' => $data->getId()]),
                'edit' => $this->router->generate('team_edit', ['id' => $data->getId()]),
                'members' => $this->router->generate('team_members', ['id' => $data->getId()]),
                'name' => $data->getName(),
                'leaderNickName' => $data->getLeaderNickName(),
                'memberCount' => $data->getMemberCount()
            ];
        }
        return $tableData;
    }
}
