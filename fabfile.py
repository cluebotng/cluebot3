import subprocess
import sys
import os.path
import time
from fabric.api import run, env, sudo
from fabric.contrib import files

DEST_DIR = '/data/project/cluebot3/cluebot3'
LOG_DIR = '/data/project/cluebot3/logs'
REPO_URL = 'https://github.com/DamianZaremba/cluebot3.git'

# Internal settings
env.hosts = ['login-stretch.tools.wmflabs.org']
env.use_ssh_config = True
env.sudo_user = 'tools.cluebot3'
env.sudo_prefix = "/usr/bin/sudo -ni"


def _check_workingdir_clean():
    '''
    Internal function, checks for any uncommitted local changes
    '''
    p = subprocess.Popen(['git', 'diff', '--exit-code'],
                         stdout=subprocess.PIPE,
                         stderr=subprocess.PIPE)
    p.communicate()

    if p.returncode != 0:
        print('There are local, uncommited changes.')
        print('Refusing to deploy.')
        sys.exit(1)


def _check_remote_up2date():
    '''
    Internal function, ensures the local HEAD hash is the same as the remote HEAD hash for master
    '''
    p = subprocess.Popen(['git', 'ls-remote', REPO_URL, 'master'],
                         stdout=subprocess.PIPE,
                         stderr=subprocess.PIPE)
    remote_sha1 = p.communicate()[0].split('\t')[0].strip()

    p = subprocess.Popen(['git', 'rev-parse', 'HEAD'],
                         stdout=subprocess.PIPE,
                         stderr=subprocess.PIPE)
    local_sha1 = p.communicate()[0].strip()

    if local_sha1 != remote_sha1:
        print('There are comitted changes, not pushed to github.')
        print('Refusing to deploy.')
        sys.exit(1)


def _setup():
    '''
    Internal function, configures the correct environment directories
    '''
    PARENT_DEST_DIR = os.path.dirname(DEST_DIR)
    if not files.exists(PARENT_DEST_DIR):
        sudo('mkdir -p "%(dir)s"' % {'dir': PARENT_DEST_DIR})

    if not files.exists(DEST_DIR):
        print('Cloning repo')
        sudo('git clone "%(url)s" "%(dir)s"' % {'dir': DEST_DIR, 'url': REPO_URL})


def _stop():
    '''
    Internal function, calls jstop on the bot grid job
    '''
    print('Stopping bot')
    sudo('jstop cluebot3 | true')


def _start():
    '''
    Internal function, calls jstart on the start.sh script
    '''
    print('Starting bot')
    sudo('jstart -N cluebot3 -e /dev/null -o /dev/null -mem 15G %s/start.sh' % DEST_DIR)


def _update_code(start=True):
    '''
    Clone or pull the git repo into the defined DEST_DIR
    :param start: (Bool) Should services be started/restarted
    Also updates cron if start = True
    '''
    print('Resetting local changes')
    sudo('cd "%(dir)s" && git reset --hard && git clean -fd' %
         {'dir': DEST_DIR})

    print('Updating code')
    sudo('cd "%(dir)s" && git pull origin master' % {'dir': DEST_DIR})

    print('Running composer')
    sudo('cd "%(dir)s" && php composer.phar self-update' % {'dir': DEST_DIR})
    sudo('cd "%(dir)s" && php composer.phar install --no-dev' % {'dir': DEST_DIR})

    if start:
        print('Updating cron')
        sudo('crontab %(dir)s/crontab' % {'dir': DEST_DIR})


def restart():
    '''
    Stop then start the bot grid task
    '''
    _stop()
    time.sleep(10)
    _start()


def _deploy(start=True):
    '''
    Internal deployment function
    :param start: (Bool) Should services be started/restarted
    '''
    _check_workingdir_clean()
    _check_remote_up2date()

    _setup()
    _update_code(start)
    if start:
        restart()


def deploy():
    '''
    Deploy the code and restart the bot
    '''
    _deploy(True)


def init():
    '''
    Deploy the code, but don't configure service monitoring or restart the job
    '''
    _deploy(False)
