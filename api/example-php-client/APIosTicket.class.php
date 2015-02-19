<?php
class APIosTicketException extends Exception {}

class APIosTicket {
  private $url;
  private $apikey;
  private $apiuser;
  private $apipasswd;
  private $error;

  private function __construct($url, $apikey, $apiuser, $apipasswd) {
    $this->url = $url;
    $this->apikey = $apikey;
    $this->apiuser = $apiuser;
    $this->apipasswd = $apipasswd;
  }

  public static function connect($url, $apikey, $apiuser, $apipasswd) {
    $api = new self($url, $apikey, $apiuser, $apipasswd);
    if($api->connectionTest());
      return $api;
    return false;
  }


  public function connectionTest() {
    try {
      self::getTicket(1);
      return true;
    } catch(APIosTicketException $e) {
      $this->error = $e->getMessage();
      return false;
    }
  }


  public function search($query, $criteria = array()) {
    $data = array('query' => $query, 'criteria' => $criteria);
    return json_decode($this->request($data, 'tickets.json?search'));
  }

  public function getTicket($ticketid) {
    $data = array('id' => $ticketid);
    return json_decode($this->request($data, 'tickets.json?getTicket'));
  }

  public function getThreadEntry($threadentryid) {
    $data = array('id' => $threadentryid);
    return json_decode($this->request($data, 'tickets.json?getThreadEntry'));
  }

  public function changeTicketStatus($ticketid, $status, $comments) {
    $data = array('id' => $ticketid, 'status' => $status, 'comments' => $comments);
    return json_decode($this->request($data, 'tickets.json?changeTicketStatus'));
  }


  private function request($data, $req) {
    $data = array_merge($data, array('user' => $this->apiuser, 'passwd' => $this->apipasswd));
    #curl post
    $ch = curl_init();
    $url = $this->url.$req;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_USERAGENT, 'osTicket API Client v1.8');
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Expect:', 'X-API-Key: '.$this->apikey));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    $result=curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlerror = curl_error($ch);
    curl_close($ch);
    if ($code != 201)
        throw new APIosTicketException($curlerror ?: $result);

    return $result;
  }
}

