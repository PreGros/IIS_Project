<?php

namespace App\PL\Controller;

use App\BL\User\UserManager;
use App\PL\DataTable\User\UserDataTable;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    #[Route('/users', name: 'users')]
    public function getUsers(Request $request, UserDataTable $dataTable): Response
    {
        $table = $dataTable->create()->handleRequest($request);

        if ($table->isCallback()){
            return $table->getResponse();
        }

        return $this->render('user/index.html.twig', ['datatable' => $table]);
    }

    #[Route('/users/{id<\d+>}/demote', name: 'user_demote')]
    public function demoteUser(int $id, UserManager $userManager): Response
    {
        $userManager->removeRole('ROLE_ADMIN', $id);
        return $this->redirectToRoute('users');
    }

    #[Route('/users/{id<\d+>}/promote', name: 'user_promote')]
    public function promoteUser(int $id, UserManager $userManager): Response
    {
        $userManager->addRole('ROLE_ADMIN', $id);
        return $this->redirectToRoute('users');
    }

    #[Route('/users/{id<\d+>}/delete', name: 'user_delete')]
    public function deleteUser(int $id, UserManager $userManager): Response
    {
        return $this->redirectToRoute('users');
    }

    #[Route('/users/{id<\d+>}', name: 'user_info')]
    public function getUserInfo(int $id, UserManager $userManager): Response
    {
        $userModel = $userManager->getUser($id);
        return $this->render('user/info.html.twig', ['user' => $userModel]);
    }
}
