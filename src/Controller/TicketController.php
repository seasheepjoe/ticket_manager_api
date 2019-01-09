<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Entity\User;
use App\Entity\Ticket;
use App\Entity\Message;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

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

    /**
     * @Route("/tickets/contributors/remove", name="remove-contributor", methods={"POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function removeContributor(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $em = $this->getDoctrine()->getManager();
        $ticketRepo = $em->getRepository(Ticket::class);
        $userRepo = $em->getRepository(User::class);

        if (!isset($data["ticket_id"]) || !isset($data["contributor_id"])) {
            return new JsonResponse([
                "status" => "error",
                "message" => "missing_parameter"
            ]);
        }

        $ticket = $ticketRepo->find($data["ticket_id"]);
        
        if ($ticket === null) {
            return new JsonResponse([
                "status" => "error",
                "message" => "could_not_find_ticket"
            ]);
        }

        $contributors = $ticket->getContributors();

        if (empty($contributors)) {
            return new JsonResponse([
                "status" => "error",
                "message" => "no_contributors_in_ticket"
            ]);
        }

        $user = $userRepo->find($data["contributor_id"]);

        if ($user === null) {
            return new JsonResponse([
                "status" => "error",
                "message" => "could_not_find_user"
            ]);
        }

        $isUserContributor = $contributors->contains($user);

        if (!$isUserContributor) {
            return new JsonResponse([
                "status" => "error",
                "message" => "user_not_contributor"
            ]);
        }

        $ticket->removeContributor($user);
        $em->persist($ticket);
        $em->flush();
 
        return new JsonResponse([
            "status" => "success",
            "data" => $ticket->getInfo()
        ]);
    }

    /**
     * @Route("/tickets/contributors/add", name="add-contributor", methods={"POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function addContributor(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $em = $this->getDoctrine()->getManager();
        $ticketRepo = $em->getRepository(Ticket::class);
        $userRepo = $em->getRepository(User::class);

        if (!isset($data["ticket_id"]) || !isset($data["contributor_id"])) {
            return new JsonResponse([
                "status" => "error",
                "message" => "missing_parameter"
            ]);
        }

        $ticket = $ticketRepo->find($data["ticket_id"]);
        
        if ($ticket === null) {
            return new JsonResponse([
                "status" => "error",
                "message" => "could_not_find_ticket"
            ]);
        }

        $contributors = $ticket->getContributors();

        $user = $userRepo->find($data["contributor_id"]);

        if ($user === null) {
            return new JsonResponse([
                "status" => "error",
                "message" => "could_not_find_user"
            ]);
        }

        $isUserContributor = $contributors->contains($user);

        if ($isUserContributor) {
            return new JsonResponse([
                "status" => "error",
                "message" => "user_is_already_contributing"
            ]);
        }

        $ticket->addContributor($user);
        $em->persist($ticket);
        $em->flush();
 
        return new JsonResponse([
            "status" => "success",
            "data" => $ticket->getInfo()
        ]);
    }    

    /**
     * @Route("/tickets/messages/add", name="add-message", methods={"POST"})
     */
    public function addMessage(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $em = $this->getDoctrine()->getManager();
        $ticketRepo = $em->getRepository(Ticket::class);
        $userRepo = $em->getRepository(User::class);
            
        if (!isset($data["ticket_id"]) || !isset($data["message_content"])) {
            return new JsonResponse([
                "status" => "error",
                "message" => "missing_parameter"
            ]);
        }

        if (empty($data["message_content"])) {
            return new JsonResponse([
                "status" => "error",
                "message" => "message_content_empty"
            ]);
        }

        $ticket = $ticketRepo->find($data["ticket_id"]);

        if ($ticket === null) {
            return new JsonResponse([
                "status" => "error",
                "message" => "could_not_find_ticket"
            ]);
        }

        $contributors = $ticket->getContributors();

        $user = $this->getUser();

        if ($user === null) {
            return new JsonResponse([
                "status" => "error",
                "message" => "could_not_find_user"
            ]);
        }

        $isUserContributor = $contributors->contains($user);

        if (!$isUserContributor && !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse([
                "status" => "error",
                "message" => "not_contributing_to_ticket"
            ]);
        }

        $message = new Message();
        $message->setAuthor($user);
        $message->setContent($data["message_content"]);
        $message->setCreatedAt(new \DateTime);
        $message->setTicket($ticket);
        $em->persist($message);
        $em->flush();
        
        return new JsonResponse([
            "status" => "success",
            "message" => $message->getInfo()
        ]);
    }
}
