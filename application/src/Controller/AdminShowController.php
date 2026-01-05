<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

/** @noinspection PhpUndefinedClassInspection */

namespace App\Controller;

use App\Entity\Show;
use App\Form\ShowType;
use App\Repository\ElectionRepository;
use App\Repository\SeasonRepository;
use App\Repository\ShowRepository;
use App\Service\AnilistApi;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/show')]
class AdminShowController extends AbstractController
{
    /**
     * @param Request $request
     * @param ShowRepository $showRepository
     * @param SeasonRepository $seasonRepository
     * @param ElectionRepository $electionRepository
     * @return Response
     */
    #[Route('/', name: 'admin_show_index', methods: ['GET'])]
    public function index(
        Request $request,
        ShowRepository $showRepository,
        SeasonRepository $seasonRepository,
        ElectionRepository $electionRepository
    ): Response {
        $electionIsActive = $electionRepository->electionIsAvailable();
        $session = $request->getSession();
        $currentPage = $session->get('page', 1);
        $currentPerPage = $session->get('perPage', 10);
        $currentSort = $session->get('sort', 'rumaji_asc');
        $currentSeason = $session->get('season', '');
        $pageNum = $request->query->get('page', $currentPage);
        $perPage = $request->query->get('perPage', $currentPerPage);
        if ($perPage !== $currentPerPage) {
            $pageNum = 1;
        }
        $sort = $request->query->get('sort', $currentSort);
        $season = $request->query->get('season', $currentSeason);
        if ($season === '') {
            $season = null;
        } else {
            $season = (int)$season;
        }
        switch($sort) {
            case 'english_asc':
                $sortColumn = 'english';
                $sortOrder = 'ASC';
                break;
            case 'english_desc':
                $sortColumn = 'english';
                $sortOrder= 'DESC';
                break;
            case 'rumaji_desc':
                $sortColumn = 'rumaji';
                $sortOrder = 'DESC';
                break;
            case 'excluded_asc':
                $sortColumn = 'excluded';
                $sortOrder = 'ASC';
                break;
            case 'excluded_desc':
                $sortColumn = 'excluded';
                $sortOrder = 'DESC';
                break;
            default:
                $sortColumn = 'rumaji';
                $sortOrder = 'ASC';
        }
        $session->set('page', $pageNum);
        $session->set('perPage', $perPage);
        $session->set('sort', $sort);
        $session->set('season', $season);
        $pagerfanta = $showRepository->getShowsSortedPaged($sortColumn, $sortOrder, $pageNum, $perPage, $season);
        $shows = $pagerfanta->getCurrentPageResults();
        $seasons = $seasonRepository->getAllInRankOrder(true);
        return $this->render('show/index.html.twig', [
            'user' => $this->getUser(),
            'shows' => $shows,
            'pager' => $pagerfanta,
            'selectedSortName' => $sort,
            'perPage' => $perPage,
            'selectedSeason' => $season,
            'seasons' => $seasons,
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param Request                $request
     * @param AnilistApi             $anilistApi
     * @param ElectionRepository     $electionRepository
     * @param SeasonRepository       $seasonRepository
     * @param EntityManagerInterface $em
     * @return Response
     * @throws GuzzleException
     * @throws JsonException
     */
    #[Route('/new', name: 'admin_show_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        AnilistApi $anilistApi,
        ElectionRepository $electionRepository,
        SeasonRepository $seasonRepository,
        EntityManagerInterface $em
    ): Response {
        $electionIsActive = $electionRepository->electionIsAvailable();
        $show = new Show();

        // Pre-populate seasons from session if available
        $session = $request->getSession();
        $previouslySelectedSeasonIds = $session->get('last_selected_seasons', []);

        if (!empty($previouslySelectedSeasonIds)) {
            $previousSeasons = $seasonRepository->findBy(['id' => $previouslySelectedSeasonIds]);
            foreach ($previousSeasons as $season) {
                $show->addSeason($season);
            }
        }

        $form = $this->createForm(ShowType::class, $show);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Validate that no seasons with active elections are being added
            $selectedSeasons = $show->getSeasons();
            foreach ($selectedSeasons as $season) {
                if ($season->hasActiveElection()) {
                    $this->addFlash('error', 'Cannot link show to season "' . $season->getName() . '" - it has an active election');
                    return $this->render('show/new.html.twig', [
                        'user' => $this->getUser(),
                        'show' => $show,
                        'form' => $form->createView(),
                        'mode' => 'add',
                        'electionIsActive' => $electionIsActive,
                    ]);
                }
            }

            // Store selected season IDs in session for next time
            $selectedSeasonIds = [];
            foreach ($selectedSeasons as $season) {
                $selectedSeasonIds[] = $season->getId();
            }
            $session->set('last_selected_seasons', $selectedSeasonIds);

            $this->saveNewRelatedShows($show, $em);
            try {
                $anilistData = $anilistApi->fetch($show->getAnilistId());
                if ($anilistData !== null) {
                    $anilistApi->updateShow($show, $anilistData);
                    $this->addFlash('success', 'Updated from the Anilist API');
                } else {
                    $this->addFlash('warning', 'Update from the Anilist API failed');
                }
            } /** @noinspection PhpUnusedLocalVariableInspection */ catch (Exception $e) {
                $this->addFlash('warning', 'Update from the Anilist API failed');
            }

            $em->persist($show);
            $em->flush();

            return $this->redirectToRoute('admin_show_edit', ['id' => $show->getId()]);
        }

        return $this->render('show/new.html.twig', [
            'user' => $this->getUser(),
            'show' => $show,
            'form' => $form->createView(),
            'mode' => 'add',
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * AJAX endpoint to search shows for autocomplete
     */
    #[Route('/search', name: 'admin_show_search', options: ['expose' => true], methods: ['GET'])]
    public function search(Request $request, ShowRepository $showRepository): Response
    {
        $term = $request->query->get('term', '');
        /** @noinspection PhpRedundantOptionalArgumentInspection */
        $excludeParam = $request->query->get('exclude', null);

        // Convert exclude parameter to int or null
        $currentShowId = null;
        if ($excludeParam !== null && $excludeParam !== '') {
            $currentShowId = (int) $excludeParam;
        }

        if (strlen($term) < 2) {
            return $this->json([]);
        }

        $shows = $showRepository->searchByTitle($term, $currentShowId);

        $results = [];
        foreach ($shows as $show) {
            $results[] = [
                'id' => $show->getId(),
                'text' => $show->getTitlesForSelect(),
            ];
        }

        return $this->json($results);
    }

    /**
     * @param Show $show
     * @param ElectionRepository $electionRepository
     * @return Response
     */
    #[Route('/{id}', name: 'admin_show_show', methods: ['GET'])]
    public function show(
        Show $show,
        ElectionRepository $electionRepository
    ): Response {
        $electionIsActive = $electionRepository->electionIsAvailable();
        return $this->render('show/show.html.twig', [
            'user' => $this->getUser(),
            'show' => $show,
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param Request                $request
     * @param Show                   $show
     * @param AnilistApi             $anilistApi
     * @param ElectionRepository     $electionRepository
     * @param ShowRepository         $showRepository
     * @param EntityManagerInterface $em
     * @return Response
     * @throws GuzzleException
     * @throws JsonException
     */
    #[Route('/{id}/edit', name: 'admin_show_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Show $show,
        AnilistApi $anilistApi,
        ElectionRepository $electionRepository,
        ShowRepository $showRepository,
        EntityManagerInterface $em
    ): Response {
        $originalRelatedShows = $showRepository->getRelatedShows($show);

        // Capture original seasons before form handling
        $originalSeasons = [];
        foreach ($show->getSeasons() as $season) {
            $originalSeasons[$season->getId()] = $season;
        }

        $electionIsActive = $electionRepository->electionIsAvailable();
        $form = $this->createForm(ShowType::class, $show);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get new seasons after form submission
            $newSeasons = [];
            foreach ($show->getSeasons() as $season) {
                $newSeasons[$season->getId()] = $season;
            }

            // Check for removed seasons with active elections
            foreach ($originalSeasons as $seasonId => $season) {
                if (!isset($newSeasons[$seasonId]) && $season->hasActiveElection()) {
                    $this->addFlash('error', 'Cannot remove season "' . $season->getName() . '" - it has an active election');
                    return $this->render('show/edit.html.twig', [
                        'user' => $this->getUser(),
                        'show' => $show,
                        'form' => $form->createView(),
                        'mode' => 'edit',
                        'electionIsActive' => $electionIsActive,
                    ]);
                }
            }

            // Check for added seasons with active elections
            foreach ($newSeasons as $seasonId => $season) {
                if (!isset($originalSeasons[$seasonId]) && $season->hasActiveElection()) {
                    $this->addFlash('error', 'Cannot add season "' . $season->getName() . '" - it has an active election');
                    return $this->render('show/edit.html.twig', [
                        'user' => $this->getUser(),
                        'show' => $show,
                        'form' => $form->createView(),
                        'mode' => 'edit',
                        'electionIsActive' => $electionIsActive,
                    ]);
                }
            }

            foreach($originalRelatedShows as $originalRelatedShow) {
                $originalRelatedShow->setFirstShow(null);
                $em->persist($originalRelatedShow);
            }
            $this->saveNewRelatedShows($show, $em);

            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            if ($form->get('updateFromAnilist') && $form->get('updateFromAnilist')->isClicked()) {
                try {
                    $anilistData = $anilistApi->fetch($show->getAnilistId());
                    if ($anilistData !== null) {
                        $anilistApi->updateShow($show, $anilistData);
                        $this->addFlash('success', 'Updated from the Anilist API');
                    } else {
                        $this->addFlash('warning', 'Update from the Anilist API failed');
                    }
                } /** @noinspection PhpUnusedLocalVariableInspection */ catch (Exception $e) {
                    $this->addFlash('warning', 'Update from the Anilist API failed');
                }
            }

            $em->persist($show);
            $em->flush();

            $this->addFlash("success", "Show updated");
        }

        return $this->render('show/edit.html.twig', [
            'user' => $this->getUser(),
            'show' => $show,
            'form' => $form->createView(),
            'mode' => 'edit',
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param Request                $request
     * @param Show                   $show
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/{id}', name: 'admin_show_delete', methods: ['DELETE'])]
    public function delete(Request $request, Show $show, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$show->getId(), $request->request->get('_token'))) {
            if (!$show->canBeDeleted()) {
                $this->addFlash('error', 'Cannot delete show: it has linked seasons');
                return $this->redirectToRoute('admin_show_edit', ['id' => $show->getId()]);
            }

            $em->remove($show);
            $em->flush();
            $this->addFlash('success', 'Show deleted successfully');
        }

        return $this->redirectToRoute('admin_show_index');
    }

    /**
     * @param Show $show
     * @param ObjectManager $em
     */
    private function saveNewRelatedShows(Show $show, ObjectManager $em): void
    {
        $newRelatedShows = $show->getRelatedShows();
        foreach ($newRelatedShows as $newRelatedShow) {
            $newRelatedShow->setFirstShow($show);
            $em->persist($newRelatedShow);
        }
    }
}
