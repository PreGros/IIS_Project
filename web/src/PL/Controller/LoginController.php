<?php

namespace App\PL\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\PL\Form\User\LoginFormType;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function index(AuthenticationUtils $authenticationUtils, FormFactoryInterface $formFactory): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')){
            $this->addFlash('warning', 'Pathetic, forgetful human (u are logged in)');
            return $this->redirectToRoute('teams');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $defaultData = [
            'username' => $lastUsername,
        ];
        $form = $formFactory->createNamed('login', LoginFormType::class, $defaultData);

        return $this->renderForm('login/index.html.twig', [
            'error' => $error,
            'form' => $form
        ]);
    }
}
