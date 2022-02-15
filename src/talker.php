<?php

namespace ltajniaa\FinvuCommunicator;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request;
use Ramsey\Uuid\Uuid;


class Talker
{
    private $account;
    private $token;
    private $baseUri;

    function __construct($account)
    {
        $this->account = $account;
        $this->baseUri = "https://" . $account . ".fiu.finfactor.in/finsense/API/V1/";
    }

    function setAccessToken($token)
    {
        $this->token = $token;
    }

    function getAccessToken($login, $pass)
    {
        $rtStatus = "success";
        $rtStore = [];
        $rtApiCallData = [];

        if (!$this->token) {
            $client = new Client(['base_uri' => $this->baseUri, 'timeout'  => 2.0,]);
            $data = [
                'header' => ["rid" => Uuid::uuid4()->toString(), "ts" =>  date(\DateTime::ISO8601), "channelId" => "finsense"],
                'body' => ["userId" => $login, "password" => $pass]
            ];
            $rtApiCallData['endpoint'] =  $this->baseUri . 'User/Login';
            $rtApiCallData['request'] = $data;

            $code = 0;
            try {
                $response = $client->request('POST', 'User/Login', ['http_errors' => false, 'body' => json_encode($data)]);
                $code = $response->getStatusCode();
            } catch (\Exception $e) {
                $rtStatus = "error";                
            }

            $rtApiCallData['status_code'] = $code;

            if ($code == 200) {
                try {
                    $contents  = $response->getBody()->getContents();
                    $rtApiCallData['response'] = $contents;

                    $res = (object)json_decode($contents);

                    if (!$res->body->token) {
                        throw new \Exception('invalid data');
                    }


                    $this->token =  $res->body->token;
                    $rtStore['token'] = $this->token;
                } catch (\Exception $e) {
                    $rtStatus = "failure";
                }
            } else if ($code == 0) {
                $rtStatus = "error";
            } else {
                $rtStatus = "failure";
                try {
                    $contents  = $response->getBody()->getContents();
                    $rtApiCallData['response'] = $contents;
                } catch (\Exception $e) {
                    $rtStatus = "failure";
                }
            }
        } else {
            $rtStore['token'] = $this->token;
        }

        $responseJSON = ['status' =>  $rtStatus, 'store' => $rtStore, 'apiCallData' => $rtApiCallData];

        return json_encode($responseJSON);
    }

    function raiseConsentRequest($custId, $consentDescription, $templateName, $userSessionId)
    {
        $rtStatus = "success";
        $rtStore = [];
        $rtApiCallData = [];

        $client = new Client(['base_uri' => $this->baseUri, 'timeout'  => 2.0]);
        $data = [
            'header' => ["rid" => Uuid::uuid4()->toString(), "ts" =>  date(\DateTime::ISO8601), "channelId" => "finsense"],
            'body' => ["custId" => $custId, "consentDescription" => $consentDescription, "templateName" => $templateName, "userSessionId" => $userSessionId]
        ];

        $rtApiCallData['endpoint'] =  $this->baseUri . 'ConsentRequestEncrypt';
        $rtApiCallData['request'] = $data;

        $code = 0;
        try {
            $response = $client->request('POST', 'ConsentRequestEncrypt', ['http_errors' => false, 'headers' => ['Authorization' => 'Bearer: ' . $this->token, 'content-type' => 'application/json'], 'body' => json_encode($data)]);
            $code = $response->getStatusCode();
        } catch (\Exception $e) {
            $rtStatus = "error";
        }

        $rtApiCallData['status_code'] = $code;

        if ($code == 200) {
            try {
                $contents = $response->getBody()->getContents();
                $rtApiCallData['response'] = $contents;

                $res = (object)json_decode($contents);

                if (!$res->body->encryptedRequest) {
                    throw new \Exception('invalid data');
                }

                $outputText = '<script src="https://finvu.in/sdk/dist/finvu-aa.js"></script>
                <script>
                var ecreq = "' . $res->body->encryptedRequest . '"; var reqdate = "' . $res->body->requestDate . '";var fi = "' . $res->body->encryptedFiuId . '";
                function launchAA(event){
                    let aa = new FinvuAA();
                    aa.open(ecreq, reqdate, fi, function (response){
                    //alert(response.data.status);
                    });
                    
                }
                </script>
                <input type="button" onclick="javascript:launchAA()" value="Share via AA">';

                $rtStore['JavaScriptForPage'] = $outputText;

                $rtStore['ConsentHandle'] = $res->body->ConsentHandle;
                $rtStore['encryptedRequest'] = $res->body->encryptedRequest;
                $rtStore['requestDate'] = $res->body->requestDate;
                $rtStore['encryptedFiuId'] = $res->body->encryptedFiuId;
            } catch (\Exception $e) {
                $rtStatus = "failure";
            }
        } else if ($code == 0) {
            $rtStatus = "error";
        } else {
            $rtStatus = "failure";
            try {
                $contents  = $response->getBody()->getContents();
                $rtApiCallData['response'] = $contents;
            } catch (\Exception $e) {
                $rtStatus = "failure";
            }
        }

        $responseJSON = ['status' =>  $rtStatus, 'store' => $rtStore, 'apiCallData' => $rtApiCallData];
        return json_encode($responseJSON);
    }

    function checkConsentStatus($custId, $consentHandleId)
    {

        $rtStatus = "success";
        $rtStore = [];
        $rtApiCallData = [];

        $client = new Client(['base_uri' => $this->baseUri, 'timeout'  => 2.0]);
        $code = 0;

        $rtApiCallData['endpoint'] =  $this->baseUri . 'ConsentStatus/' . $consentHandleId . '/' . $custId;
        $rtApiCallData['request'] = "";
        try {
            $response = $client->request('GET', 'ConsentStatus/' . $consentHandleId . '/' . $custId, ['http_errors' => false, 'headers' => ['Authorization' => 'Bearer: ' . $this->token, 'content-type' => 'application/json']]);
            $code = $response->getStatusCode();
        } catch (\Exception $e) {
            $rtStatus = "error";
        }

        $rtApiCallData['status_code'] = $code;

        if ($code == 200) {
            try {
                $contents = $response->getBody()->getContents();
                $rtApiCallData['response'] = $contents;

                $res = (object)json_decode($contents);

                if (!$res->body->consentStatus) {
                    throw new \Exception('invalid data');
                }

                $rtStore['consentStatus'] = $res->body->consentStatus;
                $rtStore['consentId'] = $res->body->consentId;
            } catch (\Exception $e) {
                $rtStatus = "failure";
            }
        } else if ($code == 0) {
            $rtStatus = "error";
        } else {
            $rtStatus = "failure";
            try {
                $contents  = $response->getBody()->getContents();
                $rtApiCallData['response'] = $contents;
            } catch (\Exception $e) {
                $rtStatus = "failure";
            }
        }

        $responseJSON = ['status' =>  $rtStatus, 'store' => $rtStore, 'apiCallData' => $rtApiCallData];
        return json_encode($responseJSON);
    }

    function triggerDataRequest($custId, $consentId, $consentHandleId, $dateTimeRangeFrom, $dateTimeRangeTo)
    {
        $rtStatus = "success";
        $rtStore = [];
        $rtApiCallData = [];

        $client = new Client(['base_uri' => $this->baseUri, 'timeout'  => 2.0]);
        $data = [
            'header' => ["rid" => Uuid::uuid4()->toString(), "ts" =>  date(\DateTime::ISO8601), "channelId" => "finsense"],
            'body' => ["custId" => $custId, "consentId" => $consentId, "consentHandleId" => $consentHandleId, "dateTimeRangeFrom" => $dateTimeRangeFrom, 'dateTimeRangeTo' => $dateTimeRangeTo]
        ];

        $code = 0;

        $rtApiCallData['endpoint'] =  $this->baseUri . 'FIRequest';
        $rtApiCallData['request'] = $data;

        try {
            $response = $client->request('POST', 'FIRequest', ['http_errors' => false, 'headers' => ['Authorization' => 'Bearer: ' . $this->token, 'content-type' => 'application/json'], 'body' => json_encode($data)]);
            $code = $response->getStatusCode();
        } catch (\Exception $e) {
            $rtStatus = "error";
        }

        $rtApiCallData['status_code'] = $code;

        if ($code == 200) {
            try {
                $contents = $response->getBody()->getContents();
                $rtApiCallData['response'] = $contents;

                $res = (object)json_decode($contents);
                if (!$res->body->sessionId) {
                    throw new \Exception('invalid data');
                }

                $rtStore['txnid'] = $res->body->txnid;
                $rtStore['sessionId'] = $res->body->sessionId;
            } catch (\Exception $e) {
                $rtStatus = "failure";
            }
        } else if ($code == 0) {
            $rtStatus = "error";
        } else {
            $rtStatus = "failure";
            try {
                $contents  = $response->getBody()->getContents();
                $rtApiCallData['response'] = $contents;
            } catch (\Exception $e) {
                $rtStatus = "failure";
            }
        }
        $responseJSON = ['status' =>  $rtStatus, 'store' => $rtStore, 'apiCallData' => $rtApiCallData];
        return json_encode($responseJSON);
    }

    function checkFetchRequestStatus($consentId, $sessionId, $consentHandleId, $custId)
    {
        $rtStatus = "success";
        $rtStore = [];
        $rtApiCallData = [];
        $resultArr = [];
        $client = new Client(['base_uri' => $this->baseUri, 'timeout'  => 2.0]);
        $code = 0;

        $rtApiCallData['endpoint'] =  $this->baseUri . 'FIStatus/' . $consentId . '/' . $sessionId . '/' . $consentHandleId . '/' . $custId;
        $rtApiCallData['request'] = "";
        try {
            $response = $client->request('GET', 'FIStatus/' . $consentId . '/' . $sessionId . '/' . $consentHandleId . '/' . $custId, ['http_errors' => false, 'headers' => ['Authorization' => 'Bearer: ' . $this->token, 'content-type' => 'application/json']]);
            $code = $response->getStatusCode();
        } catch (\Exception $e) {
            $rtStatus = "error";
        }

        $rtApiCallData['status_code'] = $code;

        if ($code == 200) {
            try {
                $contents = $response->getBody()->getContents();
                $rtApiCallData['response'] = $contents;

                $res = (object)json_decode($contents);
                if (!$res->body->fiRequestStatus) {
                    throw new \Exception('invalid data');
                }
                $rtStore['fiRequestStatus'] = $res->body->fiRequestStatus;
            } catch (\Exception $e) {
                $rtStatus = "failure";
            }
        } else if ($code == 0) {
            $rtStatus = "error";
        } else {
            $rtStatus = "failure";
            try {
                $contents  = $response->getBody()->getContents();
                $rtApiCallData['response'] = $contents;
            } catch (\Exception $e) {
                $rtStatus = "failure";
            }
        }
        $responseJSON = ['status' =>  $rtStatus, 'store' => $rtStore, 'apiCallData' => $rtApiCallData];
        return json_encode($responseJSON);
    }

    function fetchData($custId, $consentId, $sessionId)
    {
        $rtStatus = "success";
        $rtStore = [];
        $rtApiCallData = [];

        $client = new Client(['base_uri' => $this->baseUri, 'timeout'  => 2.0]);
        $code = 0;

        $rtApiCallData['endpoint'] =  $this->baseUri . 'FIFetch/' . $custId . '/' . $consentId . '/' . $sessionId;
        $rtApiCallData['request'] = "";

        try {
            $response = $client->request('GET', 'FIFetch/' . $custId . '/' . $consentId . '/' . $sessionId, ['http_errors' => false, 'headers' => ['Authorization' => 'Bearer: ' . $this->token, 'content-type' => 'application/json']]);
            $code = $response->getStatusCode();
        } catch (\Exception $e) {
            $rtStatus = "error";
        }

        $rtApiCallData['status_code'] = $code;

        if ($code == 200) {
            try {
                $contents = $response->getBody()->getContents();
                $rtApiCallData['response'] = $contents;

                $res = (object)json_decode($contents);
                if (!$res->body) {
                    throw new \Exception('invalid data');
                }

                $rtStore['fetchedData'] = json_encode($res->body);
            } catch (\Exception $e) {
                $rtStatus = "failure";
            }
        } else if ($code == 0) {
            $rtStatus = "error";
        } else {
            $rtStatus = "failure";
            try {
                $contents  = $response->getBody()->getContents();
                $rtApiCallData['response'] = $contents;
            } catch (\Exception $e) {
                $rtStatus = "failure";
            }
        }

        $responseJSON = ['status' =>  $rtStatus, 'store' => $rtStore, 'apiCallData' => $rtApiCallData];
        return json_encode($responseJSON);
    }
}
