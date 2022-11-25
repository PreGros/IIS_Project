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

    private bool $isAdmin;

    private bool $maxAchieved;

    private bool $matchesGenerated;

    public function __construct(DataTableFactory $dataTableFactory, UrlGeneratorInterface $router, TournamentManager $tournamentManager)
    {
        $this->factory = $dataTableFactory;
        $this->router = $router;
        $this->tournamentManager = $tournamentManager;
    }

    public function create(int $tournamentId, bool $isAdmin, bool $maxAchieved, bool $matchesGenerated): DataTable
    {
        $this->tournamentId = $tournamentId;
        $this->isAdmin = $isAdmin;
        $this->maxAchieved = $maxAchieved;
        $this->matchesGenerated = $matchesGenerated;

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
                    '<i class="bi {% if row.isApproved %} bi-check-lg {% else %} bi-x-lg {% endif %}" title="{% if row.isApproved %}Approved{% else %}Not Approved{% endif %}"></i>'.
                    ' ' .
                    '{% if row.canApprove %}' .
                    ($matchesGenerated ? '<span title="Tournament already started">' : ($maxAchieved ? '<span title="Maximum number of participants already achieved">' : '')) .
                        '<a href="{% if row.isApproved %}{{ row.disapproveURL }}{% else %}{{ row.approveURL }}{% endif %}" class="btn btn-secondary {% if not row.deactivated %}" {% else %}disabled" aria-disabled="true" {% endif %}">{% if row.isApproved %}Disapprove{% else %}Approve{% endif %}</a>' .
                    (($maxAchieved || $matchesGenerated)? '</span>' : '') .
                    '{% endif %}'
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
                'canApprove' => $data->getCreatedByCurrentUser() || $this->isAdmin,
                //'deactivated' => $this->tournamentManager->checkUserDeactivated($data->getIdOfParticipant(), $data->getIsTeam()),
                'deactivated' => $data->getDeactivatedParticipant() || ($this->maxAchieved && !$data->getApproved()) || $this->matchesGenerated,
                'approveURL' => $this->router->generate('participant_approve', ['tId' => $this->tournamentId, 'pId' => $data->getId()]),
                'disapproveURL' => $this->router->generate('participant_disapprove', ['tId' => $this->tournamentId, 'pId' => $data->getId()])
            ];
        }
        return $tableData;
    }
}
