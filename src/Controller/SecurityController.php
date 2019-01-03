<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Entity\User;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="login", methods={"POST"})
     */
    public function login(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        return new JsonResponse([
            "status" => "login",
            "data" => $data
        ]);
    }

    /**
     * @Route("/register", name="register", methods={"POST"})
     */
    public function register(Request $request, UserPasswordEncoderInterface $encoder)
    {
        $data = json_decode($request->getContent(), true);
        $entityManager = $this->getDoctrine()->getManager();
        $userRepo = $entityManager->getRepository(User::class);

        if ($userRepo->findOneBy(['email' => $data['email']])) {
            return new JsonResponse([
                "status" => "error",
                "message" => "user_already_exists"
            ]);
        }

        // other checks to do.

        $newUser = new User();
        $newUser->setFirstname($data["firstname"]);
        $newUser->setLastname($data["lastname"]);
        $newUser->setEmail($data["email"]);
        $newUser->setCreatedAt(new \DateTime());
        $encodedPassword = $encoder->encodePassword($newUser, $data["password"]);
        $newUser->setPassword($encodedPassword);

        $entityManager->persist($newUser);
        $entityManager->flush();

        return new JsonResponse([
            "status" => "success",
            "user" => $newUser->getInfo()
        ]);
    }
}
