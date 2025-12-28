<?php /** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace App\Entity\View;

use App\Entity\Election;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

final class BuffedElection
{
    private Election $election;

    private Collection $voteTallies;

    public function __construct(Election $election)
    {
        $this->election = $election;
        $this->voteTallies = new ArrayCollection();
    }

    /**
     * @return Election
     */
    public function getElection(): Election
    {
        return $this->election;
    }

    /**
     * @param Election $election
     */
    public function setElection(Election $election): void
    {
        $this->election = $election;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getVoteTallies(): ArrayCollection|Collection
    {
        return $this->voteTallies;
    }

    /**
     * @param array $voteTallies
     */
    public function setVoteTallies(array $voteTallies): void
    {
        $this->voteTallies->clear();
        foreach ($voteTallies as $voteTally) {
            $this->addVoteTally($voteTally);
        }
    }

    /**
     * @param VoteTally $voteTally
     */
    public function addVoteTally(VoteTally $voteTally): void
    {
        if (!$this->voteTallies->containsKey($voteTally->getId())) {
            $this->voteTallies->set($voteTally->getId(), $voteTally);
        }
    }

}
