<?php

class NiuApi {

    public static $token = null;
    public static $serial_no = null;

    const API_BASE_URL = 'https://app-api-fk.niu.com';
    const ACCOUNT_BASE_URL = 'https://account.niu.com';

    public static function get_token($email, $password, $country_code) {
        $data = new UrlRequest(self::ACCOUNT_BASE_URL . '/appv2/login', array('account' => $email, 'countryCode' => $country_code, 'password' => $password));
        if (is_object($data) && property_exists($data, 'response') && property_exists($data->response, 'data') && property_exists($data->response->data, 'token')) {
            return $data->response->data->token;
        }
        return false;
    }

    public static function get_vehicles() {
        if (self::$token === null)
            return false;
        return self::return_response_data(new UrlRequest(self::API_BASE_URL . '/motoinfo/list', [], self::$token));
    }

    public static function get_motor_info() {
        if (self::$token === null || self::$serial_no === null)
            return false;
        return self::return_response_data(new UrlRequest(self::API_BASE_URL . '/v3/motor_data/index_info?sn=' . self::$serial_no, null, self::$token));
    }

    public static function get_overall_tally() {
        if (self::$token === null || self::$serial_no === null)
            return false;
        return self::return_response_data(new UrlRequest(self::API_BASE_URL . '/motoinfo/overallTally', ['sn' => self::$serial_no], self::$token));
    }

    public static function get_battery_info() {
        if (self::$token === null || self::$serial_no === null)
            return false;
        return self::return_response_data(new UrlRequest(self::API_BASE_URL . '/v3/motor_data/battery_info?sn=' . self::$serial_no, null, self::$token));
    }
    
    public static function get_battery_health() {
        if (self::$token === null || self::$serial_no === null)
            return false;
        return self::return_response_data(new UrlRequest(self::API_BASE_URL . '/v3/motor_data/battery_info/health?sn=' . self::$serial_no, null, self::$token));
    }

    public static function get_tracks_available($limit = 100, $offset = 0) {
        if (self::$token === null || self::$serial_no === null)
            return false;
        return self::return_response_data(new UrlRequest(self::API_BASE_URL . '/v3/motor_data/track', ['sn' => self::$serial_no, 'index' => $offset, 'pagesize' => $limit], self::$token));
    }

    public static function get_tracks_details($track_id, $track_date) {
        if (self::$token === null || self::$serial_no === null)
            return false;
        return self::return_response_data(new UrlRequest(self::API_BASE_URL . '/motoinfo/track/detail', ['sn' => self::$serial_no, 'trackId' => $track_id, 'date' => $track_date], self::$token));
    }
    
    public static function get_firmware_version() {
        if (self::$token === null || self::$serial_no === null)
            return false;
        return self::return_response_data(new UrlRequest(self::API_BASE_URL . '/motorota/getfirmwareversion', ['sn' => self::$serial_no], self::$token));
    }    
    

    private static function return_response_data($data) {
        if (is_object($data) && property_exists($data, 'response') && property_exists($data->response, 'status') && $data->response->status !== 0) {
            echo PHP_EOL . 'ERROR:' . @$data->response->desc . PHP_EOL . @$data->response->trace . PHP_EOL . PHP_EOL;
            return false;
        }
        if (is_object($data) && property_exists($data, 'response') && property_exists($data->response, 'data')) {
            return $data->response->data;
        }
        return false;
    }

}

class UrlRequest {

    public $response_code = null;
    public $response = null;

    public function __construct($url, $postdata = null, $token = null) {
        $headers = ['Accept-Language: en-US'];
        if ($token !== null) {
            $headers[] = 'token: ' . $token;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($postdata !== null) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->response = curl_exec($ch);
        if (($decoded = json_decode($this->response)) !== false) {
            $this->response = $decoded;
        }
        $this->response_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
    }

}
