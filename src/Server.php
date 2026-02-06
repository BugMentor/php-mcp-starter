<?php

namespace BugMentor\Mcp;

class Server
{
    private string $name;
    private string $version;
    private array $tools = [];

    public function __construct(string $name, string $version)
    {
        $this->name = $name;
        $this->version = $version;
    }

    public function registerTool(object $tool): void
    {
        $this->tools[$tool->getName()] = $tool;
    }

    /**
     * @param resource|null $stdin
     * @param resource|null $stdout
     * @param resource|null $stderr
     */
    public function run($stdin = null, $stdout = null, $stderr = null): void
    {
        $stdin = $stdin ?? STDIN;
        $stdout = $stdout ?? STDOUT;
        $stderr = $stderr ?? STDERR;

        // Log startup to stderr so it doesn't break JSON-RPC on stdout
        fwrite($stderr, "Starting MCP Server: {$this->name} v{$this->version}...\n");

        while (true) {
            $input = fgets($stdin);
            if ($input === false) break;

            $request = json_decode($input, true);
            if (!$request || !isset($request['method'])) continue;

            try {
                $response = $this->handleRequest($request);
                if ($response) {
                    $this->sendResponse($response, $stdout);
                }
            } catch (\Throwable $e) {
                fwrite($stderr, "Error: " . $e->getMessage() . "\n");
            }
        }
    }

    private function handleRequest(array $request): ?array
    {
        $method = $request['method'];
        $id = $request['id'] ?? null;

        return match ($method) {
            'initialize' => $this->handleInitialize($id),
            'notifications/initialized' => null, // Acknowledge without response
            'tools/list' => $this->handleListTools($id),
            'tools/call' => $this->handleCallTool($id, $request['params'] ?? []),
            default => [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => ['code' => -32601, 'message' => 'Method not found']
            ]
        };
    }

    private function handleInitialize($id): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [
                    'tools' => ['listChanged' => false],
                ],
                'serverInfo' => [
                    'name' => $this->name,
                    'version' => $this->version,
                ],
            ],
        ];
    }

    private function handleListTools($id): array
    {
        $toolDefinitions = [];
        foreach ($this->tools as $tool) {
            $toolDefinitions[] = $tool->getDefinition();
        }

        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'tools' => $toolDefinitions,
            ],
        ];
    }

    private function handleCallTool($id, array $params): array
    {
        $name = $params['name'];
        $args = $params['arguments'] ?? [];

        if (!isset($this->tools[$name])) {
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => ['code' => -32601, 'message' => 'Tool not found']
            ];
        }

        try {
            $resultContent = $this->tools[$name]->execute($args);
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'content' => [['type' => 'text', 'text' => $resultContent]],
                    'isError' => false,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'content' => [['type' => 'text', 'text' => "Error: " . $e->getMessage()]],
                    'isError' => true,
                ],
            ];
        }
    }

    /**
     * @param resource $stdout
     */
    private function sendResponse(array $response, $stdout = null): void
    {
        $stdout = $stdout ?? STDOUT;
        fwrite($stdout, json_encode($response) . "\n");
    }
}
