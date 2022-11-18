<?php

namespace App\BL\Tournament;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

use App\BL\Util\AutoMapper;
use App\BL\Util\StringUtil;
use App\BL\Tournament\TournamentModel;
use App\BL\Util\DataTableState;
use App\DAL\Entity\Tournament;

class TournamentManager
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security
    )
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function createTournament(TournamentModel $tournamentModel)
    {
        /** @var Tournament */
        $tournament = AutoMapper::map($tournamentModel, Tournament::class, trackEntity: false);
        $tournament->setCreatedBy(
            AutoMapper::map(
                $this->security->getUser(),
                \App\DAL\Entity\User::class,
                trackEntity: false
            )
        );

        //TODO: setTournamentType

        $this->entityManager->persist($tournament);
        $this->entityManager->flush();
    }

     /**
     * @return \Traversable<TournamentModel>
     */
    public function getTournaments(DataTableState $state): \Traversable
    {
        /** @var \App\DAL\Repository\TournamentRepository */
        $repo = $this->entityManager->getRepository(Tournament::class);
        
        $paginator = $repo->findTableData(
            $state->getLimit(),
            $state->getStart(),
            $state->getOrderColumn(),
            $state->isAsceding(),
            $state->getSearch(),
            ParticipantType::getByName($state->getSearch())
        );
        $state->setCount($paginator->count());

        foreach ($paginator as $entity){
            yield AutoMapper::map($entity, TournamentModel::class, trackEntity: false);
        }
    }

    public function getTournament(int $id): TournamentModel
    {
        /** @var \App\DAL\Repository\TournamentRepository */
        $repo = $this->entityManager->getRepository(Tournament::class);
        
        $tournament = $repo->find($id);

        /** @var \App\BL\Tournament\TournamentModel */
        return AutoMapper::map($tournament, \App\BL\Tournament\TournamentModel::class, trackEntity: true);
    }
}
