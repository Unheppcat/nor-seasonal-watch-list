<?php

namespace App\Controller;

use App\Entity\ShowSeasonScore;
use App\Form\ShowSeasonScoreType;
use App\Repository\ElectionRepository;
use App\Repository\ShowSeasonScoreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/show/season/score')]
class AdminShowSeasonScoreController extends AbstractController
{
    /**
     * @param ShowSeasonScoreRepository $showSeasonScoreRepository
     * @param ElectionRepository $electionRepository
     * @return Response
     */
    #[Route('/', name: 'admin_show_season_score_index', options: ['expose' => true], methods: ['GET'])]
    public function index(
        ShowSeasonScoreRepository $showSeasonScoreRepository,
        ElectionRepository $electionRepository
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive($this->isGranted('ROLE_SWL_SPECIAL_ELECTION_VOTER'));
        return $this->render('show_season_score/index.html.twig', [
            'user' => $this->getUser(),
            'show_season_scores' => $showSeasonScoreRepository->findAll(),
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param Request                $request
     * @param ElectionRepository     $electionRepository
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/new', name: 'admin_show_season_score_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        ElectionRepository $electionRepository,
        EntityManagerInterface $em
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive($this->isGranted('ROLE_SWL_SPECIAL_ELECTION_VOTER'));
        $showSeasonScore = new ShowSeasonScore();
        $form = $this->createForm(ShowSeasonScoreType::class, $showSeasonScore);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($showSeasonScore);
            $em->flush();

            return $this->redirectToRoute('admin_show_season_score_index');
        }

        return $this->render('show_season_score/new.html.twig', [
            'user' => $this->getUser(),
            'show_season_score' => $showSeasonScore,
            'form' => $form->createView(),
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param ShowSeasonScore $showSeasonScore
     * @param ElectionRepository $electionRepository
     * @return Response
     */
    #[Route('/{id}', name: 'admin_show_season_score_show', methods: ['GET'])]
    public function show(
        ShowSeasonScore $showSeasonScore,
        ElectionRepository $electionRepository
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive($this->isGranted('ROLE_SWL_SPECIAL_ELECTION_VOTER'));
        return $this->render('show_season_score/show.html.twig', [
            'user' => $this->getUser(),
            'show_season_score' => $showSeasonScore,
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param Request                $request
     * @param ShowSeasonScore        $showSeasonScore
     * @param ElectionRepository     $electionRepository
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/{id}/edit', name: 'admin_show_season_score_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ShowSeasonScore $showSeasonScore,
        ElectionRepository $electionRepository,
        EntityManagerInterface $em
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive($this->isGranted('ROLE_SWL_SPECIAL_ELECTION_VOTER'));
        $form = $this->createForm(
            ShowSeasonScoreType::class,
            $showSeasonScore,
            [
                'attr' => [
                    'id' => 'show_season_score_' . $showSeasonScore->getId(),
                    'class' => 'show_season_score_form',
                ]
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                // Just send back fact of success
                return new JsonResponse(['data' => ['status' => 'success']]);
            }

            return $this->redirectToRoute('admin_show_season_score_index');
        }

        if ($request->isXmlHttpRequest()) {
            // There was a validation error, return just the form
            $html = $this->renderView('show_season_score/_form.html.twig', [
                'form' => $form->createView(),
            ]);
            return new Response($html, 400);
        }

        return $this->render('show_season_score/edit.html.twig', [
            'user' => $this->getUser(),
            'show_season_score' => $showSeasonScore,
            'form' => $form->createView(),
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param Request                $request
     * @param ShowSeasonScore        $showSeasonScore
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/{id}', name: 'admin_show_season_score_delete', methods: ['DELETE'])]
    public function delete(Request $request, ShowSeasonScore $showSeasonScore, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$showSeasonScore->getId(), $request->request->get('_token'))) {
            $em->remove($showSeasonScore);
            $em->flush();
        }

        return $this->redirectToRoute('admin_show_season_score_index');
    }
}
