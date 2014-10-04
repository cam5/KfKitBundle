<?php

namespace Kf\KitBundle\Service;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Knp\Menu\Matcher\MatcherInterface;

class MenuBuilder
{
    private $factory;
    private $matcher;
    private $menu;

    /** @var  ContainerInterface */
    private $container;

    public function __construct(FactoryInterface $factory, MatcherInterface $matcher, ContainerInterface $container)
    {
        $this->factory = $factory;
        $this->matcher = $matcher;
        $this->container = $container;
    }

    public function createMenu($name, $data)
    {
        return $this->bindMenuItem(
            $name,
            $data
        );
    }

    protected function bindMenuItem($name, $data, ItemInterface $menu = null)
    {
        $options = isset($data['options']) ? $data['options'] : [];
        if (isset($options['filter'])) {
            $options = $this->filter($name, $options, $options['filter']);
            if (!$options) {
                return;
            }
        }

        $child = isset($menu) ?
            $menu->addChild($name, $options)
            : $this->factory->createItem($name, $options);

        if (isset($data['childrenMethod'])) {
            $this->childrenMethod($data['childrenMethod'], $child, $data);
        } elseif (isset($data['children'])) {
            foreach ($data['children'] as $k => $v) {
                $this->bindMenuItem($k, $v, $child);
            }
        }
        if ($menu) {
            $child->setCurrent($this->match($child));
        }

        return $child;
    }

    protected function filter($name, $data, $filter)
    {
        return $this->container->get($filter)->filter($name, $data);
    }

    protected function match($child)
    {
        $m = $this->matcher;

        return $m->isCurrent($child) || $m->isAncestor($child);
    }

    protected function childrenMethod($method, $child, $data)
    {
        $this->{"method$method"}($child, $data);
    }
}
