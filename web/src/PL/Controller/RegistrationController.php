<?php

namespace App\PL\Controller;

use App\BL\User\UserModel;
use App\PL\Form\User\RegistrationFormType;
use App\BL\Security\EmailVerifier;
use App\BL\User\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    private FormLoginAuthenticator $authenticator;
    private UserManager $userManager;

    public function __construct(FormLoginAuthenticator $authenticator, UserManager $userManager)
    {
        $this->authenticator = $authenticator;
        $this->userManager = $userManager;
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserAuthenticatorInterface $authenticatorManager): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')){
            $this->addFlash('warning', 'Please, logout first (U DUMB)');
            return $this->redirectToRoute('teams');
        }

        $user = new UserModel();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userManager->registerUser($user, $form->get('plainPassword')->getData());

            $authenticatorManager->authenticateUser(
                $user,
                $this->authenticator, $request,
                [(new RememberMeBadge())->enable()]
            );
            
            $this->addFlash('success', 'Your email address has been verified.');
            return $this->redirectToRoute('datatable');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView()
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->userManager->handleEmailConfirmation($request->getUri(), $this->getUser());
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('danger', $exception->getReason());

            return $this->redirectToRoute('app_register');
        }

        // TODO: Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('teams');
    }
}
