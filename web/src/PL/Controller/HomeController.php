<?php

namespace App\PL\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\PL\DataTable\Test\TestDataTable;

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
    
    #[Route('/datatable', name: 'datatable')]
    public function showAction(Request $request, TestDataTable $dataTable): Response
    {
        $table = $dataTable->create()->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('list.html.twig', ['datatable' => $table]);
    }
}
