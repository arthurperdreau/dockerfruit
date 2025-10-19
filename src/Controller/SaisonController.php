<?php

namespace App\Controller;

use App\Entity\Saison;
use App\Repository\SaisonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/saisons')]
final class SaisonController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(SaisonRepository $repo, SerializerInterface $serializer): JsonResponse
    {
        $saisons = $repo->findAll();
        $json = $serializer->serialize($saisons, 'json', ['groups' => ['saison-read']]);
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/{id}/show', methods: ['GET'])]
    public function show(Saison $saison, SerializerInterface $serializer): JsonResponse
    {
        $json = $serializer->serialize($saison, 'json', ['groups' => ['saison-read']]);
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/new', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data['name'])) return new JsonResponse(['error' => 'name required'], 400);

        $saison = new Saison();
        $saison->setName($data['name']);
        $em->persist($saison);
        $em->flush();

        return new JsonResponse($serializer->serialize($saison, 'json', ['groups' => ['saison-read']]), 201, [], true);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Saison $saison, Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (isset($data['name'])) $saison->setName($data['name']);
        $em->flush();
        return new JsonResponse($serializer->serialize($saison, 'json', ['groups' => ['saison-read']]), 200, [], true);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Saison $saison, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($saison);
        $em->flush();
        return new JsonResponse(['message' => 'Saison deleted'], 200);
    }
}
