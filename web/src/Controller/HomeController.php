<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    #[Route('/test')]
    public function test(Request $request): Response
    {
        $isAjax = $request->isXmlHttpRequest();

        return $this->render('test.html.twig', [
            'ajax' => $isAjax,
        ]);
    }
}
