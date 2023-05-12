<?php

namespace App\Controller;

use App\Entity\Team;
use App\Form\TeamType;
use App\Repository\TeamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Player;
use App\Repository\PlayerRepository;

#[Route('/team')]
class TeamController extends AbstractController
{
    #[Route('/', name: 'app_team_index', methods: ['GET'])]
    public function index(TeamRepository $teamRepository): Response
    {
        return $this->render('team/index.html.twig', [
            'teams' => $teamRepository->findAll(),
        ]);
    }

    #[Route('/transfer-market', name: 'transfer_market', methods: ['GET', 'POST'])]
    public function transfer(Request $request, EntityManagerInterface $entityManager, TeamRepository $teamRepository): Response
    {
        $players = $entityManager
            ->getRepository('App\Entity\Player')
            ->findAll();

        $teams = $teamRepository->findAll();

        return $this->render('transfer-market.html.twig', [
            'players' => $players,
            'teams' => $teams,
        ]);
    }

    #[Route('/new', name: 'app_team_new', methods: ['GET', 'POST'])]
    public function new(Request $request, TeamRepository $teamRepository): Response
    {
        $team = new Team();
        $team->setName('');
        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $teamRepository->save($team, true);

            return $this->redirectToRoute('app_team_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('team/new.html.twig', [
            'team' => $team,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_team_show', methods: ['GET'])]
    public function show(Team $team): Response
    {
        return $this->render('team/show.html.twig', [
            'team' => $team,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_team_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Team $team, TeamRepository $teamRepository): Response
    {
        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $teamRepository->save($team, true);

            return $this->redirectToRoute('app_team_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('team/edit.html.twig', [
            'team' => $team,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_team_delete', methods: ['POST'])]
    public function delete(Request $request, Team $team, TeamRepository $teamRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $team->getId(), $request->request->get('_token'))) {
            $teamRepository->remove($team, true);
        }

        return $this->redirectToRoute('app_team_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/players/buy', name: 'buy', methods: ['POST'])]
    public function buy(Request $request, PlayerRepository $Player, TeamRepository $Team, EntityManagerInterface $EntityManager): Response
    {
        $playerId = $request->request->get('playerId');
        $teamId = $request->request->get('teamId');

        $player = $Player->find($playerId);
        $team = $Team->find($teamId);

        if ($team->getMoneyBalance() >= $player->getPrice()) {
            $team->addPlayer($player);
            $team->setMoneyBalance($team->getMoneyBalance() - $player->getPrice());
            $EntityManager->persist($team);
            $EntityManager->flush();

            return $this->redirectToRoute('app_transfer_market', [], Response::HTTP_SEE_OTHER);
        } else {
            return $this->render('transfer-market.html.twig', [
                'players' => $Player->findAll(),
                'teams' => $Team->findAll(),
                'message' => 'You do not have enough money to buy this player.',
            ]);
        }
    }
}
