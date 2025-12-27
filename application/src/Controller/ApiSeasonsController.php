<?php /** @noinspection UnknownInspectionInspection */

/** @noinspection PhpUnused */

namespace App\Controller;

use App\Repository\SeasonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ApiSeasonsController extends AbstractController
{
    /**
     * @param SeasonRepository $seasonRepository
     * @return JsonResponse
     */
    #[Route('/api/v1/seasons', name: 'api_seasons', options: ['expose' => false])]
    public function index(SeasonRepository $seasonRepository): JsonResponse
    {
        $seasons = $seasonRepository->getAllInRankOrder();
        $data = [];
        foreach ($seasons as $season) {
            $data[] = $season->jsonSerialize();
        }
        return new JsonResponse($data);
    }
}
