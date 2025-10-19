<?php

namespace App\Controller;

use App\Entity\Legume;
use App\Repository\LegumeRepository;
use App\Repository\SaisonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;


#[Route("/api/legumes")]
final class LegumeController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(LegumeRepository $repo, SerializerInterface $serializer): JsonResponse
    {
        $user = $this->getUser();
        $profile = $user->getProfile();

        if (!in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            if (!$profile || !$profile->isPrenium()) {
                return new JsonResponse(['error' => 'Premium required'], 403);
            }
        }

        $legumes = $repo->findAll();
        $json = $serializer->serialize($legumes, 'json', ['groups' => ['legume-read']]);
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/new', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, SaisonRepository $saisonRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['name'], $data['saison'])) {
            return new JsonResponse(['error' => 'Missing "name" or "saison" field'], 400);
        }

        $saison = $saisonRepository->find($data['saison']);
        if (!$saison) {
            return new JsonResponse(['error' => 'Invalid saison ID'], 400);
        }

        // Crée le légume
        $legume = new Legume();
        $legume->setName($data['name']);
        $legume->setSaison($saison);
        $legume->setOwner($this->getUser());

        $em->persist($legume);
        $em->flush();

        // Sérialisation du résultat
        $json = $serializer->serialize($legume, 'json', ['groups' => ['legume-read']]);
        return new JsonResponse($json, 201, [], true);
    }

    #[Route('/{id}/edit', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Legume $legume, Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (isset($data['name'])) $legume->setName($data['name']);
        $em->flush();

        $json = $serializer->serialize($legume, 'json', ['groups' => ['legume-read']]);
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/{id}/delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Legume $legume, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($legume);
        $em->flush();
        return new JsonResponse(['message' => 'Legume deleted'], 200);
    }
}
