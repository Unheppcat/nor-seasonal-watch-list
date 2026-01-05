<?php

namespace App\Controller;

use App\Entity\ElectionVote;
use App\Form\ElectionVoteType;
use App\Repository\ElectionRepository;
use App\Repository\ElectionVoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/election/vote')]
class AdminElectionVoteController extends AbstractController
{
    /**
     * @param ElectionVoteRepository $electionVoteRepository
     * @param ElectionRepository $electionRepository
     * @return Response
     */
    #[Route('/', name: 'admin_election_vote_index', methods: ['GET'])]
    public function index(
        ElectionVoteRepository $electionVoteRepository,
        ElectionRepository $electionRepository
    ): Response {
        $electionIsActive = $electionRepository->electionIsAvailable->
        return $this->render('election_vote/index.html.twig', [
            'user' => $this->getUser(),
            'election_votes' => $electionVoteRepository->findAll(),
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param Request                $request
     * @param ElectionRepository     $electionRepository
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/new', name: 'admin_election_vote_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        ElectionRepository $electionRepository,
        EntityManagerInterface $em
    ): Response {
        $electionIsActive = $electionRepository->electionIsAvailable->
        $electionVote = new ElectionVote();
        $form = $this->createForm(ElectionVoteType::class, $electionVote);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($electionVote);
            $em->flush();

            return $this->redirectToRoute('admin_election_vote_index');
        }

        return $this->render('election_vote/new.html.twig', [
            'user' => $this->getUser(),
            'election_vote' => $electionVote,
            'form' => $form->createView(),
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param ElectionVote $electionVote
     * @param ElectionRepository $electionRepository
     * @return Response
     */
    #[Route('/{id}', name: 'admin_election_vote_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        ElectionVote $electionVote,
        ElectionRepository $electionRepository
    ): Response {
        $electionIsActive = $electionRepository->electionIsAvailable->
        return $this->render('election_vote/show.html.twig', [
            'user' => $this->getUser(),
            'election_vote' => $electionVote,
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param Request                $request
     * @param ElectionVote           $electionVote
     * @param ElectionRepository     $electionRepository
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/{id}/edit', name: 'admin_election_vote_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ElectionVote $electionVote,
        ElectionRepository $electionRepository,
        EntityManagerInterface $em
    ): Response {
        $electionIsActive = $electionRepository->electionIsAvailable();
        $form = $this->createForm(ElectionVoteType::class, $electionVote);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('admin_election_vote_index');
        }

        return $this->render('election_vote/edit.html.twig', [
            'user' => $this->getUser(),
            'election_vote' => $electionVote,
            'form' => $form->createView(),
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param Request                $request
     * @param ElectionVote           $electionVote
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/{id}', name: 'admin_election_vote_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Request $request, ElectionVote $electionVote, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$electionVote->getId(), $request->request->get('_token'))) {
            $em->remove($electionVote);
            $em->flush();
        }

        return $this->redirectToRoute('admin_election_vote_index');
    }
}
