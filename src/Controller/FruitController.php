<?php

namespace App\Controller;

use App\Entity\Fruit;
use App\Repository\FruitRepository;
use App\Repository\SaisonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/fruits')]
final class FruitController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(FruitRepository $repo, SerializerInterface $serializer): JsonResponse
    {
        $fruits = $repo->findAll();
        $json = $serializer->serialize($fruits, 'json', ['groups' => ['fruit-read']]);
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/new', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, SaisonRepository $saisonRepo): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (empty($data['name']) || empty($data['saison_id'])) {
            return new JsonResponse(['error' => 'name and saison_id required'], 400);
        }

        $saison = $saisonRepo->find($data['saison_id']);
        if (!$saison) {
            return new JsonResponse(['error' => 'Saison not found'], 404);
        }

        $fruit = new Fruit();
        $fruit->setName($data['name']);
        $fruit->setSaison($saison);
        $fruit->setOwner($user);

        $em->persist($fruit);
        $em->flush();

        $json = $serializer->serialize($fruit, 'json', ['groups' => ['fruit-read']]);
        return new JsonResponse($json, 201, [], true);
    }


    #[Route('/{id}/edit', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(Fruit $fruit, Request $request, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if ($fruit->getOwner() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['name'])) $fruit->setName($data['name']);

        $em->flush();
        $json = $serializer->serialize($fruit, 'json', ['groups' => ['fruit-read']]);
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/{id}/delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Fruit $fruit, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if ($fruit->getOwner() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $em->remove($fruit);
        $em->flush();

        return new JsonResponse(['message' => 'Fruit deleted'], 200);
    }
}
