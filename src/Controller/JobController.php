<?php

namespace App\Controller;

use App\Entity\Job;
use App\Repository\JobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/jobs', name: 'api_job_')]
#[IsGranted('ROLE_USER')]
class JobController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private JobRepository $jobRepository
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $status = $request->query->get('status');

        $jobs = $status
            ? $this->jobRepository->findBy(['status' => $status], ['createdAt' => 'DESC'])
            : $this->jobRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->json([
            'success' => true,
            'data' => array_map(fn($job) => $this->serializeJob($job), $jobs)
        ]);
    }

    #[Route('/available', name: 'available', methods: ['GET'])]
    public function available(): JsonResponse
    {
        $jobs = $this->jobRepository->findBy(
            ['status' => Job::STATUS_AVAILABLE],
            ['createdAt' => 'DESC']
        );

        return $this->json([
            'success' => true,
            'data' => array_map(fn($job) => $this->serializeJob($job), $jobs)
        ]);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $job = $this->jobRepository->find($id);

        if (!$job) {
            return $this->json(['success' => false, 'message' => 'Job not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['success' => true, 'data' => $this->serializeJob($job)]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title']) || !isset($data['location'])) {
            return $this->json([
                'success' => false,
                'message' => 'Missing required fields',
                'errors' => [
                    'title' => $data['title'] ?? 'Title is required',
                    'location' => $data['location'] ?? 'Location is required'
                ]
            ], Response::HTTP_BAD_REQUEST);
        }

        $job = new Job();
        $job->setTitle(trim($data['title']))
            ->setDescription($data['description'] ?? null)
            ->setLocation(trim($data['location']))
            ->setStatus(Job::STATUS_AVAILABLE);

        $this->em->persist($job);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Job created successfully',
            'data' => $this->serializeJob($job)
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $job = $this->jobRepository->find($id);
        if (!$job) return $this->json(['success' => false, 'message' => 'Job not found'], Response::HTTP_NOT_FOUND);

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) $job->setTitle(trim($data['title']));
        if (isset($data['description'])) $job->setDescription($data['description']);
        if (isset($data['location'])) $job->setLocation(trim($data['location']));
        if (isset($data['status'])) {
            $validStatuses = [Job::STATUS_AVAILABLE, Job::STATUS_ASSIGNED, Job::STATUS_COMPLETED, Job::STATUS_CANCELLED];
            if (!in_array($data['status'], $validStatuses)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Invalid status',
                    'errors' => ['status' => 'Status must be: available, assigned, completed, or cancelled']
                ], Response::HTTP_BAD_REQUEST);
            }
            $job->setStatus($data['status']);
        }

        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Job updated successfully',
            'data' => $this->serializeJob($job)
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $job = $this->jobRepository->find($id);
        if (!$job) return $this->json(['success' => false, 'message' => 'Job not found'], Response::HTTP_NOT_FOUND);

        $this->em->remove($job);
        $this->em->flush();

        return $this->json(['success' => true, 'message' => 'Job deleted successfully']);
    }

    private function serializeJob(Job $job): array
    {
        return [
            'id' => $job->getId(),
            'title' => $job->getTitle(),
            'description' => $job->getDescription(),
            'location' => $job->getLocation(),
            'status' => $job->getStatus(),
            'created_at' => $job->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $job->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
