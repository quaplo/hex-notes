<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use App\Domain\Project\Model\ProjectWorker;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity]
#[ORM\Table(name: 'projects')]
class ProjectEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

	/**
	 * @var Collection<int, ProjectWorkerEntity>
	 */
    #[ORM\OneToMany(mappedBy: 'project', targetEntity: ProjectWorkerEntity::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $projectWorkers;

    public function __construct(
        string $id,
        string $name,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $deletedAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->createdAt = $createdAt;
        $this->deletedAt = $deletedAt;
        $this->projectWorkers = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?DateTimeImmutable $dateTime = null): self
    {
        $this->deletedAt = $dateTime ? $dateTime : new DateTimeImmutable();
        return $this;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
    }

	/**
	 * @return Collection<int, ProjectWorkerEntity>
	 */
    public function getProjectWorkers(): Collection
    {
        return $this->projectWorkers;
    }

    public function addProjectWorker(ProjectWorkerEntity $worker): void
    {
        if (!$this->projectWorkers->contains($worker)) {
            $this->projectWorkers->add($worker);
        }
    }

    public function clearProjectWorkers(): void
    {
        $this->projectWorkers->clear();
    }
}
