<?php

namespace core;

use \Exception;

/**
 *
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 7/29/17
 * @package politics
 */
class Shell {

    public $command;
    public $stdout;
    public $stderr;
    public $status;

    /**
     * Shell constructor.
     * @param $cmd
     * @throws Exception
     */
    public static function exec($cmd) {
        try {
            $self = new self();
            $self->command = $cmd;

            $descriptorspec = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $handle = proc_open("bash", $descriptorspec, $pipes);
            if (!is_resource($handle))
                throw new Exception("Command failed. $cmd");

            // execute command
            fwrite($pipes[0], "$cmd;");
            fclose($pipes[0]);

            $self->stdout = stream_get_contents($pipes[1]);
            $self->stderr = stream_get_contents($pipes[2]);

            do {
                $self->status = proc_get_status($handle);
            } while ($self->status['running']);

            fclose($pipes[1]);
            fclose($pipes[2]);

            if (!empty($stderr))
                throw new Exception($stderr);

            // validate the resource
            if ($self->status['exitcode'] > 0)
                throw new Exception(json_encode($self));

            proc_close($handle);

            return $self;

        } catch (Exception $e) {
            if (isset($handle) && is_resource($handle)) proc_close($handle);
            return $self;
        }
    }

}