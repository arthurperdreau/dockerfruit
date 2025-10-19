<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/prenium')]
class PreniumController extends AbstractController
{
    public function __construct()
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
    }

    #[Route('', name: 'prenium', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UserRepository $userRepo,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');

            $user = $userRepo->findOneBy(['email' => $email]);

            if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
                $this->addFlash('error', 'Identifiants incorrects.');
                return $this->redirectToRoute('prenium');
            }

            return $this->redirectToRoute('prenium_checkout', ['id' => $user->getId()]);
        }

        return $this->render('prenium/index.html.twig');
    }

    #[Route('/checkout/{id}', name: 'prenium_checkout')]
    public function checkout(int $id, UserRepository $userRepo, EntityManagerInterface $em): Response
    {
        $user = $userRepo->find($id);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('prenium');
        }

        $profile = $user->getProfile();
        if (!$profile) {
            $this->addFlash('error', 'Profil introuvable.');
            return $this->redirectToRoute('prenium');
        }

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => 500,
                    'product_data' => [
                        'name' => 'Abonnement Premium',
                        'description' => 'Accès Premium aux légumes.',
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('prenium_success', [], 0),
            'cancel_url' => $this->generateUrl('prenium', [], 0),
            'metadata' => [
                'user_id' => $user->getId(),
            ],
        ]);

        return $this->redirect($session->url);
    }

    #[Route('/success', name: 'prenium_success')]
    public function success(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if ($user && $user->getProfile()) {
            $user->getProfile()->setPrenium(true);
            $em->flush();
        }

        return $this->render('prenium/success.html.twig');
    }
}
