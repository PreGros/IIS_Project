<?php

namespace App\BL\User;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

use App\BL\Util\AutoMapper;
use App\BL\Security\EmailVerifier;
use App\DAL\Entity\User;

class UserManager
{
    private EmailVerifier $emailVerifier;

    private UserPasswordHasherInterface $userPasswordHasher;

    private EntityManagerInterface $entityManager;

    public function __construct(
        EmailVerifier $emailVerifier,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    )
    {
        $this->emailVerifier = $emailVerifier;
        $this->userPasswordHasher = $userPasswordHasher;
        $this->entityManager = $entityManager;
    }

    public function registerUser(UserModel $userModel, string $plainPassword)
    {   
        $userModel->setPassword(
            $this->userPasswordHasher->hashPassword(
                $userModel,
                $plainPassword
            )
        );

        /** @var User */
        $user = AutoMapper::map($userModel, User::class, trackEntity: false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $userModel = AutoMapper::map($user, $userModel);
        $this->sendVerificationEmail($userModel);
    }

    private function sendVerificationEmail(UserModel $user)
    {
        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
            (new TemplatedEmail())
                ->from(new Address('iis-proj@stud.fit.vutbr.cz', 'IIS Projekt'))
                ->to($user->getEmail())
                ->subject('Please Confirm your Email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );
    }

    public function handleEmailConfirmation(string $signedUrl, UserModel $userModel)
    {
        $this->emailVerifier->handleEmailConfirmation($signedUrl, $userModel);
        
        $userModel->setIsVerified(true);
        /** @var User */
        $user = AutoMapper::map($userModel, User::class, trackEntity: false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * @return \Traversable<UserModel>
     */
    public function getUsers(int $limit): \Traversable
    {   
        /** @var \App\DAL\Repository\UserRepository */
        $repo = $this->entityManager->getRepository(User::class);
        
        foreach ($repo->findBy([], limit: $limit) as $user){
            /** @var \App\BL\User\UserModel */
            $userModel = AutoMapper::map($user, \App\BL\User\UserModel::class, trackEntity: false);
            yield $userModel;
        }
    }

    public function getUser(int $id): UserModel
    {
        /** @var \App\DAL\Repository\UserRepository */
        $repo = $this->entityManager->getRepository(User::class);
        
        $user = $repo->find($id);

        /** @var \App\BL\User\UserModel */
        return AutoMapper::map($user, \App\BL\User\UserModel::class, trackEntity: true);
    }

    public function deleteUser(int $userId)
    {
        /** @var \App\DAL\Repository\UserRepository */
        $repo = $this->entityManager->getRepository(User::class);
        $user = $this->entityManager->getReference(User::class, $userId);

        $repo->remove($user, true);
    }

    public function addRole(string $roleName, int $userId)
    {
        /** @var \App\DAL\Repository\UserRepository */
        $repo = $this->entityManager->getRepository(User::class);
        /** @var UserModel */
        $userModel = AutoMapper::map($repo->find($userId), UserModel::class);
        $roles = $userModel->getRoles();

        if (in_array($roleName, $roles)){
            return;
        }

        $roles[] = $roleName;
        $userModel->setRoles($roles);

        $user = AutoMapper::map($userModel, User::class, trackEntity: false);
        $repo->save($user, true);
    }

    public function removeRole(string $roleName, int $userId)
    {
        /** @var \App\DAL\Repository\UserRepository */
        $repo = $this->entityManager->getRepository(User::class);
        /** @var UserModel */
        $userModel = AutoMapper::map($repo->find($userId), UserModel::class);
        $roles = $userModel->getRoles();

        if (!in_array($roleName, $roles)){
            return;
        }

        unset($roles[array_search($roleName, $roles)]);
        $userModel->setRoles($roles);

        $user = AutoMapper::map($userModel, User::class, trackEntity: false);
        $repo->save($user, true);
    }
}
