<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApiController extends AbstractController
{
    #[Route('/api', name: 'api')]
    public function index(): Response
    {
        $user = $this->getUser();
        return $this->render('api/index.html.twig', [
            'controller_name' => 'ApiController',
            'user' => $user,
        ]);
    }
}
