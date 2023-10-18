<?php


namespace H2o\PermissionManager\Manager;


use GuzzleHttp\Client;

class RemoteRepository extends StoreAbstract
{
    protected $url;

    protected $server;

    public function __construct($logger, $scopes, $url, $server)
    {
        parent::__construct($logger, $scopes);
        $this->url = $url . '/permission/sync';
        $this->server = $server;
    }

    public function getLastUpdateTime()
    {
        return $this->call('getLastUpdateTime');
    }

    public function saveChanges($changes)
    {
        if (empty($changes)) {
            return 0;
        }
        $this->call('update', compact('changes'));
        return count($changes);
    }

    protected function call($cation, $options = [])
    {
        $options['action'] = $cation;
        $options['server'] = $this->server;
        return (new Client)->post($this->url, ['json' => $options])->getBody()->getContents();
    }
}
