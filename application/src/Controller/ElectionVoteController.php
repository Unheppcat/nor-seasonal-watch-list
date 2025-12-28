<?php

namespace App\Controller;

use App\Entity\Election;
use App\Entity\ElectionVote;
use App\Entity\User;
use App\Form\ElectionVoteType;
use App\Repository\ElectionVoteRepository;
use App\Repository\ShowRepository;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/election/vote')]
class ElectionVoteController extends AbstractController
{
    /**
     * @param Request                $request
     * @param ElectionVote           $electionVote
     * @param ElectionVoteRepository $electionVoteRepository
     * @param ShowRepository         $showRepository
     * @param EntityManagerInterface $em
     * @return Response
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    #[Route('/{id}/edit', name: 'election_vote_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ElectionVote $electionVote,
        ElectionVoteRepository $electionVoteRepository,
        ShowRepository $showRepository,
        EntityManagerInterface $em
    ): Response {
        try {
            /** @var User $user */
            $user = $this->getUser();
            if ($user === null) {
                return new JsonResponse(['data' => ['status' => 'permission_denied']], Response::HTTP_FORBIDDEN);
            }
            if (!$electionVote->getUser() || $electionVote->getUser()->getId() !== $user->getId()) {
                return new JsonResponse(['data' => ['status' => 'permission_denied']], Response::HTTP_FORBIDDEN);
            }

            $election = $electionVote->getElection();
            if ($election === null) {
                return new JsonResponse(['data' => ['status' => 'election_not_found']], Response::HTTP_NOT_FOUND);
            }

            $maxVotes = $election->getMaxVotes();
            $currentVoteCount = $electionVoteRepository->getCountForUserAndElection($user, $election);
            if ($maxVotes < 1 || $maxVotes === null) {
                $maxVotes = -1;
            }

            if ($election->getElectionType() === Election::RANKED_CHOICE_ELECTION) {
                $shows = $showRepository->getShowsForSeasonElectionEligible($election->getSeason());
                $showCount = count($shows);
            } else {
                $showCount = 0;
            }

            $form = $this->createForm(
                ElectionVoteType::class,
                $electionVote,
                [
                    'attr' => [
                        'id' => 'election_vote_' . $electionVote->getId(),
                        'class' => 'election_vote_form',
                    ],
                    'election_type' => $election->getElectionType(),
                    'show_count' => $showCount,
                    'form_key' => 0,
                ]
            );
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                if ($maxVotes > 0 && $currentVoteCount >= $maxVotes && $electionVote->getChosen()) {
                    $electionVote->setChosen(false);
                    $em->persist($electionVote);
                    $em->flush();
                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse(
                            ['data' => [
                                'status' => 'failure',
                                'message' => 'Too many votes, limit is ' . $maxVotes . '. Please unselect another choice first.'
                            ]],
                            Response::HTTP_BAD_REQUEST
                        );
                    }

                    throw new UnauthorizedHttpException('This page should never be requested directly.');
                }
                $em->flush();

                if ($request->isXmlHttpRequest()) {
                    if ($election->getElectionType() === Election::RANKED_CHOICE_ELECTION) {
                        $message = 'Change received.';
                    } else {
                        $newCurrentVoteCount = ($electionVote->getChosen()) ? $currentVoteCount + 1 : $currentVoteCount - 1;
                        $remainingChoices = $maxVotes - $newCurrentVoteCount;
                        switch ($remainingChoices) {
                            case 0:
                                $message = $maxVotes === 1 ? 'Vote received.' : 'All votes received.';
                                break;
                            case 1:
                                if ($newCurrentVoteCount > $currentVoteCount) {
                                    $message = 'Vote received, 1 choice left.';
                                } else {
                                    $message = 'Change received, 1 choice left.';
                                }
                                break;
                            default:
                                $votesIncreased = $newCurrentVoteCount > $currentVoteCount;
                                if ($maxVotes > 0) {
                                    if ($votesIncreased) {
                                        $message = 'Vote received, ' . $remainingChoices . ' choices left.';
                                    } else {
                                        $message = 'Change received, ' . $remainingChoices . ' choices left.';
                                    }
                                } elseif ($votesIncreased) {
                                    $message = 'Vote received.';
                                } else {
                                    $message = 'Change received.';
                                }
                        }
                    }
                    return new JsonResponse(
                        ['data' => [
                            'status' => 'success',
                            'message' => $message
                        ]]
                    );
                }

                throw new UnauthorizedHttpException('This page should never be requested directly.');
            }

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(
                    ['data' => [
                        'status' => 'failure',
                        'message' => "Submitted data was invalid"
                    ]],
                    Response::HTTP_BAD_REQUEST
                );

//                $html = $this->renderView('election_vote/edit.html.twig', [
//                    'election_vote' => $electionVote,
//                    'election' => $election,
//                    'form' => $form->createView(),
//                ]);
//                return new Response($html, 400);
            }
        } /** @noinspection PhpUnusedLocalVariableInspection */ catch (UniqueConstraintViolationException $e) {
            return new JsonResponse(
                ['data' => [
                    'status' => 'failed',
                    'message' => 'Internal error, please reload the page.'
                ]],
                Response::HTTP_BAD_REQUEST);
        }

        throw new UnauthorizedHttpException('This page should never be requested directly.');
    }
}
