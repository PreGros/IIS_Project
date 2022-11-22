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

class TournamentParticipantDataTable
{
    private DataTableFactory $factory;

    private UrlGeneratorInterface $router;

    private TournamentManager $tournamentManager;

    private int $tournamentId;

    public function __construct(DataTableFactory $dataTableFactory, UrlGeneratorInterface $router, TournamentManager $tournamentManager)
    {
        $this->factory = $dataTableFactory;
        $this->router = $router;
        $this->tournamentManager = $tournamentManager;
    }

    public function create(int $tournamentId): DataTable
    {
        $this->tournamentId = $tournamentId;

        $dataTable = $this->factory->create()
            ->add('name', TwigStringColumn::class, [
                'label' => 'Name',
                'searchable' => true,
                'orderable' => true,
                'template' => '<a href="{{ row.info }}">{{ row.displayName }}</a>'
            ])
            ->add('isApproved', TwigStringColumn::class, [
                'label' => 'Is Approved',
                'searchable' => false,
                'orderable' => true,
                'template' =>
                    '<i class="bi {% if row.isApproved %} bi-check-lg {% else %} bi-x-lg {% endif %}"></i>'.
                    ' ' .
                    '<a href="{% if row.isApproved %}{{ row.disapproveURL }}{% else %}{{ row.approveURL }}{% endif %}" class="btn btn-secondary">{% if row.isApproved %}Disapprove{% else %}Approve{% endif %}</a>'
            ]);


        return $dataTable->createAdapter(DataTableAdapter::class, [
                'callback' => fn(DataTableState $state) => $this->parseTableData($state),
                'objectForCallback' => $this
            ]);
    }

    private function parseTableData(DataTableState $state): array
    {
        $tableData = [];
        foreach ($this->tournamentManager->getTournamentParticipants($state, $this->tournamentId) as $data){
            $tableData[] = [
                'info' => $this->router->generate($data->getIsTeam() ? 'team_info' : 'user_info', ['id' => $data->getIdOfParticipant()]),
                'displayName' => $data->getNameOfParticipant(),
                'isApproved' => $data->getApproved(),
                //'approveURL' => $this->router->generate('participant_approve', ['id' => $data->getIdOfParticipant()]),
                'approveURL' => $this->router->generate('tournament_info', ['id' => $this->tournamentId]),
                //'disapproveURL' => $this->router->generate('participant_disapprove', ['id' => $data->getIdOfParticipant()]),
                'disapproveURL' => $this->router->generate('tournament_info', ['id' => $this->tournamentId])
                //'modifiable' => ($this->isCreator || $data->getCreatedByCurrentUser())
            ];
        }
        return $tableData;
    }
}
