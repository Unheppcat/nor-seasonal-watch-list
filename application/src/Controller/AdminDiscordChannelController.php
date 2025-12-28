<?php

namespace App\Controller;

use App\Entity\DiscordChannel;
use App\Form\DiscordChannelType;
use App\Repository\DiscordChannelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/discord/channel')]
class AdminDiscordChannelController extends AbstractController
{
    /**
     * @param DiscordChannelRepository $discordChannelRepository
     * @return Response
     */
    #[Route('/', name: 'admin_discord_channel_index', methods: ['GET'])]
    public function index(DiscordChannelRepository $discordChannelRepository): Response
    {
        return $this->render('discord_channel/index.html.twig', [
            'user' => $this->getUser(),
            'discord_channels' => $discordChannelRepository->findAll(),
        ]);
    }

    /**
     * @param Request                $request
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/new', name: 'admin_discord_channel_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $discordChannel = new DiscordChannel();
        $form = $this->createForm(DiscordChannelType::class, $discordChannel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($discordChannel);
            $show = $discordChannel->getShow();
            /** @noinspection PhpConditionAlreadyCheckedInspection */
            if ($show !== null) {
                $show->setDiscordChannel($discordChannel);
                $em->persist($show);
            }
            $em->flush();

            return $this->redirectToRoute('admin_discord_channel_index');
        }

        return $this->render('discord_channel/new.html.twig', [
            'user' => $this->getUser(),
            'discord_channel' => $discordChannel,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param DiscordChannel $discordChannel
     * @return Response
     */
    #[Route('/{id}', name: 'admin_discord_channel_show', methods: ['GET'])]
    public function show(DiscordChannel $discordChannel): Response
    {
        return $this->render('discord_channel/show.html.twig', [
            'user' => $this->getUser(),
            'discord_channel' => $discordChannel,
        ]);
    }

    /**
     * @param Request                $request
     * @param DiscordChannel         $discordChannel
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/{id}/edit', name: 'admin_discord_channel_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, DiscordChannel $discordChannel, EntityManagerInterface $em): Response
    {
        $previousShow = $discordChannel->getShow();
        $form = $this->createForm(DiscordChannelType::class, $discordChannel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $show = $discordChannel->getShow();
            /** @noinspection PhpConditionAlreadyCheckedInspection */
            if ($previousShow !== null && ($show === null || $show->getId() !== $previousShow->getId())) {
                $previousShow->setDiscordChannel(null);
                $em->persist($previousShow);
            }
            if ($show !== null) {
                $show->setDiscordChannel($discordChannel);
                $em->persist($show);
            }
            $em->flush();

            return $this->redirectToRoute('admin_discord_channel_index');
        }

        return $this->render('discord_channel/edit.html.twig', [
            'user' => $this->getUser(),
            'discord_channel' => $discordChannel,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param Request                $request
     * @param DiscordChannel         $discordChannel
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/{id}', name: 'admin_discord_channel_delete', methods: ['DELETE'])]
    public function delete(Request $request, DiscordChannel $discordChannel, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$discordChannel->getId(), $request->request->get('_token'))) {
            $show = $discordChannel->getShow();
            /** @noinspection PhpConditionAlreadyCheckedInspection */
            if ($show !== null) {
                $show->setDiscordChannel(null);
                $em->persist($show);
            }
            $em->remove($discordChannel);
            $em->flush();
        }

        return $this->redirectToRoute('admin_discord_channel_index');
    }
}
