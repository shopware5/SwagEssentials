<?php declare(strict_types=1);

namespace SwagEssentials\CacheMultiplexer\Api;

class Response
{
    protected $rawBody;
    protected $body;
    protected $code;
    protected $errorMessage;
    protected $success = false;

    public function __construct($body, $curlHandle)
    {
        $this->rawBody = $body;

        if ($body === false) {
            $this->errorMessage = curl_error($curlHandle);
            return;
        }

        $this->code = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);

        if (null === $decodedResult = json_decode($this->rawBody, true)) {
            $jsonErrors = [
                JSON_ERROR_NONE => 'No error has occurred.',
                JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded.',
                JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded.',
                JSON_ERROR_SYNTAX => 'Syntax error.',
            ];

            $jsonError = $jsonErrors[json_last_error()];
            $rawErrorBody = print_r($body, true);

            $this->errorMessage = <<<error
<h2>Could not decode json</h2>
json_last_error: $jsonError;
<br>Raw:<br>
<pre>$rawErrorBody"</pre>";
error;
            return;
        }

        if (!isset($decodedResult['success'])) {
            $this->errorMessage = 'Could not parse Response';
            return;
        }

        if (!$decodedResult['success']) {
            $this->errorMessage = $decodedResult['message'];
            return;
        }

        $this->success = true;
        $this->body = @$decodedResult['data'];
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getRawBody()
    {
        return $this->rawBody;
    }

    public function getResult()
    {
        return $this->body;
    }

    public function isSuccess()
    {
        return ($this->success === true);
    }
}
