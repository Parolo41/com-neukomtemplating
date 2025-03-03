<?php

namespace Neukom\Component\NeukomTemplating\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Uri\Uri;

class TemplateModel extends ItemModel {

    /**
     * Returns a message for display
     * @param integer $pk Primary key of the "message item", currently unused
     * @return object Message object
     */
    public function getItem($pk = null): object {
        $input = Factory::getApplication()->getInput();
        $user = Factory::getUser();
        $templateConfigName = $input->getString('templateConfigName');

        $aliases = [
            'userid' => $user->id,
            'username' => $user->name,
        ];

        $templateFields = [
            'id',
            'header',
            'template',
            'footer',
            'detail_template',
            'tablename',
            'id_field_name',
            'fields',
            'url_parameters',
            'condition',
            'sorting',
            'limit',
            'user_id_link_field',
            'show_detail_page',
            'enable_search',
            'enable_pagination',
            'page_size',
            'allow_edit',
            'allow_create',
            'form_send_behaviour',
            'access',
            'joined_tables',
            'contact_email_field',
            'contact_display_name',
        ];

        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true);
        $query->select(
            $db->quoteName($templateFields)
        );
        $query->from($db->quoteName('#__neukomtemplating_templates'));
        $query->where('name = "' . $templateConfigName . '"');
        $db->setQuery($query);
        $templateConfig = $db->loadObject();

        $loader = new \Twig\Loader\ArrayLoader([
            'condition' => $templateConfig->condition,
        ]);
        $twig = new \Twig\Environment($loader);

        $levels = $user->getAuthorisedViewLevels();

        if (!in_array((int)$templateConfig->access, $levels)) {
            throw new \Exception("Missing access levels for this template", 403);
        }

        $fieldConfig = json_decode($templateConfig->fields, true);
        $fields = [];
        $fieldNames = [$templateConfig->id_field_name];

        $tableFields = $db->getTableColumns('#__' . $templateConfig->tablename);

        foreach ($fieldConfig as $field) {
            $fieldName = $field['name'];

            if ($fieldName == $templateConfig->id_field_name || !array_key_exists($fieldName, $tableFields)) {
                continue;
            }

            if ($field['showInForm'] || array_key_exists($field['type'], $aliases)) {
                $fields[] = $field;
            }
            
            if (!array_key_exists($field['type'], $aliases)) {
                $fieldNames[] = $field['name'];
            }
        }

        $urlParameters = [];
        $urlDbInserts = [];
        $urlParameterConfig = json_decode($templateConfig->url_parameters, true);

        foreach($urlParameterConfig as $config) {
            if ($config['name'] == '') {
                continue;
            }

            $urlParameters[$config['name']] = $input->get($config['name'], $config['default'], 'string');

            if ($config['insertIntoDb']) {
                $urlDbInserts[] = $config['name'];
            }
        }

        $dataQuery = $db->getQuery(true);
        $dataQuery->select($db->quoteName($fieldNames));
        $dataQuery->from($db->quoteName('#__' . $templateConfig->tablename));

        $conditionList = [];

        if (trim($templateConfig->condition) != "") {
            $conditionList[] = '(' . $twig->render('condition', array_merge($aliases, [ 'urlParameters' => $urlParameters ])) . ')';
        }

        if (trim($templateConfig->sorting) != "") {
            $dataQuery->order($templateConfig->sorting);
        }

        if ($templateConfig->limit != "" && (int)$templateConfig->limit > 0) {
            $dataQuery->setLimit((int)$templateConfig->limit);
        }

        if ($templateConfig->user_id_link_field != "") {
            $conditionList[] = '(' . $templateConfig->user_id_link_field . " = " . $user->id . ')';
        }

        $searchTerm = $input->get('searchTerm', '', 'string');

        if ($searchTerm != '' && $templateConfig->enable_search == "1") {
            $searchConditions = [];

            foreach($fieldNames as $fieldName) {
                $searchConditions[] = $fieldName . " LIKE '%" . $searchTerm . "%'";
            }

            $conditionList[] = '(' . implode(' OR ', $searchConditions) . ')';
        }

        if (sizeof($conditionList) > 0) {
            $dataQuery->where(implode(' AND ', $conditionList));
        }

        $db->setQuery($dataQuery);
        $data = $db->loadObjectList($templateConfig->id_field_name);

        $pageSize = max(1, ($templateConfig->enable_pagination != "1" || intval($templateConfig->page_size) == 0) ? sizeof($data) : intval($templateConfig->page_size));
        $lastPageNumber = ceil(sizeof($data) / $pageSize);

        if ($templateConfig->enable_pagination == "1" && intval($templateConfig->page_size) > 0) {
            $pageNumber = max($input->get('pageNumber', 1, 'int'), 1);

            $data = array_slice($data, intval($templateConfig->page_size) * ($pageNumber - 1), intval($templateConfig->page_size), true);
        }

        $joinedTables = json_decode($templateConfig->joined_tables, true);

        foreach ($joinedTables as $key => $joinedTable) {
            $joinedTables[$key]['options'] = $this->queryJoinedTableOptions($joinedTable);
        }

        foreach ($data as $record) {
            $this->queryJoinedTables($record, $joinedTables, $templateConfig->id_field_name);
        }

        $item = new \stdClass();
        $item->id = $templateConfig->id;
        $item->templateName = $templateConfigName;
        $item->tableName = $templateConfig->tablename;
        $item->idFieldName = $templateConfig->id_field_name;
        $item->header = $templateConfig->header;
        $item->template = $templateConfig->template;
        $item->footer = $templateConfig->footer;
        $item->detailTemplate = $templateConfig->detail_template;
        $item->showDetailPage = ($templateConfig->show_detail_page == "1");
        $item->userIdLinkField = $templateConfig->user_id_link_field;
        $item->enableSearch = ($templateConfig->enable_search == "1");
        $item->enablePagination = ($templateConfig->enable_pagination == "1");
        $item->pageSize = $pageSize;
        $item->lastPageNumber = $lastPageNumber;
        $item->allowEdit = ($templateConfig->allow_edit == "1");
        $item->allowCreate = ($templateConfig->allow_create == "1");
        $item->formSendBehaviour = $templateConfig->form_send_behaviour;
        $item->data = $data;
        $item->aliases = $aliases;
        $item->contactEmailField = $templateConfig->contact_email_field;
        $item->contactDisplayName = $templateConfig->contact_display_name;
        
        $item->fields = $fields;
        $item->urlParameters = $urlParameters;
        $item->urlDbInserts = $urlDbInserts;
        $item->joinedTables = $joinedTables;

        return $item;
    }

    private function queryJoinedTables($record, $joinedTables, $idFieldName) {
        $db = Factory::getContainer()->get('DatabaseDriver');

        foreach ($joinedTables as $joinedTable) {
            if ($joinedTable['connectionType'] == "NToOne") {
                $joinedTableQuery = $db->getQuery(true);

                $foreignKeyName = $joinedTable['NToOne-foreignKey'];
                $joinedIdFieldName = $joinedTable['NToOne-remoteId'];
                
                if ($record->{$foreignKeyName} == "") {
                    $record->{$joinedTable['name']} = [];
                    continue;
                }

                $selectedFields = array_map('trim', explode(',', $joinedTable['foreignFields']));

                if (!in_array($joinedIdFieldName, $selectedFields)) {
                    $selectedFields[] = $joinedIdFieldName;
                }

                $joinedTableQuery->select($db->quoteName($selectedFields));
                $joinedTableQuery->from($db->quoteName('#__' . $joinedTable['name']));
                $joinedTableQuery->where($db->quoteName($joinedIdFieldName) . ' = ' . $record->{$foreignKeyName});

                $db->setQuery($joinedTableQuery);
                $data = $db->loadObjectList();
    
                $record->{$joinedTable['alias']} = $data;
            } else if ($joinedTable['connectionType'] == "OneToN") {
                $joinedTableQuery = $db->getQuery(true);

                $foreignKeyName = $joinedTable['OneToN-foreignKey'];

                $selectedFields = array_map('trim', explode(',', $joinedTable['foreignFields']));

                $joinedTableQuery->select($db->quoteName($selectedFields));
                $joinedTableQuery->from($db->quoteName('#__' . $joinedTable['name']));
                $joinedTableQuery->where($db->quoteName($foreignKeyName) . ' = ' . $record->{$idFieldName});

                $db->setQuery($joinedTableQuery);
                $data = $db->loadObjectList();
    
                $record->{$joinedTable['alias']} = $data;
            } else if ($joinedTable['connectionType'] == "NToN") {
                $joinedTableQuery = $db->getQuery(true);

                $intermediateTableName = $joinedTable['NToN-intermediateTable'];
                $localForeignKeyField = 'interm.' . $joinedTable['NToN-intermediateLocalKey'];
                $remoteForeignKeyField = 'interm.' . $joinedTable['NToN-intermediateRemoteKey'];
                $remoteIdField = 'remote.' . $joinedTable['NToN-remoteId'];

                $selectedFields = [$localForeignKeyField, $remoteForeignKeyField];

                foreach (array_map('trim', explode(',', $joinedTable['foreignFields'])) as $field) {
                    $selectedFields[] = 'remote.' . $field;
                }

                if (!in_array($remoteIdField, $selectedFields)) {
                    $selectedFields[] = $remoteIdField;
                }

                $joinedTableQuery->select($db->quoteName($selectedFields));
                $joinedTableQuery->from($db->quoteName('#__' . $intermediateTableName, 'interm'));
                $joinedTableQuery->where($db->quoteName($localForeignKeyField) . ' = ' . $record->{$idFieldName});
                $joinedTableQuery->join('INNER', $db->quoteName('#__' . $joinedTable['name'], 'remote') . ' ON ' . $db->quoteName($remoteIdField) . ' = ' . $db->quoteName($remoteForeignKeyField));
                
                $db->setQuery($joinedTableQuery);
                $data = $db->loadObjectList();
                
                $record->{$joinedTable['alias']} = $data;
            }
        }
    }

    private function queryJoinedTableOptions($joinedTable) {
        $db = Factory::getContainer()->get('DatabaseDriver');

        if ($joinedTable['showInForm'] == false) {
            return [];
        }

        if ($joinedTable['connectionType'] == "NToOne") {
            $joinedTableOptionsQuery = $db->getQuery(true);

            $idFieldName = $joinedTable['NToOne-remoteId'];

            $joinedTableOptionsQuery->select($db->quoteName([$idFieldName, $joinedTable['displayField']]));
            $joinedTableOptionsQuery->from($db->quoteName('#__' . $joinedTable['name']));

            $db->setQuery($joinedTableOptionsQuery);
            $data = $db->loadObjectList();

            return $data;
        } else if ($joinedTable['connectionType'] == "NToN") {
            $joinedTableOptionsQuery = $db->getQuery(true);

            $remoteIdField = $joinedTable['NToN-remoteId'];

            $joinedTableOptionsQuery->select($db->quoteName([$remoteIdField, $joinedTable['displayField']]));
            $joinedTableOptionsQuery->from($db->quoteName('#__' . $joinedTable['name']));

            $db->setQuery($joinedTableOptionsQuery);
            $data = $db->loadObjectList();

            return $data;
        }
    }
}
