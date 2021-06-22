<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

/** @noinspection PhpUndefinedClassInspection */

namespace App\Controller;

use App\Entity\Show;
use App\Form\ShowType;
use App\Repository\ElectionRepository;
use App\Repository\SeasonRepository;
use App\Repository\ShowRepository;
use App\Service\AnilistApi;
use Doctrine\Persistence\ObjectManager;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/show")
 */
class AdminShowController extends AbstractController
{
    /**
     * @Route("/", name="admin_show_index", methods={"GET"})
     * @param Request $request
     * @param ShowRepository $showRepository
     * @param SeasonRepository $seasonRepository
     * @param ElectionRepository $electionRepository
     * @return Response
     */
    public function index(
        Request $request,
        ShowRepository $showRepository,
        SeasonRepository $seasonRepository,
        ElectionRepository $electionRepository
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive();
        $session = $request->getSession();
        $currentPage = $session->get('page', 1);
        $currentPerPage = $session->get('perPage', 10);
        $currentSort = $session->get('sort', 'rumaji_asc');
        $currentSeason = $session->get('season', '');
        $pageNum = $request->get('page', $currentPage);
        $perPage = $request->get('perPage', $currentPerPage);
        if ($perPage !== $currentPerPage) {
            $pageNum = 1;
        }
        $sort = $request->get('sort', $currentSort);
        $season = $request->get('season', $currentSeason);
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
        $seasons = $seasonRepository->getAllInRankOrder();
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
     * @Route("/new", name="admin_show_new", methods={"GET","POST"})
     * @param Request $request
     * @param AnilistApi $anilistApi
     * @param ElectionRepository $electionRepository
     * @return Response
     * @throws GuzzleException
     * @throws JsonException
     */
    public function new(
        Request $request,
        AnilistApi $anilistApi,
        ElectionRepository $electionRepository
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive();
        $show = new Show();
        $form = $this->createForm(ShowType::class, $show);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $this->saveNewRelatedShows($show, $em);
            $anilistData = $anilistApi->fetch($show->getAnilistId());
            if ($anilistData !== null) {
                $anilistApi->updateShow($show, $anilistData);
            }

            $em->persist($show);
            $em->flush();

            return $this->redirectToRoute('admin_show_index');
        }

        return $this->render('show/new.html.twig', [
            'user' => $this->getUser(),
            'show' => $show,
            'form' => $form->createView(),
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @Route("/{id}", name="admin_show_show", methods={"GET"})
     * @param Show $show
     * @param ElectionRepository $electionRepository
     * @return Response
     */
    public function show(
        Show $show,
        ElectionRepository $electionRepository
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive();
        return $this->render('show/show.html.twig', [
            'user' => $this->getUser(),
            'show' => $show,
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="admin_show_edit", methods={"GET","POST"})
     * @param Request $request
     * @param Show $show
     * @param AnilistApi $anilistApi
     * @param ElectionRepository $electionRepository
     * @param ShowRepository $showRepository
     * @return Response
     * @throws GuzzleException
     * @throws JsonException
     */
    public function edit(
        Request $request,
        Show $show,
        AnilistApi $anilistApi,
        ElectionRepository $electionRepository,
        ShowRepository $showRepository
    ): Response {
        $originalRelatedShows = $showRepository->getRelatedShows($show);
        $electionIsActive = $electionRepository->electionIsActive();
        $form = $this->createForm(ShowType::class, $show);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            foreach($originalRelatedShows as $originalRelatedShow) {
                $originalRelatedShow->setFirstShow(null);
                $em->persist($originalRelatedShow);
            }
            $this->saveNewRelatedShows($show, $em);
            $anilistData = $anilistApi->fetch($show->getAnilistId());
            if ($anilistData !== null) {
                $anilistApi->updateShow($show, $anilistData);
            }
            $em->persist($show);
            $em->flush();

            return $this->redirectToRoute('admin_show_index');
        }

        return $this->render('show/edit.html.twig', [
            'user' => $this->getUser(),
            'show' => $show,
            'form' => $form->createView(),
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @Route("/{id}", name="admin_show_delete", methods={"DELETE"})
     * @param Request $request
     * @param Show $show
     * @return Response
     */
    public function delete(Request $request, Show $show): Response
    {
        if ($this->isCsrfTokenValid('delete'.$show->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($show);
            $entityManager->flush();
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
