<?php
/** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */

namespace App\Controller;

use App\Entity\ElectionVote;
use App\Entity\User;
use App\Form\ElectionVoteType;
use App\Repository\ElectionRepository;
use App\Repository\ElectionVoteRepository;
use App\Repository\ShowRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MyVoteController extends AbstractController
{
    /**
     * @Route("/vote", name="my_vote")
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param ShowRepository $showRepository
     * @param ElectionRepository $electionRepository
     * @param ElectionVoteRepository $electionVoteRepository
     * @return Response
     * @throws NonUniqueResultException
     */
    public function index(
        Request $request,
        EntityManagerInterface $em,
        ShowRepository $showRepository,
        ElectionRepository $electionRepository,
        ElectionVoteRepository $electionVoteRepository
    ): Response {
        $electionId = $request->get('election');
        $election = null;
        if ($electionId !== null) {
            $election = $electionRepository->find($electionId);
        }
        if ($election === null) {
            $election = $electionRepository->getFirstActiveElection();
        }
        if ($election === null) {
            $this->addFlash('warning', 'There is no election to vote on at this time.');
            return $this->redirectToRoute('https_default');
        }

        /** @var User $user */
        $user = $this->getUser();
        $data = [];

        $shows = $showRepository->getShowsForSeasonElectionEligible($election->getSeason());
        foreach ($shows as $key => $show) {
            $vote = $electionVoteRepository->getForUserAndShowAndElection(
                $user,
                $show,
                $election
            );
            if ($vote === null) {
                $vote = new ElectionVote();
                $vote->setUser($user);
                $vote->setShow($show);
                $vote->setElection($election);
                $vote->setSeason($election->getSeason());
                $vote->setChosen(false);
                $em->persist($vote);
                $em->flush();
            }
            $form = $this->createForm(
                ElectionVoteType::class,
                $vote,
                [
                    'attr' => [
                        'id' => 'list_my_vote_form_' . $key,
                        'class' => 'list_my_vote_form',
                    ],
                    'show_vote_only' => true,
                    'form_key' => $key,
                    'action' => $this->generateUrl('election_vote_edit', ['id' => $vote->getId()])
                ]
            );
            $data[] = ['vote' => $vote, 'form' => $form->createView()];
        }
        return $this->render('my_vote/index.html.twig', [
            'controller_name' => 'MyVoteController',
            'election' => $election,
            'data' => $data
        ]);
    }
}
