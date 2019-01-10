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
    public function login(Request $request, UserPasswordEncoderInterface $encoder)
    {
        $data = json_decode($request->getContent(), true);
        $entityManager = $this->getDoctrine()->getManager();
        $userRepo = $entityManager->getRepository(User::class);
        
        if (!isset($data["email"]) || !isset($data["password"])) {
            return new JsonResponse([
                "status" => "error",
                "message" => "missing_parameter"
            ]);
        }

        $user = $userRepo->findOneBy(["email" => $data["email"]]);

        if ($user === null) {
            return new JsonResponse([
                "status" => "error",
                "message" => "invalid_email"
            ]);
        }

        if (!$encoder->isPasswordValid($user, $data["password"])) {
            return new JsonResponse([
                "status" => "error",
                "message" => "invalid_password"
            ]);
        }

        return new JsonResponse([
            "status" => "success",
            "user" => $user->getInfo()
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

        if (!isset($data["firstname"]) || !isset($data["lastname"]) || !isset($data["email"]) || !isset($data["password"]) || !isset($data["password_check"])) {
            return new JsonResponse([
                "status" => "error",
                "message" => "missing_parameter"
            ]);
        }

        if ($userRepo->findOneBy(['email' => $data['email']])) {
            return new JsonResponse([
                "status" => "error",
                "message" => "user_already_exists"
            ]);
        }

        if ($data['password'] != $data['password_check']) {
            return new JsonResponse([
                "status" => "error",
                "message" => "passwords_not_match"
            ]);
        }

        $newUser = new User();
        $newUser->setFirstname($data["firstname"]);
        $newUser->setLastname($data["lastname"]);
        $newUser->setEmail($data["email"]);
        $newUser->setCreatedAt(new \DateTime());
        $encodedPassword = $encoder->encodePassword($newUser, $data["password"]);
        $newUser->setPassword($encodedPassword);
        $newUser->setApiToken(bin2hex(random_bytes(64)));
        $entityManager->persist($newUser);
        $entityManager->flush();

        return new JsonResponse([
            "status" => "success",
            "user" => $newUser->getInfo()
        ]);
    }

    /**
     * @Route("/auth/check", name="check-auth", methods={"GET"})
     */
    public function checkAuth(Request $request)
    {
        $user = $this->getUser();
        
        if ($user === null) {
            return new JsonResponse([
                "status" => "error",
                "message" => "not_logged_in"
            ]);
        }

        return new JsonResponse([
            "status" => "success",
            "user" => $user->getInfo()
        ]);
    }
}