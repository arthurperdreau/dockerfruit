<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register( SerializerInterface $serializer, UserRepository $userRepository ,Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');
        $emailAlreadyTaken = $userRepository->findOneBy(['email' => $user->getEmail()]);
        if ($emailAlreadyTaken) {
            return $this->json(["message" => "Email already taken"], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->setPassword($userPasswordHasher->hashPassword($user, $user->getPassword()));
        $entityManager->persist($user);
        $entityManager->flush();

        $profile = new Profile();
        $profile->setOfUser($user);
        $profile->setIsPrenium(false);

        $entityManager->persist($profile);
        $entityManager->flush();


        return $this->json($user, Response::HTTP_CREATED, []);

    }


}
