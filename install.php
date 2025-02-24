<?php

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Exception\FilesystemException;

\defined('_JEXEC') or die;

return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->set(
            InstallerScriptInterface::class,
            new class (
                $container->get(AdministratorApplication::class),
                $container->get(DatabaseInterface::class)
            ) implements InstallerScriptInterface {
                private AdministratorApplication $app;
                private DatabaseInterface $db;

                public function __construct(AdministratorApplication $app, DatabaseInterface $db) {
                    $this->app = $app;
                    $this->db  = $db;
                }

                public function preflight(string $type, InstallerAdapter $parent): bool {
                    return true;
                }

                public function install(InstallerAdapter $parent): bool
                {
                    $this->app->enqueueMessage('Successful installed.');
        
                    return true;
                }
        
                public function update(InstallerAdapter $parent): bool
                {
                    $this->app->enqueueMessage('Successful updated.');
        
                    return true;
                }
        
                public function uninstall(InstallerAdapter $parent): bool
                {
                    $this->app->enqueueMessage('Successful uninstalled.');
        
                    return true;
                }
        
                public function postflight(string $type, InstallerAdapter $parent): bool
                {
                    if ($type == 'update') {
                        $this->fixJsonFormats();
                    }
        
                    return true;
                }

                private function fixJsonFormats() {
                    $query = $this->db->getQuery(true);

                    $query->select($this->db->quoteName(['id', 'fields', 'url_parameters', 'joined_tables']));
                    $query->from($this->db->quoteName('#__neukomtemplating_templates'));

                    $this->db->setQuery($query);

                    $templates = $this->db->loadObjectList();

                    foreach ($templates as $template) {
                        $updatedTemplate = new stdClass();

                        $updatedTemplate->id = $template->id;
                        $updatedTemplate->fields = $template->fields;
                        $updatedTemplate->url_parameters = $template->url_parameters;
                        $updatedTemplate->joined_tables = $template->joined_tables;

                        if (!json_validate($template->fields)) {
                            $updatedTemplate->fields = json_encode($this->getFields($template));
                        }

                        if (!json_validate($template->url_parameters)) {
                            $updatedTemplate->url_parameters = json_encode($this->getUrlParameters($template));
                        }
                        
                        if (!json_validate($template->joined_tables)) {
                            $updatedTemplate->joined_tables = json_encode($this->getJoinedTables($template));
                        }

                        $result = $this->db->updateObject('#__neukomtemplating_templates', $updatedTemplate, 'id');
                    }
                }

                function getFields($template) {
                    $fieldInputs = [
                        'name',
                        'type',
                        'required',
                        'showInForm',
                        'label',
                        'selectOptions',
                    ];

                    $fields = $template->fields != '' ? explode(';', $template->fields) : [];
                    $fieldObjects = array();

                    foreach ($fields as $field) {
                        $fieldValues = explode(':', $field);
                        $fieldObject = new stdClass();

                        for ($i = 0; $i < count($fieldValues); $i++) {
                            $fieldObject->{$fieldInputs[$i]} = $fieldValues[$i];
                        }

                        $fieldObjects[] = $fieldObject;
                    }

                    return $fieldObjects;
                }

                function getUrlParameters($template) {
                    $urlParameterInputs = [
                        'name',
                        'default',
                        'insertIntoDb',
                    ];

                    $urlParameters = $template->url_parameters != '' ? explode(';', $template->url_parameters) : [];
                    $urlParameterObjects = array();

                    foreach ($urlParameters as $urlParameter) {
                        $urlParameterValues = explode(':', $urlParameter);
                        $urlParameterObject = new stdClass();

                        for ($i = 0; $i < count($urlParameterValues); $i++) {
                            $urlParameterObject->{$urlParameterInputs[$i]} = $urlParameterValues[$i];
                        }

                        $urlParameterObjects[] = $urlParameterObject;
                    }

                    return $urlParameterObjects;
                }

                function getJoinedTables($template) {
                    $joinedTableInputs = [
                        'name',
                        'displayField',
                        'connectionType',
                        'connectionInfo',
                        'foreignFields',
                        'showInForm',
                        'alias',
                        'formName',
                    ];

                    $joinedTableConnectionInfo = [
                        'NToOne' => [
                            'foreignKey',
                            'remoteId',
                        ],
                        'OneToN' => [
                            'foreignKey',
                        ],
                        'NToN' => [
                            'intermediateTable',
                            'intermediateLocalKey',
                            'intermediateRemoteKey',
                            'remoteId',
                        ],
                    ];

                    $joinedTables = $template->joined_tables != '' ? explode(';', $template->joined_tables) : [];
                    $joinedTableObjects = array();

                    foreach ($joinedTables as $joinedTable) {
                        $joinedTableValues = explode(':', $joinedTable);
                        $joinedTableObject = new stdClass();

                        for ($i = 0; $i < count($joinedTableValues); $i++) {
                            if ($i == 3) continue;

                            $joinedTableObject->{$joinedTableInputs[$i]} = $joinedTableValues[$i];
                        }

                        foreach ($joinedTableConnectionInfo as $connectionType=>$infoFields) {
                            foreach ($infoFields as $infoField) {
                                $joinedTableObject->{$connectionType . '-' . $infoField} = '';
                            }
                        }

                        if (!empty($joinedTableValues[2]) && !empty($joinedTableConnectionInfo[$joinedTableValues[2]])) {
                            $connectionInfoValues = explode(',', $joinedTableValues[3]);
                            $connectionType = $joinedTableValues[2];
                            $infoFields = $joinedTableConnectionInfo[$connectionType];

                            for ($i = 0; $i < count($connectionInfoValues); $i++) {
                                $joinedTableObject->{$connectionType . '-' . $infoFields[$i]} = $connectionInfoValues[$i];
                            }
                        }

                        $joinedTableObjects[] = $joinedTableObject;
                    }

                    return $joinedTableObjects;
                }
            }
        );
    }
};