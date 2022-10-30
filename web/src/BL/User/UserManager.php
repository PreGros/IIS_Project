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
}
