# Run full test suite via Docker (Pest + manual-test + exploratory).
# Requires: Docker. Usage: .\test-everything.ps1
$ErrorActionPreference = 'Stop'
$root = $PSScriptRoot
Set-Location $root
docker build -f Dockerfile.test -t php-mcp-test .
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
docker run --rm -v "${root}:/app" -w /app php-mcp-test
exit $LASTEXITCODE
