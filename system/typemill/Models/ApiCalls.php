<?php

namespace Typemill\Models;

class ApiCalls
{
    private $error = null;

    private $timeout = 5;

    public function getError()
    {
        return $this->error;
    }

    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;
    }

    public function makePostCall(string $url, array $data, $authHeader = '')
    {
        if (in_array('curl', get_loaded_extensions())) 
        {
            return $this->makeCurlCall($url, 'POST', $data, $authHeader);
        }

        return $this->makeFileGetContentsCall($url, 'POST', $data, $authHeader);
    }

    public function makeGetCall($url, $authHeader = '')
    {
        if (in_array('curl', get_loaded_extensions()))
        {
            return $this->makeCurlCall($url, 'GET', null, $authHeader);
        }

        return $this->makeFileGetContentsCall($url, 'GET', null, $authHeader);
    }

    private function makeCurlCall($url, $method, $data = false, $customHeaders = '')
    {
        $this->error = null;

        $headers = [
            "Content-Type: application/json",
        ];

        $headers = $this->addCustomHeaders($headers, $customHeaders);

        $curl = curl_init($url);
        if (defined('CURLSSLOPT_NATIVE_CA') && version_compare(curl_version()['version'], '7.71', '>='))
        {
            curl_setopt($curl, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);  
        }        
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        if ($method === 'POST' && $data)
        {
            $postdata = json_encode($data);
            if ($postdata === false)
            {
                $this->error = "JSON encoding error: " . json_last_error_msg();
                return false;
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
            curl_setopt($curl, CURLOPT_POST, true);
        }
#        curl_setopt($curl, CURLOPT_FAILONERROR, true);

        $response = curl_exec($curl);

        if ($response === false)
        {
            $this->error = curl_error($curl);
            curl_close($curl);
            return false;
        }

        curl_close($curl);
        return $response;
    }

    private function makeFileGetContentsCall($url, $method, $data = null, $customHeaders = '')
    {
        $this->error = null;

        $headers = [
            "Content-Type: application/json"
        ];

        $headers = $this->addCustomHeaders($headers, $customHeaders);

        $options = [
            'http' => [
                'method'  => $method,
                'ignore_errors' => true,
                'header'  => implode("\r\n", $headers),
            ]
        ];

        if ($method === 'POST' && $data !== null)
        {
            $postdata = json_encode($data);
            if ($postdata === false)
            {
                $this->error = "JSON encoding error: " . json_last_error_msg();
                return false;
            }
            $options['http']['content'] = $postdata;
        }

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        if ($response === false)
        {
            if (!empty($http_response_header) && isset($http_response_header[0]))
            {
                $parts          = explode(' ', $http_response_header[0], 3);
                $status_code    = $parts[1] ?? 'Unknown';
                $msg            = $parts[2] ?? 'No status message';

                $this->error = Translations::translate('We got an error from file_get_contents: ') . $status_code . ' ' . $msg;
            }
            else
            {
                $this->error = Translations::translate('No HTTP response received or file_get_contents is blocked.');
            }

            return false;
        }

        return $response;
    }

    private function addCustomHeaders($headers, $customHeaders = '')
    {
        if(!is_array($headers))
        {
            return false;
        }

        if(!empty($customHeaders))
        {
            if(is_array($customHeaders))
            {
                foreach($customHeaders as $cHeader)
                {
                    $headers[] = $cHeader;
                }
            }
            elseif(is_string($customHeaders))
            {
                $headers[] = $customHeaders;
            }
        }

        return $headers;
    }
}