<?php

namespace KF\KitBundle\Symfony;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Kf\KitBundle\Symfony\Controller as Helpers;

class Controller extends BaseController
{
    use Helpers\DoctrineORMHelper;
    use Helpers\FlashAlertHelper;
    use Helpers\RequestHelper;
}

