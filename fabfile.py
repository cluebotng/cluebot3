import subprocess
import sys
import os.path
from fabric.api import run, env, sudo
from fabric.contrib import files

DEST_DIR = '/data/project/cluebot3/cluebot3'
LOG_DIR = '/data/project/cluebot3/logs'
REPO_URL = 'https://github.com/DamianZaremba/cluebot3.git'

# Internal settings
env.hosts = ['tools-login.wmflabs.org']
env.use_ssh_config = True
env.sudo_user = 'tools.cluebot3'
env.sudo_prefix = "/usr/bin/sudo -ni"


def _check_workingdir_clean():
    p = subprocess.Popen(['git', 'diff', '--exit-code'],
                         stdout=subprocess.PIPE,
                         stderr=subprocess.PIPE)
    p.communicate()

    if p.returncode != 0:
        print('There are local, uncommited changes.')
        print('Refusing to deploy.')
        sys.exit(1)


def _check_remote_up2date():
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
    PARENT_DEST_DIR = os.path.dirname(DEST_DIR)
    if not files.exists(PARENT_DEST_DIR):
        sudo('mkdir -p "%(dir)s"' % {'dir': PARENT_DEST_DIR})

    if not files.exists(DEST_DIR):
        print('Cloning repo')
        sudo('git clone "%(url)s" "%(dir)s"' % {'dir': DEST_DIR, 'url': REPO_URL})


def _stop():
    print('Stopping bot')
    sudo('jstop cluebot3 | true')


def _start():
    print('Starting bot')
    '''
    sudo(('jstart -N start-cluebot3 -e /dev/null -o /dev/null -mem 12G '
          'php -f %s/cluebot3.php' % DEST_DIR))
    '''
    return


def _update_code():
    print('Resetting local changes')
    sudo('cd "%(dir)s" && git reset --hard && git clean -fd' %
         {'dir': DEST_DIR})

    print('Updating code')
    sudo('cd "%(dir)s" && git pull origin master' % {'dir': DEST_DIR})

    print('Running composer')
    sudo('cd "%(dir)s" && php -n -d extension=json.so composer.phar self-update' % {'dir': DEST_DIR})
    sudo('cd "%(dir)s" && php -n -d extension=json.so composer.phar install' % {'dir': DEST_DIR})


def restart():
    _stop()
    _start()


def deploy():
    _check_workingdir_clean()
    _check_remote_up2date()

    _setup()
    _update_code()
    restart()
