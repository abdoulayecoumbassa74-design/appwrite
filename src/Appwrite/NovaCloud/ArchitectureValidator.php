<?php

namespace Appwrite\NovaCloud;

final class ArchitectureValidator
{
    /**
     * @param list<string> $requiredFiles
     * @param list<string> $expectedRoutes
     * @param list<string> $expectedTransportCapabilities
     */
    public function __construct(
        private readonly string $root,
        private readonly array $requiredFiles = self::REQUIRED_FILES,
        private readonly array $expectedRoutes = self::EXPECTED_ROUTES,
        private readonly array $expectedTransportCapabilities = self::EXPECTED_TRANSPORT_CAPABILITIES,
    ) {
    }

    private const REQUIRED_FILES = [
        'gateway/api-gateway/Caddyfile',
        'gateway/config/routes.json',
        'gateway/config/rbac.json',
        'gateway/config/rate-limits.yaml',
        'gateway/edge-auth/policy.rego',
        'gateway/load-balancer/traefik-dynamic.yaml',
        'services/catalog.yaml',
        'internal/tenancy/policy.rego',
        'proto/novacloud/compute.proto',
        'proto/novacloud/vm.proto',
        'helm/novacloud/Chart.yaml',
        'helm/novacloud/values.yaml',
        'helm/novacloud/templates/api-gateway.yaml',
        'kubernetes/base/api-gateway.yaml',
        'opentofu/main.tf',
        'opentofu/providers.tf',
        'dashboard/config/navigation.json',
        'README.novacloud.md',
    ];

    private const EXPECTED_ROUTES = [
        '/api/v1/auth',
        '/api/v1/compute',
        '/api/v1/vm',
        '/api/v1/network',
        '/api/v1/storage',
        '/api/v1/kubernetes',
        '/api/v1/database',
    ];

    private const EXPECTED_TRANSPORT_CAPABILITIES = [
        'http2',
        'websocket',
        'grpc',
        'requestLogging',
    ];

    /**
     * @return list<string>
     */
    public function failures(): array
    {
        $failures = [];

        foreach ($this->requiredFiles as $file) {
            if (!is_file($this->root . DIRECTORY_SEPARATOR . $file)) {
                $failures[] = 'file:' . $file;
            }
        }

        $routesFile = $this->root . '/gateway/config/routes.json';
        if (!is_file($routesFile)) {
            return $failures;
        }

        $routes = json_decode((string) file_get_contents($routesFile), true);
        if (!is_array($routes)) {
            $failures[] = 'json:gateway/config/routes.json';

            return $failures;
        }

        $actualRoutes = array_column($routes['routes'] ?? [], 'path');
        foreach ($this->expectedRoutes as $route) {
            if (!in_array($route, $actualRoutes, true)) {
                $failures[] = 'route:' . $route;
            }
        }

        $transport = $routes['transport'] ?? [];
        foreach ($this->expectedTransportCapabilities as $capability) {
            if (($transport[$capability] ?? false) !== true) {
                $failures[] = 'transport:' . $capability;
            }
        }

        return $failures;
    }

    public function isValid(): bool
    {
        return $this->failures() === [];
    }
}
