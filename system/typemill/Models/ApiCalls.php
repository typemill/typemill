<?php

namespace Typemill\Models;

class ApiCalls
{
    private $error = null;

    public function getError()
    {
        return $this->error;
    }

    public function makePostCall(string $url, array $data, $authHeader = '')
    {
        if (in_array('curl', get_loaded_extensions())) {
            return $this->makeCurlCall($url, 'POST', $data, $authHeader);
        }

        return $this->makeFileGetContentsCall($url, 'POST', $data, $authHeader);
    }

    public function makeGetCall($url, $authHeader = '')
    {
        if (in_array('curl', get_loaded_extensions())) {
            return $this->makeCurlCall($url, 'GET', null, $authHeader);
        }

        return $this->makeFileGetContentsCall($url, 'GET', null, $authHeader);
    }

    private function makeCurlCall($url, $method, $data = false, $authHeader = '')
    {
        $this->error = null;

        $headers = [
            "Content-Type: application/json",
        ];

        if (!empty($authHeader)) {
            $headers[] = $authHeader;
        }

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        if ($method === 'POST' && $data) {
            $postdata = json_encode($data);
            if ($postdata === false) {
                $this->error = "JSON encoding error: " . json_last_error_msg();
                return false;
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
            curl_setopt($curl, CURLOPT_POST, true);
        }
        curl_setopt($curl, CURLOPT_FAILONERROR, true);

        $response = curl_exec($curl);

        if ($response === false) {
            $this->error = curl_error($curl);
        }
        curl_close($curl);

        return $response !== false ? $response : false;
    }

    private function makeFileGetContentsCall($url, $method, $data = null, $authHeader = '')
    {
        $this->error = null;

        $headers = [
            "Content-Type: application/json"
        ];

        if (!empty($authHeader)) {
            $headers[] = $authHeader;
        }

        $options = [
            'http' => [
                'method'  => $method,
                'ignore_errors' => true,
                'header'  => implode("\r\n", $headers),
            ]
        ];

        if ($method === 'POST' && $data !== null) {
            $postdata = json_encode($data);
            if ($postdata === false) {
                $this->error = "JSON encoding error: " . json_last_error_msg();
                return false;
            }
            $options['http']['content'] = $postdata;
        }

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            $this->error = 'file_get_contents failed for ' . $method . ' request.';
        }

        return $response !== false ? $response : false;
    }
}
