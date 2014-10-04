<?php

namespace Kf\KitBundle\Symfony\Controller;

use Kf\KitBundle\Doctrine\ORM\Repository\EntityRepository;

trait DoctrineORMHelper{
    /**
     * @param      $entity
     * @param bool $flush
     *
     * @return $this
     */
    public function save($entity, $flush = true)
    {
        $em = $this->getDoctrine()->getManager();
        if (is_array($entity)) {
            foreach ($entity as $item) {
                $this->save($item, false);
            }
        } else {
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
        $em = $this->getDoctrine()->getManager();
        if (is_array($entity)) {
            foreach ($entity as $item) {
                $this->remove($item, false);
            }
        } else {

            $em->remove($entity);
        }
        if ($flush) {
            $em->flush();
        }

        return $this;
    }

    /**
     * @return EntityRepository
     */
    public function getRepo($class = null)
    {
        if (!isset($class)) {
            $class = static::ENTITY_CLASS;
        }

        return $this->getDoctrine()->getManager()->getRepository($class);
    }
}
