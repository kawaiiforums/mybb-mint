<?php

namespace mint;

class AcpEntityManagementController
{
    protected $pageUrl;
    protected $actionUrl;
    protected $actionName;
    protected $columns = [];
    protected $foreignKeyData = [];
    protected $entityOptions = [];
    protected $listManagerOptions = [];
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
            if (is_callable($this->insertController)) {
                ($this->insertController)();
            } else {
                $this->defaultInsertController();
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

            echo '<br />';

            $this->outputAddForm();
        }
    }

    public function setColumns(array $columns): void
    {
        foreach ($columns as $columnName => &$column) {
            if (!isset($column['customizable'])) {
                $column['customizable'] = $columnName == 'id' ? false : true;
            }

            if (!isset($column['listed'])) {
                $column['listed'] = true;
            }

            if (!isset($column['presenter'])) {
                $column['presenter'] = null;
            }

            if (!isset($column['outputHandler'])) {
                $column['outputHandler'] = null;
            }
        }

        $this->columns = $columns;
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
            $this->actionUrl . '&amp;add=1',
            $this->actionLang('add')
        );
    }

    public function outputList()
    {
        $itemsNum = $this->dbRepository->count();

        $defaultOptions = [
            'mybb' => $this->mybb,
            'baseurl' => $this->actionUrl,
            'order_columns' => array_keys($this->columns),
            'order_dir' => 'asc',
            'items_num' => $itemsNum,
            'per_page' => 20,
        ];

        $listManager = new ListManager(
            array_merge($defaultOptions, $this->listManagerOptions)
        );

        $query = $this->dbRepository->get('*', $listManager->sql(), $this->foreignKeyData);

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

        $table->output($this->actionLang());

        echo $listManager->pagination();
    }

    protected function defaultInsertController(): void
    {
        if ($this->mybb->request_method == 'post') {
            $data = array_intersect_key($this->mybb->input, $this->getCustomizableColumns());

            $this->dbRepository->insert($data);

            \flash_message($this->actionLang('added'), 'success');
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

                $this->dbRepository->updateById($entity['id'], $data);

                \flash_message($this->actionLang('updated'), 'success');
                \admin_redirect($this->actionUrl);
            } else {
                $this->page->output_header($this->actionLang());
                $this->page->output_nav_tabs($this->sub_tabs, $this->actionName);

                $this->outputForm(
                    $this->actionUrl . '&amp;option=update&amp;id=' . (int)$entity['id'],
                    $this->actionLang('update'),
                    $entity
                );
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

    protected function outputForm(string $url, string $title, array $entity = []): void
    {
        $form = new \Form($url, 'post');

        $form_container = new \FormContainer($title);

        foreach ($this->columns as $columnName => $column) {
            if ($column['customizable'] === true) {
                if (!empty($column['formElement'])) {
                    $formElement = $column['formElement']($form, $entity);
                } else {
                    $formMethod = $column['formMethod'] ?? 'generate_text_box';

                    if ($entity) {
                        $value = $entity[$columnName];
                    } else {
                        $value = false;
                    }

                    $formElement = $form->$formMethod($columnName, $value);
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
}
