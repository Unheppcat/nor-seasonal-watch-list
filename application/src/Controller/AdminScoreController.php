<?php

namespace App\Controller;

use App\Entity\Score;
use App\Form\ScoreType;
use App\Repository\ElectionRepository;
use App\Repository\ScoreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/score')]
class AdminScoreController extends AbstractController
{
    /**
     * @param ScoreRepository $scoreRepository
     * @param ElectionRepository $electionRepository
     * @return Response
     */
    #[Route('/', name: 'admin_score_index', methods: ['GET'])]
    public function index(
        ScoreRepository $scoreRepository,
        ElectionRepository $electionRepository
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive();
        return $this->render('score/index.html.twig', [
            'user' => $this->getUser(),
            'scores' => $scoreRepository->findAllInRankOrder(),
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param Request                $request
     * @param ElectionRepository     $electionRepository
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/new', name: 'admin_score_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        ElectionRepository $electionRepository,
        EntityManagerInterface $em
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive();
        $score = new Score();
        $form = $this->createForm(ScoreType::class, $score);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $score->setIcon($score->getIcon());
            $em->persist($score);
            $em->flush();

            return $this->redirectToRoute('admin_score_index');
        }

        return $this->render('score/new.html.twig', [
            'user' => $this->getUser(),
            'score' => $score,
            'form' => $form->createView(),
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param Score $score
     * @param ElectionRepository $electionRepository
     * @return Response
     */
    #[Route('/{id}', name: 'admin_score_show', methods: ['GET'])]
    public function show(
        Score $score,
        ElectionRepository $electionRepository
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive();
        return $this->render('score/show.html.twig', [
            'user' => $this->getUser(),
            'score' => $score,
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param Request                $request
     * @param Score                  $score
     * @param ElectionRepository     $electionRepository
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/{id}/edit', name: 'admin_score_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Score $score,
        ElectionRepository $electionRepository,
        EntityManagerInterface $em
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive();
        $form = $this->createForm(ScoreType::class, $score);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $score->setIcon($score->getIcon());
            $em->flush();

            return $this->redirectToRoute('admin_score_index');
        }

        return $this->render('score/edit.html.twig', [
            'user' => $this->getUser(),
            'score' => $score,
            'form' => $form->createView(),
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param Request                $request
     * @param Score                  $score
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/{id}', name: 'admin_score_delete', methods: ['DELETE'])]
    public function delete(Request $request, Score $score, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$score->getId(), $request->request->get('_token'))) {
            $em->remove($score);
            $em->flush();
        }

        return $this->redirectToRoute('admin_score_index');
    }
}
