<?php

namespace Kf\KitBundle\Doctrine\ORM\Traits;

trait TimestampableEntity
{
    use CreableEntity;

    /**
     * @\Gedmo\Mapping\Annotation\Timestampable(on="update")
     * @\Doctrine\ORM\Mapping\Column(type="datetime")
     */
    protected $updatedAt;

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }


}
