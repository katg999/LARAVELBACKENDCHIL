# Quick Alias Setup

To make Docker commands work exactly like local commands, add these aliases to your shell:

## For zsh (macOS default)

Add to `~/.zshrc`:

```bash
# VoiceFlow Simulation aliases
alias vf-local='php artisan voiceflow:simulate'
alias vf-docker='./voiceflow-docker.sh'
alias vf='./voiceflow-docker.sh'  # Short version
```

Then reload:
```bash
source ~/.zshrc
```

## For bash

Add to `~/.bashrc` or `~/.bash_profile`:

```bash
# VoiceFlow Simulation aliases
alias vf-local='php artisan voiceflow:simulate'
alias vf-docker='./voiceflow-docker.sh'
alias vf='./voiceflow-docker.sh'  # Short version
```

Then reload:
```bash
source ~/.bashrc
```

## Usage After Setup

```bash
# Local
vf-local

# Docker
vf-docker

# Auto-detect (use vf-docker by default)
vf

# With options
vf --type=school --email=test@school.com --action=login --auto-verify
```

## Project-Specific Alias (Recommended)

Even better, create a project-specific alias in the project root:

```bash
# In LARAVELBACKENDCHIL directory
cat > vf << 'EOF'
#!/bin/bash
if docker ps --format '{{.Names}}' | grep -q "^laravel_app$"; then
    ./voiceflow-docker.sh "$@"
else
    php artisan voiceflow:simulate "$@"
fi
EOF

chmod +x vf
```

Now just use:
```bash
./vf
```

It automatically uses Docker if containers are running, otherwise falls back to local!
