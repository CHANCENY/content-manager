<?php

namespace Simp\Core\modules\structures\views;

use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\modules\database\Database;
use Simp\Core\modules\user\current_user\CurrentUser;
use Symfony\Component\HttpFoundation\Request;

class Display
{
    protected array $display = [];
    protected array $view = [];
    protected string $display_id = '';
    protected array $placeholders = [];
    protected string $view_display_query = '';
    protected array $view_display_results = [];

    public function __construct(string $display_id)
    {
        $display = ViewsManager::viewsManager()->getDisplay($display_id);
        if (!empty($display)) {
            $this->display = $display;
            $this->display_id = $display_id;
            $this->view = ViewsManager::viewsManager()->getView($display['view']);
        }
    }

    public function isDisplayExists(): bool
    {
        return !empty($this->display);
    }

    public function isViewExists(): bool
    {
        return !empty($this->view);
    }

    /**
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function isAccessible(): bool
    {
        $permissions = $this->display['permission'] ?? [];
        $permissions = is_array($permissions) ? $permissions : [$permissions];
        $current_user = CurrentUser::currentUser()?->getUser()?->getRoles() ?? [];
        if (!empty($current_user)) {
            $roles = array_map(function ($item) {
                return $item->getRoleName();
            }, $current_user);
            return !empty(array_intersect($roles, $permissions));
        }
        return in_array('anonymous', $permissions);
    }

    public function prepareQuery(Request $request): void
    {
        $generateMySQLQuery = function(Request $request, $fields, $sortCriteria = [], $filterCriteria = []): string
        {
            $selectFields = [];
            $fromTables = [];
            $joins = [];
            $default_filters = [];

            foreach ($fields as $key => $details) {
               $field_name = $details['field'];
               $default_filters['node_data.bundle'][] = $details['content_type'] !== 'node' ? $details['content_type'] : null;
               $table =  $details['content_type'] !== 'node' ?"node__$field_name" : 'node_data';
               $fromTables[] = $table;
               $selectFields[] = $details['content_type'] !== 'node' ? "{$table}.{$field_name}__value AS {$field_name}" : "{$table}.{$field_name} AS {$field_name}";
            }
            $fromTables = array_unique($fromTables);
            if (($key = array_search('node_data', $fromTables)) !== false) {
                unset($fromTables[$key]);
                array_unshift($fromTables, 'node_data');
            }
            $default_filters['node_data.bundle'] = array_unique(array_filter($default_filters['node_data.bundle']));
            $selectFields = array_unique(array_filter($selectFields));
            if (array_search('node_data.nid', $selectFields) === false) {
                $new_select = $selectFields;
                $selectFields = ['node_data.nid AS nid', ...$new_select];

            }

            // Make basic select join query.
            $join_statement_line = "SELECT ".implode(', ', $selectFields)." FROM {$fromTables[0]}";
            for ($i = 1; $i < count($fromTables); $i++) {
                $join_statement_line .= " LEFT JOIN " . $fromTables[$i] . " ON " . $fromTables[$i] . ".nid = " . $fromTables[0] . ".nid";
            }

            // Add the filters for where clause. Default filters.
            $where_line = "WHERE ";
            foreach ($default_filters as $key => $value) {
                if (is_array($value)) {
                    $value = implode(',', array_map(function ($v) { return "'$v'"; },$value));
                    $where_line .= " {$key} IN ({$value})";
                }
                else {
                    $where_line .= " {$key} = '{$value}'";
                }
            }
            $join_statement_line .= " {$where_line}";

            // TODO: add custom filters
            $custom_filters = [];
            foreach ($filterCriteria as $key => $details) {
                $field_name = $details['field'];
                $table = $details['content_type'] !== 'node' ?"node__$field_name" : 'node_data';
                $conjunction = $details['settings']['conjunction'] ?? 'AND';
                $param_name = $details['settings']['param_name'] ?? '';
                $field_name = $details['content_type'] !== 'node' ? "{$field_name}__value" : $field_name;
                $custom_filters[] = "{$table}.{$field_name} = :{$param_name}";
                $custom_filters[] = $conjunction;
                $param_value = null;
                if ($request->get($param_name))
                    $param_value =$request->get($param_name);
                elseif($request->request->get($param_name))
                    $param_value = $request->request->get($param_name);
                else
                    $param_value = json_decode($request->getContent(),true)[$param_name] ?? null;

                $this->placeholders[$param_name] = $param_value;
            }
            if (!empty($custom_filters)) {
                $custom_filters = array_slice($custom_filters, 0, count($custom_filters) - 1);
                $join_statement_line .= " AND (" . implode(', ', $custom_filters) . ")";
            }

            // add sort criteria
            if (!empty($sortCriteria)) {
                $sort_criteria = " GROUP BY node_data.nid ORDER BY ";
                foreach ($sortCriteria as $key => $details) {
                    $field_name = $details['field'];
                    $table = $details['content_type'] !== 'node' ?"node__$field_name" : 'node_data';
                    $action = $details['settings']['order_in'] ?? 'ASC';
                    $sort_criteria .= "{$table}.{$field_name} {$action}";
                }
                $join_statement_line .= " {$sort_criteria}";
            }
            else {
                $sort_criteria = " GROUP BY node_data.nid";
                $join_statement_line .= " {$sort_criteria}";
            }
            return $join_statement_line;

        };
        $this->view_display_query = $generateMySQLQuery($request,
            $this->display['fields'],
            $this->display['sort_criteria'],
            $this->display['filter_criteria']
        );
    }

    public function runDisplayQuery(): void
    {
        if (!empty($this->view_display_query)) {
            $statement = Database::database()->con()->prepare($this->view_display_query);
            if (!empty($this->placeholders)) {
                foreach ($this->placeholders as $key => $value) {
                    $statement->bindValue(':' . $key, $value);
                }
            }
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            dump($result);
            foreach ($result as $row) {

            }
        }

    }

    public static function display(string $display_id): Display
    {
        return new Display($display_id);
    }
}