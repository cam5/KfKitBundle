<?php

namespace Kf\KitBundle\Doctrine\ORM\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository as BaseEntityRepository;
use Kf\KitBundle\Doctrine\ORM\Query\QueryBuilderUtils;

class EntityRepository extends BaseEntityRepository
{
    const ALIAS = 'entity';

    static protected $joinColumns = [];
    static protected $searchFields = [
        'name'
    ];

    static protected $checkFields = [];

    /**
     * @return object
     */
    public function create()
    {
        $class = $this->getClassName();

        return new $class();
    }

    /**
     * @param      $entity
     * @param bool $flush
     *
     * @return $this
     */
    public function save($entity, $flush = true)
    {
        $em = $this->getEntityManager();
        if (is_array($entity)) {
            foreach ($entity as $item) {
                $this->save($item, false);
            }
        } else {
            $this->validateClass(get_class($entity));
            $em->persist($entity);
        }
        if ($flush) {
            $em->flush();
        }

        return $this;
    }

    /**
     * @param      $entity
     * @param bool $flush
     *
     * @return $this
     */
    public function remove($entity, $flush = true)
    {
        $em = $this->getEntityManager();
        if (is_array($entity)) {
            foreach ($entity as $item) {
                $this->save($item, false);
            }
        } else {
            $this->validateClass(get_class($entity));
            $em->remove($entity);
        }
        if ($flush) {
            $em->flush();
        }

        return $this;
    }

    /**
     * @param $class
     *
     * @throws \BadMethodCallException
     */
    protected function validateClass($class)
    {
        if (!$this->supportsClass($class)) {
            throw new \BadMethodCallException(
                sprintf(
                    'Instances of "%s" are not supported.',
                    $class
                )
            );
        }
    }

    /**
     * @param $class
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        return $this->getEntityName() === $class
        || is_subclass_of($class, $this->getEntityName());
    }

    /**
     * @param array $criteria
     * @return QueryBuilder
     */
    public function getQuery($criteria = null)
    {
        $ret = $this->createQueryBuilder($this->getAlias());
        if ($criteria) {
            $this->processCriteria($ret, $criteria);
        }

        return $ret;
    }

    public function processCriteria(QueryBuilder $query, $criteria = null)
    {
        if (isset($criteria['s'])) {
            $this->addSearch($query, $criteria['s']);
        }
        if (isset($criteria['@leftjoin'])) {
            $query->leftJoin($criteria['@leftjoin'], $this->getAlias());
        } elseif (isset($criteria['@join'])) {
            $query->join($criteria['@join'], $this->getAlias());
        } elseif (isset($criteria['@innerjoin'])) {
            $query->innerJoin($criteria['@innerjoin'], $this->getAlias());
        }

        if (isset($criteria['@select'])) {
            $query->addSelect($this->getAlias());
        }
        if (isset($criteria['@limit'])) {
            $query->setMaxResults($criteria['@limit']);
        }
        if (isset($criteria['@offset'])) {
            $query->setFirstResult($criteria['@offset']);
        }
        if (isset($criteria['@orderby'])) {
            foreach ($criteria['@orderby'] as $k => $v) {
                $query->addOrderBy($this->getAlias() . '.' . $k, $v);
            }
        }
        $this->checkFields($query, $criteria);
        $this->doJoins($query, $criteria);

        return $query;
    }

    protected function checkFields($query, $criteria)
    {
        foreach (static::$checkFields as $k) {
            if (isset($criteria[$k])) {
                $query->andWhere($this->getAlias() . '.' . $k . ' = :' . $k);
                $query->setParameter($k, $criteria[$k]);
            }
        }
    }

    private function doJoins(QueryBuilder $query, $criteria)
    {
        foreach (static::$joinColumns as $k => $v) {
            if (isset($criteria['@join-' . $k])) {
                /** @var EntityRepository $repo */
                $repo                           = $this->getRepo($v);
                $mode                           = isset($criteria['@join-' . $k . '-mode']) ?
                    $criteria['@join-' . $k . '-mode']
                    : '@leftjoin';
                $criteria['@join-' . $k][$mode] = $this->getAlias() . '.' . $k;
                $repo->processCriteria($query, $criteria['@join-' . $k]);
            }
        }
    }

    private function addSearch($query, $s)
    {
        $fields = static::$searchFields;
        foreach ($fields as $k => $v) {
            if (strpos($v, '.') === false) {
                $fields[$k] = $this->getAlias() . '.' . $v;
            }
        }
        QueryBuilderUtils::addBasicSearchToQuery($query, $fields, $s);
    }

    public function getCount($criteria = null)
    {
        return $this->getQuery($criteria)
            ->select('COUNT(DISTINCT ' . $this->getAlias() . ')')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getAlias()
    {
        return static::ALIAS;
    }

    public function getOne($criteria)
    {
        return $this->getQuery($criteria)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function get($criteria)
    {
        return $this->getQuery($criteria)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $k
     * @throws Exception
     * @return EntityRepository
     */
    protected function getRepo($k)
    {
        $ret = $this->getEntityManager()->getRepository($k);
        if (!($ret instanceof EntityRepository)) {
            throw new \Exception("$k repository should extends " . __CLASS__);
        }

        return $ret;
    }
}

