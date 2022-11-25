<?php

namespace App\BL\User;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

use App\BL\Util\AutoMapper;
use App\BL\Security\EmailVerifier;
use App\BL\Security\UserProvider;
use App\BL\Util\DataTableState;
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

    public function editUser(UserModel $userModel)
    {
        /** @var User */
        $user = AutoMapper::map($userModel, User::class, trackEntity: false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
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
    public function getUsers(DataTableState $state): \Traversable
    {   
        /** @var \App\DAL\Repository\UserRepository */
        $repo = $this->entityManager->getRepository(User::class);
        
        $paginator = $repo->findTableData(
            $state->getLimit(),
            $state->getStart(),
            $state->getOrderColumn(),
            $state->isAsceding(),
            $state->getSearch()
        );
        $state->setCount($paginator->count());

        foreach ($paginator as $user){
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

    public function deactivateUser(int $id)
    {
        $repo = $this->entityManager->getRepository(User::class);

        /** @var UserModel */
        $userModel = AutoMapper::map($repo->find($id), UserModel::class);

        $userModel->setPassword("");
        $userModel->setIsDeactivated(true);

        /** @var User */
        $user = AutoMapper::map($userModel, User::class, trackEntity: false);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
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

    public function changePwd(string $oldPwd, string $newPwd, UserModel $userModel, UserProvider $userProvider) : bool
    {
        if (!$this->userPasswordHasher->isPasswordValid($userModel, $oldPwd)) {
            return false;
        }

        $newHashedPassword = $this->userPasswordHasher->hashPassword(
            $userModel,
            $newPwd
        );

        $userProvider->upgradePassword($userModel, $newHashedPassword);

        return true;
    }

    public function checkOnCreateValidity(UserModel $userModel, string &$errMessage) : bool
    {
        if (!$this->checkUniqueNickname($errMessage, $userModel->getNickname())){
            return false;
        }
        if (!$this->checkUniqueEmail($errMessage, $userModel->getEmail())){
            return false;
        }

        return true;
    }

    public function checkUniqueNickname(string &$errMessage, string $userNickname) : bool
    {
        /** @var \App\DAL\Repository\UserRepository */
        $repo = $this->entityManager->getRepository(\App\DAL\Entity\User::class);
        /** cannot get member by reference, so find by ids is performed */
        $user = $repo->findOneBy(['nickname' => $userNickname]);
    
        if ($user !== NULL){
            $errMessage = "User with given nickname already exists";
            return false;
        }

        return true;
    }

    public function checkUniqueEmail(string &$errMessage, string $userEmail) : bool
    {
        /** @var \App\DAL\Repository\UserRepository */
        $repo = $this->entityManager->getRepository(\App\DAL\Entity\User::class);
        /** cannot get member by reference, so find by ids is performed */
        $user = $repo->findOneBy(['email' => $userEmail]);
    
        if ($user !== NULL){
            $errMessage = "User with given email already exists";
            return false;
        }

        return true;
    }

    public function getUsersStatistics(int $id)
    {
        /** @var \App\DAL\Repository\UserRepository */
        $repo = $this->entityManager->getRepository(\App\DAL\Entity\User::class);
        $statistics = $repo->findStatistics($id);
        return Automapper::map($statistics, UserStatisticsModel::class, trackEntity: false);
    }
}
