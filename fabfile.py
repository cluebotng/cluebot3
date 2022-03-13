import time
from pathlib import PosixPath

import requests
from fabric import Connection, Config, task
from patchwork import files


def _get_latest_github_release(org, repo):
    """Return the latest release tag from GitHub"""
    r = requests.get(f"https://api.github.com/repos/{org}/{repo}/releases/latest")
    r.raise_for_status()
    return r.json()["tag_name"]


RELEASE = _get_latest_github_release("cluebotng", "cluebot3")
TOOL_DIR = PosixPath("/data/project/cluebot3")

c = Connection(
    "login-buster.toolforge.org",
    config=Config(overrides={"sudo": {"user": "tools.cluebot3", "prefix": "/usr/bin/sudo -ni"}}),
)


def _setup():
    """Setup the core directory structure"""
    if not files.exists(c, f'{TOOL_DIR / "apps"}'):
        print("Creating apps path")
        c.sudo(f'mkdir -p {TOOL_DIR / "apps"}')

    release_dir = f'{TOOL_DIR / "apps" / "cluebot3"}'
    if not files.exists(c, release_dir):
        print("Cloning repo")
        c.sudo(f"git clone https://github.com/cluebotng/cluebot3.git {release_dir}")


def _stop():
    """Stop k8s job."""
    print("Stopping k8s job")
    c.sudo("toolforge-jobs delete cluebot3")


def _start():
    """Start k8s job."""
    print("Starting k8s jobs")
    c.sudo("toolforge-jobs run cluebot3 --image tf-php74"
           " --continuous --mem 1024Mi --cpu 1 --command "
           "'cd /data/project/cluebot3/apps/cluebot3/ && exec php -f cluebot3.php'")


def _update_bot():
    """Update the bot release."""
    print(f"Moving bot to {RELEASE}")
    release_dir = TOOL_DIR / "apps" / "cluebot3"

    c.sudo(f"git -C {release_dir} reset --hard")
    c.sudo(f"git -C {release_dir} clean -fd")
    c.sudo(f"git -C {release_dir} fetch -a")
    c.sudo(f"git -C {release_dir} checkout {RELEASE}")

    c.sudo(f'{release_dir / "composer.phar"} self-update')
    c.sudo(f'{release_dir / "composer.phar"} install -d {release_dir}')


@task()
def restart(c):
    """Restart the k8s jobs, without changing releases."""
    _stop()
    _start()


@task()
def deploy(c):
    """Deploy the bot to the current release."""
    _setup()
    _update_bot()
    restart(c)
