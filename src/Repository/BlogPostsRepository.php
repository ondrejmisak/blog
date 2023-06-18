<?php
namespace App\Repository;

use App\Entity\BlogPosts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
Use App\Entity\Tags;

/**
 * @extends ServiceEntityRepository<BlogPosts>
 *
 * @method BlogPosts|null find($id, $lockMode = null, $lockVersion = null)
 * @method BlogPosts|null findOneBy(array $criteria, array $orderBy = null)
 * @method BlogPosts[]    findAll()
 * @method BlogPosts[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BlogPostsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlogPosts::class);
    }

    public function save(BlogPosts $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByFilter(array $search, $sort)
    {   
        $query = $this->createQueryBuilder('u');
        $postTags = null;
        if (isset($search['tags']) && $search['tags'] <> "") {
            $tags = explode(",", $search['tags']);
            foreach ($tags as $key => $value) {
                $tags[$key] = trim($value);
                if ($tags[$key] == "")
                    unset($tags[$key]);
            }
            $postTags = $this->getEntityManager()->getRepository(Tags::class)->findBy(["tag" => $tags]);
        }
        
        $postTagsRow[] = null;
        if ($postTags){
            foreach ($postTags as $row) {
                $postTagsRow[] = $row->getPost()->getId();
            }
        }
       
        if (isset($search['tags']) && $search['tags'] <> "") {
            $query->where('u.id IN (:tags)');
            $Parameters['tags'] = $postTagsRow;
            $query->setParameters($Parameters);
        }
 
        foreach ($sort as $key => $val) {
            $query->orderBy('u.' . $key, $val);
        }
        return $query->getQuery()->getResult();
    }

    public function remove(BlogPosts $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return BlogPosts[] Returns an array of BlogPosts objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?BlogPosts
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
