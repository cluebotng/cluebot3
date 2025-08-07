import base64
import os
from pathlib import PosixPath
from typing import Optional, Dict, Any

import requests
from fabric import Connection, Config, task


def _get_latest_release(org: str, repo: str) -> str:
    """Return the latest release tag from GitHub"""
    r = requests.get(f"https://api.github.com/repos/{org}/{repo}/releases/latest")
    r.raise_for_status()
    return r.json()["tag_name"]


TARGET_RELEASE = os.environ.get("TARGET_RELEASE")
TARGET_USER = os.environ.get("TARGET_USER", "cluebot3")
TOOL_DIR = PosixPath("/data/project") / TARGET_USER
IMAGE_NAMESPACE = f"tool-{TARGET_USER}"
IMAGE_TAG = "reviewer"

c = Connection(
    "login.toolforge.org",
    config=Config(overrides={"sudo": {"user": f"tools.{TARGET_USER}", "prefix": "/usr/bin/sudo -ni"}}),
)


def _push_file_to_remote(file_name: str, replace_vars: Optional[Dict[str, Any]] = None):
    replace_vars = {} if replace_vars is None else replace_vars

    with (PosixPath(__file__).parent / "configs" / file_name).open("r") as fh:
        file_contents = fh.read()

    for key, value in replace_vars.items():
        file_contents = file_contents.replace(f'{"{{"} {key} {"}}"}', value)

    encoded_contents = base64.b64encode(file_contents.encode("utf-8")).decode("utf-8")
    target_path = (TOOL_DIR / file_name).as_posix()
    c.sudo(f"bash -c \"base64 -d <<< '{encoded_contents}' > '{target_path}'\"")


def build_bot():
    """Update the bot release."""
    latest_release = TARGET_RELEASE or _get_latest_release("cluebotng", "cluebot3")
    print(f"Moving cluebot3 to {latest_release}")

    # Build
    c.sudo(
        f"XDG_CONFIG_HOME={TOOL_DIR} toolforge "
        f"build start -L "
        f"--ref {latest_release} "
        f"-i {IMAGE_TAG} "
        "https://github.com/cluebotng/cluebot3.git"
    )


def _update_jobs():
    _push_file_to_remote("jobs.yaml", {
        "image_namespace": IMAGE_NAMESPACE,
        "image_tag": IMAGE_TAG,
    })
    c.sudo(f"XDG_CONFIG_HOME={TOOL_DIR} toolforge jobs load {TOOL_DIR / 'jobs.yaml'}")


def _restart():
    c.sudo(f"XDG_CONFIG_HOME={TOOL_DIR} toolforge jobs restart cluebot3")


@task()
def deploy_jobs(_ctx):
    _update_jobs()


@task()
def deploy(_ctx):
    """Deploy the current release."""
    build_bot()
    _update_jobs()
    _restart()
