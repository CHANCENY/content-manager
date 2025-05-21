<?php

namespace Simp\Core\modules\database;
use Symfony\Component\HttpFoundation\Request;
use Simp\Core\modules\services\Service;

class DatabaseRecorder {

    public function __construct(string $query_run, int|float $executed_time)
    {
        if (session_id()) {
             $request = Service::serviceManager()->request;
            if (empty($GLOBALS[session_id()])) {
                $GLOBALS[session_id()] = [
                    $request->getRequestUri() => [
                       [
                         'query' => $query_run,
                        'execute_time' => $executed_time,
                       ]
                    ]
                ];
            }
            else {
                $GLOBALS[\session_id()][ $request->getRequestUri()][] = [
                    'query' => $query_run,
                    'execute_time' => $executed_time,
                ];
            }
        }
    }

    public function getActivity(string $current_uri): array {
        return $GLOBALS[session_id()][$current_uri] ?? [];
    }

    public static function factory(string $query, int|float $time): DatabaseRecorder {
        return new self($query, $time);
    }
}
