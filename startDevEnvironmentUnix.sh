#!/bin/bash
if ! docker info &>/dev/null 2>&1; then
    echo "Error: Docker is not running. Please start Docker Desktop and try again."
    exit 1
fi
trap 'docker compose down' EXIT
docker compose up -d && (cd ./backend && composer run dev)
