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
use Symfony\Component\Routing\Attribute\Route;

class MyVoteController extends AbstractController
{
    /**
     * Display election list or route to appropriate page based on active election count
     *
     * @param ElectionRepository     $electionRepository
     * @param ElectionVoteRepository $electionVoteRepository
     * @return Response
     * @throws NonUniqueResultException
     */
    #[Route('/vote', name: 'my_vote')]
    public function index(
        ElectionRepository $electionRepository,
        ElectionVoteRepository $electionVoteRepository
    ): Response {
        $activeElections = $electionRepository->getAllActiveElections();

        // Filter out restricted elections if user doesn't have special role
        $hasSpecialRole = $this->isGranted('ROLE_SWL_SPECIAL_ELECTION_VOTER');

        $accessibleElections = array_filter($activeElections, static function($election) use ($hasSpecialRole) {
            // If election is restricted and user doesn't have special role, exclude it
            return !($election->getRestrictedAccess() && !$hasSpecialRole);
        });

        if (count($accessibleElections) === 0) {
            // No accessible election - show "no election" message
            $nextElection = $electionRepository->getNextAvailableElection();
            return $this->render('my_vote/no_election.html.twig', [
                'user' => $this->getUser(),
                'election' => $nextElection,
                'electionIsActive' => false,
            ]);
        }

        if (count($accessibleElections) === 1) {
            // Single accessible election - redirect to ballot
            return $this->redirectToRoute('my_vote_ballot', [
                'id' => array_values($accessibleElections)[0]->getId()
            ]);
        }

        // Multiple accessible elections - show election list
        /** @var User $user */
        $user = $this->getUser();
        $electionData = [];

        foreach ($accessibleElections as $election) {
            $electionData[] = [
                'election' => $election,
                'hasVoted' => $electionVoteRepository->hasUserVotedInElection($user, $election)
            ];
        }

        return $this->render('my_vote/election_list.html.twig', [
            'user' => $user,
            'elections' => $electionData,
        ]);
    }

    /**
     * Display ballot for a specific election
     *
     * @param int                    $id
     * @param Request                $request
     * @param EntityManagerInterface $em
     * @param ShowRepository         $showRepository
     * @param ElectionRepository     $electionRepository
     * @param ElectionVoteRepository $electionVoteRepository
     * @return Response
     * @throws NonUniqueResultException
     * @noinspection PhpUnusedParameterInspection
     */
    #[Route('/vote/{id}', name: 'my_vote_ballot', requirements: ['id' => '\d+'])]
    public function ballot(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        ShowRepository $showRepository,
        ElectionRepository $electionRepository,
        ElectionVoteRepository $electionVoteRepository
    ): Response {
        $election = $electionRepository->find($id);

        // Validate election exists
        if (!$election) {
            throw $this->createNotFoundException('Election not found');
        }

        // CRITICAL: Validate election is active
        if (!$election->isActive()) {
            $this->addFlash('error', 'This election is not currently active.');
            return $this->redirectToRoute('my_vote');
        }

        // CRITICAL: Validate user has access to restricted elections
        if ($election->getRestrictedAccess() && !$this->isGranted('ROLE_SWL_SPECIAL_ELECTION_VOTER')) {
            $this->addFlash('error', 'You do not have permission to access this election.');
            return $this->redirectToRoute('my_vote');
        }

        /** @var User $user */
        $user = $this->getUser();
        $data = [];

        $shows = $showRepository->getShowsForSeasonElectionEligible($election->getSeason());
        $showCount = count($shows);
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
                $vote->setRank($showCount);
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
                    'election_type' => $election->getElectionType(),
                    'show_count' => $showCount,
                    'form_key' => $key,
                    'action' => $this->generateUrl('election_vote_edit', ['id' => $vote->getId()])
                ]
            );
            $data[] = ['vote' => $vote, 'form' => $form->createView()];
        }

        // Make the order of shows random
        shuffle($data);

        // Check if there are multiple active elections (for showing election list link)
        $activeElections = $electionRepository->getAllActiveElections();
        $hasMultipleElections = count($activeElections) > 1;

        return $this->render('my_vote/index.html.twig', [
            'user' => $this->getUser(),
            'controller_name' => 'MyVoteController',
            'election' => $election,
            'electionIsActive' => true, // Election is always active in ballot() due to validation above
            'hasMultipleElections' => $hasMultipleElections,
            'data' => $data
        ]);
    }
}
