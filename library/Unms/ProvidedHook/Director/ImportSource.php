<?php

namespace Icinga\Module\Unms\ProvidedHook\Director;

use Icinga\Module\Director\Hook\ImportSourceHook;
use Icinga\Module\Director\Web\Form\QuickForm;
use Icinga\Module\Unms\UnmsAPI;
use Icinga\Exception\ConfigurationError;

/**
 * Class ImportSource
 * @package Icinga\Module\Unms\ProvidedHook\Director
 */
class ImportSource extends ImportSourceHook
{

    protected $api;

    /**
     * @inheritdoc
     */
    public function fetchData()
    {
      $api     = $this->api();
      $devices = $api->getDevices();
      $data    = array();

      foreach ($devices as $d) {
        $arr  = (array) $d;
        $vars = $this->flattenWithKeys($arr, '.');

        $data[] = (object) array(
          'hostname'    => $d->identification->hostname,
          'displayname' => $d->identification->displayName,
          'macaddr'     => $d->identification->mac,
          'role'        => $d->identification->role,
          'ip'          => preg_replace('/(.*)\/.*/', '$1', $d->ipAddress),
          'vars'        => $vars
        );
      };

      return $data;
    }

    /**
     * @inheritdoc
     */
    public function listColumns()
    {
        $api     = $this->api();
        $devices = $api->getDevices();
        $keys    = array();

        foreach ($devices as $k => $v) {
          $d_arr  = (array) $v;
          $nskeys = array_keys($this->flattenWithKeys($d_arr, '.', 'vars.'));
          array_push($keys, $nskeys);
        }

        $columns = array_merge(array(
            'hostname',
            'displayname',
            'macaddr',
            'role',
            'ip',
            'vars'
          ), array_unique($nskeys));

        return $columns;
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultKeyColumnName()
    {
        return 'displayname';
    }

    /**
     * @inheritdoc
     * @throws \Zend_Form_Exception
     */
    public static function addSettingsFormFields(QuickForm $form)
    {
        /** @var $form \Icinga\Module\Director\Forms\ImportSourceForm */

        $form->addElement('text', 'username', array(
            'label'        => 'API Username',
            'required'     => true
        ));

        $form->addElement('password', 'password', array(
            'label'        => 'API Password',
            'required'     => true
        ));

        $form->addElement('text', 'address', array(
            'label'        => 'Endpoint Hostname / IP Address',
            'required'     => true
        ));

        $form->addElement('text', 'port', array(
            'label'        => 'Endpoint Port',
            'required'     => true
        ));

        $form->addElement('checkbox', 'validate_ssl', array(
            'label'        => 'Validate SSL Certificate',
            'required'     => false,
            'value'        => 0,
        ));

        return;
    }

    protected function flattenWithKeys(array $array, $childPrefix = '.', $root = '', $result = array())
    {
      foreach($array as $k => $v) {
        if(is_array($v) || is_object($v)) $result = $this->flattenWithKeys( (array) $v, $childPrefix, $root . $k . $childPrefix, $result);
        else $result[ $root . $k ] = $v;
      }

      return $result;
    }

    /**
     * @throws \Icinga\Exception\ConfigurationError
     * @return UnmsAPI
     */
    protected function api()
    {
      if ($this->api === null) {
        $this->api = new UnmsAPI(
          $this->getSetting('username'),
          $this->getSetting('password'),
          $this->getSetting('address').':'.$this->getSetting('port'),
          $this->getSetting('validate_ssl')
        );
      }

      if ($this->api->login()) {
        return $this->api;
      } else { 
        throw new ConfigurationError(
          'Cannot log into UNMS API endpoint %s',
          $this->getSetting('address').":".$this->getSetting('port')
        );
      } 
    }
}
