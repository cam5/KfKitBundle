<?php

namespace Kf\KitBundle\Symfony\Controller;

trait FlashAlertHelper
{
    /**
     * @param $type
     * @param $text
     *
     * @return $this
     */
    public function addAlert($type = 'success', $text = null)
    {
        if (!isset($text)) {
            $text = $this->getRequest()->get('_route') . '.' . $type;
        }
        /** @var Session $s */
        $s = $this->getRequest()->getSession();
        $s = $s->getFlashBag();
        $s->set('alert-' . $type, $text);

        return $this;
    }
}
