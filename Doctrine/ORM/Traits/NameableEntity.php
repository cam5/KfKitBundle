<?php

namespace Kf\KitBundle\Doctrine\ORM\Traits;

trait NameableEntity
{
    /**
     * @var string
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=255, nullable=false)
     * @\Gedmo\Mapping\Annotation\Slug(fields={"name"}, updatable=true)
     */
    private $slug;

    /**
     * Set name
     *
     * @param string $name
     * @return Tournament
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return Game
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }
} 
