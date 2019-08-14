<?php
/**
 * Created Vlad Karapetyan
 */

abstract class GluuOxd_Gluufolder_Helper_ClientOXDRP extends Mage_Core_Helper_Abstract{

    protected $data = array();
    protected $command;
    protected $params = array();
    protected $response_json;
    protected $response_object;
    protected $response_data = array();
    protected static $socket = null;


    /**
     * abstract Client_oxd constructor.
     */
    public function __construct()
    {
        $this->setCommand();
    }
    /**
     * request to oxd socket
     **/
    public function oxd_socket_request($data, $char_count = 8192){
        $oxd_config = json_decode(Mage::getStoreConfig('gluu/oxd/gluu_config'), true);
        self::$socket = stream_socket_client('127.0.0.1:' . $oxd_config['gluu_oxd_port'], $errno, $errstr, STREAM_CLIENT_PERSISTENT);
        if (!self::$socket) {
            return 'Can not connect to oxd server';
        }else{
            fwrite(self::$socket, $data);
            $result = fread(self::$socket, $char_count);
            fclose(self::$socket);
            return $result;
        }

    }
    /**
     * send function sends the command to the oxD server.
     *
     * Args:
     * command (dict) - Dict representation of the JSON command string
     **/
    public function request()
    {
        $this->setParams();

        $jsondata = json_encode($this->getData(), JSON_UNESCAPED_SLASHES);

        $lenght = strlen($jsondata);
        if($lenght<=0){
            return array('status'=> false, 'message'=> 'Sorry .Problem with oxd.');
        }else{
            $lenght = $lenght <= 999 ? "0" . $lenght : $lenght;
        }

        $this->response_json =  $this->oxd_socket_request(utf8_encode($lenght . $jsondata));
        if($this->response_json !='Can not connect to oxd server'){
            $this->response_json = str_replace(substr($this->response_json, 0, 4), "", $this->response_json);
            if ($this->response_json) {
                $object = json_decode($this->response_json);
                if ($object->status == 'error') {
                    if($object->data->error == "invalid_op_host"){
                        return array('status'=> false, 'message'=> $object->data->error);
                    }elseif($object->data->error == "internal_error"){
                        return array('status'=> false, 'message'=> $object->data->error , 'error_message'=>$object->data->error_description);
                    }else{
                        return array('status'=> false, 'message'=> $object->data->error . ' : ' . $object->data->error_description);
                    }
                } elseif ($object->status == 'ok') {
                    $this->response_object = json_decode($this->response_json);
                    return array('status'=> true);
                }
            }
        }else{
            return array('status'=> false, 'message'=> 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.');
        }

    }

    /**
     * @return mixed
     */
    public function getResponseData()
    {
        if (!$this->getResponseObject()) {
            $this->response_data = 'Data is empty';
            return;
        } else {
            $this->response_data = $this->getResponseObject()->data;
        }
        return $this->response_data;
    }

    /**
     * @return array
     */
    public function getData()
    {
        $this->data = array('command' => $this->getCommand(), 'params' => $this->getParams());
        return $this->data;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @param string $command
     */
    abstract function setCommand();

    /**
     * getResult function geting result from oxD server.
     * Return: response_object - The JSON response parsing to object
     **/
    public function getResponseObject()
    {
        return $this->response_object;
    }

    /**
     * function getting result from oxD server.
     * return: response_json - The JSON response from the oxD Server
     **/
    public function getResponseJSON()
    {
        return $this->response_json;
    }

    /**
     * @param array $params
     */
    abstract function setParams();

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

}