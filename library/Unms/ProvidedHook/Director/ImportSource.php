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
      /* if ($this->getSetting('query_type') === 'resource') {
      return $this->fetchResourceData();
      } */

      $api  = $this->api();
      $data = array();

      // TODO: flatten the entire array and use period namespaced keys 
      // (rather than hardcoding like this)
      foreach ($api->getDevices() as $d) {
        $data[] = (object) array(
          'hostname'    => $d->identification->hostname,
          'displayname' => $d->identification->displayName,
          'macaddr'     => $d->identification->mac,
          'model'       => $d->identification->modelName,
          'role'        => $d->identification->role,
          'ip'          => preg_replace('/(.*)\/.*/', '$1', $d->ipAddress),
        );
      };

      return $data;
    }

    /**
     * @inheritdoc
     */
    public function listColumns()
    {
        /* if ($this->getSetting('query_type') === 'resource') {
            return array(
                'certname',
                'type',
                'title',
                'exported',
                'parameters',
                'environment',
            );
        } */

        $columns = array(
            'hostname',
            'displayname',
            'macaddr',
            'model',
            'role',
            'ip'
        );

        /* foreach ($this->db()->listFactNames() as $name) {
            $columns[] = 'facts.' . $name;
        } */

        return $columns;
    }

    /**
     * @return \stdClass[]
     */
    /* protected function fetchResourceData()
    {
        return $this->db()->fetchResourcesByType($this->getSetting('resource_type'));
    } */

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
