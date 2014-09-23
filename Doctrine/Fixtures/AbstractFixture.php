<?php

namespace Kf\KitBundle\Doctrine\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture as BaseFixture;
use Doctrine\Common\DataFixtures\Doctrine;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AbstractFixture extends BaseFixture implements ContainerAwareInterface
{

    /**
     * @var ContainerInterface
     */
    protected $container;
    /** @var  ObjectManager */
    protected $manager;

    protected function getFixturesDir()
    {
        if($this->container->hasParameter('app.fixture.dir')){
            return $this->container->getParameter('app.fixture.dir');
        }else{
            $reflector = new \ReflectionClass(get_class($this));
            return dirname($reflector->getFileName()).'/fixtures';
        }
    }

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;
        $this->execute();
    }

    protected function processYamlFixtures($class, $fileName)
    {
        if (!file_exists($fileName)) {
            return [];
        }
        $obj = new YamlFixturesProcessor($this);

        return $obj->execute($class, $fileName);
    }

    public function execute()
    {
        $manager = $this->manager;
        $items   = $this->processYamlFixtures($this->getEntityClass(), $this->getYamlFileName());
        foreach ($items as $item) {
            $manager->persist($item);
        }
        $manager->flush();
    }

    protected function getEntityClass()
    {
        return static::ENTITY_CLASS;
    }

    protected function getYamlFileName()
    {
        $x = $this->getEntityClass();
        $x = trim(strtolower(str_replace("Bundle\\Entity\\", '_', substr($x, strpos($x, "\\")))), "\\");
//        echo $this->getFixturesDir() . "/$x.yml\r\n";
        return $this->getFixturesDir() . "/$x.yml";
    }

    public function makeFile($name)
    {
        $filename = $name;
        $path     = $this->getFixturesDir() . '/media/';
        $fullname = $path . $filename;
        return new UploadedFile(
            $fullname, $filename,
            finfo_file( finfo_open( FILEINFO_MIME_TYPE ), $fullname );,
            filesize($fullname),
            null
        );
    }

}
