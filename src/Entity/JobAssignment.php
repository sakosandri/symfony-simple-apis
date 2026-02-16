<?php

namespace App\Entity;

use App\Repository\JobAssignmentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobAssignmentRepository::class)]
#[ORM\Table(name: 'job_assignments')]
#[ORM\HasLifecycleCallbacks]
class JobAssignment
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'assignments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Job::class, inversedBy: 'assignments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Job $job = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $scheduledDate = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $assessment = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $rating = null;

    #[ORM\Column(type: 'string', length: 20)]
    private ?string $status = self::STATUS_SCHEDULED;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->status = self::STATUS_SCHEDULED;
    }

    #[ORM\PrePersist]
    public function onCreate(): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getJob(): ?Job
    {
        return $this->job;
    }

    public function setJob(?Job $job): static
    {
        $this->job = $job;
        return $this;
    }

    public function getScheduledDate(): ?\DateTimeImmutable
    {
        return $this->scheduledDate;
    }

    public function setScheduledDate(\DateTimeImmutable $scheduledDate): static
    {
        $this->scheduledDate = $scheduledDate;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getAssessment(): ?string
    {
        return $this->assessment;
    }

    public function setAssessment(?string $assessment): static
    {
        $this->assessment = $assessment;
        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): static
    {
        $this->rating = $rating;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function markAsCompleted(string $assessment, int $rating): static
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->assessment = $assessment;
        $this->rating = $rating;

        // Update job status if needed
        if ($this->job && $this->job->getStatus() === Job::STATUS_ASSIGNED) {
            $this->job->setStatus(Job::STATUS_COMPLETED);
        }

        return $this;
    }
}