<?php

namespace App\Controller;

use App\Entity\UserEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

class UserController extends AbstractController
{
    /**
     * @Route("/user/login", name="userLogin")
     */
    public function login()
    {
        return new JsonResponse([
            "email" => "loiseaubillonlouis@gmail.com"
        ]);
    }

    /**
     * @Route("/user/register", name="userRegister", methods={"POST"})
     */
    public function register(Request $request): JsonResponse
    { 
        $entityManager = $this->getDoctrine()->getManager();
        $data = json_decode($request->getContent(), true);

        if (!isset($data["firstname"]) || !isset($data["lastname"]) || !isset($data["email"]) || !isset($data["password"])) {
            return new JsonResponse([
                "status" => "error",
                "message" => "missing_parameter"
            ]);
        }

        $user = new UserEntity();
        $user->setFirstname($data["firstname"]);
        $user->setLastname($data["lastname"]);
        $user->setEmail($data["email"]);
        $user->setPassword($data["password"]);
        $user->setToken("dzzdzdz");

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse([
            "status" => "success",
            "data" => $user 
        ]);
    }
    
}
