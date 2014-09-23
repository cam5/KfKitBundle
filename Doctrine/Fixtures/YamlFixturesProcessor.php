<?php


namespace Kf\KitBundle\Doctrine\Fixtures;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Kf\KitBundle\Utils\StringUtils;

class YamlFixturesProcessor
{
    protected $accessor;

    public function __construct(AbstractFixture $loader)
    {
        $this->accessor = PropertyAccess::createPropertyAccessor();
        $this->loader   = $loader;
    }

    public function execute($class, $fileName)
    {
        $data   = Yaml::parse(file_get_contents($fileName));
        if(isset($data['file'])){
            return $this->execute($class,dirname($fileName).$data['file']);
        }
        $items  = [];
        $common = isset($data['common']) ? $data['common'] : null;
        foreach ($data['items'] as $k => $v) {
            try {
                $items[] = $this->createItem($class, $k, $v, $common);
            } catch (MappingException $e) {
                ;
            }
        }

        return $items;
    }

    private function createItem($class, $reference, $values, $commonValues = null)
    {
        $ret = new $class();
        if (isset($commonValues)) {
            foreach ($commonValues as $kk => $vv) {
                $this->addValue($ret, $kk, $vv);
            }
        }
        foreach ($values as $kk => $vv) {
            $this->addValue($ret, $kk, $vv);
        }
        if (!is_numeric($reference)) {
            $this->loader->setReference($reference, $ret);
        }

        return $ret;
    }


    private function addValue($obj, $kk, $vv)
    {
        $vv = $this->getValue($vv);
        $this->accessor->setValue($obj, $kk, $vv);
    }

    private function getValue($vv)
    {
        if (is_array($vv)) {
            foreach ($vv as $k => $v) {
                $vv[$k] = $this->getValue($v);
            }
        } elseif (StringUtils::startsWith($vv, '@')) {
            preg_match('/@((?:[\w])+)\[([^\]]*)\]/', $vv, $subj);
            $method = 'process' . ucwords($subj[1]);
            if (!method_exists($this, $method)) {
                throw new \Exception('bad process method ' . $subj[1]);
            }
            $vv = $this->$method($subj[2]);
        }

        return $vv;
    }


    private function processRandom($period)
    {
        if (strpos($period, ',')) {
            list($min, $max) = explode(',', $period);
        } else {
            $min = 1;
            $max = $period;
        }

        return rand($min, $max);
    }


    private function processMethod($name)
    {
        $args = explode(',', $name);
        if (method_exists($this->loader, $args[0])) {
            return call_user_func_array(array($this->loader, $args[0]), array_slice($args, 1));
        } else {
            throw new \Exception('bad method call ' . $name);
        }
    }


    private function processReference($ref)
    {
        return $this->loader->getReference($ref);
    }

    private function processDatetime($ret)
    {
        return new \DateTime($ret);
    }
} 
