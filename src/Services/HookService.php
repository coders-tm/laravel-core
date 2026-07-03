<?php

namespace Coderstm\Services;

/**
 * Hook System Service
 *
 * WordPress-style hooks system with full multiple arguments support.
 * Based on the WordPress plugin API and millat/laravel-hooks package.
 */
class HookService
{
    protected array $filters = [];

    protected array $merged_filters = [];

    protected array $current_filter = [];

    public function add_filter(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        $idx = $this->hookUniqueId($tag, $callback, $priority);
        $this->filters[$tag][$priority][$idx] = [
            'function' => $callback,
            'accepted_args' => $accepted_args,
        ];
        unset($this->merged_filters[$tag]);

        return true;
    }

    public function apply_filters(string $tag, $value, ...$args)
    {
        if (isset($this->filters['all'])) {
            $this->current_filter[] = $tag;
            $all_args = func_get_args();
            $this->callAllHooks($all_args);
        }

        if (! isset($this->filters[$tag]) || empty($this->filters[$tag])) {
            if (isset($this->filters['all'])) {
                array_pop($this->current_filter);
            }

            return $value;
        }

        if (! isset($this->filters['all'])) {
            $this->current_filter[] = $tag;
        }

        if (! isset($this->merged_filters[$tag])) {
            ksort($this->filters[$tag]);
            $this->merged_filters[$tag] = true;
        }

        reset($this->filters[$tag]);

        $filter_args = array_merge([$value], $args);

        do {
            $current_priority_filters = current($this->filters[$tag]);
            if ($current_priority_filters === false) {
                break;
            }

            foreach ($current_priority_filters as $the_) {
                if (! is_null($the_['function'])) {
                    $filter_args[0] = $value;
                    $value = call_user_func_array(
                        $the_['function'],
                        array_slice($filter_args, 0, (int) $the_['accepted_args'])
                    );
                }
            }
        } while (next($this->filters[$tag]) !== false);

        array_pop($this->current_filter);

        return $value;
    }

    public function add_action(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        return $this->add_filter($tag, $callback, $priority, $accepted_args);
    }

    public function do_action(string $tag, ...$args): void
    {
        if (isset($this->filters['all'])) {
            $this->current_filter[] = $tag;
            $all_args = func_get_args();
            $this->callAllHooks($all_args);
        }

        if (! isset($this->filters[$tag]) || empty($this->filters[$tag])) {
            if (isset($this->filters['all'])) {
                array_pop($this->current_filter);
            }

            return;
        }

        if (! isset($this->filters['all'])) {
            $this->current_filter[] = $tag;
        }

        if (! isset($this->merged_filters[$tag])) {
            ksort($this->filters[$tag]);
            $this->merged_filters[$tag] = true;
        }

        reset($this->filters[$tag]);

        do {
            $current_priority_actions = current($this->filters[$tag]);
            if ($current_priority_actions === false) {
                break;
            }

            foreach ((array) $current_priority_actions as $the_) {
                if (! is_null($the_['function'])) {
                    call_user_func_array(
                        $the_['function'],
                        array_slice($args, 0, (int) $the_['accepted_args'])
                    );
                }
            }
        } while (next($this->filters[$tag]) !== false);

        array_pop($this->current_filter);
    }

    protected function hookUniqueId(string $tag, $function, $priority)
    {
        static $filter_id_count = 0;

        if (is_string($function)) {
            return $function;
        }

        if (is_object($function)) {
            $function = [$function, ''];
        } else {
            $function = (array) $function;
        }

        if (is_object($function[0])) {
            if (function_exists('spl_object_hash')) {
                return spl_object_hash($function[0]).$function[1];
            } else {
                $obj_idx = get_class($function[0]).$function[1];
                if (! isset($function[0]->filter_id)) {
                    if ($priority === false) {
                        return false;
                    }
                    $obj_idx .= isset($this->filters[$tag][$priority]) ?
                        count((array) $this->filters[$tag][$priority]) : $filter_id_count;
                    $function[0]->filter_id = $filter_id_count;
                    $filter_id_count++;
                } else {
                    $obj_idx .= $function[0]->filter_id;
                }

                return $obj_idx;
            }
        } elseif (is_string($function[0])) {
            return $function[0].$function[1];
        }

        return false;
    }

    protected function callAllHooks(array $args): void
    {
        reset($this->filters['all']);
        do {
            foreach ((array) current($this->filters['all']) as $the_) {
                if (! is_null($the_['function'])) {
                    call_user_func_array($the_['function'], $args);
                }
            }
        } while (next($this->filters['all']) !== false);
    }
}
