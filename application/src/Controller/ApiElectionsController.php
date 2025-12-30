<?php /** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */

namespace App\Controller;

use App\Entity\Election;
use App\Repository\ElectionRepository;
use App\Service\VoterInfoHelper;
use DateTime;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ApiElectionsController extends AbstractController
{
    /**
     * Get a paginated list of all elections (excluding active and future elections)
     *
     * @param Request            $request
     * @param ElectionRepository $electionRepository
     * @return JsonResponse
     * @throws \Exception
     */
    #[Route('/api/v1/elections', name: 'api_elections', options: ['expose' => false], methods: ['GET'])]
    public function index(
        Request $request,
        ElectionRepository $electionRepository
    ): JsonResponse {
        // Check if user has required role
        if (!$this->isGranted('ROLE_SWL_STAFF')) {
            return new JsonResponse([
                'status' => 403,
                'message' => 'Access denied. ROLE_SWL_STAFF or ROLE_SWL_ADMIN required.',
            ], 403);
        }

        // Get pagination parameters
        $page = max(1, (int)$request->query->get('page', 1));
        $perPage = max(1, min(100, (int)$request->query->get('perPage', 5)));

        // Get all elections ordered by start date descending
        $allElections = $electionRepository->findBy([], ['startDate' => 'DESC']);

        // Filter out active elections and future elections
        $filteredElections = array_filter($allElections, static function($election) {
            $now = new DateTime();
            // Exclude if currently active
            if ($election->isActive()) {
                return false;
            }
            // Exclude if start date is in the future
            if ($election->getStartDate() && $election->getStartDate() > $now) {
                return false;
            }
            return true;
        });

        // Re-index the array after filtering
        $filteredElections = array_values($filteredElections);

        $totalElections = count($filteredElections);
        $totalPages = (int)ceil($totalElections / $perPage);

        // Calculate offset and get page of results
        $offset = ($page - 1) * $perPage;
        $elections = array_slice($filteredElections, $offset, $perPage);

        $data = [
            'status' => 200,
            'pagination' => [
                'currentPage' => $page,
                'perPage' => $perPage,
                'totalItems' => $totalElections,
                'totalPages' => $totalPages,
            ],
            'elections' => [],
        ];

        foreach ($elections as $election) {
            $data['elections'][] = $election->jsonSerialize();
        }

        return new JsonResponse($data);
    }

    /**
     * Get a single election by ID with vote tallies (only if election is not active)
     *
     * @param int $id
     * @param ElectionRepository $electionRepository
     * @param VoterInfoHelper $voterInfoHelper
     * @return JsonResponse
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    #[Route('/api/v1/elections/{id}', name: 'api_election_by_id', requirements: ['id' => '\d+'], options: ['expose' => false], methods: ['GET'])]
    public function byId(
        int $id,
        ElectionRepository $electionRepository,
        VoterInfoHelper $voterInfoHelper
    ): JsonResponse {
        // Check if user has required role
        if (!$this->isGranted('ROLE_SWL_STAFF')) {
            return new JsonResponse([
                'status' => 403,
                'message' => 'Access denied. ROLE_SWL_STAFF or ROLE_SWL_ADMIN required.',
            ], 403);
        }

        $election = $electionRepository->find($id);

        if ($election === null) {
            return new JsonResponse([
                'status' => 404,
                'message' => 'Election not found',
            ], 404);
        }

        // Return error if election is currently active
        if ($election->isActive()) {
            return new JsonResponse([
                'status' => 400,
                'message' => 'Cannot retrieve data for an active election',
            ], 400);
        }

        return $this->getElectionData($election, $voterInfoHelper);
    }

    /**
     * Get the most recent election with vote tallies (excluding active and future elections)
     *
     * @param ElectionRepository $electionRepository
     * @param VoterInfoHelper $voterInfoHelper
     * @return JsonResponse
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    #[Route('/api/v1/elections/most_recent', name: 'api_election_most_recent', options: ['expose' => false], methods: ['GET'])]
    public function mostRecent(
        ElectionRepository $electionRepository,
        VoterInfoHelper $voterInfoHelper
    ): JsonResponse {
        // Check if user has required role
        if (!$this->isGranted('ROLE_SWL_STAFF')) {
            return new JsonResponse([
                'status' => 403,
                'message' => 'Access denied. ROLE_SWL_STAFF or ROLE_SWL_ADMIN required.',
            ], 403);
        }

        // Get all elections ordered by start date descending
        $allElections = $electionRepository->findBy([], ['startDate' => 'DESC']);

        // Filter to find the most recent non-active, non-future election
        $now = new DateTime();
        $election = null;
        foreach ($allElections as $e) {
            // Skip if currently active
            if ($e->isActive()) {
                continue;
            }
            // Skip if start date is in the future
            if ($e->getStartDate() && $e->getStartDate() > $now) {
                continue;
            }
            // This is the most recent non-active, non-future election
            $election = $e;
            break;
        }

        if ($election === null) {
            return new JsonResponse([
                'status' => 404,
                'message' => 'No completed elections found',
            ], 404);
        }

        return $this->getElectionData($election, $voterInfoHelper);
    }

    /**
     * Get election data with vote tallies (same as displayed on /admin/election/{id})
     *
     * @param Election $election
     * @param VoterInfoHelper $voterInfoHelper
     * @return JsonResponse
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    private function getElectionData(
        Election $election,
        VoterInfoHelper $voterInfoHelper
    ): JsonResponse {
        $info = $voterInfoHelper->getInfo($election);

        $data = [
            'status' => 200,
            'election' => $election->jsonSerialize(),
            'totalVoterCount' => $info['totalVoterCount'],
            'voteTallies' => [],
        ];

        $voteTallies = $info['voteTallies'];

        // For simple elections, sort by buffedVoteCount (highest first) and add rank
        if ($election->getElectionType() === Election::SIMPLE_ELECTION) {
            // Sort by buffedVoteCount descending
            usort($voteTallies, static function($a, $b) {
                return $b->getBuffedVoteCount() <=> $a->getBuffedVoteCount();
            });

            // Add rank field with tie handling
            $currentRank = 1;
            $previousCount = null;

            foreach ($voteTallies as $index => $voteTally) {
                $currentCount = $voteTally->getBuffedVoteCount();

                if ($previousCount === null || $currentCount !== $previousCount) {
                    // Not a tie, set rank to current position (index + 1)
                    // This naturally handles the "bump" after ties
                    $currentRank = $index + 1;
                }
                // If it's a tie, keep the same rank as previous

                $serialized = $voteTally->jsonSerialize();
                $serialized['rank'] = $currentRank;
                $data['voteTallies'][] = $serialized;

                $previousCount = $currentCount;
            }
        } else {
            // For ranked-choice elections, tallies already have rank from minimax
            // Just serialize them as-is (rank is already in the jsonSerialize output)
            foreach ($voteTallies as $voteTally) {
                $data['voteTallies'][] = $voteTally->jsonSerialize();
            }
        }

        return new JsonResponse($data);
    }
}
