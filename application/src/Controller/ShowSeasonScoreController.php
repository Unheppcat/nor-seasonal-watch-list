<?php /** @noinspection DuplicatedCode */

namespace App\Controller;

use App\Entity\ShowSeasonScore;
use App\Entity\User;
use App\Form\ShowSeasonScoreType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/show/season/score')]
class ShowSeasonScoreController extends AbstractController
{
    /**
     * @param Request                $request
     * @param ShowSeasonScore        $showSeasonScore
     * @param string                 $key
     * @param FormFactoryInterface   $formFactory
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/{id}/edit/{key}', name: 'admin_show_season_score_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ShowSeasonScore $showSeasonScore,
        string $key,
        FormFactoryInterface $formFactory,
        EntityManagerInterface $em
    ): Response {
        try {
            /** @var User $user */
            $user = $this->getUser();
            if ($user === null) {
                return new JsonResponse(['data' => ['status' => 'permission_denied']], Response::HTTP_FORBIDDEN);
            }
            if (!$showSeasonScore->getUser() || $showSeasonScore->getUser()->getId() !== $user->getId()) {
                return new JsonResponse(['data' => ['status' => 'permission_denied']], Response::HTTP_FORBIDDEN);
            }

            $form = $formFactory->createNamed(
                'show_season_score_' . $key,
                ShowSeasonScoreType::class,
                $showSeasonScore,
                [
                    'attr' => [
                        'id' => 'show_season_score_' . $showSeasonScore->getId(),
                        'class' => 'show_season_score_form',
                    ]
                ]
            );
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em->flush();

                if ($request->isXmlHttpRequest()) {
                    // Just send back fact of success
                    return new JsonResponse(['data' => ['status' => 'success']]);
                }

                throw new UnauthorizedHttpException('This page should never be requested directly.');
            }

            if ($request->isXmlHttpRequest()) {
                // There was a validation error, return just the form
                $html = $this->renderView('show_season_score/_form.html.twig', [
                    'form' => $form->createView(),
                ]);
                return new Response($html, 400);
            }
        } /** @noinspection PhpRedundantCatchClauseInspection */ /** @noinspection PhpUnusedLocalVariableInspection */ catch (UniqueConstraintViolationException $e) {
            return new JsonResponse(['data' => ['status' => 'failed']], Response::HTTP_BAD_REQUEST);
        }

        throw new UnauthorizedHttpException('This page should never be requested directly.');
    }
}
