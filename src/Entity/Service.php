<?php

namespace App\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ServiceRepository")
 */
class Service
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $global_id;

    /**
     * @ORM\Column(type="string", length=300)
     */
    private $Name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $Razdel;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $Idx;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $Kod;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $Nomdescr;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGlobalId(): ?int
    {
        return $this->global_id;
    }

    public function setGlobalId(int $global_id): self
    {
        $this->global_id = $global_id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->Name;
    }

    public function setName(string $Name): self
    {
        $this->Name = $Name;

        return $this;
    }

    public function getRazdel(): ?string
    {
        return $this->Razdel;
    }

    public function setRazdel(string $Razdel): self
    {
        $this->Razdel = $Razdel;

        return $this;
    }

    public function getIdx(): ?string
    {
        return $this->Idx;
    }

    public function setIdx(string $Idx): self
    {
        $this->Idx = $Idx;

        return $this;
    }

    public function getKod(): ?string
    {
        return $this->Kod;
    }

    public function setKod(string $Kod): self
    {
        $this->Kod = $Kod;

        return $this;
    }

    public function getNomdescr(): ?string
    {
        return $this->Nomdescr;
    }

    public function setNomdescr(?string $Nomdescr): self
    {
        $this->Nomdescr = $Nomdescr;

        return $this;
    }
}
