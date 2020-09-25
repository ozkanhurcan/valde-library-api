<?php

namespace App\Controller;

use App\Entity\Author;
use App\Helper\Arrays;
use App\Form\AuthorType;
use Doctrine\ORM\EntityNotFoundException;
use Egulias\EmailValidator\Exception\ExpectingCTEXT;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use mysql_xdevapi\Exception;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api",name="api_")
 */
class AuthorController extends AbstractFOSRestController
{
    /**
     * @Rest\Get("/author")
     */
    public function fetchAll()
    {
        $repository = $this->getDoctrine()->getRepository(Author::class);
        $authors = $repository->findAll();

        /** @var Serializer $serializer */
        $serializer = $this->container->get('serializer');
        $authors = $serializer->serialize($authors, 'json', [
            'circular_reference_handler' => function ($objects) {
                foreach ($objects as $object) {
                    return $object->normalize(new Author());
                }
            }
        ]);
        return new Response($authors);
    }

    /**
     * @Rest\Get("/author/{id}")
     * @param int $id
     * @return Response
     */
    public function fetch(int $id)
    {
        $repository = $this->getDoctrine()->getRepository(Author::class);
        $author = $repository->findOneBy(['id' => $id]);

        /** @var Serializer $serializer */
        return new Response(json_encode($author->normalize()));
    }

    /**
     * @Rest\Post("/author")
     * @param Request $request
     * @return Response
     */
    public function create(Request $request)
    {
        $author = new Author();
        $form = $this->createForm(AuthorType::class, $author);
        $data = json_decode($request->getContent(), true);
        $form->submit($data);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($author);
            $em->flush();
            return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_CREATED));
        }
        return $this->handleView($this->view($form->getErrors()));
    }

    /** @Rest\Delete("/author/{id}")
     * @param int $id
     * @return EntityNotFoundException|\Exception|Response
     */
    public function delete(int $id)
    {
        $repository = $this->getDoctrine()->getRepository(Author::class);
        $author = $repository->findOneBy(['id' => $id]);
        $em = $this->getDoctrine()->getManager();
        try {
            $em->remove($author);
            $em->flush();
        } catch (EntityNotFoundException $e) {
            return new EntityNotFoundException('Entity not found',$e->getCode());
        } catch (\Exception $e) {
            return new \Exception('An error occurred', $e->getCode());
        }
        return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_MOVED_PERMANENTLY));
    }

    /**
     * @Rest\Patch("/author/{id}")
     * @param $id
     * @param Request $request
     * @throws \Exception
     */
    public function update($id, Request $request) {
        ///** @var Arrays $arrayHelper */
       // $arrayHelper = $this->get('helper.array');
        $repository = $this->getDoctrine()->getRepository(Author::class);
        $author = $repository->findOneBy(['id' => $id]);
        $data = json_decode($request->getContent(), true);
        $em = $this->getDoctrine()->getManager();
        $data['id'] = $id;
        $data = self::merge($author->toArray(), $data);
        $author->setData($data);

        try {
            $em->persist($author);
            $em->flush();
        } catch (\Exception $e) {
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
