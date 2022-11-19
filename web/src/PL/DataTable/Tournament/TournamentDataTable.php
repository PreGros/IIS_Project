<?php

namespace App\PL\DataTable\Tournament;

use App\BL\Tournament\TournamentManager;
use App\BL\Util\DataTableAdapter;
use App\BL\Util\DataTableState;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableFactory;
use Omines\DataTablesBundle\Column\NumberColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigStringColumn;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TournamentDataTable
{
    private DataTableFactory $factory;

    private UrlGeneratorInterface $router;

    private TournamentManager $tournamentManager;

    public function __construct(DataTableFactory $dataTableFactory, UrlGeneratorInterface $router, TournamentManager $tournamentManager)
    {
        $this->factory = $dataTableFactory;
        $this->router = $router;
        $this->tournamentManager = $tournamentManager;
    }

    public function create(): DataTable
    {
        return $this->factory->create()
            ->add('name', TwigStringColumn::class, [
                'label' => 'Name',
                'searchable' => true,
                'orderable' => true,
                'template' => '<a href="{{ row.info }}">{{ row.displayName }}</a>'
            ])
            ->add('createdByNickName', TwigStringColumn::class, [
                'label' => 'Created By',
                'searchable' => true,
                'orderable' => true,
                'template' => '<a href="{{ row.createdByInfo }}">{{ row.createdByNickName }}</a>'
            ])
            ->add('participantType', TextColumn::class, [
                'label' => 'Participant Type',
                'searchable' => true,
                'orderable' => true
            ])
            ->add('date', DateTimeColumn::class, [
                'label' => 'Date',
                'format' => 'j. n. Y G:i',
                'searchable' => true,
                'orderable' => true
            ])
            ->add('action', TwigStringColumn::class, [
                'label' => 'Action',
                'searchable' => false,
                'orderable' => false,
                'template' => 
                    //'<a href="{{ row.info }}" class="btn btn-secondary">Info</a>' . 
                    //' ' .
                    '<a href="{{ row.edit }}" class="btn btn-secondary">Edit</a>' .
                    ' ' .
                    '<a href="{{ row.delete }}" class="btn btn-danger" onclick="return confirm(\'U sure?\')">Delete</a>'
                ])
            ->createAdapter(DataTableAdapter::class, [
                'callback' => fn(DataTableState $state) => $this->parseTableData($state),
                'objectForCallback' => $this
            ]);
    }

    private function parseTableData(DataTableState $state): array
    {
        $tableData = [];
        foreach ($this->tournamentManager->getTournaments($state) as $data){
            $tableData[] = [
                'info' => $this->router->generate('tournament_info', ['id' => $data->getId()]),
                'delete' => $this->router->generate('tournament_delete', ['id' => $data->getId()]),
                'edit' => $this->router->generate('tournament_edit', ['id' => $data->getId()]),
                'displayName' => $data->getName(),
                'participantType' => $data->getParticipantType(false)->label(),
                'date' => $data->getDate(),
                'createdByNickName' => $data->getCreatedByNickName(),
                'createdByInfo' => $this->router->generate('user_info', ['id' => $data->getCreatedById()])
            ];
        }
        return $tableData;
    }
}
