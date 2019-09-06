<?php
/**
 * Created by PhpStorm.
 * User: Boris Guzeev
 * Date: 05.09.2019
 * Time: 14:55
 */

namespace App\Controller;

use App\Entity\Service;
use App\Repository\ServiceRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class ServiceController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function index()
    {
        $services = $this->getDoctrine()
            ->getRepository(Service::class)
            ->findAll();

        $services = print_r($services, true);

        return $this->render('base.html.twig',[
            'output' => $services
        ]);
    }

    /**
     * @Route("/upload")
     */
    public function upload()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $loadingResult = $this-> getDoctrine()->getRepository(Service::class)
            ->loadServices($entityManager);

        if ($loadingResult)
            return new Response(
                'ok'
            );
        else {
            return new Response(
                'ошибка сохранения',
                500
            );
        }
    }

    /**
     *
     * @Route("/list")
     * @var QueryBuilder $queryBuilder
     */
    public function list()
    {
        $tree = $this->getDoctrine()
            ->getRepository(Service::class)
            ->tree();

        return new Response(
            $tree,
            200,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * @Route("/search/{slug}")
     */
    public function search($slug)
    {
        $slug = trim($slug);
        // если введены только цифры и точки, то ищем по Коду
        $pattern = '/^[0-9.]+$/';
        if (preg_match($pattern, $slug)) {
            $finded = $this->getDoctrine()
                ->getRepository(Service::class)
                ->findByField('Kod', $slug);

        } else {
            $finded = $this->getDoctrine()
                ->getRepository(Service::class)
                ->findByField('Name', $slug);
        }
        return new Response(
            $finded,
            200,
            ['Content-Type' => 'application/json']
        );
    }
}