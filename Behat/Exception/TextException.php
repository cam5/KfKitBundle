<?php

namespace Kf\KitBundle\Behat\Exception;

/*
 * This file is part of the Behat\Mink.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Mink response's text exception.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class TextException extends \Exception
{
    private $text;

    /**
     * Initializes exception.
     *
     * @param string     $message   optional message
     * @param text    $text   session instance
     * @param \Exception $exception expectation exception
     */
    public function __construct($message = null, $text, \Exception $exception = null)
    {
        $this->text = $text;
        parent::__construct($message ?: $exception->getMessage());
    }


    /**
     * Returns exception message with additional context info.
     *
     * @return string
     */
    public function __toString()
    {
        try {
            $string   = sprintf("%s\n\n%s",
                $this->getMessage(),
                $this->text
            );
        } catch (\Exception $e) {
            return $this->getMessage();
        }

        return $string;
    }
}
