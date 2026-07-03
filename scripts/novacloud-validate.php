<?php

$root = dirname(__DIR__);
$required = [
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

$missing = [];
foreach ($required as $file) {
    if (!is_file($root . DIRECTORY_SEPARATOR . $file)) {
        $missing[] = $file;
    }
}

$routes = json_decode((string) file_get_contents($root . '/gateway/config/routes.json'), true);
$expectedRoutes = [
    '/api/v1/auth',
    '/api/v1/compute',
    '/api/v1/vm',
    '/api/v1/network',
    '/api/v1/storage',
    '/api/v1/kubernetes',
    '/api/v1/database',
];
$actualRoutes = array_column($routes['routes'] ?? [], 'path');

foreach ($expectedRoutes as $route) {
    if (!in_array($route, $actualRoutes, true)) {
        $missing[] = 'route:' . $route;
    }
}

$transport = $routes['transport'] ?? [];
foreach (['http2', 'websocket', 'grpc', 'requestLogging'] as $capability) {
    if (($transport[$capability] ?? false) !== true) {
        $missing[] = 'transport:' . $capability;
    }
}

if ($missing !== []) {
    fwrite(STDERR, "NovaCloud validation failed:\n- " . implode("\n- ", $missing) . "\n");
    exit(1);
}

echo "NovaCloud architecture validation passed.\n";
