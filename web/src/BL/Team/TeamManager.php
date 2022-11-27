<?php

namespace App\BL\Team;

use Doctrine\ORM\EntityManagerInterface;
use App\BL\Util\DataTableState;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

use App\BL\Util\AutoMapper;
use App\BL\Util\StringUtil;
use App\BL\Team\TeamModel;
use App\BL\Team\TeamTableModel;
use App\DAL\Entity\Member;
use App\DAL\Entity\Team;

class TeamManager
{
    private string $teamImagesDir;
    private EntityManagerInterface $entityManager;
    private Security $security;
    private SluggerInterface $slugger;

    public function __construct(
        string $teamImagesDir,
        EntityManagerInterface $entityManager,
        Security $security,
        SluggerInterface $slugger
    )
    {
        $this->teamImagesDir = $teamImagesDir;
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->slugger = $slugger;
    }

    public function createTeam(TeamModel $teamModel)
    {
        $this->saveTeamImage($teamModel);

        /** @var Team */
        $team = AutoMapper::map($teamModel, Team::class, ['id'], trackEntity: false);
        $team->setLeader(
            AutoMapper::map(
                $this->security->getUser(),
                \App\DAL\Entity\User::class,
                trackEntity: false
            )
        );

        $this->entityManager->persist($team);
        $this->entityManager->flush();
    }

    private function saveTeamImage(TeamModel $teamModel)
    {
        /** @var UploadedFile */
        $image = $teamModel->getImage();
        $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        // this is needed to safely include the file name as part of the URL
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $image->guessExtension();

        if ($teamModel->getImagePath() !== null){
            $this->removeImage($teamModel);
        }
        // Move the file to the directory where team images are stored
        try {
            $image->move(
                $this->teamImagesDir,
                $newFilename
            );
        } catch (FileException $e) {
            throw $e;
            // ... handle exception if something happens during file upload
        }

        // updates the 'ImagePath' property to store the image file name
        $teamModel->setImagePath($newFilename);
    }

    private function removeImage(TeamModel $teamModel)
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->teamImagesDir . '/' . $teamModel->getImagePath());
    }

    public function getTeam(int $id): TeamModel
    {
        /** @var \App\DAL\Repository\TeamRepository */
        $repo = $this->entityManager->getRepository(Team::class);
        $entity = $repo->findTeamWithCount($id);
        /** @var TeamModel */
        $teamModel = AutoMapper::map($entity['team'], TeamModel::class);
        /** memberCount == members + leader (1) */
        $teamModel->setMembersCount($entity['memberCount'] + 1);
        return $teamModel;
    }

    public function updateTeam(TeamModel $teamModel)
    {
        /** @var Team */
        if ($teamModel->getImage() !== null){
            $this->saveTeamImage($teamModel);
        }
        $team = AutoMapper::map($teamModel, Team::class, trackEntity: false);

        $this->entityManager->persist($team);
        $this->entityManager->flush();
    }
    
    public function getPeople(int $teamId, string $query, int $limit = 50): \Traversable
    {
        /** @var \App\DAL\Repository\TeamRepository */
        $repo = $this->entityManager->getRepository(Team::class);

        /** @var \App\DAL\Entity\User */
        foreach ($repo->findNewMembers($teamId, StringUtil::shave($query), $limit) as $user){
            yield ['value' => $user->getId(), 'text' => $user->getNickname()];
        }
    }

    /**
     * @return \Traversable<TeamTableModel>
     */
    public function getTeams(DataTableState $state): \Traversable
    {
        /** @var \App\DAL\Repository\TeamRepository */
        $repo = $this->entityManager->getRepository(Team::class);
        $searchMemberCount = intval($state->getSearch());
        /** members == memberCount - leader (1) */
        $searchMemberCount--;

        $paginator = $repo->findTableData(
            $state->getLimit(),
            $state->getStart(),
            $state->getOrderColumn(),
            $state->isAsceding(),
            $state->getSearch(),
            $searchMemberCount
        );
        $state->setCount($paginator->count());

        /** @var ?\App\BL\User\UserModel */
        $user = $this->security->getUser();

        foreach ($paginator as $entity){
            if (!$entity['team'] instanceof Team){
                continue;
            }
            /** @var TeamTableModel */
            $teamModel = AutoMapper::map($entity['team'], TeamTableModel::class, trackEntity: false);
            $teamModel->setLeaderNickName($entity['team']->getLeader()->getNickname());
            $teamModel->setLeaderId($entity['team']->getLeader()->getId());
            /** memberCount == members + leader (1) */
            $teamModel->setMemberCount($entity['memberCount'] + 1);
            $teamModel->setIsCurrentUserLeader($teamModel->getLeaderId() === $user?->getId());
            yield $teamModel;
        }
    }

    /**
     * @return \Traversable<TeamBasicTableModel>
     */
    public function getTeamsByUser(int $userId): \Traversable
    {
        /** @var \App\DAL\Repository\TeamRepository */
        $repo = $this->entityManager->getRepository(Team::class);
        $teams = $repo->findTeamsByMember($userId);

        foreach ($teams as $team){
            /** @var TeamBasicTableModel */
            $teamModel = AutoMapper::map($team['team'], TeamBasicTableModel::class, trackEntity: false);
            $teamModel->setIsUserLeader($team['isLeader']);
            yield $teamModel;
        }
    }

    public function deleteTeam(int $id)
    {
        /** @var \App\DAL\Repository\TeamRepository */
        $repo = $this->entityManager->getRepository(Team::class);
        $team = $this->entityManager->getReference(\App\DAL\Entity\Team::class, $id);
        $repo->remove($team, true);
    }

    /**
     * @param array<int> $userIds
     */
    public function addMembers(array $userIds, int $teamId)
    {
        if (empty($userIds)){
            return;
        }

        $team = $this->entityManager->getReference(\App\DAL\Entity\Team::class, $teamId);
        foreach ($userIds as $id){
            $member = new \App\DAL\Entity\Member();
            $member
                ->setTeam($team)
                ->setUser($this->entityManager->getReference(\App\DAL\Entity\User::class, $id));
            $this->entityManager->persist($member);
        }

        $this->entityManager->flush();
    }

    public function deleteMember(int $teamId, int $userId)
    {
        /** @var \App\DAL\Repository\MemberRepository */
        $repo = $this->entityManager->getRepository(\App\DAL\Entity\Member::class);
        $member = new \App\DAL\Entity\Member();
        /** cannot get member by reference, so find by ids is performed */
        $member = $repo->findOneBy(['team' => $teamId, 'user' => $userId]);
        $repo->remove($member, true);
    }

    /**
     * @return \Traversable<\App\BL\User\UserMemberModel>
     */
    public function getTeamMembers(int $id, int $limit = 100): \Traversable
    {
        /** @var \App\DAL\Repository\TeamRepository */
        $repo = $this->entityManager->getRepository(Team::class);
        
        foreach ($repo->findTeamMembers($id, $limit) as $res){
            /** @var \App\BL\User\UserMemberModel */
            $userModel = AutoMapper::map($res['user'], \App\BL\User\UserMemberModel::class, trackEntity: false);
            $userModel->setIsLeader((bool)$res['isLeader']);
            yield $userModel;
        }
    }

    public function isCurrentUserLeader(int $teamId): bool
    {
        /** @var \App\DAL\Repository\TeamRepository */
        $repo = $this->entityManager->getRepository(Team::class);

        $team = $repo->find($teamId);
        /** @var \App\BL\User\UserModel */
        $leader = AutoMapper::map($team->getLeader(), \App\BL\User\UserModel::class, trackEntity: false);

        /** @var ?\App\BL\User\UserModel */
        $user = $this->security->getUser();
        return $user?->getId() === $leader->getId();
    }

    /**
     * @return \Traversable<\App\BL\Team\TeamModel>
     */
    public function getUserTeams(?int $userId): \Traversable
    {
        /** @var \App\DAL\Repository\TeamRepository */
        $repo = $this->entityManager->getRepository(Team::class);

        foreach ($repo->findBy(['leader' => $userId]) as $res){
            /** @var \App\BL\Team\TeamModel */
            yield  AutoMapper::map($res, \App\BL\Team\TeamModel::class, trackEntity: false);
        }
    }

    public function getFormatedUserTeams(?int $userId) : array
    {
        $teams = $this->getUserTeams($userId);
        $teamsFormated = [];
        foreach ($teams as $team) {
            if (!$team->getIsDeactivated()){
                $teamsFormated[$team->getName()] = $team->getId();
            }
        }
        return $teamsFormated;
    }

    public function getRegisteredTeamParticipant($tournamentId, $currUserId)
    {
        /** @var \App\DAL\Repository\TournamentParticipantRepository */
        $participantRepo = $this->entityManager->getRepository(\App\DAL\Entity\TournamentParticipant::class);
        /** @var \App\DAL\Repository\MemberRepository */
        $memberRepo = $this->entityManager->getRepository(\App\DAL\Entity\Member::class);

        $registeredParticipantTeams = $participantRepo->findBy(['tournament' => $tournamentId]);

        foreach ($registeredParticipantTeams as $participant) {
            $team = $participant->getSignedUpTeam();
            if($team?->getLeader()->getId() === $currUserId){
                return [true, $team->getId(), $team->getName()];
            }
            else{
                // hledej jako member
                if($memberRepo->findOneBy(['team' => $team, 'user' => $currUserId])!==null){
                    return [false, $team->getId(), $team->getName()];
                }
            }
        }

        return [false, null, null];
    }

    public function checkOnCreateValidity(TeamModel $teamModel, string &$errMessage) : bool
    {
        if (!$this->checkUniqueName($errMessage, $teamModel->getName())){
            return false;
        }

        return true;
    }

    public function checkUniqueName(string &$errMessage, string $teamName) : bool
    {
        /** @var \App\DAL\Repository\TeamRepository */
        $repo = $this->entityManager->getRepository(\App\DAL\Entity\Team::class);
        $team = new \App\DAL\Entity\Team();
        /** cannot get member by reference, so find by ids is performed */
        $member = $repo->findOneBy(['name' => $teamName]);

        if ($member !== NULL){
            $errMessage = "Name is already used by another team!";
            return false;
        }

        return true;
    }

    public function deactivateTeam(int $id)
    {
        $repo = $this->entityManager->getRepository(Team::class);

        /** @var TeamModel */
        $teamModel = AutoMapper::map($repo->find($id), TeamModel::class);

        $teamModel->setIsDeactivated(TRUE);

        /** @var Team */
        $team = AutoMapper::map($teamModel, Team::class, trackEntity: false);

        $this->entityManager->persist($team);
        $this->entityManager->flush();
    }

    public function getTeamStatistics(int $id)
    {
        /** @var \App\DAL\Repository\TeamRepository */
        $repo = $this->entityManager->getRepository(\App\DAL\Entity\Team::class);
        $statistics = $repo->findStatistics($id);
        return Automapper::map($statistics, TeamStatisticsModel::class, trackEntity: false);
    }
}
