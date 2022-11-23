<?php

namespace App\BL\Tournament;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

use App\BL\Util\AutoMapper;
use App\BL\Util\StringUtil;
use App\BL\Tournament\TournamentModel;
use App\BL\Tournament\TournamentTableModel;
use App\BL\Util\DataTableState;
use App\DAL\Entity\MatchParticipant;
use App\DAL\Entity\Team;
use App\DAL\Entity\Tournament;
use App\DAL\Entity\TournamentMatch;
use App\DAL\Entity\TournamentParticipant;
use App\DAL\Entity\TournamentType;
use DateTime;
use Symfony\Component\Form\Test\FormInterface;

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

        $tournament->setTournamentType(
            AutoMapper::map(
                $tournamentModel->getTournamentTypeModel(),
                \App\DAL\Entity\TournamentType::class,
                trackEntity: false
            )
        );

        $this->entityManager->persist($tournament);
        $this->entityManager->flush();
    }

    /**
     * @return \Traversable<TournamentTableModel>
     */
    public function getTournaments(DataTableState $state): \Traversable
    {
        /** @var \App\DAL\Repository\TournamentRepository */
        $repo = $this->entityManager->getRepository(Tournament::class);
        /** @var \App\BL\User\UserModel */
        $user = $this->security->getUser();
        
        $paginator = $repo->findTableData(
            $user?->getId(),
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
            $tournamentModel->setCurrentUserRegistrationState($entity['approved_participant']);
            $tournamentModel->setCreatedByCurrentUser($tournamentModel->getCreatedById()  === $user?->getId());
            $tournamentModel->setTournamentTypeName($entity['type_name']);
            yield $tournamentModel;
        }
    }

    public function getTournament(int $id): TournamentModel
    {
        /** @var \App\DAL\Repository\TournamentRepository */
        $repo = $this->entityManager->getRepository(Tournament::class);
        
        /** @var \App\BL\User\UserModel */
        $user = $this->security->getUser();
        $tournament = $repo->findInfo($id, $user?->getId());

        /** @var \App\BL\Tournament\TournamentModel */
        $tournamentModel = AutoMapper::map($tournament['tournament'], \App\BL\Tournament\TournamentModel::class, trackEntity: true);
        $tournamentModel->setCreatedByNickName($tournament['tournament']->getCreatedBy()->getNickname());
        $tournamentModel->setCreatedById($tournament['tournament']->getCreatedBy()->getId());
        $tournamentModel->setApproved($tournament['tournament']->getApprovedBy() !== null);
        $tournamentModel->setCurrentUserRegistrationState($tournament['approved_participant']);
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
    public function getTournamentTypes(bool $trackEntity = false): \Traversable
    {
        /** @var \App\DAL\Repository\TournamentTypeRepository */
        $repo = $this->entityManager->getRepository(TournamentType::class);

        foreach ($repo->findAll() as $type){
            yield AutoMapper::map($type, TournamentTypeModel::class, trackEntity: $trackEntity);
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

    public function approveTournament(int $id){
        /** @var \App\DAL\Repository\TournamentRepository */
        $repo = $this->entityManager->getRepository(Tournament::class);
        
        $tournament = $repo->find($id);
        $tournament->setApprovedBy(
            AutoMapper::map(
                $this->security->getUser(),
                \App\DAL\Entity\User::class,
                trackEntity: false
            )
        );

        $repo->save($tournament, true);
    }

    public function disapproveTournament(int $id){
        /** @var \App\DAL\Repository\TournamentRepository */
        $repo = $this->entityManager->getRepository(Tournament::class);
        
        $tournament = $repo->find($id);
        $tournament->setApprovedBy(null);

        $repo->save($tournament, true);
    }

    public function addTournamentParticipantCurrUser(int $tournamentId){
        /** @var \App\DAL\Repository\TournamentRepository */
        $repo = $this->entityManager->getRepository(Tournament::class);
        
        $tournament = $repo->find($tournamentId);

        $participant = new \App\DAL\Entity\TournamentParticipant;
        $participant
            ->setApproved(false)
            ->setTournament($tournament)
            ->setSignedUpUser(
                AutoMapper::map(
                    $this->security->getUser(),
                    \App\DAL\Entity\User::class,
                    trackEntity: false
                )
            );

        $this->entityManager->persist($participant);
        $this->entityManager->flush();
    }

    public function addTournamentParticipantTeam(int $tournamentId, int $teamId){
        /** @var \App\DAL\Repository\TournamentRepository */
        $repo = $this->entityManager->getRepository(Tournament::class);
        
        $tournament = $repo->find($tournamentId);

        $participant = new \App\DAL\Entity\TournamentParticipant;
        $participant
            ->setApproved(false)
            ->setTournament($tournament)
            ->setSignedUpTeam(
                $this->entityManager->getRepository(Team::class)->find($teamId)
            );

        $this->entityManager->persist($participant);
        $this->entityManager->flush();
    }

    public function removeTournamentParticipant(int $tournamentId, int $currUserId){
        /** @var \App\DAL\Repository\TournamentParticipantRepository */
        $repo = $this->entityManager->getRepository(TournamentParticipant::class);

        $participant = $repo->findOneBy(['tournament' => $tournamentId, 'signedUpUser' => $currUserId]);

        if($participant === null){
            $participant = $repo->findTeam($tournamentId, $currUserId);
        }

        $repo->remove($participant, true);
    }

    /*** For different danger warnings */
    public function checkOnCreateValidity(TournamentModel $tournament, string &$errMessage) : bool
    {
        if (!$this->checkTeamMemberCount($errMessage, $tournament, $tournament->getMinTeamMemberCount(), $tournament->getMaxTeamMemberCount())){
            return false;
        }

        if (!$this->checkParticipantCount($errMessage, $tournament->getMinParticipantCount(), $tournament->getMaxParticipantCount())){
            return false;
        }
        
        if (!$this->checkRegStartValidity($errMessage, $tournament->getRegistrationDateStart(), $tournament->getRegistrationDateEnd(), $tournament->getDate())){
            return false;
        }
        
        return true;
    }

    public function checkRegStartValidity(string &$errMessage, DateTime $registrationStart,DateTime $registrationEnd, DateTime $tournamentStart) : bool
    {
        if ($registrationStart >= $registrationEnd){
            $errMessage = "Registration start date needs to be before registration end!";
            return false;
        }
        if ($registrationStart >= $tournamentStart){
            $errMessage = "Registration start date needs to be before tournament start!";
            return false;
        }
        if ($registrationEnd >= $tournamentStart){
            $errMessage = "Registration end date needs to be before tournament start!";
            return false;
        }
        return true;
    }

    public function checkTeamMemberCount(string &$errMessage, TournamentModel $tournament, ?int $numberA, ?int $numberB) : bool
    {
        /** if max number is not defined than it is set to minimum number (minimum = maximum) */
        if ($numberA === NULL){
            $tournament->setMinTeamMemberCount(0);
            $numberA = 0;
        }
        if ($numberB === NULL){
            $errMessage = "Maximum team member count needs to be specified!";
            return false;
        }

        if ($numberA > $numberB){
            $errMessage = "Minimum team member count cannot be bigger than maximum team member count!";
            return false;
        }
        return true;
    }

    public function checkParticipantCount(string &$errMessage, ?int $numberA, ?int $numberB) : bool
    {
        if ($numberA > $numberB){
            $errMessage = "Minimum participant count cannot be bigger than maximum participant count!";
            return false;
        }
        return true;
    }

    public function checkOnCreateTypeValidity(TournamentTypeModel $tournamentType, string &$errMessage) : bool
    {
        if (!$this->checkUniqueTypeName($errMessage, $tournamentType->getName())){
            return false;
        }
        
        return true;
    }

    public function checkUniqueTypeName(string &$errMessage, string $typeName) : bool
    {
        /** @var \App\DAL\Repository\TournamentTypeRepository */
        $repo = $this->entityManager->getRepository(\App\DAL\Entity\TournamentType::class);
        /** cannot get member by reference, so find by ids is performed */
        $type = $repo->findOneBy(['name' => $typeName]);

        if ($type !== NULL){
            $errMessage = "This tournament type already exists!";
            return false;
        }

        return true;
    }

    /**
     * @return \Traversable<TournamentParticipantTableModel>
     */
    public function getTournamentParticipants(DataTableState $state, int $tournamentId): \Traversable
    {
        /** @var \App\DAL\Repository\TournamentParticipantRepository */
        $repo = $this->entityManager->getRepository(TournamentParticipant::class);
        /** @var \App\BL\User\UserModel */
        $user = $this->security->getUser();
        
        $paginator = $repo->findTableData(
            $user?->getId(),
            $state->getLimit(),
            $state->getStart(),
            $state->getOrderColumn(),
            $state->isAsceding(),
            $state->getSearch(),
            $tournamentId
        );
        $state->setCount($paginator->count());

        foreach ($paginator as $entity){
            /** @var TournamentParticipant */
            $participant = $entity['participant'];
            /** @var TournamentParticipantTableModel */
            $tournamentParticipantModel = AutoMapper::map($participant, TournamentParticipantTableModel::class, trackEntity: false);
            $tournamentParticipantModel->setCreatedByCurrentUser((bool)$entity['createdByCurrUser']);
            $tournamentParticipantModel->setIsTeam($participant->getSignedUpTeam()!==null);
            $tournamentParticipantModel->setIdOfParticipant($tournamentParticipantModel->getIsTeam() ? $participant->getSignedUpTeam()->getId() : $participant->getSignedUpUser()->getId());
            $tournamentParticipantModel->setNameOfParticipant($tournamentParticipantModel->getIsTeam() ? $participant->getSignedUpTeam()->getName() : $participant->getSignedUpUser()->getNickname() );
            yield $tournamentParticipantModel;
        }
    }

    public function approveParticipant(int $id){
        /** @var \App\DAL\Repository\TournamentParticipantRepository */
        $repo = $this->entityManager->getRepository(TournamentParticipant::class);
        
        $participant = $repo->find($id);
        $participant->setApproved(true);

        $repo->save($participant, true);
    }

    public function disapproveParticipant(int $id){
        /** @var \App\DAL\Repository\TournamentParticipantRepository */
        $repo = $this->entityManager->getRepository(TournamentParticipant::class);
        
        $participant = $repo->find($id);
        $participant->setApproved(false);

        $repo->save($participant, true);
    }

    public function getFormTournamentType() : array
    {
        $typesFormated = [];
        foreach ($this->getTournamentTypes(true) as $type){
            $typesFormated[$type->getName()] = $type;
        }
        return $typesFormated;
    }
    
    public function areTournamentMatchesGenerated(int $tournamentId) : bool
    {
        /** @var \App\DAL\Repository\TournamentMatchRepository */
        $repo = $this->entityManager->getRepository(TournamentMatch::class);
        
        $match = $repo->findOneBy(['tournament' => $tournamentId]);

        return $match !== null;
    }
}
