<?php

namespace App\Console\Commands;

use App\Services\WorldKeep\Mcp\Server;
use Illuminate\Console\Command;

class WorldKeepMcpCommand extends Command
{
    protected $signature = 'worldkeep:mcp';

    protected $description = 'Run WorldKeep MCP server on stdio (JSON-RPC)';

    public function handle(Server $server): int
    {
        return $server->runStdio();
    }
}
