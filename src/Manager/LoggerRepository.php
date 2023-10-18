<?php


namespace H2o\PermissionManager\Manager;


use H2o\PermissionManager\Manager\Store\AppendAction;
use H2o\PermissionManager\Manager\Store\CreateAction;
use H2o\PermissionManager\Manager\Store\DeleteAction;
use H2o\PermissionManager\Manager\Store\Recorder;
use H2o\PermissionManager\Manager\Store\RemoveAction;
use H2o\PermissionManager\Manager\Store\UpdateAction;

class LoggerRepository
{
    private $path;

    private $filename = 'permission.log';

    public function __construct($path)
    {
        $this->setPath($path);
    }

    public function setPath($path)
    {
        $this->path = base_path(trim($path, '/'));
        return $this;
    }

    public function getPath($full = true)
    {
        if ($full) {
            return $this->path . '/' . $this->filename;
        }
        return $this->path;
    }

    public function read($start = null, $end = null, $scopes = null)
    {
        $path = $this->getPath();
        $recorder = new Recorder($scopes);
        if (file_exists($path)) {
            $fs = fopen($path, 'r');
            while (!feof($fs)) {
                $row = trim(fgets($fs));
                if (!$row) continue;

                $action = $this->parseLine($row);

                if ($start && ($action->date < $start || ($action->date === $start && $start !== $end))) {
                    $recorder->call($action);
                    continue;
                }

                if ($end && $action->date > $end) break;
                $recorder->record($action);
            }
            fclose($fs);
        }
        return $recorder;
    }

    public function write($changes)
    {
        if (empty($changes)) {
            return;
        }
        $date = date('Y-m-d H:i:s');
        $fs = fopen($this->getPath(), 'a');
        foreach ($changes as $change) {
            fwrite($fs, $this->formatLine($date, $change));
        }
        fclose($fs);
    }

    protected function formatLine($date, $change)
    {
        $str = "[$date] {$change['action']}: {$change['name']}";
        if (!empty($change['data'])) {
            $str .= ' > ' . json_encode($change['data']);
        }
        return $str . PHP_EOL;
    }

    protected function parseLine($line)
    {
        preg_match('/^(?:\[([\d\s:-]{19})])(?:\s(\w+):)(?:\s([\w.-]+))(?:\s>\s(.+))?$/', $line, $match);

        if (empty($match[4])) {
            $data = null;
        } else {
            $data = json_decode($match[4], JSON_OBJECT_AS_ARRAY);
        }

        switch ($match[2]) {
            case 'CREATE':
                return new CreateAction($match[3], $match[1], $data);
            case 'UPDATE':
                return new UpdateAction($match[3], $match[1], $data);
            case 'DELETE':
                return new DeleteAction($match[3], $match[1], $data);
            case 'APPEND':
                return new AppendAction($match[3], $match[1], $data);
            case 'REMOVE':
                return new RemoveAction($match[3], $match[1], $data);
            default:
                return null;
        }
    }
}
