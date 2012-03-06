<?php

class Livefyre_http {

    public function __construct() {
        $this->default_content_type = 'application/x-www-form-urlencoded';
    }

    public function request($url, $args = array()) {
        /* valid $args members (all optional):
            method: HTTP method
            data: associative array of "form" data
        */
        if ( !isset($args['method']) ) {
            $args['method'] = isset($args['data']) ? 'POST' : 'GET';
        }
        $result = array( 'response' => false,
                         'body' => false);
        $method_name = $this->has_curl() ? 'curl_request' : 'gfc_request';
        return $this->$method_name($url, $args, $result);
    }

    private function has_curl() {
        return function_exists('curl_init');
    }

    private function curl_request($url, $args = array(), &$result) {
        $curl_options = array(CURLOPT_RETURNTRANSFER  => true);
        if ( $args['method'] == 'POST' ) {
            $curl_options = array(
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_POST            => 1,
                CURLOPT_POSTFIELDS      => http_build_query($args['data']),
                CURLOPT_HTTPHEADER      => array("Content-Type: $this->default_content_type")
            );
        }
        $ch = curl_init($url); 
        curl_setopt_array($ch, $curl_options);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $result['response'] = array( 'code' => curl_getinfo($ch, CURLINFO_HTTP_CODE) );
        $result['body'] = $response;
        return $result;
    }

    private function gfc_request($url, $args = array(), &$result) {
        if ( $args['method'] == 'POST' ) {
            $data_url = http_build_query($args['data']);
            $data_len = strlen($data_url);
            $result['body'] = file_get_contents(
                $url, false, 
                stream_context_create(
                    array(
                        'http'=>array(
                            'method'=>'POST',
                            'header'=>"Connection: close\r\nContent-Length: $data_len\r\nContent-Type: $this->default_content_type\r\n",
                            'content'=>$data_url
                        )
                    )
                )
            );
        } else {
            $result['body'] = file_get_contents($url);
        }
        // we don't have a resp code, so lets fake it!
        $result_code = $result['body'] ? 200 : 500;
        $result['response'] =  array( 'code' => $result_code );
        return $result;
    }

}

?>
