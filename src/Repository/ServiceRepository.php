<?php

namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\ORMException;

/**
 * @method Service|null find($id, $lockMode = null, $lockVersion = null)
 * @method Service|null findOneBy(array $criteria, array $orderBy = null)
 * @method Service[]    findAll()
 * @method Service[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }


    private static function log($text) {
        $text = date('d m Y h:i:s') . ' | ' . $text  . "\n";
        file_put_contents('log.txt', $text, FILE_APPEND);
    }

    /**
     * Загружает услуги из xml файла в БД
     *
     * @param EntityManager $em
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     */
    public static function loadServices(EntityManager $em)
    {
        self::log('Начало загрузки');
        if ($_FILES['file']['name'] && $_FILES['file']['error'] == 0) {
            $xml = simplexml_load_file($_FILES['file']['tmp_name']);

            $conn = $em->getConnection();
            $p = $conn->getDatabasePlatform();
            $conn->executeUpdate($p->getTruncateTableSQL('service', true));

            $length = sizeof($xml);
            self::log('Количество строк: ' . $length);
            $batchSize = 20;
            for ($i = 0; $i < $length; $i++) {
                $service = new Service;
                $service
                    ->setGlobalId($xml->array[$i]->global_id->__toString())
                    ->setName($xml->array[$i]->Name->__toString())
                    ->setRazdel($xml->array[$i]->Razdel->__toString())
                    ->setIdx($xml->array[$i]->Idx->__toString())
                    ->setKod($xml->array[$i]->Kod->__toString())
                    ->setNomdescr($xml->array[$i]->Nomdescr->__toString());

                $em->persist($service);

                if (($i % $batchSize) === 0) {
                    $em->flush();
                    $em->clear();
                }
            }

            try {
                $em->persist($service);
                $em->flush();
                $em->clear();
            } catch (ORMException $e) {
                echo $e->getMessage();
            }
        }
        self::log('Конец загрузки');
    }

    public function tree()
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $services = $queryBuilder
            ->select("s.id, CONCAT(s.Name, ' ', s.Kod) AS text, s.Idx")
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

        $output = connectChild('', $services);
        return json_encode($output);
    }

    // /**
    //  * @return Service[] Returns an array of Service objects
    //  */
    public function findByKodField($value)
    {
        $queryBuilder = $this->createQueryBuilder('s');

        $allServices = $queryBuilder
            ->select("s.id, CONCAT(s.Name, ' ', s.Kod) AS text, s.Idx")
            ->add('from', Service::class . ' s')
            ->getQuery()
            ->getResult()
        ;

        $findedServices = $this->createQueryBuilder('s')
            ->select("s.id, CONCAT(s.Name, ' ', s.Kod) AS text, s.Idx")
            ->add('from', Service::class . ' s')
            ->andWhere('s.Kod LIKE :val')
            ->setParameter('val', '%' . $value . '%')
            ->orderBy('s.Idx', 'ASC')
            //->setMaxResults(10)
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
        return json_encode($findedServices);
    }

    /*
    public function findOneBySomeField($value): ?Service
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
