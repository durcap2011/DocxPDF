<?php

declare(strict_types=1);

namespace durcap2011\DocxPDF\Placeholder;

/**
 * Interfaccia per i tipi di placeholder.
 */
interface PlaceholderInterface
{
    /**
     * Restituisce il nome del placeholder.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Restituisce il tipo del placeholder.
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Restituisce il valore del placeholder come stringa XML per Word.
     *
     * @return string
     */
    public function toXmlString(): string;

    /**
     * Restituisce il valore del placeholder come HTML.
     *
     * @return string
     */
    public function toHtmlString(): string;
}