<?php
/** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */

namespace App\Controller;

use App\Entity\Season;
use App\Entity\ShowSeasonScore;
use App\Entity\User;
use App\Repository\ActivityRepository;
use App\Repository\ElectionRepository;
use App\Repository\ScoreRepository;
use App\Repository\SeasonRepository;
use App\Repository\ShowRepository;
use App\Repository\ShowSeasonScoreRepository;
use App\Repository\UserRepository;
use App\Service\SelectedAllWatchModeHelper;
use App\Service\SelectedSeasonHelper;
use App\Service\SelectedSortHelper;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function Symfony\Component\String\u;

class AllWatchController extends AbstractController
{
    /**
     * @Route("/community/watch", name="all_watch_index", options={"expose"=true})
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param SeasonRepository $seasonRepository
     * @param ShowRepository $showRepository
     * @param ShowSeasonScoreRepository $showSeasonScoreRepository
     * @param UserRepository $userRepository
     * @param ScoreRepository $scoreRepository
     * @param ActivityRepository $activityRepository
     * @param ElectionRepository $electionRepository
     * @param SelectedSeasonHelper $selectedSeasonHelper
     * @param SelectedSortHelper $selectedSortHelper
     * @param SelectedAllWatchModeHelper $selectedAllWatchModeHelper
     * @return Response
     * @throws Exception
     * @throws NonUniqueResultException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function index(
        Request $request,
        EntityManagerInterface $em,
        SeasonRepository $seasonRepository,
        ShowRepository $showRepository,
        ShowSeasonScoreRepository $showSeasonScoreRepository,
        UserRepository $userRepository,
        ScoreRepository $scoreRepository,
        ActivityRepository $activityRepository,
        ElectionRepository $electionRepository,
        SelectedSeasonHelper $selectedSeasonHelper,
        SelectedSortHelper $selectedSortHelper,
        SelectedAllWatchModeHelper $selectedAllWatchModeHelper
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive();

        $userKeys = $this->loadUserKeys($userRepository);
        $userCount = count($userKeys);
        $selectedSortName = $selectedSortHelper->getSelectedSort($request,'community_watch');
        $data = [];
        $season = $selectedSeasonHelper->getSelectedSeason($request);

        $showData = $this->getShowData(
            $season,
            $showRepository,
            $selectedSortName,
            $scoreRepository,
            $activityRepository,
            $showSeasonScoreRepository,
            $em,
            $userKeys,
            $data
        );

        /** @var User $user */
        $user = $this->getUser();
        $selectedViewMode = $selectedAllWatchModeHelper->getSelectedMode($request, $user);

        $selectedSeasonId = ($season === null) ? null : $season->getId();
        $sortOptions = [
            'show_asc' => 'Show &#9660;',
            'show_desc' => 'Show &#9650;',
            'activity_desc' => 'Activity &#9660;',
            'activity_asc' => 'Activity &#9650;',
            'recommendations_desc' => 'Recommendations &#9660;',
            'recommendations_asc' => 'Recommendations &#9650;',
        ];
        $seasons = $seasonRepository->getAllInRankOrder();

        return $this->render('all_watch/index.html.twig', [
            'controller_name' => 'AllWatchController',
            'seasons' => $seasons,
            'selectedSeasonId' => $selectedSeasonId,
            'users' => $showData['userKeys'],
            'user' => $user,
            'data' => $showData['data'],
            'total_columns' => 2 + $userCount,
            'selectedSortName' => $selectedSortName,
            'sortOptions' => $sortOptions,
            'selectedViewMode' => $selectedViewMode,
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @Route("/community/export", name="all_watch_export", options={"expose"=true})
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param ShowRepository $showRepository
     * @param ShowSeasonScoreRepository $showSeasonScoreRepository
     * @param UserRepository $userRepository
     * @param ScoreRepository $scoreRepository
     * @param ActivityRepository $activityRepository
     * @param SelectedSeasonHelper $selectedSeasonHelper
     * @param SelectedSortHelper $selectedSortHelper
     * @return Response
     * @throws Exception
     * @throws NonUniqueResultException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function export(
        Request $request,
        EntityManagerInterface $em,
        ShowRepository $showRepository,
        ShowSeasonScoreRepository $showSeasonScoreRepository,
        UserRepository $userRepository,
        ScoreRepository $scoreRepository,
        ActivityRepository $activityRepository,
        SelectedSeasonHelper $selectedSeasonHelper,
        SelectedSortHelper $selectedSortHelper

    ): Response {
        /** @noinspection DuplicatedCode */
        $userKeys = $this->loadUserKeys($userRepository);
        $selectedSortName = $selectedSortHelper->getSelectedSort($request,'community_watch');
        $data = [];
        $season = $selectedSeasonHelper->getSelectedSeason($request);

        $showData = $this->getShowData(
            $season,
            $showRepository,
            $selectedSortName,
            $scoreRepository,
            $activityRepository,
            $showSeasonScoreRepository,
            $em,
            $userKeys,
            $data
        );
        $data = $showData['data'];
        $output = [];

        $usersWithData = [];
        foreach ($data as $show) {
            foreach ($show['scores'] as $showSeasonScore) {
                $usersWithData[$showSeasonScore->getUser()->getDiscordUsername().' activity'] = '';
                $usersWithData[$showSeasonScore->getUser()->getDiscordUsername().' rec'] = '';
            }
        }
        $header = null;
        foreach ($data as $row) {
            $userData = [];
            foreach ($usersWithData as $key => $value) {
                $userData[$key] = $value;
            }
            $myRow = [];
            $myRow['title'] = $row['show']['title'];
            foreach($row['scores'] as $score) {
                if ($score->getUser() && $score->getScore()) {
                    $userData[$score->getUser()->getDiscordUsername() . ' rec'] = $score->getScore()->getName();
                }
            }
            foreach($row['scores'] as $activity) {
                if ($activity->getUser() && $activity->getActivity()) {
                    $userData[$activity->getUser()->getDiscordUsername() . ' activity'] = $activity->getActivity()->getNickname();
                }
            }
            foreach($userData as $key => $value) {
                $myRow[$key] = $value;
            }
            $myRow['PTW'] = (int)$row['consolidatedActivities']['ptw_count'];
            $myRow['Watching'] = (int)$row['consolidatedActivities']['watching_count'];
            $myRow['Stopped'] = (int)$row['consolidatedActivities']['stopped_count'];
            $myRow['Unfavorable'] = (int)$row['consolidatedScores']['unfavorable_count'];
            $myRow['Neutral'] = (int)$row['consolidatedScores']['neutral_count'];
            $myRow['Favorable'] = (int)$row['consolidatedScores']['favorable_count'];
            $myRow['Highly favorable'] = (int)$row['consolidatedScores']['highly_favorable_count'];
            $myRow['Th8a should'] = (int)$row['consolidatedScores']['th8a_count'];
            $myRow['Total count'] = (int)$row['consolidatedScores']['all_count'];
            $myRow['Calculated rec'] = (float)$row['consolidatedScores']['score_total'];

            if ($header === null) {
                $header = array_keys($myRow);
                $output[] = $header;
            }

            $output[] = $myRow;
        }

        return $this->buildCsvResponse($output);

    }

    private function buildCsvResponse(array $data): Response
    {
        $fp = fopen('php://temp', 'wb');
        foreach ($data as $datum) {
            fputcsv($fp, $datum);
        }

        rewind($fp);
        $response = new Response(stream_get_contents($fp));
        fclose($fp);

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="all_watches.csv"');

        return $response;
    }

    /**
     * @param Season|null $season
     * @param ShowRepository $showRepository
     * @param string|null $selectedSortName
     * @param ScoreRepository $scoreRepository
     * @param ActivityRepository $activityRepository
     * @param ShowSeasonScoreRepository $showSeasonScoreRepository
     * @param EntityManagerInterface $em
     * @param array $userKeys
     * @param array $data
     * @return array
     * @throws Exception
     * @throws NonUniqueResultException
     * @throws \Doctrine\DBAL\Driver\Exception
     * @noinspection DuplicatedCode
     */
    private function getShowData(
        ?Season $season,
        ShowRepository $showRepository,
        ?string $selectedSortName,
        ScoreRepository $scoreRepository,
        ActivityRepository $activityRepository,
        ShowSeasonScoreRepository $showSeasonScoreRepository,
        EntityManagerInterface $em,
        array $userKeys,
        array $data
    ): array {
        if ($season !== null) {
            $maxScoreCount = 0;
            $minScoreCount = 0;
            $maxActivityCount = 0;
            $shows = $showRepository->getShowsForSeason($season, null, $selectedSortName);
            if ($selectedSortName !== 'show_asc' && $selectedSortName !== 'show_desc') {
                // When sorting by a calculated value (avg or sum in this case), Doctrine returns
                // an array of arrays, with each entry looking like this:
                //   [ 0 => $show, 'calculated_value' => "1.000" ]
                $actualShows = [];
                foreach ($shows as $showContainer) {
                    $actualShows[] = $showContainer[0];
                }
                $shows = $actualShows;
            }

            // Add in any missing individual score rows
            /** @var User $user */
            $user = $this->getUser();
            $defaultScore = $scoreRepository->getDefaultScore();
            $defaultActivity = $activityRepository->getDefaultActivity();
            foreach ($shows as $show) {
                $score = $showSeasonScoreRepository->getForUserAndShowAndSeason(
                    $user,
                    $show,
                    $season
                );
                if ($score === null) {
                    $score = new ShowSeasonScore();
                    $score->setUser($user);
                    $score->setShow($show);
                    $score->setSeason($season);
                    $score->setScore($defaultScore);
                    $score->setActivity($defaultActivity);
                    $em->persist($score);
                    $em->flush();
                }
            }
            // End of adding missing score rows

            $activityValues = [];
            $activities = $activityRepository->findAll();
            foreach ($activities as $activity) {
                $activityValues[$activity->getSlug()] = $activity->getValue();
            }

            $consolidatedShowActivities = $showSeasonScoreRepository->getActivitiesForSeason($season);
            $keyedConsolidatedShowActivities = [];
            foreach ($consolidatedShowActivities as $consolidatedShowActivity) {
                $consolidatedShowActivity['total_count'] =
                    ($consolidatedShowActivity['watching_count'] * $activityValues['watching']) +
                    ($consolidatedShowActivity['ptw_count'] * $activityValues['ptw']);
                $maxActivityCount = max([
                    $maxActivityCount,
                    $consolidatedShowActivity['total_count']
                ]);
                $consolidatedShowActivity['activities_array'] = '[' .
                    ($consolidatedShowActivity['watching_count'] * $activityValues['watching']) . ',' .
                    ($consolidatedShowActivity['ptw_count'] * $activityValues['ptw']) . ']';
                $keyedConsolidatedShowActivities[$consolidatedShowActivity['show_id']] = $consolidatedShowActivity;
            }

            $consolidatedShowScores = $showSeasonScoreRepository->getScoresForSeason($season);
            $keyedConsolidatedShowScores = [];
            foreach ($consolidatedShowScores as $consolidatedShowScore) {

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
                if ($moodAverageValue > 5) {
//                    $moodEmoji = 'emoji-heart-eyes-fill';
                    $moodEmoji = <<<EOF
<i class="bi bi-circle-fill bi-x-upper-half d-flex align-items-center mood-emoji-heart-eyes-color"></i>
<i class="bi bi-circle-fill bi-x-lower-half d-flex align-items-center mood-emoji-dark-mouth-color"></i>
<i class="bi bi-emoji-heart-eyes-fill d-flex align-items-center mood-emoji-favorable-color"></i>
EOF;
                } elseif ($moodAverageValue > 1) {
                    // $moodEmoji = 'emoji-smile-fill';
                    $moodEmoji = <<<EOF
<i class="bi bi-circle-fill bi-x-shrunk-circle d-flex align-items-center mood-emoji-dark-mouth-color"></i>
<i class="bi bi-emoji-smile-fill d-flex align-items-center mood-emoji-favorable-color"></i>
EOF;
                } elseif ($moodAverageValue > -1) {
                    // $moodEmoji = 'emoji-neutral-fill';
                    $moodEmoji = <<<EOF
<i class="bi bi-circle-fill bi-x-shrunk-circle d-flex align-items-center mood-emoji-dark-mouth-color"></i>
<i class="bi bi-emoji-neutral-fill d-flex align-items-center mood-emoji-neutral-color"></i>
EOF;
                } else {
                    // $moodEmoji = 'emoji-frown-fill';
                    $moodEmoji = <<<EOF
<i class="bi bi-circle-fill bi-x-shrunk-circle d-flex align-items-center mood-emoji-light-mouth-color"></i>
<i class="bi bi-emoji-frown-fill d-flex align-items-center mood-emoji-unfavorable-color"></i>
EOF;
                }
                $maxScoreCount = max([
                    $maxScoreCount,
                    ($consolidatedShowScore['th8a_count'] +
                        $consolidatedShowScore['highly_favorable_count'] +
                        $consolidatedShowScore['favorable_count'] +
                        $consolidatedShowScore['neutral_count'])
                ]);
                $minScoreCount = max([
                    $minScoreCount,
                    $consolidatedShowScore['unfavorable_count']
                ]);
                $consolidatedShowScore['scores_array'] = '[' .
                    $consolidatedShowScore['th8a_count'] . ',' .
                    $consolidatedShowScore['highly_favorable_count'] . ',' .
                    $consolidatedShowScore['favorable_count'] . ',' .
                    $consolidatedShowScore['neutral_count'] . ',' .
                    $consolidatedShowScore['unfavorable_count'] .
                    ']';
                $consolidatedShowScore['mood_array'] = [
                    'mood_average_value' => $moodAverageValue,
                    'mood_emoji' => $moodEmoji,
                ];
                $keyedConsolidatedShowScores[$consolidatedShowScore['show_id']] = $consolidatedShowScore;
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
                $scoreValues = $showSeasonScoreRepository->findAllForSeasonAndShow($season, $show, 'displayname');
                $filteredScores = [];
                foreach ($scoreValues as $scoreValue) {
                    $score = $scoreValue[0];
                    if ($score->getScore() !== null && $score->getScore()->getValue() !== 0) {
                        $userKeys[$score->getUser()->getUsername()] = true;
                    }
                    if (
                        ($score->getScore() && $score->getScore()->getSlug() !== 'none')
                        || ($score->getActivity() && $score->getActivity()->getSlug() !== 'none')
                    ) {
                        $filteredScores[] = $score;
                    }
                }
                $data[] = [
                    'show' => $showInfo,
                    'consolidatedActivities' => $keyedConsolidatedShowActivities[$show->getId()] ?? null,
                    'consolidatedScores' => $keyedConsolidatedShowScores[$show->getId()] ?? null,
                    'scores' => $filteredScores,
                    'scoreCount' => count($filteredScores),
                    'maxScoreCount' => $maxScoreCount,
                    'minScoreCount' => $minScoreCount,
                    'maxActivityCount' => $maxActivityCount,
                ];
            }
        }
        return [ 'userKeys' => $userKeys, 'data' => $data ];
    }

    /**
     * @param UserRepository $userRepository
     * @return array
     */
    private function loadUserKeys(UserRepository $userRepository): array
    {
        $users = $userRepository->getAllSorted();
        $userKeys = [];
        foreach ($users as $user) {
            $userKeys[$user->getUsername()] = false;
        }
        unset($users);
        return $userKeys;
    }
}
