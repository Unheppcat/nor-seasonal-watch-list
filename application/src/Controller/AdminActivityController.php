<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Form\ActivityType;
use App\Repository\ActivityRepository;
use App\Repository\ElectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/activity')]
class AdminActivityController extends AbstractController
{
    /**
     * @param ActivityRepository $activityRepository
     * @param ElectionRepository $electionRepository
     * @return Response
     */
    #[Route('/', name: 'admin_activity_index', methods: ['GET'])]
    public function index(
        ActivityRepository $activityRepository,
        ElectionRepository $electionRepository
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive();
        return $this->render('activity/index.html.twig', [
            'user' => $this->getUser(),
            'activities' => $activityRepository->findAll(),
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param Request                $request
     * @param ElectionRepository     $electionRepository
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/new', name: 'admin_activity_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        ElectionRepository $electionRepository,
        EntityManagerInterface $em
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive();
        $activity = new Activity();
        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $activity->setIcon($activity->getIcon());
            $em->persist($activity);
            $em->flush();

            return $this->redirectToRoute('admin_activity_index');
        }

        return $this->render('activity/new.html.twig', [
            'user' => $this->getUser(),
            'activity' => $activity,
            'form' => $form->createView(),
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param Activity $activity
     * @param ElectionRepository $electionRepository
     * @return Response
     */
    #[Route('/{id}', name: 'admin_activity_show', methods: ['GET'])]
    public function show(
        Activity $activity,
        ElectionRepository $electionRepository
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive();
        return $this->render('activity/show.html.twig', [
            'user' => $this->getUser(),
            'activity' => $activity,
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param Request                $request
     * @param Activity               $activity
     * @param ElectionRepository     $electionRepository
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/{id}/edit', name: 'admin_activity_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Activity $activity,
        ElectionRepository $electionRepository,
        EntityManagerInterface $em
    ): Response {
        $electionIsActive = $electionRepository->electionIsActive();
        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $activity->setIcon($activity->getIcon());
            $em->flush();

            return $this->redirectToRoute('admin_activity_index');
        }

        return $this->render('activity/edit.html.twig', [
            'user' => $this->getUser(),
            'activity' => $activity,
            'form' => $form->createView(),
            'electionIsActive' => $electionIsActive,
        ]);
    }

    /**
     * @param Request                $request
     * @param Activity               $activity
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/{id}', name: 'admin_activity_delete', methods: ['DELETE'])]
    public function delete(Request $request, Activity $activity, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$activity->getId(), $request->request->get('_token'))) {
            $em->remove($activity);
            $em->flush();
        }

        return $this->redirectToRoute('admin_activity_index');
    }
}
