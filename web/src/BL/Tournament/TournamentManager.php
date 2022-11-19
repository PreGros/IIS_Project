<?php

namespace App\BL\Tournament;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

use App\BL\Util\AutoMapper;
use App\BL\Util\StringUtil;
use App\BL\Tournament\TournamentModel;
use App\BL\Tournament\TournamentTableModel;
use App\BL\Util\DataTableState;
use App\DAL\Entity\Tournament;
use App\DAL\Entity\TournamentType;

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
        $tournament = AutoMapper::map($tournamentModel, Tournament::class, ['id'], false);
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

    //  /**
    //  * @return \Traversable<TournamentModel>
    //  */
    // public function getTournaments(DataTableState $state): \Traversable
    // {
    //     /** @var \App\DAL\Repository\TournamentRepository */
    //     $repo = $this->entityManager->getRepository(Tournament::class);
        
    //     $paginator = $repo->findTableData(
    //         $state->getLimit(),
    //         $state->getStart(),
    //         $state->getOrderColumn(),
    //         $state->isAsceding(),
    //         $state->getSearch(),
    //         ParticipantType::getByName($state->getSearch())
    //     );
    //     $state->setCount($paginator->count());

    //     foreach ($paginator as $entity){
    //         yield AutoMapper::map($entity, TournamentModel::class, trackEntity: false);
    //     }
    // }

    /**
     * @return \Traversable<TournamentTableModel>
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
            /** @var TournamentTableModel */
            $tournamentModel = AutoMapper::map($entity['tournament'], TournamentTableModel::class, trackEntity: false);
            $tournamentModel->setCreatedById($entity['tournament']->getCreatedBy()->getId());
            $tournamentModel->setCreatedByNickName($entity['tournament']->getCreatedBy()->getNickname());
            $tournamentModel->setApproved((bool)$entity['approved']);
            yield $tournamentModel;
        }
    }

    public function getTournament(int $id): TournamentModel
    {
        /** @var \App\DAL\Repository\TournamentRepository */
        $repo = $this->entityManager->getRepository(Tournament::class);
        
        $tournament = $repo->find($id);

        /** @var \App\BL\Tournament\TournamentModel */
        $tournamentModel = AutoMapper::map($tournament, \App\BL\Tournament\TournamentModel::class, trackEntity: true);
        $tournamentModel->setCreatedByNickName($tournament->getCreatedBy()->getNickname());
        $tournamentModel->setCreatedById($tournament->getCreatedBy()->getId());
        return $tournamentModel;
    }

    public function deleteTournament(int $id)
    {
        /** @var \App\DAL\Repository\TournamentRepository */
        $repo = $this->entityManager->getRepository(Tournament::class);
        $tournament = $this->entityManager->getReference(\App\DAL\Entity\Tournament::class, $id);
        $repo->remove($tournament, true);
    }

    public function updateTournament(TournamentModel $tournamentModel)
    {
        /** @var Tournament */
        $tournament = AutoMapper::map($tournamentModel, Tournament::class, trackEntity: false);
        
        $this->entityManager->persist($tournament);
        $this->entityManager->flush();
    }

    /**
     * @return \Traversable<TournamentType>
     */
    public function getTournamentTypes(): \Traversable
    {
        /** @var \App\DAL\Repository\TournamentTypeRepository */
        $repo = $this->entityManager->getRepository(TournamentType::class);

        foreach ($repo->findAll() as $type){
            yield AutoMapper::map($type, TournamentTypeModel::class, trackEntity: false);
        }
    }

    public function deleteTournamentType(int $id)
    {
        /** @var \App\DAL\Repository\TournamentTypeRepository */
        $repo = $this->entityManager->getRepository(TournamentType::class);

        $type = $this->entityManager->getReference(TournamentType::class, $id);
        $repo->remove($type, true);
    }

    public function createTournamentType(TournamentTypeModel $typeModel)
    {
        /** @var \App\DAL\Repository\TournamentTypeRepository */
        $repo = $this->entityManager->getRepository(TournamentType::class);

        $type = AutoMapper::map($typeModel, TournamentType::class, trackEntity: false);
        $repo->save($type, true);
    }
}
