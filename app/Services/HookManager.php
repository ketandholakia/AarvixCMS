<?php

namespace App\Services;

class HookManager
{
    protected $actions = [];
    protected $filters = [];

    // --- Actions ---

    public function addAction(string $hook, callable $callback, int $priority = 10)
    {
        $this->actions[$hook][$priority][] = $callback;
    }

    public function doAction(string $hook, ...$args)
    {
        if (!isset($this->actions[$hook])) {
            return;
        }

        ksort($this->actions[$hook]);

        foreach ($this->actions[$hook] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                call_user_func_array($callback, $args);
            }
        }
    }

    // --- Filters ---

    public function addFilter(string $hook, callable $callback, int $priority = 10)
    {
        $this->filters[$hook][$priority][] = $callback;
    }

    public function applyFilters(string $hook, $value, ...$args)
    {
        if (!isset($this->filters[$hook])) {
            return $value;
        }

        ksort($this->filters[$hook]);

        foreach ($this->filters[$hook] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                // The first argument passed to a filter callback is always the value being filtered.
                $callbackArgs = array_merge([$value], $args);
                $value = call_user_func_array($callback, $callbackArgs);
            }
        }

        return $value;
    }
}
