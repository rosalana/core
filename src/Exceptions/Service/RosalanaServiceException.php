<?php

namespace Rosalana\Core\Exceptions\Service;

class RosalanaServiceException extends \Exception
{
    /**
     * Zatím jenom ukázka
     * Generické exception pro jakoukoli chybu v Rosalana Services.
     * Pro každou konkrétní service by bylo dobré vytvořit vlastní vyjímku,
     * 
     * Může obsahovat: 
     * - `getServiceName()` || `getService()` ...
     * 
     * služby jako
     * - BasecampUnavailableException
     * - ContextSyncException
     * - AppInitializationException
     * - HookExecutionException
     * - ServiceCommunicationException
     * - ServiceNotFoundException
     * - atd...
     */


    protected $message = 'An error occurred in the Rosalana service.';
    protected $code = 500;

    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        if (!empty($message)) {
            $this->message = $message;
        }
        if ($code > 0) {
            $this->code = $code;
        }

        parent::__construct($this->message, $this->code, $previous);
    }
}