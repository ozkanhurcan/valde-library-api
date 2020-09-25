<?php

namespace App\Controller;

use App\Entity\Author;
use App\Entity\Book;
use App\Form\BookType;
use Doctrine\ORM\EntityNotFoundException;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api",name="api_")
 */
class BookController extends AbstractFOSRestController
{
    /**
     * @Rest\Get("/book")
     */
    public function fetchAll()
    {
        $repository = $this->getDoctrine()->getRepository(Book::class);
        $books = $repository->findAll();


        /** @var Serializer $serializer */
        $serializer = $this->container->get('serializer');
        $books = $serializer->serialize($books, 'json', [
            'circular_reference_handler' => function ($object) {
                unset($object);
            }
        ]);
        return new Response($books);
    }

    /**
     * @Rest\Get("/book/{id}")
     * @param int $id
     * @return Response
     */
    public function fetch(int $id)
    {
        $repository = $this->getDoctrine()->getRepository(Book::class);
        $book = $repository->findOneBy(['id' => $id]);

        /** @var Serializer $serializer */
        $serializer = $this->container->get('serializer');
        $book = $book->normalize();
        return new Response(json_encode($book));
    }

    /**
     * @Rest\Post("/book")
     * @param Request $request
     * @return Response
     */
    public function create(Request $request)
    {
        $book = new Book();
        $form = $this->createForm(BookType::class, $book);
        $data = json_decode($request->getContent(), true);
        $form->submit($data);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($book);
            $em->flush();
            return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_CREATED));
        }
        return $this->handleView($this->view($form->getErrors()));
    }

    /** @Rest\Delete("/book/{id}")
     * @param int $id
     * @return EntityNotFoundException|\Exception|Response
     */
    public function delete(int $id)
    {
        $repository = $this->getDoctrine()->getRepository(Book::class);
        $book = $repository->findOneBy(['id' => $id]);
        $em = $this->getDoctrine()->getManager();
        try {
            $em->remove($book);
            $em->flush();
        } catch (EntityNotFoundException $e) {
            return new EntityNotFoundException('Entity not found', $e->getCode());
        } catch (\Exception $e) {
            return new \Exception('An error occurred', $e->getCode());
        }
        return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_MOVED_PERMANENTLY));
    }


    /**
     * @Rest\Patch("/book/{id}")
     * @param $id
     * @param Request $request
     * @throws \Exception
     */
    public function update($id, Request $request)
    {
        ///** @var Arrays $arrayHelper */
        // $arrayHelper = $this->get('helper.array');
        $repository = $this->getDoctrine()->getRepository(Book::class);
        $book = $repository->findOneBy(['id' => $id]);
        $data = json_decode($request->getContent(), true);
        $em = $this->getDoctrine()->getManager();
        $data['id'] = $id;
        $data = self::merge($book->toArray(), $data);
        $data['author'] = $this->getDoctrine()->getRepository(Author::class)->findOneBy(['id' => $data['author']]);
        $book->setData($data);

        try {
            $em->persist($book);
            $em->flush();
        } catch (\Exception $e) {
            dump($e->getMessage());
            exit();
            throw new \Exception('An error occurred', $e->getCode());
        }
    }


    public static function merge(array $a, array $b, $preserveNumericKeys = false)
    {
        foreach ($b as $key => $value) {
            if ($value) {
                $a[$key] = $value;
            } elseif (isset($a[$key])) {
                if ($value) {
                    unset($a[$key]);
                } elseif (!$preserveNumericKeys && is_int($key)) {
                    $a[] = $value;
                } elseif (is_array($value) && is_array($a[$key])) {
                    $a[$key] = static::merge($a[$key], $value, $preserveNumericKeys);
                } else {
                    $a[$key] = $value;
                }
            } else {
                if (!$value) {
                    $a[$key] = $value;
                }
            }
        }

        return $a;
    }
}
