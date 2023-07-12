<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ListenerMakeCommand as BaseListenerMakeCommand;

/**
 * Custom make:listener command.
 */

class ListenerMakeCommand extends BaseListenerMakeCommand
{
    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
                        ? $customPath
                        : __DIR__.$stub;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('queued')) {
            return $this->option('event')
                        ? $this->resolveStubPath('/stubs/listener-queued.stub')
                        : $this->resolveStubPath('/stubs/listener-queued-duck.stub');
        }

        return $this->option('event')
                    ? $this->resolveStubPath('/stubs/listener.stub')
                    : $this->resolveStubPath('/stubs/listener-duck.stub');
    }
}
