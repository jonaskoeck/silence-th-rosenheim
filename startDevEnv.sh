#!/bin/bash
trap 'docker compose down' EXIT
docker compose up -d && cd ./backend && composer run dev
