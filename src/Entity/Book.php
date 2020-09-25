<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * @ORM\Entity(repositoryClass=BookRepository::class)
 */
class Book
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $descripton;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $dateOfPublication;

    /**
     * @MaxDepth(2)
     * @ORM\ManyToOne(targetEntity=Author::class, inversedBy="books")
     * @ORM\JoinColumn(nullable=false)
     */
    private $author;

    private function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescripton(): ?string
    {
        return $this->descripton;
    }

    public function setDescripton(?string $descripton): self
    {
        $this->descripton = $descripton;

        return $this;
    }

    public function getDateOfPublication(): ?\DateTimeInterface
    {
        return $this->dateOfPublication;
    }

    public function setDateOfPublication(?\DateTimeInterface $dateOfPublication): self
    {
        $this->dateOfPublication = $dateOfPublication;

        return $this;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor($author): self
    {
        $this->author = $author;

        return $this;
    }

    public function normalize($root = null)
    {
        $root = is_object($root) ? get_class($root) : $root;
        switch ($root) {
            case 'App\Entity\Author':
                return [
                    'id' => $this->getId(),
                    'title' => $this->getTitle(),
                    'dateOfPublication' => $this->getDateOfPublication(),
                    'description' => $this->getDescripton(),
                ];
                break;
            default:
                return [
                    'id' => $this->getId(),
                    'title' => $this->getTitle(),
                    'author' => $this->getAuthor()->normalize($this),
                    'dateOfPublication' => $this->getDateOfPublication(),
                    'description' => $this->getDescripton(),
                ];
                break;
        }
    }

    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'dateOfPublication' => $this->getDateOfPublication(),
            'description' => $this->getDescripton(),
            'author' => $this->getAuthor()->getId(),
        ];
    }

    public function setData($data)
    {
        if ($data) {
            $this->setTitle($data['title']);
            $this->setAuthor($data['author']);
            $dateTime = \DateTime::createFromFormat('d-m-Y', $data['dateOfPublication']);
            $this->setDateOfPublication($dateTime ? $dateTime : null);
            $this->setDescripton($data['description'] ? $data['description'] : null);
            $this->setId($data['id']);
        }
    }
}
