<?php

namespace Kf\KitBundle\Behat;

use Kf\KitBundle\Behat\Exception\TextException;

/**
 * Provides some steps/methods which are useful for testing a Symfony2 application.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class SymfonyMailerContext extends DefaultContext
{

    /** @var  \Swift_Message */
    protected $mail;

    /**
     * @Then no email should have been sent$/
     */
    public function noEmailShouldHaveBeenSent()
    {
        if (0 < $count = $this->loadProfile()->getCollector('swiftmailer')->getMessageCount()) {
            throw new \RuntimeException(sprintf('Expected no email to be sent, but %d emails were sent.', $count));
        }
    }

    /**
     * @Then /^email with subject "([^"]*)" should have been sent(?: to "([^"]+)")?$/
     */
    public function emailWithSubjectShouldHaveBeenSent($subject, $to = null)
    {
        $mailer = $this->loadProfile()->getCollector('swiftmailer');
        if (0 === $mailer->getMessageCount()) {
            throw new \RuntimeException('No emails have been sent.');
        }

        $foundToAddresses = null;
        $foundSubjects = array();
        foreach ($mailer->getMessages('default') as $message) {
            $foundSubjects[] = $message->getSubject();

            if (trim($subject) === trim($message->getSubject())) {
                $foundToAddresses = implode(', ', array_keys($message->getTo()));

                if (null !== $to) {
                    $toAddresses = $message->getTo();
                    if (array_key_exists($to, $toAddresses)) {
                        // found, and to address matches
                        return;
                    }

                    // check next message
                    continue;
                } else {
                    // found, and to email isn't checked
                    return;
                }

                // found
                return;
            }
        }

        if (!$foundToAddresses) {
            if (!empty($foundSubjects)) {
                throw new \RuntimeException(sprintf('Subject "%s" was not found, but only these subjects: "%s"', $subject, implode('", "', $foundSubjects)));
            }

            // not found
            throw new \RuntimeException(sprintf('No message with subject "%s" found.', $subject));
        }

        throw new \RuntimeException(sprintf('Subject found, but "%s" is not among to-addresses: %s', $to, $foundToAddresses));
    }

    /**
     * Loads the profiler's profile.
     *
     * If no token has been given, the debug token of the last request will
     * be used.
     *
     * @param string $token
     * @return \Symfony\Component\HttpKernel\Profiler\Profile
     * @throws \RuntimeException
     */
    public function loadProfile($token = null)
    {
        if (null === $token) {
            $headers = $this->getSession()->getResponseHeaders();

            if (!isset($headers['X-Debug-Token']) && !isset($headers['x-debug-token'])) {
                throw new \RuntimeException('Debug-Token not found in response headers. Have you turned on the debug flag?');
            }
            $token = isset($headers['X-Debug-Token']) ? $headers['X-Debug-Token'] : $headers['x-debug-token'];
            if (is_array($token)) {
                $token = end($token);
            }
        }

        return $this->kernel->getContainer()->get('profiler')->loadProfile($token);
    }

    /**
     * @return  \Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector
     */
    protected function getMessagesCollector()
    {
        return $this->loadProfile()->getCollector('swiftmailer');
    }

    /**
     * @Then /^I save the mail sent(?: to "([^"]+)")?(?: as "([^"]+)")?$/
     */
    public function iSaveTheMailSent($to = null, $as = 'default')
    {
        $coll = $this->getMessagesCollector();
        if (0 === $coll->getMessageCount()) {
            throw new \RuntimeException('No emails have been sent.');
        }

        $foundToAddresses = [];
        foreach ($coll->getMessages('default') as $message) {
            if ($to) {
                $toAddresses = $message->getTo();
                if (array_key_exists($to, $toAddresses)) {
                    $this->mail[$as] = $message;
                    return;
                } else {
                    $foundToAddresses = array_merge($foundToAddresses,$toAddresses);
                }
            } else {
                $this->mail[$as] = $message;
                return;
            }
        }
        throw new \RuntimeException(sprintf('"%s" is not among to-addresses: %s', $to, implode(',', array_keys($foundToAddresses))));
    }

    /**
     * @When /^I follow the link with route "([^"]+)" in mail(?: called "([^"]+)")?$/
     */
    public function iFollowTheLinkWithRouteInMail($route, $called = 'default')
    {
        preg_match_all('|http://([^\s]*)|', $this->mail[$called]->getBody(), $matches);
        $matches = $matches[0];
        $router  = $this->getContainer()->get('router');
        $found   = [];
        foreach ($matches as $url) {
            $path = preg_replace("/app_(.*).php\//", "", parse_url($url, PHP_URL_PATH));
            try {
                $match = $router->match($path);
                $check = $match['_route'];
                
                if ($check == $route) {
                    $this->getSession()->visit($url);
                    return;
                } else {
                    $found[] = $check;
                }
            } catch (ResourceNotFoundException $e) {
                //just not found
            }
        }

        throw new \RuntimeException(sprintf(
            'route "%s" not found, I\'ve found: %s',
            $route,
            implode(', ', $found)
        ));
    }

    /**
     * @When /^I click "([^"]+)" in mail(?: called "([^"]+)")?$/
     */
    public function iClickInMail($text, $called = 'default')
    {
        $doc = new \DOMDocument;
        $doc->loadhtml($this->mail[$called]->getBody());
        $xpath = new \DOMXPath($doc);
        foreach( $xpath->query('//a') as $a ) {
            $links[trim(strip_tags($a->nodeValue))] = $a->getAttribute('href');
        }
        if(isset($links[$text])){
            $this->getSession()->visit($links[$text]);
        }else{
            throw new \RuntimeException(sprintf(
               'link "%s" not found, I\'ve found: %s',
               $text,
               implode(', ', array_keys($links))
           ));
        }
    }


    /**
     * Checks, that page contains specified text.
     *
     * @Then /^(?:|I )should see "(?P<text>(?:[^"]|\\")*)" in mail(?: called "([^"]+)")?$/
     */
    public function assertMailContainsText($text, $called = 'default')
    {
        $this->textContains($this->fixStepArgument($text), $this->mail[$called]->getBody());
    }

    /**
     * Checks, that page doesn't contain specified text.
     *
     * @Then /^(?:|I )should not see "(?P<text>(?:[^"]|\\")*)" in mail(?: called "([^"]+)")?$/
     */
    public function assertMailNotContainsText($text, $called = 'default')
    {
        $this->textNotContains($this->fixStepArgument($text), $this->mail[$called]->getBody());
    }

    /**
     * Checks, that page contains text matching specified pattern.
     *
     * @Then /^(?:|I )should see text matching (?P<pattern>"(?:[^"]|\\")*") in mail(?: called "([^"]+)")?$/
     */
    public function assertMailMatchesText($pattern, $called = 'default')
    {
        $this->textMatches($this->fixStepArgument($pattern), $this->mail[$called]->getBody());
    }

    /**
     * Checks, that page doesn't contain text matching specified pattern.
     *
     * @Then /^(?:|I )should not see text matching (?P<pattern>"(?:[^"]|\\")*") in mail(?: called "([^"]+)")?$/
     */
    public function assertMailNotMatchesText($pattern, $called = 'default')
    {
        $this->textNotMatches($this->fixStepArgument($pattern), $this->mail[$called]->getBody());
    }



    /**
     * Checks that current page contains text.
     *
     * @param string $text
     *
     * @throws ResponseTextException
     */
    public function textContains($text, $actual)
    {
        $actual = preg_replace('/\s+/u', ' ', $actual);
        $regex  = '/'.preg_quote($text, '/').'/ui';

        if (!preg_match($regex, $actual)) {
            $message = sprintf('The text "%s" was not found anywhere in the text of the current page.', $text);
            throw new TextException($message, $actual);
        }
    }

    /**
     * Checks that current page does not contains text.
     *
     * @param string $text
     *
     * @throws ResponseTextException
     */
    public function textNotContains($text, $actual)
    {
        $actual = preg_replace('/\s+/u', ' ', $actual);
        $regex  = '/'.preg_quote($text, '/').'/ui';

        if (preg_match($regex, $actual)) {
            $message = sprintf('The text "%s" appears in the text of this page, but it should not.', $text);
            throw new TextException($message, $actual);
        }
    }
    /**
     * Checks that current page text matches regex.
     *
     * @param string $regex
     *
     * @throws ResponseTextException
     */
    public function textMatches($regex, $actual)
    {

        if (!preg_match($regex, $actual)) {
            $message = sprintf('The pattern %s was not found anywhere in the text of the current page.', $regex);
            throw new TextException($message, $actual);
        }
    }

    /**
     * Checks that current page text does not matches regex.
     *
     * @param string $regex
     *
     * @throws ResponseTextException
     */
    public function textNotMatches($regex, $actual)
    {
        if (preg_match($regex, $actual)) {
            $message = sprintf('The pattern %s was found in the text of the current page, but it should not.', $regex);
            throw new TextException($message, $actual);
        }
    }

}
