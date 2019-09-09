<?php
/**
 * Created by PhpStorm.
 * User: Boris Guzeev
 * Date: 05.09.2019
 * Time: 14:55
 */

namespace App\Controller;

use App\Entity\Service;
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
        $doc = new \DOMDocument();

        $newRows = 0;
        if ($_FILES['file']['name'] && $_FILES['file']['error'] == 0) {
            $reader = new \XMLReader();
            $reader->open($_FILES['file']['tmp_name']);

            while ($reader->read() && $reader->name !== 'array');
            while($reader->name == 'array') {
                $oneNode = $reader->expand();
                $node = simplexml_import_dom($doc->importNode($oneNode, true));

                if ($node->global_id) {

                    $service = $this->getDoctrine()
                        ->getRepository(Service::class)
                        ->findOneBy([
                            'global_id' => $node->global_id
                        ]);

                    if (!$service) {
                        $service = new Service;
                        $newRows++;
                    }

                    $service->setGlobalId($node->global_id->__toString());

                    if ($node->Name)
                        $service->setName($node->Name->__toString());
                    else
                        $this->log('Отсутствует элемент Name, строка: ' . $oneNode->getLineNo() );

                    if ($node->Razdel)
                        $service->setRazdel($node->Razdel->__toString());
                    else
                        $this->log('Отсутствует элемент Razdel, строка: ' . $oneNode->getLineNo() );

                    if ($node->Idx)
                        $service->setIdx($node->Idx->__toString());
                    else
                        $this->log('Отсутствует элемент Idx, строка: ' . $oneNode->getLineNo() );

                    if ($node->Kod)
                        $service->setKod($node->Kod->__toString());
                    else
                        $this->log('Отсутствует элемент Kod, строка: ' . $oneNode->getLineNo() );

                    if ($node->Nomdescr)
                        $service->setNomdescr($node->Nomdescr->__toString());
                    else
                        $this->log('Отсутствует элемент Nomdescr, строка: ' . $oneNode->getLineNo() );

                    $entityManager->persist($service);
                    $entityManager->flush();
                    $entityManager->clear();
                } else {
                    $this->log('Отсутствует обязательный элемент global_id, строка: ' . $oneNode->getLineNo() );
                }

                $reader->next('array');
            }
        }

        $table = Service::class;
        $dql = "SELECT COUNT(s.id) AS balance FROM $table s";
        $numRows = $entityManager->createQuery($dql)
            ->getSingleScalarResult();

        self::log('Общее количество строк в БД: ' . $numRows . ' количество новых: ' . $newRows);
        self::log('Конец загрузки', "\n");

        return new Response(
            'ok'
        );
    }

    /**
     * @Route("/list")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function list()
    {
        $tree = $this->tree();
        return $this->json($tree);
    }

    /**
     * @Route("/search/{slug}")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function search($slug)
    {
        $slug = trim($slug);
        // если введены только цифры и точки, то ищем по Коду
        $pattern = '/^[0-9.]+$/';
        if (preg_match($pattern, $slug)) {
            $finded = $this->findByField('Idx', $slug);

        } else {
            $finded = $this->findByField('Name', $slug);
        }

        return $this->json($finded);
    }

    private function tree()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $queryBuilder = $entityManager->createQueryBuilder('s');
        $services = $queryBuilder
            ->select("s.id, CONCAT(s.Name, ' ', s.Idx) AS text, s.Idx")
            ->add('from', Service::class . ' s')
            ->getQuery()
            ->getResult()
        ;

        function connectChild($parentId = '', $services)
        {
            $outputTree = [];
            if (empty($parentId)) {
                $pattern = '/^[A-Z]\.$/';
            } else {
                $parentId = str_replace('.', '\.', $parentId);
                $pattern = '/^' . $parentId . '\.[0-9]+$' . '/';
            }

            foreach ($services as $key => &$service) {
                if (preg_match($pattern, $service['Idx'])) {
                    $outputTree[$key] = $services[$key];

                    $service['Idx'] = ($parentId == '') ? str_replace('.', '', $service['Idx']) : $service['Idx'];
                    if ($children = connectChild($service['Idx'],$services)) {
                        $outputTree[$key]['children'] = $children;
                    }
                    unset($services[$key]);
                }
            }

            return $outputTree = array_values($outputTree);
        }

        return connectChild('', $services);
    }

    private function findByField($field, $value)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $allServices = $entityManager->createQueryBuilder('s')
            ->select("s.id, CONCAT(s.Name, ' ', s.Kod) AS text, s.Idx")
            ->add('from', Service::class . ' s')
            ->getQuery()
            ->getResult()
        ;

        $findedServices = $entityManager->createQueryBuilder('s')
            ->select("s.id, CONCAT(s.Name, ' ', s.Kod) AS text, s.Idx")
            ->add('from', Service::class . ' s')
            ->andWhere("s.$field LIKE :val")
            ->setParameter('val', '%' . $value . '%')
            ->getQuery()
            ->getResult()
        ;

        foreach ($findedServices as $key => &$findedService) {
            $Idx = str_replace('.', '\.', $findedService['Idx']);

            $pattern = '/^' . $Idx . '\.[0-9]+$/';
            foreach ($allServices as $service) {
                if (preg_match($pattern, $service['Idx'])) {
                    $findedService['children'] = [$service];
                }
            }
        }

        return $findedServices;
    }

    private function log($text, $merge = '') {
        $text = date('d m Y h:i:s') . ' | ' . $text  . "\n" . $merge;
        file_put_contents('log.txt', $text, FILE_APPEND);
    }
}