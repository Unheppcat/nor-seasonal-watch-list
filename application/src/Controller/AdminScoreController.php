<?php

namespace App\Controller;

use App\Entity\Score;
use App\Form\ScoreType;
use App\Repository\ElectionRepository;
use App\Repository\ScoreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/score")
 */
class AdminScoreController extends AbstractController
{
    /**
     * @Route("/", name="admin_score_index", methods={"GET"})
     * @param ScoreRepository $scoreRepository
     * @param ElectionRepository $electionRepository
     * @return Response
     */
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
     * @Route("/new", name="admin_score_new", methods={"GET","POST"})
     * @param Request $request
     * @param ElectionRepository $electionRepository
     * @return Response
     */
    public function new(
        Request $request,
        ElectionRepository $electionRepository
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive();
        $score = new Score();
        $form = $this->createForm(ScoreType::class, $score);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $score->setIcon($score->getIcon());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($score);
            $entityManager->flush();

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
     * @Route("/{id}", name="admin_score_show", methods={"GET"})
     * @param Score $score
     * @param ElectionRepository $electionRepository
     * @return Response
     */
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
     * @Route("/{id}/edit", name="admin_score_edit", methods={"GET","POST"})
     * @param Request $request
     * @param Score $score
     * @param ElectionRepository $electionRepository
     * @return Response
     */
    public function edit(
        Request $request,
        Score $score,
        ElectionRepository $electionRepository
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive();
        $form = $this->createForm(ScoreType::class, $score);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $score->setIcon($score->getIcon());
            $this->getDoctrine()->getManager()->flush();

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
     * @Route("/{id}", name="admin_score_delete", methods={"DELETE"})
     * @param Request $request
     * @param Score $score
     * @return Response
     */
    public function delete(Request $request, Score $score): Response
    {
        if ($this->isCsrfTokenValid('delete'.$score->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($score);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_score_index');
    }
}
