<?php

declare(strict_types=1);

namespace durcap2011\DocxPDF\Placeholder;

/**
 * Classe astratta per i placeholder.
 */
abstract class AbstractPlaceholder implements PlaceholderInterface
{
    /**
     * @var string Nome del placeholder.
     */
    protected $name;

    /**
     * @var mixed Valore del placeholder.
     */
    protected $value;

    /**
     * Costruttore.
     *
     * @param string $name Nome del placeholder.
     * @param mixed $value Valore del placeholder.
     */
    public function __construct(string $name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Restituisce il valore del placeholder.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}