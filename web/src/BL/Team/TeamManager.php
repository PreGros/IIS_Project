<?php

namespace App\BL\Team;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

use App\BL\Util\AutoMapper;
use App\BL\Team\TeamModel;
use App\BL\Team\TeamTableModel;
use App\DAL\Entity\Team;
use Symfony\Component\Filesystem\Filesystem;

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

    public function createTeam(TeamModel $teamModel){
        $this->saveTeamImage($teamModel);
        $user = $this->security->getUser();
        /** @var Team */
        $team = AutoMapper::map($teamModel, Team::class, trackEntity: false);
        $team->setLeader($user);

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
        /** @var TeamModel */
        $teamModel = AutoMapper::map($repo->find($id), TeamModel::class);
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
    
    public function getPeople(int $teamId, string $query, int $limit = 50)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $queryBuilder
            ->select('u')
            ->from(\App\DAL\Entity\User::class, 'u')
            ->leftJoin(\App\DAL\Entity\Member::class, 'm', Expr\Join::WITH, 'm.user = u')
            ->where('IDENTITY(m.team) != :teamId')
            ->andWhere('u.id != ' . '(' . $this->entityManager->createQueryBuilder()
                ->select('IDENTITY(t.leader)')
                ->from(\App\DAL\Entity\Team::class, 't')
                ->where('t.id = :teamId')
                ->setParameter('teamId', $teamId)
                ->getDQL() . ')')
            ->andWhere('u.nickname like :query')
            ->setParameter('teamId', $teamId)
            ->setParameter('query', "%{$query}%");

        $query = $queryBuilder->getQuery()->setMaxResults($limit);
    }

    /**
     * @return array<TeamTableModel>
     */
    public function getTableData(int $limit): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $queryBuilder
            ->select('count(m.user) memberCount, t team')
            ->from(Team::class, 't')
            ->innerJoin(\App\DAL\Entity\User::class, 'l', Expr\Join::WITH, 't.leader = l')
            ->leftJoin(\App\DAL\Entity\Member::class, 'm', Expr\Join::WITH, 'm.team = t')
            ->groupBy('t')
            ->addGroupBy('l');

        $query = $queryBuilder->getQuery()->setMaxResults($limit);
        $teamModels = [];
        foreach ($query->getResult() as $entity){
            if (!$entity['team'] instanceof Team){
                continue;
            }
            /** @var TeamTableModel */
            $teamModel = AutoMapper::map($entity['team'], TeamTableModel::class, trackEntity: false);
            $teamModel->setLeaderNickName($entity['team']->getLeader()->getNickname());
            /** memberCount == members + leader (1) */
            $teamModel->setMemberCount($entity['memberCount'] + 1);
            $teamModels[] = $teamModel;
        }

        return $teamModels;
    }
}
