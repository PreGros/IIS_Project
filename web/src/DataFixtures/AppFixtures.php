<?php

namespace App\DataFixtures;

use App\BL\Tournament\TournamentTypeModel;
use App\BL\User\UserModel;
use App\BL\Util\AutoMapper;
use App\DAL\Entity\TournamentType;
use App\DAL\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
        $userModel = new UserModel();
        $userModel->setEmail("admin@admin.com");
        $userModel->setFirstname("Admin");
        $userModel->setSurname("Adminovský");
        $userModel->setNickname("admin");
        $userModel->setRoles(["ROLE_USER", "ROLE_ADMIN"]);
        $userModel->setIsVerified(true);
        $userModel->setPassword($this->hasher->hashPassword($userModel, 'admin'));

        /** @var User */
        $user = AutoMapper::map($userModel, User::class, trackEntity: false);

        $manager->persist($user);
        $manager->flush();

        
        $tournamentTypeModel = new TournamentTypeModel();
        $tournamentTypeModel->setName("Others");

        /** @var TournamentType */
        $tournamentType = AutoMapper::map($tournamentTypeModel, TournamentType::class, trackEntity: false);

        $manager->persist($tournamentType);
        $manager->flush();


        $tournamentTypeModel = new TournamentTypeModel();
        $tournamentTypeModel->setName("Chess");

        /** @var TournamentType */
        $tournamentType = AutoMapper::map($tournamentTypeModel, TournamentType::class, trackEntity: false);

        $manager->persist($tournamentType);
        $manager->flush();
    }
}
