<?php

namespace App\Controller;

use App\Entity\History;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;

#[Route('/api')]
class HistoryApiController extends AbstractFOSRestController
{
    //wstrzykniecie EntityManager
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    //obsluga requesta POST dla '/exchange/values"
    #[Route('/exchange/values', methods: ['POST'])]
    public function index(Request $request): Response
    {
        //parsowanie ciala zadania do tablicy asocjacyjnej
        $data = json_decode($request->getContent(), true);

        //sprawdzenie czy obie wartosci sa dostarczone
        if (!isset($data['first_in']) || !isset($data['second_in'])) {
            return $this->handleView($this->view(['message' => 'Both values must be provided!'], Response::HTTP_BAD_REQUEST));
        }
        
        //pobranie wartosci
        $firstValue = $data['first_in'];
        $secondValue = $data['second_in'];

        //walidacja wartosi
        if(!is_int($firstValue) || !is_int($secondValue)) {
            return $this->handleView($this->view(['message' => 'Both values must be integer!'], Response::HTTP_BAD_REQUEST));
        }

        //utworzenie nowego obiektu History i ustawienie wartosci
        $history = new History();
        $history->setFirstIn($firstValue);
        $history->setSecondIn($secondValue);
        $history->setFirstOut($secondValue);
        $history->setSecondOut($firstValue);
        $history->setCreatedAt(new \DateTime());

        //zapisanie obiektu do bazy danych
        $this->entityManager->persist($history);
        $this->entityManager->flush();

        return $this->handleView($this->view(['message' => 'Values exchanged successfully'], Response::HTTP_CREATED));
    }

    //obsluga requesta GET dla "/exchange/values"
    #[Route('/exchange/values', methods: ['GET'])]
    public function getHistoryAction(): Response
    {
        //pobieranie wszystkich rekordow z bazy danych
        $repository = $this->entityManager->getRepository(History::class);
        $records = $repository->findAll();

        //sprawdzenie czy sa jakiekolwiek rekordy
        if (empty($records)) {
            return $this->handleView($this->view(['message' => 'No records found'], Response::HTTP_NOT_FOUND));
        }

        //konwersja rekordow na format ktory mozna zwrocic
        $data = [];
        foreach ($records as $record) {
            $data[] = [
                'id' => $record->getId(),
                'firstIn' => $record->getFirstIn(),
                'secondIn' => $record->getSecondIn(),
                'firstOut' => $record->getFirstOut(),
                'secondOut' => $record->getSecondOut(),
                'createdAt' => $record->getCreatedAt(),
                'updatedAt' => $record->getUpdatedAt(),
            ];
        }

        return $this->handleView($this->view($data, Response::HTTP_OK));
    }

    //oblsuga requesta PUR dla "/exchange/values/{id}"
    #[Route('/exchange/values/{id}', methods: ['PUT'])]
    public function update(Request $request, $id): Response
    {
        //parsowanie ciala zadania
        $data = json_decode($request->getContent(), true);

        //walidacja danych
        if (!isset($data['first_in']) || !isset($data['second_in']))
        {
            return $this->handleView($this->view(['message' => 'Both values must be provided'], Response::HTTP_BAD_REQUEST));
        }

        //pobranie wartosci
        $firstValue = $data['first_in'];
        $secondValue = $data['second_in'];

        //walidacja wartosci
        if(!is_int($firstValue) || !is_int($secondValue))
        {
            return $this->handleView($this->view(['message' => 'Both values must be integer!'], Response::HTTP_BAD_REQUEST));
        }

        //znalezienie obiektu w bazie danych ktory ma byc zaktualizowany
        $repository = $this->entityManager->getRepository(History::class);
        $history = $repository->find($id);

        //sprawdzenie czy obiekt isntnieje
        if(!$history) 
        {
            return $this->handleView($this->view(['message' => 'Record not found!'], Response::HTTP_NOT_FOUND));
        }

        //aktualizacja obiektu
        $history->setFirstIn($firstValue);
        $history->setSecondIn($secondValue);
        $history->setFirstOut($secondValue);
        $history->setSecondOut($firstValue);
        $history->setUpdatedAt(new \DateTime());

        //zapisanie zmian
        $this->entityManager->flush();

        return $this->handleView($this->view(['message' => 'Values updated successfully.'], Response::HTTP_OK));

    }
}
