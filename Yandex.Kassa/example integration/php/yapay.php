<?
namespace Yapay;

class Payment
{
    const SHOP_ID = 666;

    const USERAGENT = 'useragent';

    const YMAPI = 'https://penelope-demo.yamoney.ru:8083/webservice/mws/api/';

    const REPEAT_CARD_PAYMENT = 'repeatCardPayment';

    const LISTORDERS = 'listOrders';

    const HTTPHEADER = 'Content-Type:application/x-www-form-urlencoded';

    const CERTFILE = 'youcert.cer';

    const KEYFILE = 'private.key';

    const SERTPASS = 'sertpass';

    const TMPLOGFILE = './._temp_curl_log.txt';

    /**
     * @var Error переменная содержит название ошибки последней операции
     */
    public $error = NULL;

    /**
     * @var string имя последнего вызванного метода
     */
    public $lastMethod = "";

    /**
     * @var SimpleXMLElement содержит xml-объект последнего ответа
     */
    public $lastXML = NULL;

    /**
     * @var string
     */
    public $curlTrace = null;

    /**
     * @var bool флаг включает/выключает логирование выполнения curl запросов и ошибок в файл
     */
    private $debug = TRUE;

    /**
     * @var array содержит последние параметры запроса
     */
    private $lastRequestData = "";

    /**
     * @var string содержит кэш последнего ответа
     */
    public  $lastResponse = "";

    /**
     * @var указатель на файл для записи лога выполнения запроса через curl
     */
    private $logout = null;

    public function getLogfilePath()
    {
        return dirname(__FILE__) . '/../logs/yapay-log.txt';
    }

    public function getMethodUrl($name)
    {
        return self::YMAPI . $name;
    }

    public function repeatCardPayment($clientOrderID, $amount, $orderNumber, $invoiceId)
    {
        $requestData = array(
            'clientOrderId' => $clientOrderID,
            'invoiceId' => $invoiceId,
            'amount' => $amount,
            'orderNumber' => $orderNumber
        );
        return $this->doRequest(self::REPEAT_CARD_PAYMENT, $requestData);
    }

    public function listOrders($options)
    {
        $now = new DateTime('NOW');
        $requestData = array(
            "shopId" => self::SHOP_ID,
            "requestDT" => $now->format(DateTime::ISO8601),
        );

        if ($options["orderNumber"]) {
            $requestData["orderNumber"] = $options["orderNumber"];
        }

        if ($options["dateFrom"]) {
            $requestData['paymentDatetimeGreaterOrEqual'] = $options['dateFrom'];
        }

        if ($options["dateTo"]) {
            $requestData['paymentDatetimeLessOrEqual'] = $options['dateTo'];
        }

        return $this->doRequest(self::LISTORDERS, $requestData);
    }

    private function initCurl($url, $data = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(self::HTTPHEADER));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, self::USERAGENT);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLCERT, self::CERTFILE);
        curl_setopt($ch, CURLOPT_SSLKEY, self::KEYFILE);
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, "DER");
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, self::SERTPASS);

        if ($this->debug) {
            $this->logout = fopen(self::TMPLOGFILE, 'w+');
            curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
            curl_setopt($ch, CURLOPT_STDERR, $this->logout);
        }

        if ($data) {
            $data = is_array($data) ? http_build_query($data) : $data;
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        return $ch;
    }

    public function execCurl($curlHandler)
    {
        $result = curl_exec($curlHandler);

        if ($this->debug) {
            $this->curlTrace = file_get_contents(self::TMPLOGFILE);
            unlink(self::TMPLOGFILE);
        }

        return $result;
    }

    public function doRequest($method, $data) {
        $this->lastMethod = $method;
        $this->lastRequestData = $data;
        $methodUrl = $this->getMethodUrl($method);
        $ch = $this->initCurl($methodUrl, $data);
        $result = $this->execCurl($ch);

        if (!$result) {
            $this->curlErrorHandler($ch);
            return FALSE;
        }

        $xml = $this->curlSuccessHandler($result);

        if ($this->YMErrorHandler($xml)) {
            return FALSE;
        }

        return $xml;
    }

    public function log($string)
    {
        $logfileHandler = fopen($this->getLogfilePath(), 'a+');
        fwrite($logfileHandler, "\n--\n");
        fwrite($logfileHandler, date('Y-m-d H:i:s') . "\n");
        fwrite($logfileHandler, $string . "\n");
        fclose($logfileHandler);
    }

    /**
     * @param Error $error
     */
    public function logError($error = NULL)
    {
        if (!$error && !$this->error) {
            return;
        }
        if (!$error) {
            $error = $this->error;
        }
        $this->log($error->dump());
    }

    /**
     * @param $result
     * @return SimpleXMLElement
     */
    private function curlSuccessHandler($result)
    {
        $this->error = NULL;
        $this->lastResponse = $result;
        $this->lastXML = new \SimpleXMLElement($result);

        return $this->lastXML;
    }

    private function curlErrorHandler($curlHandler)
    {
        $this->error = new Error(
            'curl',
            curl_errno($curlHandler),
            curl_error($curlHandler),
            array('requestData' => $this->getLastRequestDataString())
        );
        $this->logError();
        $this->log("CURL TRACE\n" . $this->curlTrace);
        $this->lastResponse = "";
    }

    private function YMErrorHandler($xml)
    {
        if ($xml['error'] == '0') {
            return FALSE;
        }

        $dataError = array(
            'status' => $xml['status'],
            'requestData' => $this->getLastRequestDataString()
        );

        $this->error = new Error(
            'ym_api',
            $xml['error'],
            $xml['techMessage'],
            $dataError
        );
        $this->logError();
        return TRUE;
    }

    private function getLastRequestDataString() {
        $pairs = array();
        foreach($this->lastRequestData as $key => $value) {
            $pairs[] = $key . ': ' . $value;
        }
        return implode(', ', $pairs);
    }
}

class Error
{

    public $type = "";
    public $message = "";
    public $code = "";
    public $data = NULL;

    /**
     * @param $type
     * @param $code
     * @param $message
     * @param mixed $data
     */
    function __construct($type, $code, $message, $data = NULL)
    {
        $this->type = $type;
        $this->code = $code;
        $this->message = $message;

        if($data) {
            $this->data = $data;
        }
    }

    function dump()
    {
        $dump = $this->type;
        $dump .= "; ";
        $dump .= "Number: $this->code; ";
        $dump .= $this->message . "; ";

        if ($this->data) {
            $dump .= "Data: \n";
            $dump .= $this->dumpData();
        }

        return $dump;
    }

    function dumpData($data = null) {
        $data = $data ? $data : $this->data;

        if (is_array($this->data)) {
            $dumpArray = array();
            foreach($this->data as $key => $value) {
                $dumpArray[] = $key . '=' . $value;
            }
            return implode(', ', $dumpArray);
        }

        return str_replace("\n", " ", print_r($this->data, true));
    }
}
?>
