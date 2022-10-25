<?php

namespace App\PL\DataTable\Team;

use App\BL\Team\TeamManager;
use App\BL\Util\DataTableAdapter;
use Doctrine\ORM\QueryBuilder;
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
        // return $this->factory->create()
        //     ->add('name', TextColumn::class, ['label' => 'Jméno', 'searchable' => true, 'orderable' => true])
        //     ->add('nickname', TextColumn::class, ['field' => 'u.nickname', 'label' => 'NickName vedoucí', 'searchable' => true, 'orderable' => true])
        //     ->add('memberCount', NumberColumn::class, ['label' => 'Počet členů', 'searchable' => true, 'orderable' => true, 'data' => function (\App\DAL\Entity\Team $data) {
        //         return $data->getMembers()->count() + 1;
        //     }])
        //     ->add('action', TwigStringColumn::class, ['label' => 'Akce', 'searchable' => false, 'orderable' => false, 'template' => '<button class="btn btn-secondary">Detail</button>'])
        //     ->createAdapter(ORMAdapter::class, [
        //         'entity' => \App\DAL\Entity\Team::class,
        //         'query' => function (QueryBuilder $builder) {
        //             $builder
        //                 ->select('t')
        //                 ->addSelect('u')
        //                 ->from(\App\DAL\Entity\Team::class, 't')
        //                 ->innerJoin('t.leader', 'u');
        //         }
        //     ]);
        return $this->factory->create()
            ->add('name', TextColumn::class, ['label' => 'Jméno', 'searchable' => true, 'orderable' => true])
            ->add('leaderNickName', TextColumn::class, ['label' => 'NickName vedoucí', 'searchable' => true, 'orderable' => true])
            ->add('memberCount', NumberColumn::class, ['label' => 'Počet členů', 'searchable' => true, 'orderable' => true])
            ->add('action', TwigStringColumn::class, ['label' => 'Akce', 'searchable' => false, 'orderable' => false, 'template' => '<a href="{{ row.edit }}" class="btn btn-secondary">Edit</button>'])
            ->createAdapter(DataTableAdapter::class, [
                'callback' => fn(int $limit) => $this->parseTableData($limit),
                'objectForCallback' => $this
            ]);
    }

    private function parseTableData(int $limit): array
    {
        $tableData = [];
        foreach ($this->teamManager->getTableData($limit) as $data){
            $tableData[] = [
                'edit' => $this->router->generate('team_edit', ['id' => $data->getId()]),
                'name' => $data->getName(),
                'leaderNickName' => $data->getLeaderNickName(),
                'memberCount' => $data->getMemberCount()
            ];
        }
        return $tableData;
    }
}
