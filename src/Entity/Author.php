<?php

namespace App\Entity;

use App\Repository\AuthorRepository;
use Cassandra\Date;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AuthorRepository::class)
 */
class Author
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
    private $nameSurname;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $birthday;

    /**
     * @ORM\Column(type="string", length=1000, nullable=true)
     */
    private $biography;

    /**
     * @ORM\OneToMany(targetEntity=Book::class, mappedBy="author", orphanRemoval=true)
     */
    private $books;

    public function __construct($data = null)
    {
        $this->books = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    private function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getNameSurname(): ?string
    {
        return $this->nameSurname;
    }

    public function setNameSurname(string $nameSurname): self
    {
        $this->nameSurname = $nameSurname;

        return $this;
    }

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(?\DateTimeInterface $birthday): self
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getBiography(): ?string
    {
        return $this->biography;
    }

    public function setBiography(?string $biography): self
    {
        $this->biography = $biography;

        return $this;
    }

    /**
     * @return Collection|Book[]
     */
    public function getBooks(): Collection
    {
        return $this->books;
    }

    public function addBook(Book $book): self
    {
        if (!$this->books->contains($book)) {
            $this->books[] = $book;
            $book->setAuthor($this);
        }

        return $this;
    }

    public function removeBook(Book $book): self
    {
        if ($this->books->contains($book)) {
            $this->books->removeElement($book);
            // set the owning side to null (unless already changed)
            if ($book->getAuthor() === $this) {
                $book->setAuthor(null);
            }
        }

        return $this;
    }

    public function normalize($root = null) {
        $root = is_object($root) ? get_class($root) : $root;
        switch ($root) {
            case 'App\Entity\Book':
                return [
                    'id' => $this->getId(),
                    'nameSurname' => $this->getNameSurname(),
                    'biography' => $this->getBiography(),
                    'birthday' => $this->getBirthday(),
                ];
                break;
            default:
                return [
                    'id' => $this->getId(),
                    'nameSurname' => $this->getNameSurname(),
                    'biography' => $this->getBiography(),
                    'birthday' => $this->getBirthday(),
                    'books' => $this->normalizeBooks(),
                ];
                break;

        }
    }

    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'nameSurname' => $this->getNameSurname(),
            'biography' => $this->getBiography(),
            'birthday' => $this->getBirthday(),
        ];
    }

    private function normalizeBooks() {
        $books = [];
        foreach ($this->getBooks() as $book) {
            $books[] = $book->normalize($this);
        }
        return $books;
    }

    public function setData($data) {
        if ($data) {
            $dateTime = \DateTime::createFromFormat('d-m-Y', $data['birthday']);
            $this->setBirthday($dateTime ? $dateTime : null);
            $this->setId($data['id']);
            $this->setBiography($data['biography']);
            $this->setNameSurname($data['nameSurname']);
        }
    }
}