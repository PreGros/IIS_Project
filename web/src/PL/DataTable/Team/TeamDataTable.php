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

    private bool $allModifiable;

    public function __construct(DataTableFactory $dataTableFactory, UrlGeneratorInterface $router, TeamManager $teamManager)
    {
        $this->factory = $dataTableFactory;
        $this->router = $router;
        $this->teamManager = $teamManager;
    }

    public function create(bool $allModifiable = false): DataTable
    {
        $this->allModifiable = $allModifiable;

        return $this->factory->create()
            ->add('name', TwigStringColumn::class, [
                'label' => 'Name',
                'searchable' => true,
                'orderable' => true,
                'template' => '<a href="{{ row.info }}">{{ row.name }}</a>'
            ])
            ->add('leaderNickName', TwigStringColumn::class, [
                'label' => 'Leader Nickname',
                'searchable' => true,
                'orderable' => true,
                'template' => '<a href="{{ row.leaderInfo }}">{{ row.leaderNickName }}</a>'
            ])
            ->add('memberCount', NumberColumn::class, ['label' => 'Count of Members', 'searchable' => true, 'orderable' => true])
            ->add('action', TwigStringColumn::class, [
                'label' => 'Action',
                'searchable' => false,
                'orderable' => false,
                'template' =>
                    '{% if row.modifiable %}' .
                    '<a href="{{ row.edit }}" class="btn btn-secondary">Edit</a>' .
                    ' ' .
                    '<a href="{{ row.delete }}" class="btn btn-danger" onclick="return confirm(\'U sure?\')">Delete</a>' .
                    ' ' .
                    '{% endif %}'.
                    '<a href="{{ row.info }}" class="btn btn-primary">Info</a>'
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
                'name' => $data->getName(),
                'info' => $this->router->generate('team_info', ['id' => $data->getId()]),
                'leaderNickName' => $data->getLeaderNickName(),
                'leaderInfo' => $this->router->generate('user_info', ['id' => $data->getLeaderId()]),
                'memberCount' => $data->getMemberCount(),
                'modifiable' => ($this->allModifiable || $data->isCurrentUserLeader())
            ];
        }
        return $tableData;
    }
}
