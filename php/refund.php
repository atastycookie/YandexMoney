 <?php

    protected function encrypt($data, $certFile, $keyFile)
    {
        try {
            $pipes = array();
            $process = proc_open(
                'openssl smime -sign -signer ' . $certFile . ' -inkey ' . $keyFile . ' -nochain -nocerts -outform PEM -nodetach',
                array(array("pipe", "r"), array("pipe", "w"), array("pipe", "w")),
                $pipes
            );

            if (is_resource($process)) {
                fwrite($pipes[0], $data);
                fclose($pipes[0]);
                $pkcs7 = stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                proc_close($process);
                return $pkcs7;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function sendRefundRequest($cause)
    {
        $data = '<?xml version="1.0" encoding="UTF-8"?>
<returnPaymentRequest clientOrderId="' . $this->id . '" requestDT="' . date('Y-m-d\TH:i:sP') . '"
invoiceId="' .$this->invoiceId . '" shopID="' .$this->shopId . '" amount="' .sprintf("%.2f", $this->cost) . '"
currency="10643" cause="' .$cause . '" />';

        $certFile = '/path_to_project/ssl/cert.cer';
        $keyFile = '/path_to_project/ssl/key.key';
        if (($data = $this->encrypt($data, $certFile, $keyFile)) === false)
            return false;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://penelope-demo.yamoney.ru:8083/webservice/mws/api/returnPayment');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Ymoney CollectMoney');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLCERT, $certFile);
        curl_setopt($ch, CURLOPT_SSLKEY, $keyFile);
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->_pswd);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
        $response = curl_exec($ch);
        curl_close($ch);

        header('Content-Type:text/xml');
        die($response);
    }

?>
