<?php

namespace App\Controller;

use App\Entity\History;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;

class HistoryApiController extends AbstractFOSRestController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    /**
     * @Rest\Post("/api/exchange/values")
     */
    public function index(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['first_in']) || !isset($data['second_in'])) {
            return new Response('Both values must be provided!', Response::HTTP_BAD_REQUEST);
        }
        
        $firstValue = $data['first_in'];
        $secondValue = $data['second_in'];

        if(!is_int($firstValue) || !is_int($secondValue)) {
            return new Response('Both values must be integer!', Response::HTTP_BAD_REQUEST);
        }

        $history = new History();
        $history->setFirstIn($firstValue);
        $history->setSecondIn($secondValue);
        $history->setFirstOut($secondValue);
        $history->setSecondOut($firstValue);
        

        $this->entityManager->persist($history);
        $this->entityManager->flush();

        return new Response('Values exchanged successfully', Response::HTTP_CREATED);
    }
}
