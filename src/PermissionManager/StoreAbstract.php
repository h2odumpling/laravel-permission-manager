<?php


namespace H2o\PermissionManager\PermissionManager;


abstract class StoreAbstract
{

    /**
     * @var array
     *  [server, scopes, exclude]
     */
    protected $scopes;

    /**
     * @var LoggerRepository
     */
    protected $logger;

    public function __construct($logger, $scopes)
    {
        $this->logger = $logger;
        $this->scopes = $scopes;
    }

    public function serverScopeName()
    {
        return $this->scopes[0];
    }

    abstract public function getLastUpdateTime();

    abstract public function saveChanges($changes);

    public function sync($time = null)
    {
        $time = $time ?? $this->getLastUpdateTime();
        return $this->saveChanges($this->getChanges($time));
    }

    public function rollback($time = null)
    {
        $lastUpdateTime = $this->getLastUpdateTime();
        if (!$lastUpdateTime) {
            return 0;
        }
        $time = $time == -1 ? null : ($time ?: $lastUpdateTime);
        return $this->saveChanges($this->getChanges($time, $lastUpdateTime, true));
    }

    public function getChanges($start = null, $end = null, $flip = false)
    {
        $recorder = $this->logger->read($start, $end, $this->scopes[1]);

        if ($flip) {
            return $recorder->flipChanges();
        }

        return $recorder->getChanges();
    }
}
