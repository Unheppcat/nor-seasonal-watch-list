<?php /** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */
/** @noinspection DuplicatedCode */

namespace App\Controller;

use App\Entity\Season;
use App\Repository\SeasonRepository;
use App\Repository\ShowRepository;
use App\Repository\ShowSeasonScoreRepository;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use function Symfony\Component\String\u;

class ApiWatchesController extends AbstractController
{
    /**
     * @param int $year
     * @param string $yearPart
     * @param SeasonRepository $seasonRepository
     * @param ShowRepository $showRepository
     * @param ShowSeasonScoreRepository $showSeasonScoreRepository
     * @return JsonResponse
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    #[Route('/api/v1/seasons/{year}/{yearPart}/community-watches', name: 'api_watches_by_year_and_year_part', options: ['expose' => false])]
    public function byYearAndYearPart(
        int $year,
        string $yearPart,
        SeasonRepository $seasonRepository,
        ShowRepository $showRepository,
        ShowSeasonScoreRepository $showSeasonScoreRepository
    ): JsonResponse {
        /** @var Season|null $selectedSeason */
        $selectedSeasons = $seasonRepository->findBy(['year' => $year, 'yearPart' => $yearPart]);

        if (empty($selectedSeasons)) {
            $errorInfo = [
                'status' => 404,
                'message' => 'Season not found',
            ];
            return new JsonResponse($errorInfo, 404);
        }
        if (count($selectedSeasons) !== 1) {
            $errorInfo = [
                'status' => 400,
                'message' => 'Ambiguous season, too many matches found',
            ];
            return new JsonResponse($errorInfo, 400);
        }

        return $this->returnResult($selectedSeasons[0], $showRepository, $showSeasonScoreRepository);
    }

    /**
     * @param int $id
     * @param SeasonRepository $seasonRepository
     * @param ShowRepository $showRepository
     * @param ShowSeasonScoreRepository $showSeasonScoreRepository
     * @return JsonResponse
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    #[Route('/api/v1/seasons/{id}/community-watches', name: 'api_watches_by_season_id', options: ['expose' => false])]
    public function bySeasonId(
        int $id,
        SeasonRepository $seasonRepository,
        ShowRepository $showRepository,
        ShowSeasonScoreRepository $showSeasonScoreRepository
    ): JsonResponse {
        $selectedSeason = $seasonRepository->find($id);
        if ($selectedSeason === null) {
            $errorInfo = [
                'status' => 404,
                'message' => 'Season not found',
            ];
            return new JsonResponse($errorInfo, 404);
        }

        return $this->returnResult($selectedSeason, $showRepository, $showSeasonScoreRepository);
    }

    /**
     * @param Season $selectedSeason
     * @param ShowRepository $showRepository
     * @param ShowSeasonScoreRepository $showSeasonScoreRepository
     * @return array
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    private function getValues(
        Season $selectedSeason,
        ShowRepository $showRepository,
        ShowSeasonScoreRepository $showSeasonScoreRepository
    ): array {
        $values = [];

        $values['shows'] = [];
        $maxScore = 0;
        $maxActivityCount = 0;
        $shows = $showRepository->getShowsForSeason($selectedSeason);
        $consolidatedShowActivities = $showSeasonScoreRepository->getActivitiesForSeason($selectedSeason);
        $keyedConsolidatedShowActivities = [];
        foreach ($consolidatedShowActivities as $consolidatedShowActivity) {
            $showId = $consolidatedShowActivity['show_id'];
            unset($consolidatedShowActivity['show_id']);
            $consolidatedShowActivity['watching_count'] = (int)$consolidatedShowActivity['watching_count'];
            $consolidatedShowActivity['ptw_count'] = (int)$consolidatedShowActivity['ptw_count'];
            $maxActivityCount = max([
                $maxActivityCount,
                $consolidatedShowActivity['watching_count'],
                $consolidatedShowActivity['ptw_count'],
            ]);
            $keyedConsolidatedShowActivities[$showId] = $consolidatedShowActivity;
        }

        $consolidatedShowScores = $showSeasonScoreRepository->getScoresForSeason($selectedSeason);
        $keyedConsolidatedShowScores = [];
        foreach ($consolidatedShowScores as $consolidatedShowScore) {
            $showId = $consolidatedShowScore['show_id'];
            unset($consolidatedShowScore['show_id']);
            $consolidatedShowScore['yes_count'] = (int)$consolidatedShowScore['yes_count'];
            $consolidatedShowScore['no_count'] = (int)$consolidatedShowScore['no_count'];
            $consolidatedShowScore['unfavorable_count'] = (int)$consolidatedShowScore['unfavorable_count'];
            $consolidatedShowScore['neutral_count'] = (int)$consolidatedShowScore['neutral_count'];
            $consolidatedShowScore['favorable_count'] = (int)$consolidatedShowScore['favorable_count'];
            $consolidatedShowScore['highly_favorable_count'] = (int)$consolidatedShowScore['highly_favorable_count'];
            $consolidatedShowScore['th8a_count'] = (int)$consolidatedShowScore['th8a_count'];
            $consolidatedShowScore['all_count'] = (int)$consolidatedShowScore['all_count'];
            $consolidatedShowScore['score_total'] = (int)$consolidatedShowScore['score_total'];

            if ($consolidatedShowScore['all_count'] > 0) {
                $consolidatedShowScore['th8a_percent'] = round(($consolidatedShowScore['th8a_count'] / $consolidatedShowScore['all_count']) * 100);
                $consolidatedShowScore['highly_favorable_percent'] = round(($consolidatedShowScore['highly_favorable_count'] / $consolidatedShowScore['all_count']) * 100);
                $consolidatedShowScore['favorable_percent'] = round(($consolidatedShowScore['favorable_count'] / $consolidatedShowScore['all_count']) * 100);
                $consolidatedShowScore['neutral_percent'] = round(($consolidatedShowScore['neutral_count'] / $consolidatedShowScore['all_count']) * 100);
                $consolidatedShowScore['unfavorable_percent'] = round(($consolidatedShowScore['unfavorable_count'] / $consolidatedShowScore['all_count']) * 100);
            } else {
                $consolidatedShowScore['th8a_percent'] = 0;
                $consolidatedShowScore['highly_favorable_percent'] = 0;
                $consolidatedShowScore['favorable_percent'] = 0;
                $consolidatedShowScore['neutral_percent'] = 0;
                $consolidatedShowScore['unfavorable_percent'] = 0;
            }

            $moodAverageValue = ($consolidatedShowScore['all_count'] > 0) ?
                $consolidatedShowScore['score_total'] / $consolidatedShowScore['all_count'] : 0;
            $maxScore = max([
                $maxScore,
                $consolidatedShowScore['th8a_count'],
                $consolidatedShowScore['highly_favorable_count'],
                $consolidatedShowScore['favorable_count'],
                $consolidatedShowScore['neutral_count'],
                $consolidatedShowScore['unfavorable_count'],
            ]);
            $consolidatedShowScore['mood_average_value'] = $moodAverageValue;
            $keyedConsolidatedShowScores[$showId] = $consolidatedShowScore;
        }
        unset($consolidatedShowScores);
        foreach ($shows as $show) {
            $showInfo = [
                'id' => $show->getId(),
                'title' => u($show->getAllTitles())->truncate(240, '...', false),
                'shortTitle' => u($show->getJapaneseTitle())->truncate(100, '...', false),
                'coverImage' => $show->getCoverImageLarge(),
                'coverImageMedium' => $show->getCoverImageMedium(),
                'anilistId' => $show->getAnilistId(),
                'anilistShowUrl' => $show->getSiteUrl() ?: "https://anilist.co/anime/" . $show->getAnilistId(),
                'malShowUrl' => $show->getMalId() ? "https://myanimelist.net/anime/" . $show->getMalId() : '',
            ];
            $scores = $showSeasonScoreRepository->findAllForSeasonAndShow($selectedSeason, $show);
            $filteredScores = [];
            foreach ($scores as $score) {
                if (
                    ($score->getScore() && $score->getScore()->getSlug() !== 'none')
                    || ($score->getActivity() && $score->getActivity()->getSlug() !== 'none')
                ) {
                    $filteredScores[] = $score;
                }
            }
            $values['shows'][] = [
                'show' => $showInfo,
                'consolidatedActivities' => $keyedConsolidatedShowActivities[$show->getId()] ?? null,
                'consolidatedRecommendations' => $keyedConsolidatedShowScores[$show->getId()] ?? null,
                'scores' => $filteredScores,
                'scoreCount' => count($filteredScores),
                'maxScore' => $maxScore,
                'maxActivityCount' => $maxActivityCount,
            ];
        }
        return $values;
    }

    /**
     * @param Season $selectedSeason
     * @param ShowRepository $showRepository
     * @param ShowSeasonScoreRepository $showSeasonScoreRepository
     * @return JsonResponse
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    private function returnResult(
        Season $selectedSeason,
        ShowRepository $showRepository,
        ShowSeasonScoreRepository $showSeasonScoreRepository
    ): JsonResponse {
        $values = $this->getValues($selectedSeason, $showRepository, $showSeasonScoreRepository);
        $values['status'] = 200;
        $values['season'] = $selectedSeason->jsonSerialize();
        foreach ($values['shows'] as $key => $datum) {
            foreach ($datum['scores'] as $subKey => $score) {
                $values['shows'][$key]['scores'][$subKey] = $score->jsonSerializeForWatchApi();
            }
        }
        return new JsonResponse($values);
    }

}
