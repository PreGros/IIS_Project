<?php

namespace App\PL\Controller;

use App\BL\Security\UserProvider;
use App\PL\Form\User\EditFormType;
use App\BL\User\UserManager;
use App\PL\DataTable\User\UserDataTable;
use App\PL\Form\User\ChangePwdFormType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    #[Route('/users', name: 'users')]
    public function getUsers(Request $request, UserDataTable $dataTable): Response
    {
        $table = $dataTable->create($this->isGranted('ROLE_ADMIN'))->handleRequest($request);

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
        $userManager->deleteUser($id);
        return $this->redirectToRoute('users');
    }

    #[Route('/users/{id<\d+>}', name: 'user_info')]
    public function getUserInfo(int $id, UserManager $userManager): Response
    {
        $userModel = $userManager->getUser($id);

        /** @var \App\BL\User\UserModel */
        $user = $this->getUser();

        

        return $this->render('user/info.html.twig', [
            'canEdit' => $user?->isCurrentUser($id) || $this->isGranted('ROLE_ADMIN'),
            'user' => $userModel,
            'id' => $id
        ]);
    }

    #[Route('/users/{id<\d+>}/edit', name: 'user_edit')]
    public function editUserInfo(int $id, UserManager $userManager, Request $request): Response
    {
        /** @var \App\BL\User\UserModel */
        $userModel = $this->getUser();

        if (!$this->isGranted('ROLE_ADMIN') && !$userModel->isCurrentUser($id)){
            $this->addFlash('danger', 'Insufficient rights to edit user');
            return $this->redirectToRoute('users');
        }

        $form = $this->createForm(EditFormType::class, $userModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userManager->editUser($userModel);
            
            $this->addFlash('success', 'Your informations have been changed.');
            return $this->redirectToRoute('user_info', ['id' => $userModel->getId()]);
        }

        return $this->renderForm('user/edit.html.twig', [
            'editForm' => $form,
            'id' => $id
        ]);
    }

    #[Route('/users/{id<\d+>}/edit/change-pwd', name: 'user_change_pwd')]
    public function changeUserPwd(int $id, UserManager $userManager, UserProvider $userProvider, Request $request): Response
    {
        /** @var \App\BL\User\UserModel */
        $userModel = $this->getUser();

        if (!$this->isGranted('ROLE_ADMIN') && !$userModel->isCurrentUser($id)){
            $this->addFlash('danger', 'Insufficient rights to edit user');
            return $this->redirectToRoute('users');
        }

        $form = $this->createForm(ChangePwdFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($userManager->changePwd($form->get('oldPlainPassword')->getData(), $form->get('newPlainPassword')->getData(), $userModel, $userProvider))
            {
                $this->addFlash('success', 'Your password have been changed.');
                return $this->redirectToRoute('user_info', ['id' => $userModel->getId()]);
            }
            $this->addFlash('danger', 'Entered old password does not match current password.');
        }

        return $this->renderForm('user/edit_pwd.html.twig', [
            'changePwdForm' => $form
        ]);
    }
}
