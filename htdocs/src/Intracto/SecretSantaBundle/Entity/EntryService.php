<?php

namespace Intracto\SecretSantaBundle\Entity;

use JMS\DiExtraBundle\Annotation as DI;
use Intracto\SecretSantaBundle\Entity\Pool;

/**
 * @DI\Service("intracto_secret_santa.entry_service")
 */
class EntryService
{
    /**
     * @DI\Inject("mailer")
     */
    public $mailer;

    /**
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    public $em;

    /**
     * @DI\Inject("templating")
     */
    public $templating;

    /**
     * @DI\Inject("%admin_email%")
     */
    public $adminEmail;

    /**
     * Shuffles all entries for pool and save result to each entry
     *
     * @param Pool $pool
     *
     * @return boolean
     */
    public function shuffleEntries(Pool $pool)
    {
        $entries = $pool->getEntries()->getValues();

        shuffle($entries);

        foreach ($entries as $index => $entry) {
            if ($index === count($entries) - 1) {
                $peer = $entries[0];
            } else {
                $peer = $entries[$index + 1];
            }

            $entry
                ->setEntry($peer)
                ->setUrl(base_convert(sha1(uniqid(mt_rand(), true)), 16, 36))
            ;

            $this->em->persist($entry);
        }

        $this->em->flush();
    }

    /**
     * Sends out all mails for a Pool
     *
     * @param Pool $pool
     *
     * @return boolean
     */
    public function sendSecretSantaMailsForPool(Pool $pool)
    {
        $pool->setSentdate(new \DateTime("now"));
        $this->em->flush($pool);

        foreach ($pool->getEntries() as $entry) {
            $message = str_replace('(NAME)', $entry->getName(), $pool->getMessage());
            $txtBody = $this->templating->render(
                'IntractoSecretSantaBundle:Emails:secretsanta.txt.twig',
                array('pool' => $pool, 'entry' => $entry)
            );
            $htmlBody = $this->templating->render(
                'IntractoSecretSantaBundle:Emails:secretsanta.html.twig',
                array('pool' => $pool, 'entry' => $entry)
            );

            $mail = \Swift_Message::newInstance()
                ->setSubject('Your SecretSanta')
                ->setFrom($this->adminEmail, 'Santa')
                ->setTo($entry->getEmail(), $entry->getName())
                ->setBody($txtBody)
                ->addPart($htmlBody, 'text/html');
            $this->mailer->send($mail);
        }
    }
}