<?php

namespace Kf\KitBundle\Service;

use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Knp\Component\Pager\Paginator;
use Symfony\Component\HttpFoundation\Request;

class PaginationBuilder
{
    protected $routePagedSuffix = '.paged';
    protected $defaultItemsPerPage = '30';

    protected $target;
    protected $paginator;
    protected $request;
    protected $route;

    public function __construct(Paginator $paginator, Request $request)
    {
        $this->setPaginator($paginator);
        $this->setRequest($request);
    }

    /**
     * @param Request            $req
     * @param                    $target
     *
     * @return SlidingPagination
     */
    public function createPagination($target)
    {
        $req = $this->request;
        $path   = $this->evalRoute($req->get('_route'));
        $routes = $req->get('_route_params');

        $page           = $routes['page'];
        $items_per_page = isset($routes['items_per_page']) ?
            $routes['items_per_page']
            : $this->defaultItemsPerPage;

        return $this->setRoute($path)
            ->setTarget($target)
            ->getPagination($page, $items_per_page);
    }

    protected function evalRoute($route)
    {

        return self::endsWith($route, $this->routePagedSuffix) ?
            $route //substr($route, 0, strlen($route) - strlen($suffix))
            : $route . $this->routePagedSuffix;
    }

    private function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    /**
     * @param $route
     *
     * @return $this
     */
    public function setRoute($route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * @param Paginator $paginator
     *
     * @return $this
     */
    public function setPaginator(Paginator $paginator)
    {
        $this->paginator = $paginator;

        return $this;
    }

    /**
     * @param $page
     * @param $itemsPerPage
     *
     * @return SlidingPagination
     */
    public function getPagination($page, $itemsPerPage)
    {
        /** @var SlidingPagination $pagination */
        $pagination = $this->paginator->paginate($this->getTarget(), $page, $itemsPerPage);
        if ($this->route) {
            $pagination->setUsedRoute($this->route);
        }

        return $pagination;
    }

    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param $target
     *
     * @return $this
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @param string $defaultItemsPerPage
     */
    public function setDefaultItemsPerPage($defaultItemsPerPage)
    {
        $this->defaultItemsPerPage = $defaultItemsPerPage;
    }

    /**
     * @return string
     */
    public function getDefaultItemsPerPage()
    {
        return $this->defaultItemsPerPage;
    }

    /**
     * @param string $routePagedSuffix
     */
    public function setRoutePagedSuffix($routePagedSuffix)
    {
        $this->routePagedSuffix = $routePagedSuffix;
    }

    /**
     * @return string
     */
    public function getRoutePagedSuffix()
    {
        return $this->routePagedSuffix;
    }

    /**
     * @param mixed $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }
}