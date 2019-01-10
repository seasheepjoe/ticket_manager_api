<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class UserController extends AbstractController
{
    /**
     * @Route("/users/search", name="search-users", methods={"POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function index(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $entityManager = $this->getDoctrine()->getManager();
        $userRepo = $entityManager->getRepository(User::class);
        
        $users = $userRepo->findBy(['firstname' => $data['fullname']]);
        $usersArray = [];
        foreach($users as $user) {
            $usersArray[] = $user->getInfo();
        }
        
        return new JsonResponse([
            "status" => "success",
            "users" => $usersArray
        ]);
    }
}
