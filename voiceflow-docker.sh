#!/bin/bash

# VoiceFlow Simulation Docker Wrapper
# This script ensures the command runs correctly in Docker with TTY support

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}❌ Docker is not running${NC}"
    echo -e "${YELLOW}Please start Docker Desktop and try again${NC}"
    exit 1
fi

# Check if container is running
if ! docker ps --format '{{.Names}}' | grep -q "^laravel_app$"; then
    echo -e "${RED}❌ Container 'laravel_app' is not running${NC}"
    echo -e "${YELLOW}Starting containers...${NC}"
    docker-compose up -d
    sleep 2
fi

# Check if docker-compose is available
if command -v docker-compose &> /dev/null; then
    echo -e "${GREEN}✓${NC} Using docker-compose (recommended)"
    docker-compose exec app php artisan voiceflow:simulate "$@"
else
    echo -e "${GREEN}✓${NC} Using docker exec with -it flags"
    docker exec -it laravel_app php artisan voiceflow:simulate "$@"
fi
