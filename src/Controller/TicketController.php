<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Entity\User;
use App\Entity\Ticket;

class TicketController extends AbstractController
{
    /**
     * @Route("/", name="all-tickets", methods={"GET"})
     */
    public function index(Request $request)
    {
        return new JsonResponse([
            "status" => "success",
            "tickets" => $this->getDoctrine()->getManager()->getRepository(Ticket::class)->findAll()
        ]);
    }

    /**
     * @Route("/new", name="new-ticket")
     */
    public function new(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $ticketRepo = $entityManager->getRepository(Ticket::class);

        $user = $this->getUser();
        $newTicket = new Ticket();
        $newTicket->setAuthor($user->getId());
        $newTicket->setCreatedAt(new \DateTime());
        $newTicket->setUpdatedAt(new \DateTime());
        $newTicket->setStatus('opened');
        $entityManager->persist($newTicket);
        $entityManager->flush();

        return new JsonResponse([
            "status" => "success",
            "ticket" => $newTicket->getInfo()
        ]);
    }
}
