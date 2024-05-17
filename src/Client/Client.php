<?php

/**
 * Part of the AmazonGiftCode package.
 * Author: Kashyap Merai <kashyapk62@gmail.com>
 *
 */


namespace kamerk22\AmazonGiftCode\Client;

use kamerk22\AmazonGiftCode\Exceptions\AmazonErrors;
use Throwable;

class Client implements ClientInterface
{

    /**
     *
     * @param string $url The URL being requested, including domain and protocol
     * @param array $headers Headers to be used in the request
     * @param array $params Can be nested for arrays and hashes
     *
     *
     * @return String
     */
    public function request($url, $headers, $params): string
    {
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $params);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($handle);

        if ($result === false) {
            $err = curl_errno($handle);
            $message = curl_error($handle);
            $this->handleCurlError($url, $err, $message);
        }

        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            $err = curl_errno($handle);
            $message = $this->getResponseError($result, $httpCode);
            throw AmazonErrors::getError($message, $err);
        }

        return $result;

    }

    private function getResponseError(string $response, int $httpCode): string
    {
        $decoded = json_decode($response, true);

        if ($decoded && array_key_exists('message', $decoded)) {
            return 'AWS error: ' . $decoded['message'] . '(HTTP ' . $httpCode . ')';
        }

        return 'Unexpected HTTP code: ' . $httpCode . ' with response: ' . $response;
    }

    private function handleCurlError($url, $errno, $message): void
    {
        switch ($errno) {
            case CURLE_COULDNT_CONNECT:
            case CURLE_COULDNT_RESOLVE_HOST:
            case CURLE_OPERATION_TIMEOUTED:
                $msg = "Could not connect to AWS ($url).  Please check your "
                    . 'internet connection and try again.';
                break;
            case CURLE_SSL_CACERT:
            case CURLE_SSL_PEER_CERTIFICATE:
                $msg = "Could not verify AWS SSL certificate";
                break;
            case 0:
            default:
                $msg = 'Unexpected error communicating with AWS. ' . $message;
        }

        throw new \RuntimeException($msg);
    }
}