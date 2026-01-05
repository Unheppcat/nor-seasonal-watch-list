<?php
/** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */

namespace App\Controller;

use App\Entity\ShowSeasonScore;
use App\Entity\User;
use App\Form\ShowSeasonScoreType;
use App\Repository\ActivityRepository;
use App\Repository\ElectionRepository;
use App\Repository\ScoreRepository;
use App\Repository\SeasonRepository;
use App\Repository\ShowRepository;
use App\Repository\ShowSeasonScoreRepository;
use App\Service\SelectedSeasonHelper;
use App\Service\SelectedSortHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MyWatchController extends AbstractController
{
    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param SeasonRepository $seasonRepository
     * @param ShowRepository $showRepository
     * @param ShowSeasonScoreRepository $showSeasonScoreRepository
     * @param ScoreRepository $scoreRepository
     * @param ActivityRepository $activityRepository
     * @param ElectionRepository $electionRepository
     * @param SelectedSeasonHelper $selectedSeasonHelper
     * @param FormFactoryInterface $formFactory
     * @param SelectedSortHelper $selectedSortHelper
     * @return Response
     * @throws NonUniqueResultException
     */
    #[Route('/personal/watch', name: 'my_watch_index', options: ['expose' => true])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        SeasonRepository $seasonRepository,
        ShowRepository $showRepository,
        ShowSeasonScoreRepository $showSeasonScoreRepository,
        ScoreRepository $scoreRepository,
        ActivityRepository $activityRepository,
        ElectionRepository $electionRepository,
        SelectedSeasonHelper $selectedSeasonHelper,
        FormFactoryInterface $formFactory,
        SelectedSortHelper $selectedSortHelper
    ): Response {
        $electionIsActive = $electionRepository->electionIsAvailable();
        $seasons = $seasonRepository->getAllInRankOrder();

        /** @var User $user */
        $user = $this->getUser();
        $data = [];
        $selectedSeasonId = null;

        $season = $selectedSeasonHelper->getSelectedSeason($request);
        $selectedSortName = $selectedSortHelper->getSelectedSort($request,'personal_watch');
        $sortOptions = [
            'show_asc' => 'Show &#9660;',
            'show_desc' => 'Show &#9650;',
            'activity_highest' => 'Activity &#9660;',
            'activity_lowest' => 'Activity &#9650;',
            'recommendation_highest' => 'Recommendation &#9660;',
            'recommendation_lowest' => 'Recommendation &#9650;',
        ];

        $defaultScore = $scoreRepository->getDefaultScore();
        $defaultActivity = $activityRepository->getDefaultActivity();

        if ($season !== null) {
            $selectedSeasonId = $season->getId();
            $shows = $showRepository->getShowsForSeason($season, $user, $selectedSortName);
            if ($selectedSortName !== 'show_asc' && $selectedSortName !== 'show_desc') {
                // When sorting by a calculated value (max in this case), Doctrine returns an array of
                // arrays, with each entry looking like this:
                //   [ 0 => $show, 'max_score' => "1.000" ]
                $actualShows = [];
                foreach ($shows as $showContainer) {
                    $actualShows[] = $showContainer[0];
                }
                $shows = $actualShows;
            }

            foreach ($shows as $key => $show) {
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
                } else {
                    $changed = false;
                    if ($score->getScore() === null) {
                        $score->setScore($defaultScore);
                        $changed = true;
                    }
                    if ($score->getActivity() === null) {
                        $score->setActivity($defaultActivity);
                        $changed = true;
                    }
                    if ($changed) {
                        $em->persist($score);
                        $em->flush();
                    }
                }
                $form = $formFactory->createNamed(
                    'show_season_score_' . $key,
                    ShowSeasonScoreType::class,
                    $score,
                    [
                        'attr' => [
                            'id' => 'list_my_watch_form_' . $key,
                            'class' => 'list_my_watch_form',
                        ],
                        'show_score_only' => true,
                        'form_key' => $key,
                        'action' => $this->generateUrl(
                            'admin_show_season_score_edit',
                            [
                                'id' => $score->getId(),
                                'key' => $key
                            ]
                        )
                    ]
                );
                $data[] = ['score' => $score->jsonSerializeForWatchView(), 'form' => $form->createView()];
            }
        }
        $em->flush();

        return $this->render('my_watch/index.html.twig', [
            'user' => $user,
            'controller_name' => 'MyWatchController',
            'seasons' => $seasons,
            'selectedSeasonId' => $selectedSeasonId,
            'data' => $data,
            'selectedSortName' => $selectedSortName,
            'sortOptions' => $sortOptions,
            'electionIsActive' => $electionIsActive
        ]);
    }

}
