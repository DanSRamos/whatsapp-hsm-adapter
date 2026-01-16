<?php

namespace App\Module\Issue\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This is a dummy entity. Remove it!
 *
 * @ORM\Entity
 */
class Issue
{
    /**
     * @var int The entity Id
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var string Name of issue
     *
     * @ORM\Column
     *
     * @Assert\NotBlank
     *
     * @Assert\Length(
     *      min = 2,
     *      max = 10,
     *      minMessage = "Name must be at least {{ limit }} characters long",
     *      maxMessage = "Name cannot be longer than {{ limit }} characters"
     * )
     */
    protected $name = '';

    /**
     * Get the entity Id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the entity Id.
     *
     * @param int $id The entity Id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get a nice person.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set a nice person.
     *
     * @param string $name A nice person
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
