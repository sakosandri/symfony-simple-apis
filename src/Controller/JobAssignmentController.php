<?php

namespace App\Controller;

use App\Entity\Job;
use App\Entity\JobAssignment;
use App\Entity\User;
use App\Repository\JobAssignmentRepository;
use App\Repository\JobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/assignments', name: 'api_assignment_')]
#[IsGranted('ROLE_USER')]
class JobAssignmentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private JobAssignmentRepository $assignmentRepository,
        private JobRepository $jobRepository
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser) return $this->json(['success' => false, 'message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

        $status = $request->query->get('status');
        $criteria = ['user' => $currentUser];
        if ($status) $criteria['status'] = $status;

        $assignments = $this->assignmentRepository->findBy($criteria, ['scheduledDate' => 'ASC']);

        return $this->json(['success' => true, 'data' => array_map(fn($a) => $this->serializeAssignment($a), $assignments)]);
    }

    #[Route('/my', name: 'my_assignments', methods: ['GET'])]
    public function myAssignments(): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $assignments = $this->assignmentRepository->findBy(['user' => $currentUser], ['scheduledDate' => 'ASC']);

        return $this->json(['success' => true, 'data' => array_map(fn($a) => $this->serializeAssignment($a), $assignments)]);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $assignment = $this->assignmentRepository->find($id);
        if (!$assignment) return $this->json(['success' => false, 'message' => 'Assignment not found'], Response::HTTP_NOT_FOUND);
        if ($assignment->getUser()->getId() !== $currentUser->getId()) return $this->json(['success' => false, 'message' => 'Access denied'], Response::HTTP_FORBIDDEN);

        return $this->json(['success' => true, 'data' => $this->serializeAssignment($assignment)]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['job_id'], $data['scheduled_date'])) {
            return $this->json([
                'success' => false,
                'message' => 'Missing required fields',
                'errors' => [
                    'job_id' => $data['job_id'] ?? 'Job ID is required',
                    'scheduled_date' => $data['scheduled_date'] ?? 'Scheduled date is required'
                ]
            ], Response::HTTP_BAD_REQUEST);
        }

        $job = $this->jobRepository->find($data['job_id']);
        if (!$job) return $this->json(['success' => false, 'message' => 'Job not found'], Response::HTTP_NOT_FOUND);
        if ($job->getStatus() !== Job::STATUS_AVAILABLE) return $this->json(['success' => false, 'message' => 'Job not available'], Response::HTTP_BAD_REQUEST);

        try {
            $scheduledDate = new \DateTimeImmutable($data['scheduled_date'], new \DateTimeZone('UTC'));
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Invalid date format'], Response::HTTP_BAD_REQUEST);
        }

        $assignment = new JobAssignment();
        $assignment->setUser($currentUser)
                   ->setJob($job)
                   ->setScheduledDate($scheduledDate)
                   ->setStatus(JobAssignment::STATUS_SCHEDULED);

        $job->setStatus(Job::STATUS_ASSIGNED);

        $this->em->persist($assignment);
        $this->em->flush();

        return $this->json(['success' => true, 'message' => 'Job assigned successfully', 'data' => $this->serializeAssignment($assignment)], Response::HTTP_CREATED);
    }

    #[Route('/{id}/complete', name: 'complete', methods: ['POST'])]
    public function complete(int $id, Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $assignment = $this->assignmentRepository->find($id);

        if (!$assignment) return $this->json(['success' => false, 'message' => 'Assignment not found'], Response::HTTP_NOT_FOUND);
        if ($assignment->getUser()->getId() !== $currentUser->getId()) return $this->json(['success' => false, 'message' => 'Access denied'], Response::HTTP_FORBIDDEN);

        $data = json_decode($request->getContent(), true);
        if (!isset($data['assessment'], $data['rating'])) return $this->json(['success' => false, 'message' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);

        $rating = (int)$data['rating'];
        if ($rating < 1 || $rating > 5) return $this->json(['success' => false, 'message' => 'Invalid rating'], Response::HTTP_BAD_REQUEST);

        $assignment->markAsCompleted($data['assessment'], $rating);
        $this->em->flush();

        return $this->json(['success' => true, 'message' => 'Assignment completed successfully', 'data' => $this->serializeAssignment($assignment)]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $assignment = $this->assignmentRepository->find($id);

        if (!$assignment) return $this->json(['success' => false, 'message' => 'Assignment not found'], Response::HTTP_NOT_FOUND);
        if ($assignment->getUser()->getId() !== $currentUser->getId()) return $this->json(['success' => false, 'message' => 'Access denied'], Response::HTTP_FORBIDDEN);

        if ($assignment->getStatus() !== JobAssignment::STATUS_COMPLETED) {
            $job = $assignment->getJob();
            if ($job) $job->setStatus(Job::STATUS_AVAILABLE);
        }

        $this->em->remove($assignment);
        $this->em->flush();

        return $this->json(['success' => true, 'message' => 'Assignment deleted successfully']);
    }

    private function serializeAssignment(JobAssignment $assignment): array
    {
        return [
            'id' => $assignment->getId(),
            'user' => [
                'id' => $assignment->getUser()->getId(),
                'name' => $assignment->getUser()->getName(),
                'email' => $assignment->getUser()->getEmail(),
            ],
            'job' => [
                'id' => $assignment->getJob()->getId(),
                'title' => $assignment->getJob()->getTitle(),
                'location' => $assignment->getJob()->getLocation(),
                'status' => $assignment->getJob()->getStatus(),
            ],
            'scheduled_date' => $assignment->getScheduledDate()?->format('Y-m-d H:i:s'),
            'completed_at' => $assignment->getCompletedAt()?->format('Y-m-d H:i:s'),
            'assessment' => $assignment->getAssessment(),
            'rating' => $assignment->getRating(),
            'status' => $assignment->getStatus(),
            'created_at' => $assignment->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $assignment->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
