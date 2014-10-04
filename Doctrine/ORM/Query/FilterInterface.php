<?php

namespace Kf\KitBundle\Doctrine\ORM\Query;

interface FilterInterface
{
    /**
     * @return array
     */
    public function getQueryParameters();
}

