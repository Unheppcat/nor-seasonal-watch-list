<?php /** @noinspection UnknownInspectionInspection */

/** @noinspection PhpUnused */

namespace App\Controller;

use App\Entity\Election;
use App\Entity\ElectionShowBuff;
use App\Entity\View\BuffedElection;
use App\Entity\View\VoteTally;
use App\Form\BuffedElectionType;
use App\Form\ElectionType;
use App\Repository\ElectionRepository;
use App\Repository\ElectionShowBuffRepository;
use App\Repository\ElectionVoteRepository;
use App\Repository\ShowRepository;
use App\Service\ExportHelper;
use App\Service\VoterInfoHelper;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/election")
 */
class AdminElectionController extends AbstractController
{
    /**
     * @Route("/", name="admin_election_index", methods={"GET"})
     * @param ElectionRepository $electionRepository
     * @return Response
     */
    public function index(
        ElectionRepository $electionRepository
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive();
        return $this->render('election/index.html.twig', [
            'user' => $this->getUser(),
            'elections' => $electionRepository->findBy([], ['startDate' => 'desc']),
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @Route("/new", name="admin_election_new", methods={"GET","POST"})
     * @param Request $request
     * @param ElectionRepository $electionRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function new(
        Request $request,
        ElectionRepository $electionRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive();
        $election = new Election();
        $form = $this->createForm(ElectionType::class, $election);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($election);
            $entityManager->flush();

            return $this->redirectToRoute('admin_election_index');
        }

        return $this->render('election/new.html.twig', [
            'user' => $this->getUser(),
            'election' => $election,
            'form' => $form->createView(),
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @Route("/{id}", name="admin_election_show", methods={"GET"}, requirements={"id":"\d+"})
     * @param Election $election
     * @param VoterInfoHelper $voterInfoHelper
     * @return Response
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function show(
        Election $election,
        VoterInfoHelper $voterInfoHelper
    ): Response {
        $info = $voterInfoHelper->getInfo($election);
        return $this->render('election/show.html.twig', [
            'user' => $this->getUser(),
            'election' => $election,
            'votesInfo' => $info['votesInfo'],
            'totalVoterCount' => $info['totalVoterCount'],
            'voteTallies' => $info['voteTallies'],
            'electionIsActive' => $info['electionIsActive'],
        ]);
    }

    /**
     * Export election data as a CSV file
     *
     * @Route("/export/{id}", name="admin_election_export", methods={"GET"}, requirements={"id":"\d+"})
     * @param VoterInfoHelper $voterInfoHelper
     * @param Election $election
     * @return Response
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function export(
        VoterInfoHelper $voterInfoHelper,
        Election $election
    ): Response {
        $electionName = $election->getSeason() ? $election->getSeason()->getName() : '';
        $filenameParts = [
            str_replace(' ', '-', $electionName),
            $election->getStartDate() ? $election->getStartDate()->format('Ymd-Hi') : 'start',
            $election->getEndDate() ? $election->getEndDate()->format('Ymd-Hi') : 'end'
        ];
        $filename = implode('-', $filenameParts) . '.csv';

        $voterInfoHelper->initializeForExport($election);

        $fp = fopen('php://temp', 'wb');
        $voterInfoHelper->writeExport($fp);

        rewind($fp);
        $response = new Response(stream_get_contents($fp));
        fclose($fp);

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $response;
    }

    /**
     * Export election data as a CSV file
     *
     * @Route("/export_raw/{id}", name="admin_election_export_raw", methods={"GET"}, requirements={"id":"\d+"})
     * @param ExportHelper $exportHelper
     * @param ElectionVoteRepository $electionVoteRepository
     * @param Election $election
     * @return Response
     */
    public function exportRaw(
        ExportHelper $exportHelper,
        ElectionVoteRepository $electionVoteRepository,
        Election $election
    ): Response {
        $electionName = $election->getSeason() ? $election->getSeason()->getName() : '';
        $filenameParts = [
            str_replace(' ', '-', $electionName),
            $election->getStartDate() ? $election->getStartDate()->format('Ymd-Hi') : 'start',
            $election->getEndDate() ? $election->getEndDate()->format('Ymd-Hi') : 'end'
        ];
        $filename = implode('-', $filenameParts) . '-raw.csv';

        // $rawVotes is sorted User, then Show
        $rawVotes = $electionVoteRepository->getRawRankingVoteEntriesForElection($election);

        $showRows = [];
        foreach ($rawVotes as $rawVote) {
            $showTitle = $rawVote->getShow() ? $rawVote->getShow()->getEnglishTitle() : '(unknown title)';
            if (!isset($showRows[$showTitle])) {
                $showRows[$showTitle] = [$showTitle];
            }
            $showRows[$showTitle][] = $rawVote->getRank();
        }

        $userRows = $this->flipArray($showRows);

        $fp = fopen('php://temp', 'wb');
        foreach ($userRows as $userRow) {
            fwrite($fp, $exportHelper->arrayToCsv([...$userRow]) . "\n");
        }

        rewind($fp);
        $response = new Response(stream_get_contents($fp));
        fclose($fp);

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $response;
    }

    private function flipArray(array $showRows): array
    {
        $userRows = [];
        $i = -1;
        foreach ($showRows as $showRow) {
            $i++;
            $j = -1;
            foreach ($showRow as $value) {
                $j++;
                $userRows[$j][$i] = $value;
            }
        }
        return $userRows;
    }

    /**
     * @Route("/{id}/edit", name="admin_election_edit", methods={"GET","POST"}, requirements={"id":"\d+"})
     * @param Request $request
     * @param Election $election
     * @param ElectionRepository $electionRepository
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function edit(
        Request $request,
        Election $election,
        ElectionRepository $electionRepository,
        EntityManagerInterface $em
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive();
        $form = $this->createForm(ElectionType::class, $election);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('admin_election_index');
        }

        return $this->render('election/edit.html.twig', [
            'user' => $this->getUser(),
            'election' => $election,
            'form' => $form->createView(),
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @Route("/{id}/buff", name="admin_election_buff", methods={"GET","POST"}, requirements={"id":"\d+"})
     * @param Request $request
     * @param Election $election
     * @param ShowRepository $showRepository
     * @param ElectionShowBuffRepository $electionShowBuffRepository
     * @param VoterInfoHelper $voterInfoHelper
     * @param EntityManagerInterface $em
     * @return Response
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function buff(
        Request $request,
        Election $election,
        ShowRepository $showRepository,
        ElectionShowBuffRepository $electionShowBuffRepository,
        VoterInfoHelper $voterInfoHelper,
        EntityManagerInterface $em
    ): Response {
        $info = $voterInfoHelper->getInfo($election);
        $buffedElection = new BuffedElection($election);
        $buffedElection->setVoteTallies($info['voteTallies']);

        $form = $this->createForm(BuffedElectionType::class, $buffedElection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($buffedElection->getVoteTallies() as $tally) {
                /** @var VoteTally $tally */
                $showId = $tally->getShowId();
                $buffRule = $tally->getBuffRule();
                $electionShowBuffs = $electionShowBuffRepository->findBy(['election' => $election->getId(), 'animeShow' => $showId]);
                if (empty($electionShowBuffs)) {
                    if (!empty($buffRule)) {
                        $electionShowBuff = new ElectionShowBuff();
                        $electionShowBuff->setElection($election);
                        $show = $showRepository->find($showId);
                        $electionShowBuff->setAnimeShow($show);
                        $electionShowBuff->setBuffRule($buffRule);
                        $em->persist($electionShowBuff);
                    }
                } else {
                    foreach ($electionShowBuffs as $key => $buff) {
                        if ($key === 0) {
                            if (empty($buffRule)) {
                                $em->remove($buff);
                            } else {
                                $buff->setBuffRule($buffRule);
                                $em->persist($buff);
                            }
                        } else {
                            $em->remove($buff);
                        }
                    }
                }
            }
            $em->flush();

            return $this->redirectToRoute('admin_election_index');
        }

        return $this->render('election/buff.html.twig', [
            'user' => $this->getUser(),
            'election' => $election,
            'buffedElection' => $buffedElection,
            'form' => $form->createView(),
            'electionIsActive' => $info['electionIsActive'],
        ]);
    }

    /**
     * @Route("/{id}", name="admin_election_delete", methods={"DELETE"}, requirements={"id":"\d+"})
     * @param Request $request
     * @param Election $election
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function delete(
        Request $request,
        Election $election,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$election->getId(), $request->request->get('_token'))) {
            $entityManager->remove($election);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_election_index');
    }





}
