<?php

namespace mint;

class AcpEntityManagementController
{
    protected $pageUrl;
    protected $actionUrl;
    protected $actionName;
    protected $columns = [];
    protected $dataColumns = [];
    protected $foreignKeyData = [];
    protected $entityOptions = [];
    protected $listManagerOptions = [];
    protected $insertAllowed = true;
    protected $filterAllowed = false;
    protected $insertController;

    /* @var DbEntityRepository */
    protected $dbRepository;

    protected $db;
    protected $mybb;
    protected $lang;
    protected $page;
    protected $sub_tabs;

    public function __construct(string $actionName, string $dbRepository)
    {
        global $db, $mybb, $lang, $page, $sub_tabs, $pageUrl;

        $this->db = $db;
        $this->mybb = $mybb;
        $this->lang = $lang;
        $this->page = $page;
        $this->sub_tabs = $sub_tabs;

        $this->pageUrl = $pageUrl;
        $this->actionName = $actionName;
        $this->actionUrl = $this->pageUrl . '&action=' . $this->actionName;
        $this->dbRepository = $dbRepository::with($this->db);

        $this->setColumns($this->dbRepository::COLUMNS);
    }

    public function run()
    {
        if ($this->mybb->request_method == 'post' && $this->mybb->get_input('add')) {
            if ($this->insertAllowed) {
                if (is_callable($this->insertController)) {
                    ($this->insertController)();
                } else {
                    $this->defaultInsertController();
                }
            }
        } elseif (in_array($this->mybb->get_input('option'), array_keys($this->entityOptions))) {
            $entityOption = &$this->entityOptions[$this->mybb->get_input('option')];

            if (isset($entityOption['controller']) && is_callable($entityOption['controller'])) {
                $entityOption['controller']();
            } else {
                $this->{'default' . ucfirst($this->mybb->get_input('option')) . 'OptionController'}();
            }
        } else {
            $this->page->output_header($this->actionLang());
            $this->page->output_nav_tabs($this->sub_tabs, $this->actionName);

            $this->outputList();

            if ($this->insertAllowed) {
                echo '<br />';

                $this->outputAddForm();
            }

            if ($this->filterAllowed) {
                echo '<br />';

                $this->outputFilterForm();
            }

            $this->page->output_footer();
        }
    }

    public function insertAllowed(bool $status): void
    {
        $this->insertAllowed = $status;
    }

    public function listManagerOptions(array $options): void
    {
        $this->listManagerOptions = $options;
    }

    public function setColumns(array $columns): void
    {
        $dataColumns = [];

        foreach ($columns as $columnName => &$column) {
            if (!isset($column['customizable'])) {
                $column['customizable'] = $columnName == 'id' ? false : true;
            }

            if (!isset($column['listed'])) {
                $column['listed'] = true;
            }

            if (!isset($column['filter'])) {
                $column['filter'] = false;
            }

            if (!isset($column['presenter'])) {
                $column['presenter'] = null;
            }

            if (!isset($column['outputHandler'])) {
                $column['outputHandler'] = null;
            }

            if ($column['listed']) {
                if (isset($column['dataColumn'])) {
                    $dataColumns[$column['dataColumn']] = $columnName;
                } else {
                    $dataColumns[] = $columnName;
                }
            }

            if ($column['filter']) {
                $this->filterAllowed = true;
            }
        }

        $this->columns = $columns;
        $this->dataColumns = $dataColumns;
    }

    public function addForeignKeyData(array $foreignKeyData): void
    {
        $this->foreignKeyData = array_merge($this->foreignKeyData, $foreignKeyData);
    }

    public function addEntityOptions(array $entityOptions): void
    {
        $this->entityOptions = array_merge($this->entityOptions, $entityOptions);
    }

    public function outputAddForm()
    {
        $this->outputForm(
            'add',
            $this->actionUrl . '&amp;add=1',
            $this->actionLang('add')
        );
    }

    public function outputFilterForm()
    {
        $this->outputForm(
            'filter',
            $this->actionUrl,
            $this->actionLang('filter')
        );
    }

    public function outputList()
    {
        $whereConditions = $this->getFilterConditions();

        if ($whereConditions) {
            $where = 'WHERE ' . $whereConditions . ' ';

            $itemsNum = $this->db->fetch_field(
                $this->dbRepository->get('COUNT(id) AS n', $where, array_fill_keys(array_keys($this->foreignKeyData), [])),
                'n'
            );
        } else {
            $where = null;

            $itemsNum = $this->dbRepository->count($whereConditions);
        }

        $defaultOptions = [
            'mybb' => $this->mybb,
            'baseurl' => $this->actionUrl,
            'order_columns' => $this->dataColumns,
            'order_dir' => 'asc',
            'items_num' => $itemsNum,
            'per_page' => 20,
        ];

        $listManager = new ListManager(
            array_merge($defaultOptions, $this->listManagerOptions)
        );

        $query = $this->dbRepository->get('*', $where . ' ' . $listManager->sql(), $this->foreignKeyData);

        $table = new \Table;

        $listColumnCount = 0;

        foreach ($this->getListedColumns() as $columnName => $column) {
            $listColumnCount++;

            $table->construct_header(
                $listManager->link($columnName, $this->actionLang($columnName)),
                [
                    'class' => 'align_center',
                ]
            );
        }

        if ($this->entityOptions) {
            $listColumnCount++;

            $table->construct_header($this->lang->options, [
                'width' => '15%',
                'class' => 'align_center',
            ]);
        }

        if ($itemsNum > 0) {
            $i = 0;

            while ($row = $this->db->fetch_array($query)) {
                $i++;

                foreach ($this->getListedColumns() as $columnName => $column) {
                    if (is_callable($column['outputHandler'])) {
                        $value = $column['outputHandler']($row);
                    } else {
                        if (!empty($column['dataColumn'])) {
                            $key = $column['dataColumn'];
                        } else {
                            $key = $columnName;
                        }

                        if (is_callable($column['presenter'])) {
                            $value = $column['presenter']($row[$key], $row);
                        } else {
                            $value = \htmlspecialchars_uni(
                                $row[$key]
                            );
                        }
                    }

                    $table->construct_cell($value, ['class' => 'align_center']);
                }

                if ($this->entityOptions) {
                    $popup = new \PopupMenu('options_' . $i, $this->lang->options);

                    foreach ($this->entityOptions as $optionName => $option) {
                        $optionUrl = $this->actionUrl . '&amp;option=' . $optionName . '&amp;id=' . (int)$row['id'];

                        if ($optionName == 'update') {
                            $optionTitle = $this->lang->edit;
                        } elseif ($optionName == 'delete') {
                            $optionTitle = $this->lang->delete;
                        } else {
                            $optionTitle = $this->actionLang('option_' . $optionName);
                        }

                        $popup->add_item($optionTitle, $optionUrl);
                    }

                    $options = $popup->fetch();
                    $table->construct_cell($options, ['class' => 'align_center']);
                }

                $table->construct_row();
            }
        } else {
            $table->construct_cell($this->actionLang('empty'), [
                'colspan' => $listColumnCount,
                'class' => 'align_center'
            ]);
            $table->construct_row();
        }

        $table->output($this->lang->sprintf(
            $this->actionLang('list'),
            $itemsNum
        ));

        echo $listManager->pagination();
    }

    protected function getFilterConditions(): ?string
    {
        $conditions = [];

        if (isset($this->mybb->input['filter'])) {
            $inputFilterValues = $this->mybb->get_input('filter', \MyBB::INPUT_ARRAY);

            foreach ($this->columns as $columnName => $column) {
                if ($column['filter'] && isset($inputFilterValues[$columnName]) && $inputFilterValues[$columnName] !== '') {
                    $operator = '=';
                    $value = $inputFilterValues[$columnName];

                    if (isset($column['dataColumn'])) {
                        if (isset($column['filterConditionColumn'])) {
                            $queryColumn = $column['filterConditionColumn'];

                            $conditions[] = $queryColumn . ' ' . $operator . " '" . $this->db->escape_string($value) . "'";
                        }
                    } else {
                        $conditions[] = $this->dbRepository->getEscapedComparison($columnName, $operator, $value);
                    }
                }
            }
        }

        $conditionsString = implode(' AND ', $conditions);

        return $conditionsString;
    }

    protected function getValidationErrors(array $inputData): array
    {
        $errors = [];

        foreach ($this->columns as $columnName => $column) {
            if (!empty($column['validator']) && is_callable($column['validator'])) {
                $errors = array_merge($errors, $column['validator']($inputData[$columnName] ?? null));
            }
        }

        return $errors;
    }

    protected function defaultInsertController(): void
    {
        if ($this->mybb->request_method == 'post') {
            $data = array_intersect_key($this->mybb->input, $this->getCustomizableColumns());

            $errors = $this->getValidationErrors($data);

            if ($errors) {
                \flash_message($this->getFormattedErrors($errors), 'error');
            } else {
                $this->dbRepository->insert($data);

                \flash_message($this->actionLang('added'), 'success');
            }

            \admin_redirect($this->actionUrl);
        }
    }

    protected function defaultUpdateOptionController(): void
    {
        $entity = $this->dbRepository->getById(
            $this->mybb->get_input('id', \MyBB::INPUT_INT)
        );

        if ($entity) {
            if ($this->mybb->request_method == 'post') {
                $data = array_intersect_key($this->mybb->input, $this->getCustomizableColumns());

                $errors = $this->getValidationErrors($data);

                if ($errors) {
                    \flash_message($this->getFormattedErrors($errors), 'error');
                } else {
                    $this->dbRepository->updateById($entity['id'], $data);

                    \flash_message($this->actionLang('updated'), 'success');
                }

                \admin_redirect($this->actionUrl);
            } else {
                $this->page->output_header($this->actionLang());
                $this->page->output_nav_tabs($this->sub_tabs, $this->actionName);

                $this->outputForm(
                    'edit',
                    $this->actionUrl . '&amp;option=update&amp;id=' . (int)$entity['id'],
                    $this->actionLang('update'),
                    $entity
                );

                $this->page->output_footer();
            }
        }
    }

    protected function defaultDeleteOptionController(): void
    {
        $entity = $this->dbRepository->getById(
            $this->mybb->get_input('id', \MyBB::INPUT_INT)
        );

        if ($entity) {
            if ($this->mybb->request_method == 'post') {
                if ($this->mybb->get_input('no')) {
                    \admin_redirect($this->actionUrl);
                } else {
                    $this->dbRepository->deleteById($entity['id']);

                    \flash_message($this->actionLang('deleted'), 'success');
                    \admin_redirect($this->actionUrl);
                }
            } else {
                $this->page->output_confirm_action(
                    $this->actionUrl . '&amp;option=delete&amp;id=' . (int)$entity['id'],
                    $this->actionLang('delete_confirm_message'),
                    $this->actionLang('delete_confirm_title')
                );
            }
        }
    }

    protected function outputForm(string $mode, string $url, string $title, array $entity = []): void
    {
        if ($mode == 'filter') {
            $columns = $this->getFilterColumns();
        } else {
            $columns = $this->columns;
        }

        $form = new \Form($url, 'post');

        $form_container = new \FormContainer($title);

        foreach ($columns as $columnName => $column) {
            if ($mode == 'filter' || $column['customizable'] === true) {
                if ($mode == 'filter') {
                    $name = 'filter[' . $columnName . ']';
                } else {
                    $name = $columnName;
                }

                if (!empty($column['formElement'])) {
                    $formElement = $column['formElement']($form, $entity, $name);
                } else {
                    if (isset($column['formMethod'])) {
                        $formMethod = $column['formMethod'];
                    } else {
                        $formMethod = 'generate_text_box';
                    }

                    if ($entity) {
                        $value = $entity[$columnName];
                    } else {
                        $value = $column['default'] ?? false;
                    }

                    $formElement = $form->$formMethod($name, $value);
                }

                $form_container->output_row(
                    $this->actionLang($columnName),
                    $this->actionLang($columnName . '_description'),
                    $formElement
                );
            }
        }

        $form_container->end();

        $buttons[] = $form->generate_submit_button($this->namespaceLang('submit'));

        $form->output_submit_wrapper($buttons);
        $form->end();
    }

    protected function getFormattedErrors(array $errors): string
    {
        global $lang;

        $output = '';

        if ($errors) {
            $output .= '<p>' . $lang->encountered_errors . '</p>';
            $output .= '<ul>';

            foreach ($errors as $name => $data) {
                $errorString = $lang->sprintf($this->actionLang('error_' . $name), ...$data);

                $output .= '<li>' . $errorString . '</li>';
            }

            $output .= '</ul>';
        }

        return $output;
    }

    protected function namespaceLang(?string $name = null): string
    {
        $name = __NAMESPACE__ . '_admin_' . $name;

        return $this->lang->$name ?? '{' . $name . '}';
    }

    protected function actionLang(?string $name = null): string
    {
        $name = __NAMESPACE__ . '_admin_' . $this->actionName . ($name ? '_' . $name : null);

        return $this->lang->$name ?? '{' . $name . '}';
    }

    protected function getListedColumns(): array
    {
        return array_filter($this->columns, function ($column) {
            return $column['listed'] === true;
        });
    }

    protected function getCustomizableColumns(): array
    {
        return array_filter($this->columns, function ($column) {
            return $column['customizable'] === true;
        });
    }

    protected function getFilterColumns(): array
    {
        return array_filter($this->columns, function ($column) {
            return $column['filter'] === true;
        });
    }
}
