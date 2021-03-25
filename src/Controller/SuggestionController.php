<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SuggestionController extends AbstractController
{
    #[Route('/suggestion', name: 'suggestion')]
    public function index(): Response
    {
        return $this->render('suggestion/index.html.twig', [
            'controller_name' => 'SuggestionController',
        ]);
    }
}
