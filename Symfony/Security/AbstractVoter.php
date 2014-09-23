<?php

namespace Kf\KitBundle\Symfony\Security;

use Symfony\Component\Security\Core\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

abstract class AbstractVoter implements VoterInterface
{
    const SUPPORTED_CLASS = '';

    const VIEW   = 'view';
    const EDIT   = 'edit';
    const DELETE = 'delete';

    static $attributes = array(self::VIEW, self::EDIT, self::DELETE);
    private $user;
    private $entity;

    abstract function dispatch($attribute);

    public function vote(TokenInterface $token, $entity, array $attributes)
    {
        if (!$this->supportsClass(get_class($entity))) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        $attribute = $this->processAttributes($attributes);
        if (!$this->supportsAttribute($attribute)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }
        $this->user   = $token->getUser();
        $this->entity = $entity;

        return $this->dispatch($attribute);
    }

    public function supportsAttribute($attribute)
    {
        return in_array(
            $attribute,
            static::$attributes
        );
    }

    public function supportsClass($class)
    {
        return static::SUPPORTED_CLASS === $class || is_subclass_of($class, static::SUPPORTED_CLASS);
    }


    private function processAttributes($attributes)
    {
        if (1 !== count($attributes)) {
            throw new InvalidArgumentException(
                'It\'s allowed only one attribute for VIEW or EDIT'
            );
        }

        return $attributes[0];
    }

    protected function getUser()
    {
        return $this->user;
    }

    /**
     * @return mixed
     */
    protected function getEntity()
    {
        return $this->entity;
    }

    protected function checkIf($check)
    {
        return $check ? self::ACCESS_GRANTED : self::ACCESS_DENIED;
    }
}
