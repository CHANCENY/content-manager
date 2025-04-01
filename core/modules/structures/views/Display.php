<?php

namespace Simp\Core\modules\structures\views;

use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\modules\user\current_user\CurrentUser;

class Display
{
    protected array $display = [];
    protected array $view = [];
    protected string $display_id = '';

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

    public function prepareQuery(): string
    {
        $generateMySQLQuery = function($fields, $sortCriteria = [], $filterCriteria = []): string
        {
            $selectFields = [];
            $fromTables = [];
            $joins = [];

            foreach ($fields as $key => $details) {
                $parts = explode('|', $key);
                $tableAlias = $parts[0];
                $column = $parts[1];

                // Store table name
                if (!isset($fromTables[$tableAlias])) {
                    $fromTables[$tableAlias] = $details['content_type'] === 'node' ? 'node_data' : 'node__'. $details['content_type'];
                }

                // Prepare select fields
                $selectFields[] = "$tableAlias.$column AS {$details['field']}";
            }

            // Define FROM clause
            $fromClause = "FROM " . reset($fromTables) . " AS " . key($fromTables);

            // Handle multiple tables
            if (count($fromTables) > 1) {
                $primaryTable = key($fromTables);
                foreach ($fromTables as $alias => $table) {
                    if ($alias !== $primaryTable) {
                        $joins[] = "LEFT JOIN $table AS $alias ON $alias.uid = $primaryTable.uid";
                    }
                }
            }

            // Handle sorting
            $orderClause = "";
            if (!empty($sortCriteria)) {
                foreach ($sortCriteria as $key => $details) {
                    $parts = explode('|', $key);
                    $tableAlias = $parts[0];
                    $column = $parts[1];
                    $order = strtoupper($details['settings']['order_in'] ?? 'ASC');
                    $orderClause = "ORDER BY $tableAlias.$column $order";
                }
            }

            // Build the final query
            $query = "SELECT " . implode(', ', $selectFields) . " $fromClause";
            if (!empty($joins)) {
                $query .= " " . implode(" ", $joins);
            }
            if (!empty($orderClause)) {
                $query .= " $orderClause";
            }

            return $query;
        };
        return $generateMySQLQuery($this->display['fields'], $this->display['sort_criteria'], $this->display['filter_criteria']);
    }

    public static function display(string $display_id): Display
    {
        return new Display($display_id);
    }
}