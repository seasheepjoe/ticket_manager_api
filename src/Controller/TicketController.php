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
     * @Route("/tickets", name="all-tickets", methods={"GET"})
     */
    public function index(Request $request)
    {
        $user = $this->getUser();
        $tickets = $user->getTickets();
        $ticketsArray = [];

        foreach($tickets as $ticket) {
            $ticketsArray[] = $ticket->getInfo();
        }
        $tickets = $ticketsArray;
    
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $tickets = $this->getDoctrine()->getManager()->getRepository(Ticket::class)->findAll();
        }

        foreach($tickets as $key => $ticket) {
            $tickets[$key] = $ticket->getInfo();
        }

        return new JsonResponse([
            "status" => "success",
            "tickets" => $tickets
        ]);
    }

    /**
     * @Route("/tickets/new", name="new-ticket")
     */
    public function new(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $entityManager = $this->getDoctrine()->getManager();
        $ticketRepo = $entityManager->getRepository(Ticket::class);

        $user = $this->getUser();
        $newTicket = new Ticket();
        $newTicket->setAuthor($user);
        $newTicket->setCreatedAt(new \DateTime());
        $newTicket->setUpdatedAt(new \DateTime());
        $newTicket->setStatus('opened');
        $newTicket->addContributor($user);
        $newTicket->setTitle("React native is the problem");
        $user->addTicket($newTicket);
        $entityManager->persist($newTicket);
        $entityManager->flush();

        return new JsonResponse([
            "status" => "success",
            "ticket" => $newTicket->getInfo()
        ]);
    }

    /**
     * @Route("/tickets/get/{id}", name="get-ticket", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function getTicket(Request $request, $id)
    {
        $user = $this->getUser();
        $ticketRepo = $this->getDoctrine()->getManager()->getRepository(Ticket::class);
        $ticket = $ticketRepo->find($id);

        if ($ticket === null) {
            return new JsonResponse([
                "status" => "error",
                "message" => "ticket_not_found"
            ]);
        }

        $messagesArray = [];
        foreach($ticket->getMessages() as $msg) {
            $messagesArray[] = $msg->getInfo();
        }

        return new JsonResponse([
            "status" => "success",
            "ticket" => $ticket->getInfo(),
            "messages" => $messagesArray
        ]);
    }
}
