# Cloud Agent environment for the strangler-fig demo.
# Builds Docker-in-Docker + Node 22 so parity harness (Compose web/db) can run
# on Cursor cloud VMs. Do not COPY the project — Cursor checks out the commit.

FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    gnupg \
    git \
    sudo \
    iptables \
    fuse-overlayfs \
    && rm -rf /var/lib/apt/lists/*

# Node.js 22 (matches CI / local runbook)
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# GitHub CLI — pipeline opens/resumes PRs via `gh` on this VM after parity.
RUN curl -fsSL https://cli.github.com/packages/githubcli-archive-keyring.gpg \
      | dd of=/usr/share/keyrings/githubcli-archive-keyring.gpg \
    && chmod go+r /usr/share/keyrings/githubcli-archive-keyring.gpg \
    && echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" \
      | tee /etc/apt/sources.list.d/github-cli.list > /dev/null \
    && apt-get update \
    && apt-get install -y --no-install-recommends gh \
    && rm -rf /var/lib/apt/lists/*

# Docker CE + Compose (Cursor-recommended DinD stack)
RUN install -m 0755 -d /etc/apt/keyrings \
    && curl --retry 3 --retry-delay 5 -fsSL https://download.docker.com/linux/ubuntu/gpg \
      | gpg --dearmor -o /etc/apt/keyrings/docker.gpg \
    && chmod a+r /etc/apt/keyrings/docker.gpg \
    && echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
    $(. /etc/os-release && echo "$VERSION_CODENAME") stable" \
      | tee /etc/apt/sources.list.d/docker.list > /dev/null \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
      docker-ce=5:28.5.2-1~ubuntu.24.04~noble \
      docker-ce-cli=5:28.5.2-1~ubuntu.24.04~noble \
      containerd.io \
      docker-buildx-plugin \
      docker-compose-plugin \
    && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /etc/docker \
    && printf '%s\n' '{' \
    '  "storage-driver": "fuse-overlayfs"' \
    '}' > /etc/docker/daemon.json

RUN update-alternatives --set iptables /usr/sbin/iptables-legacy \
    && update-alternatives --set ip6tables /usr/sbin/ip6tables-legacy

# Non-root agent user with docker + passwordless sudo
RUN id -u ubuntu >/dev/null 2>&1 || useradd -m -s /bin/bash ubuntu \
    && groupadd -f docker \
    && usermod -aG docker ubuntu \
    && usermod -aG sudo ubuntu \
    && echo "ubuntu ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/ubuntu \
    && chmod 0440 /etc/sudoers.d/ubuntu

WORKDIR /workspace
USER ubuntu
