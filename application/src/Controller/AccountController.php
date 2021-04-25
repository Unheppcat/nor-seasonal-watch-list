<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserPreferencesType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/account")
 */
class AccountController extends AbstractController
{
    /**
     * @Route("/preferences", name="account_preferences", methods={"GET","POST"})
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $preferences = $user->getPreferences();
        $form = $this->createForm(UserPreferencesType::class, $preferences);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $colorsMode = $form->get('colors_mode_picker')->getData();
            $swlViewMode = $form->get('all_watches_view_mode_picker')->getData();
            $prefs = $user->getPreferences();
            $prefs->setColorsMode($colorsMode);
            $prefs->setAllWatchesViewMode($swlViewMode);
            $user->setPreferences($prefs);
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
        }

        return $this->render('account/preferences.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
