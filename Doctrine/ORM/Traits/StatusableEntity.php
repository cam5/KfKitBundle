<?php

namespace Kf\KitBundle\Doctrine\ORM\Traits;

use Kf\KitBundle\Functions;

trait StatusableEntity
{
    /**
     * @var string
     * @\Doctrine\ORM\Mapping\Column(type="string", length=20)
     */
    private $status;

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string|array $status
     * @return boolean
     */
    public function hasStatus($status)
    {
        return Functions::has($this->getStatus(), $status);
    }
}
