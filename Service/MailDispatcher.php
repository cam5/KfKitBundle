<?php

namespace Kf\KitBundle\Service;

use JMS\DiExtraBundle\Annotation as DI;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\Templating\EngineInterface;

/**
 * easy mail dispatching
 */
class MailDispatcher
{

    protected $templating;
    protected $mailer;
    protected $enabled = true;

    public function __construct(EngineInterface $templating, Swift_Mailer $mailer)
    {
        $this->templating = $templating;
        $this->mailer     = $mailer;
    }

    /**
     * @param       $view
     * @param array $parameters
     * @param array $settings
     *
     * @return $this
     */
    public function send($view, $parameters = [], $settings = [])
    {
        if (!$this->enabled) {
            return null;
        }

        $message = $this->createMessage($view, $parameters, $settings);
        $this->mailer->send($message);

        return $this;
    }

    /**
     * @param       $view
     * @param array $parameters
     * @param array $settings
     *
     * @return Swift_Message
     */
    public function createMessage($view, $parameters = [], $settings = [])
    {
        $settings = $this->processSettings($view, $parameters, $settings);
        /** @var Swift_Message $message */
        $message = \Swift_Message::newInstance()
            ->setSubject($settings['subject'])
            ->setFrom($settings['from'])
            ->setTo($settings['to']);
        if (isset($settings['bcc'])) {
            $message->setBcc($settings['bcc']);
        }
        if (isset($settings['cc'])) {
            $message->setCc($settings);
        }
        $this->bindBody(
            $message,
            $this->renderView($view, $parameters, 'text/html'),
            $this->renderView($view, $parameters, 'text/plain')
        );

        return $message;
    }

    protected function processSettings($view, $parameters, $settings)
    {
        $viewSettings = (array)json_decode(
            $this->renderView($view, $parameters, 'settings')
        );
        if (isset($settings['override']) && $settings['override']) {
            return array_merge($viewSettings, $settings);
        } else {
            return array_merge($settings, $viewSettings);
        }
    }

    protected function renderView($view, $parameters, $mail_mode)
    {
        return $this->templating->render(
            $view,
            array_merge($parameters, compact('mail_mode'))
        );
    }

    protected function bindBody($message, $bodyHTML, $bodyTXT)
    {
        $bodyHTML = trim($bodyHTML);
        $bodyTXT  = trim($bodyTXT);
        if (empty($bodyHTML) && !empty($bodyTXT)) {
            $message->setBody($bodyTXT, 'text/plain');
        } elseif (empty($bodyTXT) && !empty($bodyHTML)) {
            $message->setBody($bodyHTML, 'text/html');
        } else {
            $message->setBody($bodyTXT, 'text/plain');
            $message->addPart($bodyHTML, 'text/html');
        }
    }

    public function setEnabled($boolean)
    {
        $this->enabled = $boolean;
    }
}