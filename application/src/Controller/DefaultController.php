<?php /** @noinspection UnknownInspectionInspection */

namespace App\Controller;

use App\Repository\ElectionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="default", schemes={"HTTP"})
     */
    public function index(ElectionRepository $electionRepository): Response
    {
        $electionIsActive = $electionRepository->electionIsActive();
        $user = $this->getUser();
        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
            'user' => $user,
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @Route("/", name="https_default", schemes={"HTTPS"})
     * @noinspection PhpUnused
     */
    public function indexHttps(ElectionRepository $electionRepository): Response
    {
        return $this->index($electionRepository);
    }

}
