<?php


namespace Kf\KitBundle\Doctrine\Fixtures;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Kf\KitBundle\Utils\StringUtils;

class FixturesProcessor
{
    protected $accessor;

    public function __construct(AbstractFixture $loader)
    {
        $this->accessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableMagicCall()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();
        $this->loader   = $loader;
    }

    public function execute($data)
    {
        $items        = [];
        $commonValues = isset($data['common']) ? $data['common'] : [];
        foreach ($data['items'] as $reference => $values) {
            $construct = isset($values['__construct']) ? $values['__construct'] : [];
            $obj       = $this->createObject($data['class'], $construct);
            $this->bindValues($obj, $commonValues);
            $this->bindValues($obj, $values);
            if (!is_numeric($reference)) {
                $this->loader->setReference($reference, $obj);
            }
            $items[] = $obj;
        }

        return $items;
    }

    private function bindValues($obj, $values = null)
    {
        if (isset($values['__construct'])) {
            unset($values['__construct']);
        }
        foreach ($values as $kk => $vv) {
            $this->addValue($obj, $kk, $vv);
        }
    }

    private function createObject($class, $construct)
    {
        $class = new \ReflectionClass($class);
        $ret   = $class->newInstanceArgs($this->getValue($construct));

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
            if (isset($vv['@mode']) && $vv['@mode'] == 'recursive') {
                return $this->execute($vv);
            }
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
