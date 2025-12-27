<?php

namespace App\Controller;

use App\Entity\Season;
use App\Form\SeasonType;
use App\Repository\ElectionRepository;
use App\Repository\SeasonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/season')]
class AdminSeasonController extends AbstractController
{
    /**
     * @param SeasonRepository $seasonRepository
     * @param ElectionRepository $electionRepository
     * @return Response
     */
    #[Route('/', name: 'admin_season_index', methods: ['GET'])]
    public function index(
        SeasonRepository $seasonRepository,
        ElectionRepository $electionRepository
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive();
        return $this->render('season/index.html.twig', [
            'user' => $this->getUser(),
            'seasons' => $seasonRepository->getAllInRankOrder(true),
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param Request $request
     * @param ElectionRepository $electionRepository
     * @return Response
     */
    #[Route('/new', name: 'admin_season_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        ElectionRepository $electionRepository,
        EntityManagerInterface $em
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive();
        $season = new Season();
        $form = $this->createForm(SeasonType::class, $season);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($season);
            $em->flush();

            return $this->redirectToRoute('admin_season_index');
        }

        return $this->render('season/new.html.twig', [
            'user' => $this->getUser(),
            'season' => $season,
            'form' => $form->createView(),
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param Season $season
     * @param ElectionRepository $electionRepository
     * @return Response
     */
    #[Route('/{id}', name: 'admin_season_show', methods: ['GET'])]
    public function show(
        Season $season,
        ElectionRepository $electionRepository
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive();
        return $this->render('season/show.html.twig', [
            'user' => $this->getUser(),
            'season' => $season,
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param Request $request
     * @param Season $season
     * @param ElectionRepository $electionRepository
     * @return Response
     */
    #[Route('/{id}/edit', name: 'admin_season_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Season $season,
        ElectionRepository $electionRepository,
        EntityManagerInterface $em
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive();
        $form = $this->createForm(SeasonType::class, $season);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('admin_season_index');
        }

        return $this->render('season/edit.html.twig', [
            'user' => $this->getUser(),
            'season' => $season,
            'form' => $form->createView(),
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param Request $request
     * @param Season $season
     * @return Response
     */
    #[Route('/{id}', name: 'admin_season_delete', methods: ['DELETE'])]
    public function delete(Request $request, Season $season, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$season->getId(), $request->request->get('_token'))) {
            $em->remove($season);
            $em->flush();
        }

        return $this->redirectToRoute('admin_season_index');
    }
}
