<?php
namespace BAF;

class Plisio {
    private $api_key;
    private $api_url = 'https://plisio.net/api/v1/';

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    public function create_invoice($data) {
        $data['api_key'] = $this->api_key;
        return $this->request('invoices/new', $data);
    }

    public function get_invoice($id) {
        return $this->request('invoices/' . $id, ['api_key' => $this->api_key], 'GET');
    }

    private function request($endpoint, $params = [], $method = 'GET') {
        $url = $this->api_url . $endpoint;

        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function verify_callback($data) {
        if (!isset($data['verify_hash'])) {
            return false;
        }

        $verifyHash = $data['verify_hash'];
        unset($data['verify_hash']);
        ksort($data);

        if (isset($data['expire_utc'])) {
            $data['expire_utc'] = (string)$data['expire_utc'];
        }
        if (isset($data['tx_urls'])) {
            $data['tx_urls'] = html_entity_decode($data['tx_urls']);
        }

        $postString = serialize($data);
        $checkKey = hash_hmac('sha1', $postString, $this->api_key);

        return hash_equals($checkKey, $verifyHash);
    }
}
