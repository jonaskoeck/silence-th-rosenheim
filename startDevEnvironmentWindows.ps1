if (-not (docker info 2>$null)) {
    Write-Error "Docker is not running. Please start Docker Desktop and try again."
    exit 1
}
try {
    docker compose up -d
    Push-Location ./backend
    composer run dev-win
} finally {
    Pop-Location
    docker compose down
}
