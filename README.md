ClueBot III
===========

ClueBot III is an automated bot running against Wikipedia EN (see https://en.wikipedia.org/wiki/User:ClueBot_III).

It is designed to archive pages, where the `{{User:ClueBot III/ArchiveThis}}` page is transcluded.

Operational Details
-------------------

The bot currently runs on [Tool Labs](https://tools.wmflabs.org/) under the [cluebot3](https://tools.wmflabs.org/?list#toollist-cluebot3) tool.

#### Deployment

The bot is deployed using `fabric`, which will do the correct things for both configuring/updating the environment and
starting/monitoring the process.

On first deployment, 1 critical file, the `cluebot3.config.php` settings file will be missing.

The file should be created manually, owned by the tool account with 0400 permissions.

You will need to be a member of the `cluebot3` service account on [Tool Labs](https://tools.wmflabs.org/),
to be able to `become` the user and thus deploy the code.

##### Initial deployment

Locally (this will clone the code, but not start the services):
```bash
fab init
```

On the labs bastion:
```bash
become cluebot3
cd cluebot3

cp cluebot3.config.php.dist cluebot3.config.php
chown tools.cluebot3:tools.cluebot3 cluebot3.config.php
chmod 0400 cluebot3.config.php

vim cluebot3.config.php # Set the password/any other settings
```

Locally (this is a normal deploy):
```bash
fab deploy
```

### Monitoring

There are 2 sets of logging that can be looked at when investigating an issue.

1. stdout/stderr logs from the grid engine - NOTE: These are disabled by default and should not be enabled
for long periods of time. Due to some of the coding warnings around uninitilized variables etc can spew out at MB/s!

2. application logs - these are generated in select places within the code (where there use to be print statements),
the log rotation and retention is managed within the application. Logs can be found under ~/logs/cluebot3-yyyy-mm-dd.log

The change feed for the user can also be checked [here](https://en.wikipedia.org/w/index.php?limit=50&title=Special%3AContributions&contribs=user&target=ClueBot+III&namespace=&tagfilter=&year=2016&month=-1).

#### External monitoring

There is currently a perl script, which runs on a cron, that checks the last time a change was made by the [ClueBot III](https://en.wikipedia.org/wiki/User:ClueBot_III) user.

This emails [Damian](https://en.wikipedia.org/wiki/User:DamianZaremba) and [Rich](https://en.wikipedia.org/wiki/User:Rich_Smith).

### Known issues

The below are known issues:

1. memory exhaustion - due to a lot of data held within variables, plus the way PHP allocates out RAM, the grid
 engine needs to be configured to grant the process multiple GBs of memory. This is currently around 15G and needs
 to be monitored.

2. very large archives - Once an archive gets to around 200 items, the bot hangs/crashes/goes slow/does bad things.
This has been seen on a number of highly visible pages, such as https://en.wikipedia.org/wiki/User_talk:Jimbo_Wales/Archive_202#Conspiracy_Unveiled.
I suspect it is due to the size of the archive pages when the loops are done to update the index logs.

License
-------

The bot was originally written by [Cobi](https://en.wikipedia.org/wiki/User:Cobi) and is licensed under GPLv2.

Want to help?
-------------

[ClueBot III](https://en.wikipedia.org/wiki/User:ClueBot_III) needs some TLC/performance work doing to it, to bring it up to newer standards and ensure a better service to the users.

If you'd like to help improve the bot, feel free to send some [pull requests](https://github.com/DamianZaremba/cluebot3/pulls).