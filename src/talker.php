<?php
namespace ltajniaa\FinvuCommunicator;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request;
use Ramsey\Uuid\Uuid;


class Talker
{
    private $account;
    private $login;
    private $pass;
    private $token;
    private $baseUri;

    function __construct($account, $login, $pass)
    {
        $this->account = $account;
        $this->login = $login;
        $this->pass = $pass;
        $this->baseUri = "https://" . $account . ".fiu.finfactor.in/finsense/API/V1/";
    }

    function setAccessToken($token)
    {
        $this->token = $token;
    }

    function getAccessToken()
    {
        if (!$this->token) {
            $client = new Client(['base_uri' => $this->baseUri, 'timeout'  => 2.0,]);
            $data = [
                'header' => ["rid" => Uuid::uuid4()->toString(), "ts" =>  date(\DateTime::ISO8601), "channelId" => "finsense"],
                'body' => ["userId" => $this->login, "password" => $this->pass]
            ];

            //$response = $client->request('POST', 'User/Login', ['header' => [ 'content-type' => 'application/json'], 'body' => json_encode($data)]);
            $response = $client->request('POST', 'User/Login', ['body' => json_encode($data)]);
            $code = $response->getStatusCode();
            if($code ==200){
                $body = $response->getBody();
                $contents = $body->getContents();
                $res = (object)json_decode($contents);
                $this->token =  $res->body->token;
            }else{
                //throw new \Exception("API responded with error:"+$code);
            }
        }
        return $this->token;
    }

    function raiseConsentRequest($custId, $consentDescription, $templateName, $userSessionId)
    {
        $resultArr = [];
        $client = new Client(['base_uri' => $this->baseUri, 'timeout'  => 2.0 ]);
        $data = [
            'header' => ["rid" => Uuid::uuid4()->toString(), "ts" =>  date(\DateTime::ISO8601), "channelId" => "finsense"],
            'body' => ["custId" => $custId, "consentDescription"=> $consentDescription, "templateName" => $templateName, "userSessionId"=> $userSessionId]
        ];

        $code = 0;
        try{
            $response = $client->request('POST', 'ConsentRequestEncrypt', ['headers' => ['Authorization' => 'Bearer: '.$this->token, 'content-type' => 'application/json'], 'body' => json_encode($data)]);
            $code = $response->getStatusCode();
        }
        catch(\Exception $e){
            echo $e;
        }
        if($code ==200){
            $contents = $response->getBody()->getContents();
            $res = (object)json_decode($contents);

            $outputText = '<script src="https://finvu.in/sdk/dist/finvu-aa.js"></script>
            
            <script>
            var ecreq = "'. $res->body->encryptedRequest .'"; var reqdate = "'. $res->body->requestDate .'";var fi = "'. $res->body->encryptedFiuId .'";
            function launchAA(event){
                let aa = new FinvuAA();
                aa.open(ecreq, reqdate, fi, function (response){
                 //alert(response.data.status);
                });
                event.preventDefault();
            }
            </script>
            <input type="button" onclick="javascript:launchAA()" value="Share via AA">';
            echo $outputText;

            array_push($resultArr, ['ConsentHandle' => $res->body->ConsentHandle]);
            array_push($resultArr, ['encryptedRequest' => $res->body->encryptedRequest]);
            array_push($resultArr, ['requestDate' => $res->body->requestDate]);
            array_push($resultArr, ['encryptedFiuId' => $res->body->encryptedFiuId]);

        }else{
            //throw new \Exception("API responded with error:"+$code);
            print($code);
        }
        return $resultArr;
    }

    function checkConsentStatus($custId, $consentHandleId)
    {
        $resultArr = [];
        $client = new Client(['base_uri' => $this->baseUri, 'timeout'  => 2.0 ]);
        $code = 0;
        try{
            $response = $client->request('GET', 'ConsentStatus/'. $custId. '/'. $consentHandleId , ['headers' => ['Authorization' => 'Bearer: '.$this->token, 'content-type' => 'application/json']]);
            $code = $response->getStatusCode();
        }
        catch(\Exception $e){
            echo $e;
        }
        if($code ==200){
            $contents = $response->getBody()->getContents();
            $res = (object)json_decode($contents);
            array_push($resultArr, ['consentStatus' => $res->body->consentStatus]);
            array_push($resultArr, ['consentId' => $res->body->consentId]);
        }else{
            //throw new \Exception("API responded with error:"+$code);
            print($code);
        }
        return $resultArr;
    }

    function triggerDataRequest($custId, $consentId, $consentHandleId, $dateTimeRangeFrom, $dateTimeRangeTo)
    {
        $resultArr = [];
        $client = new Client(['base_uri' => $this->baseUri, 'timeout'  => 2.0 ]);
        $data = [
            'header' => ["rid" => Uuid::uuid4()->toString(), "ts" =>  date(\DateTime::ISO8601), "channelId" => "finsense"],
            'body' => ["custId" => $custId, "consentId"=> $consentId, "consentHandleId" => $consentHandleId, "dateTimeRangeFrom"=> $dateTimeRangeFrom, 'dateTimeRangeTo' => $dateTimeRangeTo]
        ];

        $code = 0;
        try{
            $response = $client->request('POST', 'FIRequest', ['headers' => ['Authorization' => 'Bearer: '.$this->token, 'content-type' => 'application/json'], 'body' => json_encode($data)]);
            $code = $response->getStatusCode();
        }
        catch(\Exception $e){
            echo $e;
        }
        if($code ==200){
            $contents = $response->getBody()->getContents();
            $res = (object)json_decode($contents);
            array_push($resultArr, ['txnid' => $res->body->txnid]);
            array_push($resultArr, ['sessionId' => $res->body->sessionId]);

        }else{
            //throw new \Exception("API responded with error:"+$code);
            print($code);
        }
        return $resultArr;
    }

    function checkFetchRequestStatus($consentId ,$sessionId, $consentHandleId, $custId)
    {
        $resultArr = [];
        $client = new Client(['base_uri' => $this->baseUri, 'timeout'  => 2.0 ]);
        $code = 0;
        try{
            $response = $client->request('GET', 'FIStatus/'. $consentId. '/'. $sessionId .'/'. $consentHandleId .'/'. $custId , ['headers' => ['Authorization' => 'Bearer: '.$this->token, 'content-type' => 'application/json']]);
            $code = $response->getStatusCode();
        }
        catch(\Exception $e){
            echo $e;
        }
        if($code ==200){
            $contents = $response->getBody()->getContents();
            $res = (object)json_decode($contents);
            array_push($resultArr, ['fiRequestStatus' => $res->body->fiRequestStatus]);
        }else{
            //throw new \Exception("API responded with error:"+$code);
            print($code);
        }
        return $resultArr;
    }

    function fetchData($custId, $consentId ,$sessionId)
    {
        $resultArr = [];
        $client = new Client(['base_uri' => $this->baseUri, 'timeout'  => 2.0 ]);
        $code = 0;
        try{
            $response = $client->request('GET', 'FIStatus/'. $custId. '/' . $consentId. '/'. $sessionId , ['headers' => ['Authorization' => 'Bearer: '.$this->token, 'content-type' => 'application/json']]);
            $code = $response->getStatusCode();
        }
        catch(\Exception $e){
            echo $e;
        }
        if($code ==200){
            $contents = $response->getBody()->getContents();
            $res = (object)json_decode($contents);
            array_push($resultArr, ['fetchedData' => $res->body]);
        }else{
            //throw new \Exception("API responded with error:"+$code);
            print($code);
        }
        return $resultArr;
    }

}
